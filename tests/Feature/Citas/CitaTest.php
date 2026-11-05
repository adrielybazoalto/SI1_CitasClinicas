<?php

namespace Tests\Feature\Citas;

use App\Exceptions\FichaNoDisponibleException;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Indisponibilidad;
use App\Models\Medico;
use App\Models\Notificacion;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use App\Services\CitaEstadoService;
use App\Services\CitaService;
use App\Services\CodigoService;
use App\Services\HorarioService;
use App\Services\PacienteService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PDOException;
use Tests\TestCase;

/**
 * Paquete 4 · CU12 Consultar fichas, CU13 Programar, CU15 Reprogramar,
 * CU16 Cancelar y CU17 Asistencia / inasistencia.
 *
 * Escenario fijo: hoy es viernes 13/11/2026 10:00. El Dr. Pérez atiende
 * Medicina general los lunes de 08:00 a 12:00, consultas de 20 min
 * → 12 fichas (8 en línea / 4 mostrador). La ficha 4 es a las 09:00.
 */
class CitaTest extends TestCase
{
    use RefreshDatabase;

    private const LUNES = '2026-11-16';

    private Especialidad $especialidad;
    private Medico $medico;
    private Horario $horario;
    private User $recepcion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viajarA('2026-11-13 10:00');
        $this->seed(SeguridadSeeder::class);

        $this->especialidad = Especialidad::create(['nombre' => 'medicina general', 'duracion_min' => 20, 'estado' => true]);
        $this->medico = Medico::create([
            'codigo' => app(CodigoService::class)->medico(),
            'matricula' => 'MP-001',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'estado' => true,
        ]);
        $this->medico->especialidades()->sync([$this->especialidad->id_especialidad]);

        $this->horario = app(HorarioService::class)->registrar([
            'id_medico' => $this->medico->id_medico,
            'id_especialidad' => $this->especialidad->id_especialidad,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
            'duracion_min' => 20,
            'consultorio' => '2',
            'estado' => true,
        ], $this->administrador());

        $this->recepcion = $this->crearUsuario('recepcion', 'Recepcionista');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /* ---------------- helpers ---------------- */

    private function viajarA(string $momento): void
    {
        Carbon::setTestNow($momento);
        CarbonImmutable::setTestNow($momento);
    }

    private function administrador(): User
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $admin->forceFill(['debe_cambiar_clave' => false])->save();

        return $admin;
    }

    private function crearUsuario(string $username, string $rol): User
    {
        return User::create([
            'id_rol' => Rol::where('nombre', $rol)->value('id_rol'),
            'username' => $username,
            'correo' => "{$username}@vidasalud.bo",
            'password_hash' => Hash::make('secreto123'),
            'debe_cambiar_clave' => false,
            'estado' => true,
        ]);
    }

    private function crearPaciente(string $ci = '1000001'): Paciente
    {
        $paciente = app(PacienteService::class)->registrar([
            'ci' => $ci,
            'nombres' => 'Ana',
            'apellidos' => 'Rojas '.$ci,
            'fecha_nacimiento' => '1990-01-01',
            'sexo' => 'F',
            'telefono' => '70000000',
            'correo' => "p{$ci}@example.com",
            'recibe_recordatorios' => true,
        ], $this->administrador())['paciente'];

        $paciente->usuario->forceFill(['debe_cambiar_clave' => false])->save();

        return $paciente;
    }

    /** @return array<string, mixed> */
    private function datosReserva(Paciente $paciente, int $nro = 4, array $cambios = []): array
    {
        return array_merge([
            'id_paciente' => $paciente->id_paciente,
            'id_especialidad' => $this->especialidad->id_especialidad,
            'id_medico' => $this->medico->id_medico,
            'fecha' => self::LUNES,
            'id_horario' => $this->horario->id_horario,
            'nro_cita' => $nro,
            'motivo' => 'Control',
        ], $cambios);
    }

    private function programar(Paciente $paciente, int $nro = 4, array $cambios = []): Cita
    {
        return app(CitaService::class)->programar($this->datosReserva($paciente, $nro, $cambios), $this->recepcion);
    }

    /** @return array<int, array<string, mixed>> */
    private function fichas(User $usuario, string $fecha = self::LUNES): array
    {
        return $this->actingAs($usuario)
            ->getJson(route('fichas.disponibles', [
                'id_especialidad' => $this->especialidad->id_especialidad,
                'id_medico' => $this->medico->id_medico,
                'fecha' => $fecha,
            ]))
            ->assertOk()
            ->json('turnos.0.fichas') ?? [];
    }

    /* ---------------- CU12 · Consultar fichas ---------------- */

    public function test_calcula_fichas_desde_el_horario(): void
    {
        $fichas = $this->fichas($this->recepcion);

        $this->assertCount(12, $fichas);
        $this->assertSame(['nro' => 4, 'hora_inicio' => '09:00', 'hora_fin' => '09:20'], array_intersect_key($fichas[3], array_flip(['nro', 'hora_inicio', 'hora_fin'])));
        $this->assertTrue(collect($fichas)->every(fn ($f) => $f['estado'] === 'libre'));
        $this->assertSame('LINEA', $fichas[7]['canal']);
        $this->assertSame('MOSTRADOR', $fichas[8]['canal']);
    }

    public function test_quita_fichas_ocupadas_bloqueadas_y_pasadas(): void
    {
        $this->programar($this->crearPaciente());

        Indisponibilidad::create([
            'id_medico' => $this->medico->id_medico,
            'inicio' => self::LUNES.' 11:00:00',
            'fin' => self::LUNES.' 12:00:00',
            'motivo' => 'Reunión',
        ]);

        $fichas = collect($this->fichas($this->recepcion))->keyBy('nro');

        $this->assertSame('ocupada', $fichas[4]['estado']);
        $this->assertSame('bloqueada', $fichas[10]['estado']);
        $this->assertSame('libre', $fichas[5]['estado']);

        // El mismo lunes a las 09:30, las fichas anteriores ya pasaron.
        $this->viajarA(self::LUNES.' 09:30');
        $fichas = collect($this->fichas($this->recepcion))->keyBy('nro');
        $this->assertSame('pasada', $fichas[2]['estado']);
        $this->assertSame('libre', $fichas[6]['estado']);
    }

    public function test_paciente_solo_ve_fichas_en_linea_dentro_de_la_ventana(): void
    {
        $paciente = $this->crearPaciente();
        $fichas = collect($this->fichas($paciente->usuario))->keyBy('nro');

        $this->assertSame('libre', $fichas[8]['estado']);
        $this->assertSame('otro_canal', $fichas[9]['estado']);

        // El lunes siguiente (23/11) está a más de 7 días: fuera de la ventana (RN17).
        $fichas = collect($this->fichas($paciente->usuario, '2026-11-23'))->keyBy('nro');
        $this->assertSame('fuera_ventana', $fichas[1]['estado']);
    }

    public function test_dia_sin_horario_y_fecha_pasada(): void
    {
        $this->actingAs($this->recepcion)
            ->getJson(route('fichas.disponibles', [
                'id_especialidad' => $this->especialidad->id_especialidad,
                'id_medico' => $this->medico->id_medico,
                'fecha' => '2026-11-17',
            ]))
            ->assertOk()
            ->assertJsonPath('turnos', [])
            ->assertJsonPath('mensaje', 'El médico no atiende esta especialidad el día seleccionado.');

        $this->actingAs($this->recepcion)
            ->getJson(route('fichas.disponibles', [
                'id_especialidad' => $this->especialidad->id_especialidad,
                'id_medico' => $this->medico->id_medico,
                'fecha' => '2026-11-09',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('fecha');
    }

    /* ---------------- CU13 · Programar cita ---------------- */

    public function test_programa_cita_en_horario_disponible(): void
    {
        $paciente = $this->crearPaciente();

        $respuesta = $this->actingAs($this->recepcion)->post(route('citas.store'), $this->datosReserva($paciente));

        $cita = Cita::firstOrFail();
        $respuesta->assertRedirect(route('citas.show', $cita));

        $this->assertSame('CM-000001', $cita->codigo);
        $this->assertSame(CitaEstadoService::PROGRAMADA, $cita->estado);
        $this->assertSame('MOSTRADOR', $cita->canal);
        $this->assertSame('09:00', $cita->hora_inicio_corta);
        $this->assertSame('09:20', $cita->hora_fin_corta);
        $this->assertSame(4, $cita->nro_cita);

        $this->assertDatabaseHas('historial_estado_cita', ['id_cita' => $cita->id_cita, 'estado_anterior' => null, 'estado_nuevo' => 'Programada']);
        $this->assertDatabaseHas('bitacora', ['accion' => 'PROGRAMAR_CITA', 'id_registro' => $cita->id_cita]);

        // Recordatorio a −24 h (RN23).
        $recordatorio = Notificacion::where('id_cita', $cita->id_cita)->where('tipo', 'RECORDATORIO')->firstOrFail();
        $this->assertSame('2026-11-15 09:00', $recordatorio->programada_para->format('Y-m-d H:i'));
    }

    public function test_impide_cita_superpuesta_para_el_mismo_medico(): void
    {
        $this->programar($this->crearPaciente('1000001'));
        $otro = $this->crearPaciente('1000002');

        $this->actingAs($this->recepcion)
            ->from(route('fichas.index'))
            ->post(route('citas.store'), $this->datosReserva($otro))
            ->assertRedirect(route('fichas.index'))
            ->assertSessionHas('error', 'La ficha ya no está disponible.');

        $this->assertSame(1, Cita::count());
    }

    public function test_rechaza_reserva_fuera_del_horario_del_medico(): void
    {
        $paciente = $this->crearPaciente();

        // Martes: el médico no atiende.
        $this->expectException(FichaNoDisponibleException::class);
        $this->programar($paciente, 4, ['fecha' => '2026-11-17']);
    }

    public function test_rechaza_ficha_inexistente_en_el_turno(): void
    {
        $this->expectException(FichaNoDisponibleException::class);
        $this->expectExceptionMessage('El turno solo tiene 12 fichas.');

        $this->programar($this->crearPaciente(), 13);
    }

    public function test_servidor_rechaza_datos_invalidos(): void
    {
        $paciente = $this->crearPaciente();

        $this->actingAs($this->recepcion)
            ->post(route('citas.store'), $this->datosReserva($paciente, 4, ['fecha' => '2026-11-09', 'id_paciente' => null]))
            ->assertSessionHasErrors(['fecha', 'id_paciente']);

        $this->assertSame(0, Cita::count());
    }

    public function test_limite_de_una_cita_por_medico_por_dia(): void
    {
        $paciente = $this->crearPaciente();
        $this->programar($paciente, 4);

        try {
            $this->programar($paciente, 6);
            $this->fail('Debió rechazar la segunda cita con el mismo médico el mismo día.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('id_paciente', $e->errors());
        }
    }

    public function test_paciente_reserva_en_linea_para_si_mismo(): void
    {
        $paciente = $this->crearPaciente();
        $otro = $this->crearPaciente('1000002');

        // Aunque envíe otro id_paciente, la cita queda a su nombre.
        $this->actingAs($paciente->usuario)
            ->post(route('citas.store'), $this->datosReserva($otro, 2))
            ->assertSessionHasNoErrors();

        $cita = Cita::firstOrFail();
        $this->assertSame($paciente->id_paciente, $cita->id_paciente);
        $this->assertSame('LINEA', $cita->canal);

        // No puede tomar fichas del cupo de mostrador.
        $this->actingAs($paciente->usuario)
            ->post(route('citas.store'), $this->datosReserva($paciente, 10, ['fecha' => '2026-11-16']))
            ->assertSessionHas('error');
    }

    public function test_paciente_penalizado_no_reserva_en_linea(): void
    {
        $paciente = $this->crearPaciente();

        foreach (['2026-11-02', '2026-11-09', '2026-10-26'] as $i => $fecha) {
            Cita::create([
                'id_paciente' => $paciente->id_paciente, 'id_medico' => $this->medico->id_medico,
                'id_especialidad' => $this->especialidad->id_especialidad, 'creado_por' => $this->recepcion->id_usuario,
                'codigo' => 'CM-90000'.$i, 'fecha' => $fecha, 'hora_inicio' => '08:00:00', 'hora_fin' => '08:20:00',
                'nro_cita' => 1, 'canal' => 'LINEA', 'estado' => 'Inasistencia',
            ]);
        }

        $this->actingAs($paciente->usuario)
            ->post(route('citas.store'), $this->datosReserva($paciente, 2))
            ->assertSessionHasErrors('id_paciente');

        // En mostrador sí puede reservar.
        $this->assertSame('Programada', $this->programar($paciente, 2)->estado);
    }

    public function test_sobrecupo_maximo_dos_por_turno(): void
    {
        $servicio = app(CitaService::class);

        foreach (['1000001', '1000002'] as $i => $ci) {
            $cita = $servicio->programar($this->datosReserva($this->crearPaciente($ci), 0, ['nro_cita' => null, 'sobrecupo' => true]), $this->recepcion);
            $this->assertTrue($cita->sobrecupo);
            $this->assertSame(13 + $i, $cita->nro_cita);
        }

        $this->expectException(FichaNoDisponibleException::class);
        $servicio->programar($this->datosReserva($this->crearPaciente('1000003'), 0, ['nro_cita' => null, 'sobrecupo' => true]), $this->recepcion);
    }

    public function test_choque_en_la_base_se_traduce_a_ficha_no_disponible(): void
    {
        // Simula que PostgreSQL rechaza el INSERT por la restricción de exclusión (23P01).
        DB::listen(function (QueryExecuted $consulta) {
            if (str_starts_with(strtolower($consulta->sql), 'insert into "cita"')) {
                $pdo = new PDOException('conflicting key value violates exclusion constraint "ex_cita_sin_superposicion"');
                $pdo->errorInfo = ['23P01', 7, 'exclusion'];

                throw new QueryException('pgsql', $consulta->sql, $consulta->bindings, $pdo);
            }
        });

        try {
            $this->programar($this->crearPaciente());
            $this->fail('Debió lanzar FichaNoDisponibleException.');
        } catch (FichaNoDisponibleException $e) {
            $this->assertSame('La ficha ya no está disponible.', $e->getMessage());
        }

        // La transacción se revirtió completa.
        $this->assertSame(0, Cita::count());
        $this->assertDatabaseMissing('historial_estado_cita', ['estado_nuevo' => 'Programada']);
    }

    /* ---------------- CU15 · Reprogramar ---------------- */

    public function test_reprograma_conservando_el_codigo(): void
    {
        $cita = $this->programar($this->crearPaciente());

        $this->actingAs($this->recepcion)
            ->put(route('citas.reprogramar.update', $cita), [
                'id_medico' => $this->medico->id_medico,
                'fecha' => self::LUNES,
                'id_horario' => $this->horario->id_horario,
                'nro_cita' => 7,
            ])
            ->assertRedirect(route('citas.show', $cita));

        $cita->refresh();
        $this->assertSame('CM-000001', $cita->codigo);
        $this->assertSame('10:00', $cita->hora_inicio_corta);
        $this->assertSame(1, $cita->veces_reprogramada);
        $this->assertSame(2, $cita->historialEstados()->count());

        // La ficha 4 quedó libre y la 7 ocupada.
        $fichas = collect($this->fichas($this->recepcion))->keyBy('nro');
        $this->assertSame('libre', $fichas[4]['estado']);
        $this->assertSame('ocupada', $fichas[7]['estado']);
    }

    public function test_reprogramacion_una_vez_salvo_que_recepcion_fuerce(): void
    {
        $cita = $this->programar($this->crearPaciente());
        $datos = ['id_medico' => $this->medico->id_medico, 'fecha' => self::LUNES, 'id_horario' => $this->horario->id_horario];

        $this->actingAs($this->recepcion)->put(route('citas.reprogramar.update', $cita), $datos + ['nro_cita' => 5])->assertSessionHasNoErrors();
        $this->actingAs($this->recepcion)->put(route('citas.reprogramar.update', $cita), $datos + ['nro_cita' => 6])->assertSessionHasErrors('cita');
        $this->actingAs($this->recepcion)->put(route('citas.reprogramar.update', $cita), $datos + ['nro_cita' => 6, 'forzar' => '1'])->assertSessionHasErrors('motivo_forzado');
        $this->actingAs($this->recepcion)->put(route('citas.reprogramar.update', $cita), $datos + ['nro_cita' => 6, 'forzar' => '1', 'motivo_forzado' => 'Pedido del médico'])->assertSessionHasNoErrors();

        $this->assertSame(2, $cita->refresh()->veces_reprogramada);
        $this->assertSame(6, $cita->nro_cita);
    }

    public function test_paciente_no_reprograma_con_menos_de_24_horas(): void
    {
        $paciente = $this->crearPaciente();
        $cita = $this->programar($paciente);

        $this->viajarA('2026-11-15 18:00');

        $this->actingAs($paciente->usuario)
            ->put(route('citas.reprogramar.update', $cita), [
                'id_medico' => $this->medico->id_medico,
                'fecha' => self::LUNES,
                'id_horario' => $this->horario->id_horario,
                'nro_cita' => 6,
            ])
            ->assertSessionHasErrors('cita');

        $this->assertSame(0, $cita->refresh()->veces_reprogramada);
    }

    /* ---------------- CU16 · Cancelar ---------------- */

    public function test_cancela_conservando_historial_y_libera_la_ficha(): void
    {
        $cita = $this->programar($this->crearPaciente());

        $this->actingAs($this->recepcion)
            ->patch(route('citas.cancelar', $cita), ['motivo_cancelacion' => ''])
            ->assertSessionHasErrors('motivo_cancelacion');

        $this->actingAs($this->recepcion)
            ->patch(route('citas.cancelar', $cita), ['motivo_cancelacion' => 'Viaje imprevisto'])
            ->assertSessionHasNoErrors();

        $cita->refresh();
        $this->assertSame('Cancelada', $cita->estado);
        $this->assertSame('Viaje imprevisto', $cita->motivo_cancelacion);
        $this->assertDatabaseHas('historial_estado_cita', ['id_cita' => $cita->id_cita, 'estado_anterior' => 'Programada', 'estado_nuevo' => 'Cancelada']);
        $this->assertSame(1, Cita::count()); // nunca se borra

        $fichas = collect($this->fichas($this->recepcion))->keyBy('nro');
        $this->assertSame('libre', $fichas[4]['estado']);

        // Una cita cancelada no se puede volver a cancelar.
        $this->actingAs($this->recepcion)
            ->patch(route('citas.cancelar', $cita), ['motivo_cancelacion' => 'Otra vez'])
            ->assertSessionHasErrors('motivo_cancelacion');
    }

    /* ---------------- CU17 · Asistencia ---------------- */

    public function test_registra_llegada_e_inasistencia_manual(): void
    {
        $cita = $this->programar($this->crearPaciente());

        // Antes del día de la cita no se puede confirmar.
        $this->actingAs($this->recepcion)->patch(route('citas.llegada', $cita))->assertSessionHasErrors('cita');

        $this->viajarA(self::LUNES.' 08:40');
        $this->actingAs($this->recepcion)->patch(route('citas.llegada', $cita))->assertSessionHasNoErrors();

        $cita->refresh();
        $this->assertSame('Confirmada', $cita->estado);
        $this->assertSame('08:40', $cita->hora_llegada->format('H:i'));

        // Se retira sin ser atendido.
        $this->actingAs($this->recepcion)->patch(route('citas.inasistencia', $cita), ['detalle' => 'Se retiró'])->assertSessionHasNoErrors();
        $this->assertSame('Inasistencia', $cita->refresh()->estado);
    }

    public function test_inasistencia_automatica_a_los_15_minutos(): void
    {
        $cita = $this->programar($this->crearPaciente('1000001'), 4);  // 09:00
        $tarde = $this->programar($this->crearPaciente('1000002'), 6); // 09:40

        $this->viajarA(self::LUNES.' 09:16');
        $this->assertSame(1, app(CitaService::class)->marcarInasistenciasAutomaticas());

        $this->assertSame('Inasistencia', $cita->refresh()->estado);
        $this->assertSame('Programada', $tarde->refresh()->estado);
        $this->assertDatabaseHas('historial_estado_cita', ['id_cita' => $cita->id_cita, 'estado_nuevo' => 'Inasistencia', 'id_usuario' => null]);
    }

    public function test_maquina_de_estados_rechaza_transiciones_invalidas(): void
    {
        $cita = $this->programar($this->crearPaciente());
        $estados = app(CitaEstadoService::class);

        $this->expectException(ValidationException::class);
        $estados->transicionar($cita, CitaEstadoService::ATENDIDA, null, null);
    }

    /* ---------------- Acceso ---------------- */

    public function test_paciente_no_ve_citas_ajenas_y_rutas_exigen_permiso(): void
    {
        $cita = $this->programar($this->crearPaciente('1000001'));
        $otro = $this->crearPaciente('1000002');

        $this->actingAs($otro->usuario)->get(route('citas.show', $cita))->assertForbidden();
        $this->actingAs($otro->usuario)->patch(route('citas.cancelar', $cita), ['motivo_cancelacion' => 'No es mía'])->assertForbidden();
        $this->actingAs($otro->usuario)->patch(route('citas.llegada', $cita))->assertForbidden();

        $medico = $this->crearUsuario('medico1', 'Medico');
        $this->actingAs($medico)->get(route('fichas.index'))->assertForbidden();
        $this->actingAs($medico)->post(route('citas.store'), [])->assertForbidden();

        $this->assertSame('Programada', $cita->refresh()->estado);
    }

    public function test_pantallas_de_citas_se_renderizan(): void
    {
        $paciente = $this->crearPaciente();
        $cita = $this->programar($paciente);

        $this->actingAs($this->recepcion)->get(route('fichas.index'))->assertOk()->assertSee('Reservar ficha');
        $this->actingAs($this->recepcion)->get(route('citas.index'))->assertOk()->assertSee('CM-000001');
        $this->actingAs($this->recepcion)->get(route('citas.show', $cita))->assertOk()->assertSee('Historial de estados');
        $this->actingAs($this->recepcion)->get(route('citas.reprogramar', $cita))->assertOk()->assertSee('Reprogramar cita CM-000001');
        $this->actingAs($paciente->usuario)->get(route('citas.index'))->assertOk()->assertSee('Mis citas');
        $this->actingAs($this->recepcion)->getJson(route('fichas.pacientes', ['q' => 'roj']))->assertOk()->assertJsonPath('0.ci', '1000001');
    }
}

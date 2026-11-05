<?php

namespace Tests\Feature\Oferta;

use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Medico;
use App\Models\Rol;
use App\Models\User;
use App\Services\CodigoService;
use App\Services\HorarioService;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CU07 · Especialidades, CU08 · Médicos y CU09 · Horarios.
 */
class OfertaMedicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SeguridadSeeder::class);
    }

    private function administrador(): User
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        if ($admin->debe_cambiar_clave) {
            $admin->forceFill(['debe_cambiar_clave' => false])->save();
        }

        return $admin;
    }

    private function crearEspecialidad(string $nombre = 'Cardiología', int $duracion = 30, bool $estado = true): Especialidad
    {
        return Especialidad::create([
            'nombre' => mb_strtolower(trim($nombre)),
            'descripcion' => null,
            'duracion_min' => $duracion,
            'estado' => $estado,
        ]);
    }

    /**
     * @param array<int, Especialidad> $especialidades
     */
    private function crearMedico(
        array $especialidades,
        string $matricula = 'MP-001',
        bool $estado = true,
        ?User $usuario = null,
    ): Medico {
        $medico = Medico::create([
            'id_usuario' => $usuario?->id_usuario,
            'codigo' => app(CodigoService::class)->medico(),
            'matricula' => $matricula,
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'telefono' => '70012345',
            'correo' => 'juan@example.com',
            'estado' => $estado,
        ]);

        $medico->especialidades()->sync(
            collect($especialidades)->pluck('id_especialidad')->all()
        );

        return $medico;
    }

    /**
     * @param array<string, mixed> $cambios
     */
    private function crearHorario(Medico $medico, Especialidad $especialidad, array $cambios = []): Horario
    {
        $datos = array_merge([
            'id_medico' => $medico->id_medico,
            'id_especialidad' => $especialidad->id_especialidad,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
            'duracion_min' => 30,
            'consultorio' => '1A',
            'estado' => true,
        ], $cambios);

        return app(HorarioService::class)->registrar($datos, $this->administrador());
    }

    /**
     * @param array<string, mixed> $cambios
     * @return array<string, mixed>
     */
    private function datosMedico(Medico $medico, array $cambios = []): array
    {
        return array_merge([
            'matricula' => $medico->matricula,
            'nombres' => $medico->nombres,
            'apellidos' => $medico->apellidos,
            'telefono' => $medico->telefono,
            'correo' => $medico->correo,
            'id_usuario' => $medico->id_usuario,
            'especialidades' => $medico->especialidades()
                ->pluck('especialidad.id_especialidad')
                ->all(),
            'estado' => $medico->estado ? '1' : '0',
        ], $cambios);
    }

    /**
     * @param array<string, mixed> $cambios
     * @return array<string, mixed>
     */
    private function datosHorario(Medico $medico, Especialidad $especialidad, array $cambios = []): array
    {
        return array_merge([
            'id_medico' => $medico->id_medico,
            'id_especialidad' => $especialidad->id_especialidad,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
            'duracion_min' => 30,
            'consultorio' => '1A',
            'estado' => '1',
        ], $cambios);
    }

    private function usuarioMedico(bool $estado = true): User
    {
        $rol = Rol::where('nombre', Rol::MEDICO)->firstOrFail();
        $sufijo = Str::lower(Str::random(8));

        return User::create([
            'id_rol' => $rol->id_rol,
            'username' => 'medico'.$sufijo,
            'correo' => 'medico'.$sufijo.'@example.com',
            'password_hash' => Hash::make('secret123'),
            'debe_cambiar_clave' => false,
            'estado' => $estado,
        ]);
    }

    public function test_especialidad_normaliza_nombre_y_descripcion(): void
    {
        $response = $this->actingAs($this->administrador())->post(route('especialidades.store'), [
            'nombre' => '  CARDIOLOGÍA   CLÍNICA ',
            'descripcion' => '  Consultas   cardiológicas ',
            'duracion_min' => 30,
            'estado' => '1',
        ]);

        $response->assertRedirect(route('especialidades.index'));

        $this->assertDatabaseHas('especialidad', [
            'nombre' => 'cardiología clínica',
            'descripcion' => 'Consultas cardiológicas',
            'duracion_min' => 30,
            'estado' => true,
        ]);
    }

    public function test_especialidad_nombre_es_unico_ignorando_mayusculas_y_espacios(): void
    {
        $this->crearEspecialidad('Cardiología');

        $response = $this->actingAs($this->administrador())->post(route('especialidades.store'), [
            'nombre' => '  CARDIOLOGÍA ',
            'duracion_min' => 30,
            'estado' => '1',
        ]);

        $response->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('especialidad', 1);
    }

    public function test_especialidad_conserva_su_propio_nombre_al_editar(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');

        $response = $this->actingAs($this->administrador())->put(
            route('especialidades.update', $especialidad),
            [
                'nombre' => 'Cardiología',
                'descripcion' => 'Actualizada',
                'duracion_min' => 45,
                'estado' => '1',
            ],
        );

        $response->assertRedirect(route('especialidades.index'));

        $this->assertDatabaseHas('especialidad', [
            'id_especialidad' => $especialidad->id_especialidad,
            'nombre' => 'cardiología',
            'descripcion' => 'Actualizada',
            'duracion_min' => 45,
        ]);
    }

    public function test_especialidad_valida_duracion_entre_10_y_120(): void
    {
        $admin = $this->administrador();

        foreach ([5, 121] as $duracion) {
            $response = $this->actingAs($admin)->post(route('especialidades.store'), [
                'nombre' => 'Especialidad '.$duracion,
                'duracion_min' => $duracion,
                'estado' => '1',
            ]);

            $response->assertSessionHasErrors('duracion_min');
        }

        $this->assertDatabaseCount('especialidad', 0);

        foreach ([10, 120] as $duracion) {
            $this->actingAs($admin)->post(route('especialidades.store'), [
                'nombre' => 'Especialidad '.$duracion,
                'duracion_min' => $duracion,
                'estado' => '1',
            ])->assertRedirect(route('especialidades.index'));
        }

        $this->assertDatabaseCount('especialidad', 2);
    }

    public function test_medico_se_registra_con_codigo_pivote_y_matricula_normalizada(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');

        $response = $this->actingAs($this->administrador())->post(route('medicos.store'), [
            'matricula' => '  mp-001 ',
            'nombres' => '  Juan ',
            'apellidos' => '  Pérez ',
            'telefono' => '',
            'correo' => '',
            'id_usuario' => '',
            'especialidades' => [$especialidad->id_especialidad],
            'estado' => '1',
        ]);

        $response->assertRedirect(route('medicos.index'));

        $medico = Medico::firstOrFail();

        $this->assertSame('MED-0001', $medico->codigo);
        $this->assertSame('MP-001', $medico->matricula);
        $this->assertSame('Juan', $medico->nombres);
        $this->assertSame('Pérez', $medico->apellidos);
        $this->assertNull($medico->telefono);
        $this->assertNull($medico->correo);
        $this->assertNull($medico->id_usuario);
        $this->assertSame('Pérez, Juan', $medico->nombre_completo);

        $this->assertDatabaseHas('medico_especialidad', [
            'id_medico' => $medico->id_medico,
            'id_especialidad' => $especialidad->id_especialidad,
        ]);
    }

    public function test_medico_exige_al_menos_una_especialidad_activa(): void
    {
        $inactiva = $this->crearEspecialidad('Inactiva', 30, false);
        $admin = $this->administrador();

        $response = $this->actingAs($admin)->post(route('medicos.store'), [
            'matricula' => 'MP-001',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'especialidades' => [$inactiva->id_especialidad],
            'estado' => '1',
        ]);

        $response->assertSessionHasErrors('especialidades');
        $this->assertDatabaseCount('medico', 0);

        $response = $this->actingAs($admin)->post(route('medicos.store'), [
            'matricula' => 'MP-002',
            'nombres' => 'Ana',
            'apellidos' => 'Gómez',
            'especialidades' => [],
            'estado' => '1',
        ]);

        $response->assertSessionHasErrors('especialidades');
        $this->assertDatabaseCount('medico', 0);
    }

    public function test_medico_matricula_es_unica(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $this->crearMedico([$especialidad], 'MP-001');

        $response = $this->actingAs($this->administrador())->post(route('medicos.store'), [
            'matricula' => ' mp-001 ',
            'nombres' => 'Otro',
            'apellidos' => 'Médico',
            'especialidades' => [$especialidad->id_especialidad],
            'estado' => '1',
        ]);

        $response->assertSessionHasErrors('matricula');
        $this->assertDatabaseCount('medico', 1);
    }

    public function test_medico_baja_logica_conserva_pivote_horarios_y_cuenta(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $usuario = $this->usuarioMedico();
        $medico = $this->crearMedico([$especialidad], 'MP-001', true, $usuario);
        $horario = $this->crearHorario($medico, $especialidad);

        $response = $this->actingAs($this->administrador())
            ->patch(route('medicos.estado', $medico));

        $response->assertRedirect();

        $medico->refresh();
        $usuario->refresh();

        $this->assertFalse($medico->estado);
        $this->assertTrue($usuario->estado);
        $this->assertDatabaseHas('medico_especialidad', [
            'id_medico' => $medico->id_medico,
            'id_especialidad' => $especialidad->id_especialidad,
        ]);
        $this->assertDatabaseHas('horario', [
            'id_horario' => $horario->id_horario,
            'estado' => true,
        ]);
    }

    public function test_no_se_puede_quitar_especialidad_con_horarios(): void
    {
        $cardiologia = $this->crearEspecialidad('Cardiología');
        $neurologia = $this->crearEspecialidad('Neurología');
        $medico = $this->crearMedico([$cardiologia, $neurologia], 'MP-001');
        $this->crearHorario($medico, $neurologia);

        $response = $this->actingAs($this->administrador())->put(
            route('medicos.update', $medico),
            $this->datosMedico($medico, [
                'especialidades' => [$cardiologia->id_especialidad],
            ]),
        );

        $response->assertSessionHasErrors('especialidades');

        $this->assertDatabaseHas('medico_especialidad', [
            'id_medico' => $medico->id_medico,
            'id_especialidad' => $neurologia->id_especialidad,
        ]);
    }

    public function test_se_puede_quitar_especialidad_sin_horarios(): void
    {
        $cardiologia = $this->crearEspecialidad('Cardiología');
        $neurologia = $this->crearEspecialidad('Neurología');
        $medico = $this->crearMedico([$cardiologia, $neurologia], 'MP-001');

        $response = $this->actingAs($this->administrador())->put(
            route('medicos.update', $medico),
            $this->datosMedico($medico, [
                'especialidades' => [$cardiologia->id_especialidad],
            ]),
        );

        $response->assertRedirect(route('medicos.index'));

        $this->assertDatabaseMissing('medico_especialidad', [
            'id_medico' => $medico->id_medico,
            'id_especialidad' => $neurologia->id_especialidad,
        ]);
    }

    public function test_horario_valida_que_hora_fin_sea_mayor_que_hora_inicio(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad]);

        $response = $this->actingAs($this->administrador())->post(
            route('horarios.store'),
            $this->datosHorario($medico, $especialidad, [
                'hora_inicio' => '12:00',
                'hora_fin' => '08:00',
            ]),
        );

        $response->assertSessionHasErrors('hora_fin');
        $this->assertDatabaseCount('horario', 0);
    }

    public function test_horario_duracion_debe_dividir_exactamente_el_turno(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad]);

        $response = $this->actingAs($this->administrador())->post(
            route('horarios.store'),
            $this->datosHorario($medico, $especialidad, [
                'hora_inicio' => '08:00',
                'hora_fin' => '12:00',
                'duracion_min' => 50,
            ]),
        );

        $response->assertSessionHasErrors('duracion_min');
        $this->assertDatabaseCount('horario', 0);
    }

    public function test_horario_no_permite_superposicion_para_el_mismo_medico_y_dia(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad]);
        $this->crearHorario($medico, $especialidad);

        $response = $this->actingAs($this->administrador())->post(
            route('horarios.store'),
            $this->datosHorario($medico, $especialidad, [
                'hora_inicio' => '11:00',
                'hora_fin' => '13:00',
            ]),
        );

        $response->assertSessionHasErrors('hora_inicio');
        $this->assertDatabaseCount('horario', 1);
    }

    public function test_horarios_adyacentes_de_otro_dia_o_de_otro_medico_son_permitidos(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad]);
        $admin = $this->administrador();

        $this->crearHorario($medico, $especialidad);

        $this->actingAs($admin)->post(route('horarios.store'), $this->datosHorario($medico, $especialidad, [
            'hora_inicio' => '12:00',
            'hora_fin' => '16:00',
        ]))->assertRedirect(route('horarios.index', ['medico' => $medico->id_medico]));

        $this->actingAs($admin)->post(route('horarios.store'), $this->datosHorario($medico, $especialidad, [
            'dia_semana' => 2,
        ]))->assertRedirect(route('horarios.index', ['medico' => $medico->id_medico]));

        $otroMedico = $this->crearMedico([$especialidad], 'MP-002');

        $this->actingAs($admin)->post(route('horarios.store'), $this->datosHorario($otroMedico, $especialidad))
            ->assertRedirect(route('horarios.index', ['medico' => $otroMedico->id_medico]));

        $this->assertDatabaseCount('horario', 4);
    }

    public function test_horario_inactivo_no_bloquea_y_no_puede_activarse_si_se_superpone(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad]);
        $admin = $this->administrador();

        $inactivo = $this->crearHorario($medico, $especialidad, [
            'hora_inicio' => '14:00',
            'hora_fin' => '16:00',
            'estado' => false,
        ]);

        $this->actingAs($admin)->post(route('horarios.store'), $this->datosHorario($medico, $especialidad, [
            'hora_inicio' => '14:00',
            'hora_fin' => '16:00',
        ]))->assertRedirect(route('horarios.index', ['medico' => $medico->id_medico]));

        $response = $this->actingAs($admin)->patch(route('horarios.estado', $inactivo));

        $response->assertSessionHasErrors('hora_inicio');

        $inactivo->refresh();
        $this->assertFalse($inactivo->estado);
    }

    public function test_capacidad_y_reparto_70_30(): void
    {
        $servicio = app(HorarioService::class);

        $this->assertSame(
            ['minutos' => 240, 'capacidad' => 8, 'cupos_linea' => 6, 'cupos_mostrador' => 2],
            $servicio->capacidad('08:00', '12:00', 30),
        );

        $this->assertSame(
            ['minutos' => 90, 'capacidad' => 3, 'cupos_linea' => 2, 'cupos_mostrador' => 1],
            $servicio->capacidad('08:00', '09:30', 30),
        );

        $this->assertSame(
            ['minutos' => 60, 'capacidad' => 2, 'cupos_linea' => 1, 'cupos_mostrador' => 1],
            $servicio->capacidad('08:00', '09:00', 30),
        );

        $this->assertSame(
            ['minutos' => 30, 'capacidad' => 1, 'cupos_linea' => 1, 'cupos_mostrador' => 0],
            $servicio->capacidad('08:00', '08:30', 30),
        );
    }

    public function test_horario_index_muestra_capacidad_y_reparto(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad]);
        $this->crearHorario($medico, $especialidad);

        $response = $this->actingAs($this->administrador())->get(route('horarios.index'));

        $response->assertOk();
        $response->assertSee('8 fichas');
        $response->assertSee('6 en línea');
        $response->assertSee('2 mostrador');
    }

    public function test_rutas_de_oferta_requieren_permiso(): void
    {
        $usuario = $this->usuarioMedico();

        $this->actingAs($usuario)->get(route('especialidades.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('medicos.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('horarios.index'))->assertForbidden();

        $admin = $this->administrador();

        foreach (['especialidades.index', 'medicos.index', 'horarios.index'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk();
        }
    }

    public function test_selector_de_cuentas_medico_excluye_vinculadas_y_conserva_la_actual(): void
    {
        $admin = $this->administrador();
        $usuario = $this->usuarioMedico();

        $this->actingAs($admin)->get(route('medicos.create'))
            ->assertOk()
            ->assertSee($usuario->username);

        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad], 'MP-001', true, $usuario);

        $this->actingAs($admin)->get(route('medicos.create'))
            ->assertOk()
            ->assertDontSee($usuario->username);

        $this->actingAs($admin)->get(route('medicos.edit', $medico))
            ->assertOk()
            ->assertSee($usuario->username);
    }

    public function test_horario_permite_editar_turno_de_medico_o_especialidad_inactivos(): void
    {
        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad]);
        $horario = $this->crearHorario($medico, $especialidad);

        $especialidad->forceFill(['estado' => false])->save();
        $medico->forceFill(['estado' => false])->save();

        $response = $this->actingAs($this->administrador())->put(
            route('horarios.update', $horario),
            $this->datosHorario($medico, $especialidad, [
                'hora_fin' => '13:00',
            ]),
        );

        $response->assertRedirect(route('horarios.index', ['medico' => $medico->id_medico]));

        $this->assertDatabaseHas('horario', [
            'id_horario' => $horario->id_horario,
            'hora_fin' => '13:00',
        ]);
    }

    public function test_formularios_de_oferta_se_renderizan(): void
    {
        $admin = $this->administrador();
        $especialidad = $this->crearEspecialidad('Cardiología');
        $medico = $this->crearMedico([$especialidad]);
        $horario = $this->crearHorario($medico, $especialidad);

        $this->actingAs($admin)->get(route('especialidades.create'))->assertOk();
        $this->actingAs($admin)->get(route('especialidades.edit', $especialidad))->assertOk();
        $this->actingAs($admin)->get(route('medicos.create'))->assertOk();
        $this->actingAs($admin)->get(route('medicos.edit', $medico))->assertOk();
        $this->actingAs($admin)->get(route('horarios.create'))->assertOk();
        $this->actingAs($admin)->get(route('horarios.edit', $horario))->assertOk();
    }

    public function test_dashboard_y_navegacion_muestran_acceso_a_oferta_medica(): void
    {
        $response = $this->actingAs($this->administrador())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Oferta Médica');
        // Menú lateral por paquetes: P3 con sus casos de uso.
        $response->assertSee('P3');
        $response->assertSee(route('especialidades.index'), false);
        $response->assertSee('Gestionar horarios (CU09)');
    }

    public function test_especialidad_index_filtra_por_estado(): void
    {
        $this->crearEspecialidad('Cardiología');
        $this->crearEspecialidad('Neurología', 30, false);

        $response = $this->actingAs($this->administrador())
            ->get(route('especialidades.index', ['estado' => '1']));

        $response->assertOk();
        $response->assertSee('cardiología');
        $response->assertDontSee('neurología');
    }

    public function test_medico_index_filtra_por_especialidad(): void
    {
        $cardiologia = $this->crearEspecialidad('Cardiología');
        $neurologia = $this->crearEspecialidad('Neurología');
        $this->crearMedico([$cardiologia], 'MP-001');
        $this->crearMedico([$neurologia], 'MP-002');

        $response = $this->actingAs($this->administrador())
            ->get(route('medicos.index', ['especialidad' => $cardiologia->id_especialidad]));

        $response->assertOk();
        $response->assertSee('MP-001');
        $response->assertDontSee('MP-002');
    }
}

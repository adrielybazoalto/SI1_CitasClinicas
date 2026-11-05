<?php

namespace Tests\Feature\Oferta;

use App\Models\Cita;
use App\Models\Indisponibilidad;
use App\Models\Medico;
use App\Models\Notificacion;
use App\Models\Pago;
use App\Models\User;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * P3 · CU10 Registrar indisponibilidad médica y reubicar citas.
 * Datos de demostración; hoy es lunes 09/11/2026 07:00.
 */
class IndisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    private User $recepcion;
    private Medico $perez;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 07:00');
        CarbonImmutable::setTestNow('2026-11-09 07:00');
        $this->seed(SeguridadSeeder::class);
        $this->seed(DatosDemoSeeder::class);

        $this->recepcion = User::where('username', 'rmendoza')->firstOrFail();
        $this->perez = Medico::where('codigo', 'MED-0001')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** Cita futura del Dr. Pérez (Medicina general, lunes/miércoles 08–12) en un día dado. */
    private function citaDelDia(string $fecha, string $hora = '09:00:00', string $pago = 'Pendiente'): Cita
    {
        $id = DB::table('cita')->insertGetId([
            'id_paciente' => DB::table('paciente')->where('codigo', 'PAC-000010')->value('id_paciente'),
            'id_medico' => $this->perez->id_medico,
            'id_especialidad' => DB::table('especialidad')->where('nombre', 'Medicina general')->value('id_especialidad'),
            'creado_por' => $this->recepcion->id_usuario, 'codigo' => 'CM-'.random_int(500000, 999999),
            'fecha' => $fecha, 'hora_inicio' => $hora, 'hora_fin' => date('H:i:s', strtotime($hora) + 1200),
            'nro_cita' => 4, 'canal' => 'MOSTRADOR', 'estado' => 'Programada', 'estado_pago' => $pago, 'monto' => 100,
        ], 'id_cita');

        return Cita::findOrFail($id);
    }

    private function registrar(string $inicio, string $fin): Indisponibilidad
    {
        $this->actingAs($this->recepcion)->post(route('indisponibilidades.store'), [
            'id_medico' => $this->perez->id_medico, 'inicio' => $inicio, 'fin' => $fin, 'motivo' => 'Congreso médico',
        ])->assertSessionHasNoErrors();

        return Indisponibilidad::latest('id_indisponibilidad')->firstOrFail();
    }

    public function test_registra_el_periodo_y_bloquea_esos_horarios(): void
    {
        $periodo = $this->registrar('2026-11-11 00:00', '2026-11-11 23:59');

        $this->assertDatabaseHas('bitacora', ['accion' => 'REGISTRAR_INDISPONIBILIDAD', 'id_registro' => $periodo->id_indisponibilidad]);

        $grilla = app(DisponibilidadService::class)->consultar(
            $this->perez->id_medico, DB::table('especialidad')->where('nombre', 'Medicina general')->value('id_especialidad'),
            '2026-11-11', DisponibilidadService::CANAL_MOSTRADOR,
        );
        $this->assertSame(0, $grilla['libres']);
    }

    public function test_valida_el_periodo(): void
    {
        $this->actingAs($this->recepcion)->post(route('indisponibilidades.store'), [
            'id_medico' => $this->perez->id_medico, 'inicio' => '2026-11-12 10:00', 'fin' => '2026-11-12 08:00', 'motivo' => 'X motivo',
        ])->assertSessionHasErrors('fin');

        $this->actingAs($this->recepcion)->post(route('indisponibilidades.store'), [
            'id_medico' => $this->perez->id_medico, 'inicio' => '2026-11-01 08:00', 'fin' => '2026-11-02 08:00', 'motivo' => 'Pasado',
        ])->assertSessionHasErrors('fin');
    }

    public function test_lista_las_citas_afectadas_con_opciones(): void
    {
        $cita = $this->citaDelDia('2026-11-11');
        $periodo = $this->registrar('2026-11-11 00:00', '2026-11-11 23:59');

        $respuesta = $this->actingAs($this->recepcion)->get(route('indisponibilidades.citas', $periodo))
            ->assertOk()
            ->assertSee($cita->codigo)
            ->assertSee('Reubicar aquí');

        $opciones = $respuesta->viewData('opciones')[$cita->id_cita];
        $this->assertNotEmpty($opciones);
        // Ninguna opción cae en el periodo bloqueado del mismo médico.
        $this->assertFalse(collect($opciones)->contains(fn ($o) => $o['fecha'] === '2026-11-11' && $o['id_medico'] === $this->perez->id_medico));
    }

    public function test_reubica_conservando_el_codigo_y_avisa_al_paciente(): void
    {
        $cita = $this->citaDelDia('2026-11-11');
        $periodo = $this->registrar('2026-11-11 00:00', '2026-11-11 23:59');
        $opcion = $this->actingAs($this->recepcion)->get(route('indisponibilidades.citas', $periodo))->viewData('opciones')[$cita->id_cita][0];

        $this->actingAs($this->recepcion)->post(route('indisponibilidades.reubicar', [$periodo, $cita]), [
            'id_medico' => $opcion['id_medico'], 'fecha' => $opcion['fecha'], 'id_horario' => $opcion['id_horario'], 'nro_cita' => $opcion['nro'],
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $movida = $cita->fresh();
        $this->assertSame($cita->codigo, $movida->codigo);
        $this->assertSame($opcion['fecha'], $movida->fecha->toDateString());
        $this->assertSame('Programada', $movida->estado);
        $this->assertTrue(Notificacion::where('id_cita', $cita->id_cita)->where('tipo', 'REPROGRAMACION')->exists());
    }

    public function test_cancela_con_reembolso_total_aunque_falten_menos_de_24_horas(): void
    {
        // Cita de hoy a las 09:00, pagada: con CU16 normal no habría reembolso.
        $cita = $this->citaDelDia('2026-11-09', '09:00:00', 'Pagado');
        Pago::create(['id_cita' => $cita->id_cita, 'tipo' => 'COBRO', 'metodo' => 'TARJETA', 'monto' => 100, 'estado' => 'APROBADO', 'registrado_por' => $this->recepcion->id_usuario]);
        $periodo = $this->registrar('2026-11-09 08:00', '2026-11-09 12:00');

        $this->actingAs($this->recepcion)->post(route('indisponibilidades.cancelar', [$periodo, $cita]))->assertSessionHasNoErrors();

        $cita->refresh();
        $this->assertSame('Cancelada', $cita->estado);
        $this->assertSame('Reembolsado', $cita->estado_pago);
        $this->assertStringContainsString('Indisponibilidad médica', $cita->motivo_cancelacion);
        $this->assertTrue(Notificacion::where('id_cita', $cita->id_cita)->where('tipo', 'CANCELACION')->exists());
    }

    public function test_no_toca_citas_fuera_del_periodo(): void
    {
        $fuera = $this->citaDelDia('2026-11-16');
        $periodo = $this->registrar('2026-11-11 00:00', '2026-11-11 23:59');

        $this->actingAs($this->recepcion)->post(route('indisponibilidades.cancelar', [$periodo, $fuera]))->assertNotFound();
        $this->assertSame('Programada', $fuera->fresh()->estado);
    }

    public function test_desactivar_un_medico_con_citas_ofrece_cu10(): void
    {
        $this->citaDelDia('2026-11-16');
        $admin = User::where('username', 'admin')->firstOrFail();
        $admin->forceFill(['debe_cambiar_clave' => false])->save();

        $this->actingAs($admin)->from(route('medicos.index'))->patch(route('medicos.estado', $this->perez))
            ->assertSessionHas('cu10');
    }

    public function test_el_medico_no_registra_indisponibilidades(): void
    {
        $this->actingAs(User::where('username', 'cperez')->firstOrFail())->get(route('indisponibilidades.index'))->assertForbidden();
    }
}

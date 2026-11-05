<?php

namespace Tests\Feature\Reportes;

use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Paquete 6 · CU23 Generar reportes (la bitácora es CU24: tests/Feature/Seguridad/BitacoraTest.php).
 * Usa los datos de demostración (citas relativas a "hoy" = 11/11/2026 10:00).
 */
class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private const HOY = '2026-11-11';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::HOY.' 10:00');
        CarbonImmutable::setTestNow(self::HOY.' 10:00');
        $this->seed(SeguridadSeeder::class);
        $this->seed(DatosDemoSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $admin->forceFill(['debe_cambiar_clave' => false])->save();

        return $admin;
    }

    private function recepcion(): User
    {
        return User::where('username', 'rmendoza')->firstOrFail();
    }

    private function rango(array $extra = []): array
    {
        return array_merge(['desde' => '2026-10-01', 'hasta' => '2026-11-30'], $extra);
    }

    public function test_reporte_por_estado_con_total_general(): void
    {
        $respuesta = $this->actingAs($this->recepcion())
            ->get(route('reportes.index', $this->rango(['tipo' => 'por_estado'])))
            ->assertOk()
            ->assertSee('Citas por estado')
            ->assertSee('Total general');

        $reporte = $respuesta->viewData('reporte');
        $this->assertSame(DB::table('cita')->count(), $reporte['total']['total']);
        $this->assertSame(
            DB::table('cita')->where('estado', 'Atendida')->count(),
            collect($reporte['grupos'][0]['filas'])->firstWhere('estado', 'Atendida')['total'],
        );
    }

    public function test_reporte_por_medico_suma_cada_estado(): void
    {
        $reporte = $this->actingAs($this->recepcion())
            ->get(route('reportes.index', $this->rango(['tipo' => 'por_medico'])))
            ->assertOk()
            ->viewData('reporte');

        $this->assertCount(6, $reporte['grupos'][0]['filas']);
        $this->assertSame(DB::table('cita')->where('estado', 'Cancelada')->count(), $reporte['total']['cancelada']);
    }

    public function test_agenda_diaria_tiene_subtotales_por_medico(): void
    {
        $reporte = $this->actingAs($this->recepcion())
            ->get(route('reportes.index', ['tipo' => 'agenda_diaria', 'fecha' => self::HOY]))
            ->assertOk()
            ->viewData('reporte');

        $esperado = DB::table('cita')->whereDate('fecha', self::HOY)->count();
        $this->assertStringStartsWith((string) $esperado, (string) $reporte['total']['hora']);
        foreach ($reporte['grupos'] as $grupo) {
            $this->assertStringStartsWith((string) count($grupo['filas']), $grupo['subtotal']['hora']);
        }
    }

    public function test_filtros_por_medico_y_estado(): void
    {
        $idMedico = DB::table('medico')->where('codigo', 'MED-0001')->value('id_medico');

        $reporte = $this->actingAs($this->recepcion())
            ->get(route('reportes.index', $this->rango(['tipo' => 'por_estado', 'medico' => $idMedico, 'estado' => 'Atendida'])))
            ->assertOk()
            ->viewData('reporte');

        $this->assertSame(
            DB::table('cita')->where('id_medico', $idMedico)->where('estado', 'Atendida')->count(),
            $reporte['total']['total'] ?? 0,
        );
    }

    public function test_valida_que_la_fecha_de_inicio_no_sea_posterior(): void
    {
        $this->actingAs($this->recepcion())
            ->get(route('reportes.index', ['tipo' => 'por_estado', 'desde' => '2026-11-30', 'hasta' => '2026-11-01']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_sin_resultados_muestra_un_mensaje(): void
    {
        $this->actingAs($this->recepcion())
            ->get(route('reportes.index', ['tipo' => 'por_estado', 'desde' => '2020-01-01', 'hasta' => '2020-01-31']))
            ->assertOk()
            ->assertSee('No hay resultados para los filtros seleccionados');
    }

    public function test_exporta_csv_con_bom_y_filtros(): void
    {
        $respuesta = $this->actingAs($this->recepcion())
            ->get(route('reportes.exportar', ['formato' => 'csv'] + $this->rango(['tipo' => 'por_especialidad'])))
            ->assertOk();

        $contenido = $respuesta->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString('Citas por especialidad', $contenido);
        $this->assertStringContainsString('Odontología', $contenido);
        $this->assertDatabaseHas('bitacora', ['accion' => 'EXPORTAR_REPORTE']);
    }

    public function test_exporta_pdf(): void
    {
        $this->actingAs($this->recepcion())
            ->get(route('reportes.exportar', ['formato' => 'pdf', 'tipo' => 'agenda_diaria', 'fecha' => self::HOY]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_ingresos_es_solo_del_administrador(): void
    {
        $this->actingAs($this->recepcion())
            ->get(route('reportes.index', $this->rango(['tipo' => 'ingresos'])))
            ->assertSessionHasErrors('tipo');

        $reporte = $this->actingAs($this->admin())
            ->get(route('reportes.index', $this->rango(['tipo' => 'ingresos'])))
            ->assertOk()
            ->viewData('reporte');

        $cobros = (float) DB::table('pago')->where('tipo', 'COBRO')->whereBetween('fecha_hora', ['2026-10-01', '2026-11-30 23:59:59'])->sum('monto');
        $reembolsos = (float) DB::table('pago')->where('tipo', 'REEMBOLSO')->whereBetween('fecha_hora', ['2026-10-01', '2026-11-30 23:59:59'])->sum('monto');
        $this->assertEqualsWithDelta($cobros - $reembolsos, $reporte['total']['neto'], 0.01);
    }

    public function test_el_medico_no_genera_reportes(): void
    {
        $this->actingAs(User::where('username', 'cperez')->firstOrFail())
            ->get(route('reportes.index'))
            ->assertForbidden();
    }
}

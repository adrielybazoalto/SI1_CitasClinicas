<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pantallas compartidas por varios CU: ?accion solo filtra y muestra un aviso. */
class AccionesMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-11 08:00');
        CarbonImmutable::setTestNow('2026-11-11 08:00');
        $this->seed(SeguridadSeeder::class);
        $this->seed(DatosDemoSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function usuario(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    public function test_reprogramar_y_cancelar_muestran_solo_citas_programadas(): void
    {
        foreach (['reprogramar' => 'Reprogramar', 'cancelar' => 'Cancelar'] as $accion => $boton) {
            $respuesta = $this->actingAs($this->usuario('rmendoza'))
                ->get(route('citas.index', ['accion' => $accion]))
                ->assertOk()
                ->assertSee('>'.$boton.'</a>', false);

            $this->assertTrue($respuesta->viewData('citas')->getCollection()->every(fn ($c) => $c->estado === 'Programada'));
        }
    }

    public function test_asistencia_muestra_solo_las_citas_activas_de_hoy(): void
    {
        $citas = $this->actingAs($this->usuario('rmendoza'))
            ->get(route('citas.index', ['accion' => 'asistencia']))
            ->assertOk()
            ->assertSee('Citas de hoy')
            ->viewData('citas')->getCollection();

        $this->assertNotEmpty($citas);
        $this->assertTrue($citas->every(fn ($c) => $c->fecha->isToday() && in_array($c->estado, ['Programada', 'Confirmada'], true)));
    }

    public function test_consultar_citas_no_muestra_el_formulario_de_reserva(): void
    {
        $this->actingAs($this->usuario('rmendoza'))
            ->get(route('fichas.index', ['accion' => 'consultar']))
            ->assertOk()
            ->assertSee('Citas disponibles')
            ->assertDontSee('id="form-reserva"', false);

        $this->actingAs($this->usuario('rmendoza'))
            ->get(route('fichas.index'))
            ->assertOk()
            ->assertSee('id="form-reserva"', false);
    }

    public function test_antecedentes_destaca_el_boton_y_el_medico_puede_entrar(): void
    {
        $this->actingAs($this->usuario('cperez'))
            ->get(route('pacientes.index', ['accion' => 'antecedentes']))
            ->assertOk()
            ->assertSee('Seleccione un paciente para registrar o actualizar sus antecedentes clínicos')
            ->assertSee('inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-800 hover:bg-purple-900 text-white text-xs font-semibold rounded-lg', false)
            ->assertDontSee('Nuevo paciente');

        // El médico no gestiona pacientes (CU05): no puede registrar uno nuevo.
        $this->actingAs($this->usuario('cperez'))->get(route('pacientes.create'))->assertForbidden();
    }

    public function test_atender_filtra_las_confirmadas(): void
    {
        $respuesta = $this->actingAs($this->usuario('cperez'))
            ->get(route('agenda.index', ['accion' => 'atender']))
            ->assertOk()
            ->assertSee('Pulse «Atender»');

        $this->assertSame('confirmadas', $respuesta->viewData('estado'));
        $this->assertTrue($respuesta->viewData('citas')->every(fn ($c) => $c->estado === 'Confirmada'));
    }
}

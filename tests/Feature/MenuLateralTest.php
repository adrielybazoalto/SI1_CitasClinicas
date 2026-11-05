<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menú lateral por paquetes (docs/TAREA_UI.md): cada rol ve solo lo suyo. */
class MenuLateralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);
        $this->seed(DatosDemoSeeder::class);
    }

    private function usuario(string $username): User
    {
        $usuario = User::where('username', $username)->firstOrFail();
        $usuario->forceFill(['debe_cambiar_clave' => false])->save();

        return $usuario;
    }

    public function test_el_administrador_ve_los_seis_paquetes(): void
    {
        $respuesta = $this->actingAs($this->usuario('admin'))->get(route('dashboard'))->assertOk();

        foreach (['Seguridad y Accesos', 'Gestión de Pacientes', 'Oferta Médica', 'Gestión de Citas', 'Atención Médica', 'Gestión Financiera y Reportes'] as $paquete) {
            $respuesta->assertSee($paquete);
        }
        // Rutas que aún no existen aparecen como "Pronto", sin error.
        $respuesta->assertSee('Pronto');
    }

    public function test_el_paciente_no_ve_seguridad_ni_oferta_medica(): void
    {
        $this->actingAs($this->usuario('p6543210'))->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Seguridad y Accesos')
            ->assertDontSee('Oferta Médica')
            ->assertSee('Gestión de Citas')
            ->assertSee('Historial del paciente');
    }

    public function test_el_medico_ve_atencion_y_no_reportes(): void
    {
        $this->actingAs($this->usuario('cperez'))->get(route('agenda.index'))
            ->assertOk()
            ->assertSee('Atención Médica')
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('Gestión Financiera y Reportes')
            ->assertDontSee(route('pacientes.index'), false);
    }
}

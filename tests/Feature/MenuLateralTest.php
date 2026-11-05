<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menú lateral: un ítem por caso de uso (config/menu.php), filtrado por rol,
 * con un solo ítem resaltado y el CU correcto en el encabezado.
 */
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

    /** Texto del menú lateral (sin el resto de la página). */
    private function menu(string $html): string
    {
        preg_match('/<nav[^>]*aria-label="Menú principal".*?<\/nav>/s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_cada_cu_aparece_una_sola_vez_para_cada_rol(): void
    {
        foreach (['admin', 'rmendoza', 'cperez', 'p6543210'] as $username) {
            $menu = $this->menu($this->actingAs($this->usuario($username))->get(route('dashboard'))->getContent());
            preg_match_all('/\((CU\d{2})\)/', $menu, $m);

            $this->assertNotEmpty($m[1], "El menú de {$username} está vacío");
            $this->assertSame(count($m[1]), count(array_unique($m[1])), "CU repetido en el menú de {$username}");
        }
    }

    public function test_el_administrador_ve_los_seis_paquetes_y_cu24(): void
    {
        $respuesta = $this->actingAs($this->usuario('admin'))->get(route('dashboard'))->assertOk();

        foreach (['Seguridad y Accesos', 'Gestión de Pacientes', 'Oferta Médica', 'Gestión de Citas', 'Atención Médica', 'Gestión Financiera y Reportes'] as $paquete) {
            $respuesta->assertSee($paquete);
        }
        $respuesta->assertSee('Consultar bitácora (CU24)')->assertSee('Gestionar caja (CU18)');
    }

    public function test_el_paciente_solo_ve_sus_casos_de_uso(): void
    {
        $menu = $this->menu($this->actingAs($this->usuario('p6543210'))->get(route('dashboard'))->getContent());

        $this->assertStringContainsString('Programar cita (CU13)', $menu);
        $this->assertStringContainsString('Cancelar cita (CU16)', $menu);
        $this->assertStringContainsString('Consultar historial (CU22)', $menu);
        $this->assertStringNotContainsString('Gestionar usuarios (CU02)', $menu);
        $this->assertStringNotContainsString('Oferta Médica', $menu);
        $this->assertStringNotContainsString('Registrar asistencia (CU17)', $menu);
    }

    public function test_solo_se_resalta_el_cu_de_la_accion(): void
    {
        $html = $this->actingAs($this->usuario('rmendoza'))
            ->get(route('citas.index', ['accion' => 'cancelar']))
            ->assertOk()
            ->assertSee('CU16 — Cancelar cita')
            ->assertSee('Pulse «Cancelar»')
            ->getContent();

        $this->assertSame(1, substr_count($this->menu($html), 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>.*?Cancelar cita \(CU16\)/s', $this->menu($html));
    }

    public function test_sin_accion_se_resalta_el_cu_principal(): void
    {
        $this->actingAs($this->usuario('cperez'))
            ->get(route('agenda.index'))
            ->assertSee('CU20 — Consultar agenda médica');

        $this->actingAs($this->usuario('cperez'))
            ->get(route('agenda.index', ['accion' => 'atender']))
            ->assertSee('CU21 — Registrar atención médica');
    }

    public function test_pantalla_alcanzada_desde_otra_muestra_su_cu(): void
    {
        $paciente = \App\Models\Paciente::firstOrFail();

        $this->actingAs($this->usuario('cperez'))
            ->get(route('pacientes.antecedentes', $paciente))
            ->assertOk()
            ->assertSee('CU06 — Antecedentes clínicos');
    }
}

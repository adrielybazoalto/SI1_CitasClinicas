<?php

namespace Tests\Feature\Seguridad;

use App\Models\Cita;
use App\Models\User;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El Administrador ve los 24 CU en el menú. En CU04, CU14 y CU21 entra en
 * modo supervisión: no paga por el paciente ni registra la atención.
 */
class AdministradorSupervisaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);
        $this->seed(DatosDemoSeeder::class);
        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->admin->forceFill(['debe_cambiar_clave' => false])->save();
    }

    public function test_el_menu_del_administrador_tiene_los_24_cu(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();
        preg_match('/<nav[^>]*aria-label="Menú principal".*?<\/nav>/s', $html, $menu);
        preg_match_all('/\((CU\d{2})\)/', $menu[0] ?? '', $m);

        $esperados = array_map(fn ($n) => sprintf('CU%02d', $n), range(2, 24));
        $this->assertEqualsCanonicalizing($esperados, array_unique($m[1]), 'CU01 es el inicio de sesión; el resto debe estar en el menú');
    }

    public function test_cu14_supervision_sin_boton_de_pagar(): void
    {
        $this->actingAs($this->admin)->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('Vista de supervisión')
            ->assertDontSee('Pagar ahora');

        $cita = Cita::where('canal', 'LINEA')->firstOrFail();
        $this->actingAs($this->admin)->get(route('pagos.show', $cita))->assertForbidden();
    }

    public function test_cu21_supervision_sin_boton_atender(): void
    {
        // La demo puede no tener citas Confirmadas (fin de semana): se prepara una.
        $cita = Cita::where('estado', 'Programada')->firstOrFail();
        DB::table('cita')->where('id_cita', $cita->id_cita)->update(['estado' => 'Confirmada']);

        $this->actingAs($this->admin)
            ->get(route('agenda.index', ['accion' => 'atender', 'medico' => $cita->id_medico, 'fecha' => $cita->fecha->toDateString()]))
            ->assertOk()
            ->assertSee('Vista de supervisión')
            ->assertDontSee(route('atencion.create', $cita));

        $this->actingAs($this->admin)->get(route('atencion.create', $cita))->assertForbidden();
    }

    public function test_cu04_lleva_al_administrador_a_la_lista_de_pacientes(): void
    {
        $this->actingAs($this->admin)->get(route('perfil.edit'))
            ->assertRedirect(route('pacientes.index'));
    }
}

<?php

namespace Tests\Feature\Citas;

use App\Models\User;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RNF20 · Comandos de voz en CU12, CU13 y CU20: el servidor solo entrega
 * las rutas permitidas para el rol y los campos de cada pantalla.
 * (El reconocimiento en sí corre en el navegador: resources/js/voz.js.)
 */
class VozTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);
        $this->seed(DatosDemoSeeder::class);
    }

    /** @return array{rutas: array<string, string>, campos: array<string, string>} */
    private function voz(string $html): array
    {
        preg_match("/data-voz data-rutas='([^']*)' data-campos='([^']*)'/", $html, $m);
        $this->assertNotEmpty($m, 'La pantalla no tiene el botón de voz');

        return ['rutas' => json_decode(html_entity_decode($m[1]), true), 'campos' => json_decode(html_entity_decode($m[2]), true)];
    }

    public function test_reservar_dicta_los_campos_y_solo_navega_a_rutas_del_rol(): void
    {
        $paciente = User::where('username', 'p6543210')->firstOrFail();
        $voz = $this->voz($this->actingAs($paciente)->get(route('fichas.index'))->assertOk()->getContent());

        $this->assertSame(['especialidad' => 'sel_especialidad', 'medico' => 'sel_medico', 'fecha' => 'sel_fecha', 'motivo' => 'motivo'], $voz['campos']);
        $this->assertArrayHasKey('nueva cita', $voz['rutas']);
        $this->assertArrayNotHasKey('agenda', $voz['rutas'], 'El paciente no tiene agenda médica');
    }

    public function test_consultar_no_dicta_el_motivo(): void
    {
        $voz = $this->voz($this->actingAs(User::where('username', 'rmendoza')->firstOrFail())
            ->get(route('fichas.index', ['accion' => 'consultar']))->getContent());

        $this->assertArrayNotHasKey('motivo', $voz['campos']);
    }

    public function test_agenda_del_medico_dicta_fecha_y_estado(): void
    {
        $voz = $this->voz($this->actingAs(User::where('username', 'cperez')->firstOrFail())->get(route('agenda.index'))->getContent());

        $this->assertSame(['fecha' => 'fecha', 'estado' => 'estado'], $voz['campos']);
        $this->assertArrayHasKey('agenda', $voz['rutas']);
        $this->assertArrayNotHasKey('nueva cita', $voz['rutas']);
    }
}

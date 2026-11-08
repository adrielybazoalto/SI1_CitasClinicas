<?php

namespace Tests\Feature\Citas;

use App\Models\User;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RNF20 · Comandos de voz: un micrófono flotante para todos los roles. El
 * servidor solo entrega los destinos permitidos para el rol (MenuModulos) y
 * cada pantalla declara los campos que se pueden dictar (CU13, CU20, CU23).
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

    private function usuario(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    /** Destinos por CU del micrófono flotante (<x-voz-global>); null si la página no lo tiene. */
    private function destinos(string $html): ?array
    {
        if (! preg_match("/data-voz data-lang=\"es-BO\" data-destinos='([^']*)'/", $html, $m)) {
            return null;
        }

        return json_decode(html_entity_decode($m[1]), true);
    }

    /** Campos que la pantalla declara para dictar (<x-voz>). */
    private function campos(string $html): ?array
    {
        return preg_match("/data-voz-campos='([^']*)'/", $html, $m) ? json_decode(html_entity_decode($m[1]), true) : null;
    }

    public function test_todos_los_roles_tienen_un_solo_microfono_flotante(): void
    {
        foreach (['p6543210', 'rmendoza', 'admin', 'cperez'] as $username) {
            $usuario = $this->usuario($username);
            $usuario->forceFill(['debe_cambiar_clave' => false])->save();
            $html = $this->actingAs($usuario)->get(route('dashboard'))->assertOk()->getContent();

            $this->assertNotNull($this->destinos($html), "{$username} no tiene el micrófono flotante");
            $this->assertSame(1, substr_count($html, 'data-voz-boton'), "{$username} debe tener un solo micrófono");
        }
    }

    public function test_en_programar_cita_la_pantalla_pasa_sus_campos_al_microfono(): void
    {
        foreach (['p6543210', 'rmendoza'] as $username) {
            $html = $this->actingAs($this->usuario($username))->get(route('fichas.index'))->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, 'data-voz-boton'), 'Debe haber un solo micrófono');
            $this->assertSame(['especialidad' => 'sel_especialidad', 'medico' => 'sel_medico', 'fecha' => 'sel_fecha', 'motivo' => 'motivo'], $this->campos($html));
        }
    }

    public function test_consultar_no_dicta_el_motivo(): void
    {
        $campos = $this->campos($this->actingAs($this->usuario('rmendoza'))->get(route('fichas.index', ['accion' => 'consultar']))->getContent());

        $this->assertArrayNotHasKey('motivo', $campos);
    }

    public function test_agenda_del_medico_dicta_fecha_y_estado(): void
    {
        $campos = $this->campos($this->actingAs($this->usuario('cperez'))->get(route('agenda.index'))->getContent());

        $this->assertSame(['fecha' => 'fecha', 'estado' => 'estado'], $campos);
    }

    public function test_reportes_declara_tipo_rango_y_filtros_para_dictar(): void
    {
        $html = $this->actingAs($this->usuario('rmendoza'))->get(route('reportes.index', ['tipo' => 'citas_fecha']))->assertOk()->getContent();

        $this->assertSame(
            ['tipo' => 'tipo', 'desde' => 'desde', 'hasta' => 'hasta', 'fecha' => 'fecha', 'medico' => 'medico', 'especialidad' => 'especialidad', 'estado' => 'estado'],
            $this->campos($html),
        );
        // Aunque este tipo usa una sola fecha, el rango y los filtros siguen en el formulario (ocultos) para que la voz los llene.
        foreach (['id="desde"', 'id="hasta"', 'id="fecha"', 'id="medico"', 'id="especialidad"', 'id="estado"'] as $campo) {
            $this->assertStringContainsString($campo, $html);
        }
    }

    public function test_reportes_ocultos_no_cambian_el_resultado(): void
    {
        // Pacientes registrados no usa los filtros de cita: siguen en el formulario, ocultos, y el reporte se genera igual.
        $this->actingAs($this->usuario('rmendoza'))
            ->get(route('reportes.index', ['tipo' => 'pacientes', 'medico' => '', 'estado' => '']))
            ->assertOk()
            ->assertSee('Pacientes registrados');
    }

    public function test_el_paciente_solo_recibe_destinos_de_paciente(): void
    {
        $destinos = $this->destinos($this->actingAs($this->usuario('p6543210'))->get(route('dashboard'))->getContent());

        $this->assertSame(route('fichas.index'), $destinos['CU13']);
        $this->assertSame(route('pagos.index'), $destinos['CU14']);
        $this->assertSame(route('citas.index', ['accion' => 'cancelar']), $destinos['CU16']);
        $this->assertSame(route('perfil.edit'), $destinos['CU04']);
        foreach (['CU18', 'CU19', 'CU20', 'CU23', 'CU24', 'CU02'] as $cu) {
            $this->assertArrayNotHasKey($cu, $destinos, "El paciente no debe poder ir a {$cu} por voz");
        }
    }

    public function test_la_recepcionista_va_a_reportes_caja_y_agenda_pero_no_a_administracion(): void
    {
        $destinos = $this->destinos($this->actingAs($this->usuario('rmendoza'))->get(route('dashboard'))->getContent());

        $this->assertSame(route('reportes.index'), $destinos['CU23']);
        $this->assertSame(route('cobros.index'), $destinos['CU19']);
        $this->assertSame(route('caja.index'), $destinos['CU18']);
        $this->assertSame(route('agenda.index'), $destinos['CU20']);
        foreach (['CU02', 'CU03', 'CU24', 'CU14', 'CU04'] as $cu) {
            $this->assertArrayNotHasKey($cu, $destinos, "La recepcionista no debe poder ir a {$cu} por voz");
        }
    }

    public function test_el_administrador_va_a_bitacora_y_usuarios(): void
    {
        $admin = $this->usuario('admin');
        $admin->forceFill(['debe_cambiar_clave' => false])->save();
        $destinos = $this->destinos($this->actingAs($admin)->get(route('dashboard'))->getContent());

        $this->assertSame(route('bitacora.index'), $destinos['CU24']);
        $this->assertSame(route('usuarios.index'), $destinos['CU02']);
        $this->assertSame(route('reportes.index'), $destinos['CU23']);
    }

    public function test_el_medico_no_va_a_reportes_ni_a_caja(): void
    {
        $destinos = $this->destinos($this->actingAs($this->usuario('cperez'))->get(route('dashboard'))->getContent());

        $this->assertArrayHasKey('CU20', $destinos);
        foreach (['CU18', 'CU19', 'CU23', 'CU24'] as $cu) {
            $this->assertArrayNotHasKey($cu, $destinos, "El médico no debe poder ir a {$cu} por voz");
        }
    }
}

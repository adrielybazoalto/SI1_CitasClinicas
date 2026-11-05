<?php

namespace Tests\Feature\Oferta;

use App\Models\Especialidad;
use App\Models\Rol;
use App\Models\Tarifa;
use App\Models\User;
use App\Services\TarifaService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** P3 · CU11 Gestionar tarifario (y RN22). Hoy es 10/11/2026. */
class TarifaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Especialidad $especialidad;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 09:00');
        CarbonImmutable::setTestNow('2026-11-10 09:00');
        $this->seed(SeguridadSeeder::class);

        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->admin->forceFill(['debe_cambiar_clave' => false])->save();
        $this->especialidad = Especialidad::create(['nombre' => 'Cardiología', 'duracion_min' => 30, 'estado' => true]);
        Tarifa::create(['id_especialidad' => $this->especialidad->id_especialidad, 'monto' => 180, 'vigente_desde' => '2026-01-01']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function nuevoPrecio(array $datos)
    {
        return $this->actingAs($this->admin)->post(route('tarifas.store'), $datos + [
            'id_especialidad' => $this->especialidad->id_especialidad,
        ]);
    }

    public function test_lista_las_especialidades_con_su_precio_vigente(): void
    {
        $this->actingAs($this->admin)->get(route('tarifas.index'))
            ->assertOk()
            ->assertSee('CU11 — Gestionar tarifario')
            ->assertSee('Cardiología')
            ->assertSee('Bs 180,00');
    }

    public function test_un_precio_nuevo_cierra_el_anterior_el_dia_previo(): void
    {
        $this->nuevoPrecio(['monto' => '200,50', 'vigente_desde' => '2026-12-01'])
            ->assertRedirect(route('tarifas.index'))
            ->assertSessionHas('status');

        $tarifas = Tarifa::orderBy('vigente_desde')->get();
        $this->assertSame('2026-11-30', $tarifas[0]->vigente_hasta->toDateString());
        $this->assertSame('200.50', $tarifas[1]->monto);
        $this->assertNull($tarifas[1]->vigente_hasta);
        $this->assertDatabaseHas('bitacora', ['accion' => 'ACTUALIZAR_TARIFA']);

        // Hasta el 30/11 rige el precio anterior; desde el 01/12, el nuevo.
        $servicio = app(TarifaService::class);
        $this->assertSame('180.00', $servicio->vigenteEn($this->especialidad->id_especialidad, CarbonImmutable::parse('2026-11-30'))->monto);
        $this->assertSame('200.50', $servicio->vigenteEn($this->especialidad->id_especialidad, CarbonImmutable::parse('2026-12-01'))->monto);
    }

    public function test_valida_monto_y_fecha(): void
    {
        $this->nuevoPrecio(['monto' => '0', 'vigente_desde' => '2026-12-01'])->assertSessionHasErrors('monto');
        $this->nuevoPrecio(['monto' => '-5', 'vigente_desde' => '2026-12-01'])->assertSessionHasErrors('monto');
        $this->nuevoPrecio(['monto' => '10.555', 'vigente_desde' => '2026-12-01'])->assertSessionHasErrors('monto');
        $this->nuevoPrecio(['monto' => '150', 'vigente_desde' => '2026-11-09'])->assertSessionHasErrors('vigente_desde');

        $this->assertSame(1, Tarifa::count());
    }

    public function test_no_se_superpone_con_un_precio_programado(): void
    {
        $this->nuevoPrecio(['monto' => '200', 'vigente_desde' => '2026-12-01']);
        $this->nuevoPrecio(['monto' => '190', 'vigente_desde' => '2026-11-20'])->assertSessionHasErrors('vigente_desde');

        $this->assertSame(2, Tarifa::count());
    }

    public function test_historial_de_precios(): void
    {
        $this->nuevoPrecio(['monto' => '200', 'vigente_desde' => '2026-12-01']);

        $this->actingAs($this->admin)->get(route('tarifas.show', $this->especialidad))
            ->assertOk()
            ->assertSeeInOrder(['Bs 200,00', 'Programada', 'Bs 180,00', 'Vigente']);
    }

    public function test_solo_el_administrador_gestiona_el_tarifario(): void
    {
        $recepcion = User::create([
            'id_rol' => Rol::where('nombre', Rol::RECEPCIONISTA)->value('id_rol'),
            'username' => 'rmendoza', 'correo' => 'r@vidasalud.bo', 'password_hash' => Hash::make('x12345678'),
            'debe_cambiar_clave' => false, 'estado' => true,
        ]);

        $this->actingAs($recepcion)->get(route('tarifas.index'))->assertForbidden();
    }
}

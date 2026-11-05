<?php

namespace Tests\Feature\Seguridad;

use App\Models\Bitacora;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Paquete 1 · CU24 Consultar bitácora (solo lectura, RNF08). */
class BitacoraTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $recepcion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);

        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->admin->forceFill(['debe_cambiar_clave' => false])->save();
        $this->recepcion = User::create([
            'id_rol' => Rol::where('nombre', Rol::RECEPCIONISTA)->value('id_rol'),
            'username' => 'rmendoza', 'correo' => 'rmendoza@vidasalud.bo',
            'password_hash' => Hash::make('secreto123'), 'debe_cambiar_clave' => false, 'estado' => true,
        ]);

        $this->registro('2026-10-01 08:00:00', 'LOGIN', $this->recepcion, 'Inicio de sesión antiguo');       // nombre viejo
        $this->registro('2026-10-15 09:30:00', Bitacora::INICIO_SESION, $this->recepcion, 'Inicio de sesión exitoso de rmendoza', '192.168.1.21');
        $this->registro('2026-10-15 10:00:00', Bitacora::PROGRAMAR_CITA, $this->recepcion, 'Cita CM-000001');
        $this->registro('2026-10-31 18:00:00', Bitacora::CIERRE_SESION, $this->admin, 'Cierre de sesión de admin', '10.0.0.5');
    }

    private function registro(string $cuando, string $accion, User $usuario, string $detalle, ?string $ip = null): void
    {
        DB::table('bitacora')->insert([
            'id_usuario' => $usuario->id_usuario, 'fecha_hora' => $cuando, 'ip' => $ip,
            'accion' => $accion, 'tabla' => 'usuario', 'detalle' => $detalle,
        ]);
    }

    public function test_el_administrador_ve_la_bitacora_mas_reciente_primero(): void
    {
        $this->actingAs($this->admin)
            ->get(route('bitacora.index'))
            ->assertOk()
            ->assertSee('CU24 — Consultar bitácora')
            ->assertSeeInOrder(['31/10/2026 18:00:00', '15/10/2026 10:00:00', '15/10/2026 09:30:00', '01/10/2026 08:00:00'])
            ->assertSee('No disponible')
            ->assertSee('Recepcionista');
    }

    public function test_otros_roles_reciben_403(): void
    {
        $this->actingAs($this->recepcion)->get(route('bitacora.index'))->assertForbidden();

        $medico = User::create([
            'id_rol' => Rol::where('nombre', Rol::MEDICO)->value('id_rol'),
            'username' => 'cperez', 'correo' => 'cperez@vidasalud.bo',
            'password_hash' => Hash::make('secreto123'), 'debe_cambiar_clave' => false, 'estado' => true,
        ]);
        $this->actingAs($medico)->get(route('bitacora.index'))->assertForbidden();
    }

    public function test_filtra_por_rango_de_fechas(): void
    {
        $this->actingAs($this->admin)
            ->get(route('bitacora.index', ['desde' => '2026-10-10', 'hasta' => '2026-10-20']))
            ->assertOk()
            ->assertSee('Cita CM-000001')
            ->assertSee('Inicio de sesión exitoso de rmendoza')
            ->assertDontSee('Cierre de sesión de admin')
            ->assertDontSee('Inicio de sesión antiguo');

        $this->actingAs($this->admin)
            ->get(route('bitacora.index', ['desde' => '2026-10-20', 'hasta' => '2026-10-10']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_filtra_por_accion_en_espanol_incluidos_los_registros_antiguos(): void
    {
        $this->actingAs($this->admin)
            ->get(route('bitacora.index', ['accion' => Bitacora::INICIO_SESION]))
            ->assertOk()
            ->assertSee('Inicio de sesión antiguo')               // guardado como LOGIN
            ->assertSee('Inicio de sesión exitoso de rmendoza')
            ->assertDontSee('Cita CM-000001')
            ->assertDontSee('>LOGIN<', false);
    }

    public function test_filtra_por_usuario_e_ip(): void
    {
        $this->actingAs($this->admin)
            ->get(route('bitacora.index', ['usuario' => $this->admin->id_usuario]))
            ->assertOk()->assertSee('Cierre de sesión de admin')->assertDontSee('Cita CM-000001');

        $this->actingAs($this->admin)
            ->get(route('bitacora.index', ['ip' => '192.168.']))
            ->assertOk()->assertSee('Inicio de sesión exitoso de rmendoza')->assertDontSee('Cierre de sesión de admin');
    }

    public function test_pagina_de_20_registros(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->registro('2026-09-01 08:'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).':00', Bitacora::PROGRAMAR_CITA, $this->recepcion, "Relleno {$i}");
        }

        $this->assertCount(20, $this->actingAs($this->admin)->get(route('bitacora.index'))->viewData('registros')->items());
    }

    public function test_es_de_solo_lectura(): void
    {
        $this->assertFalse(collect(app('router')->getRoutes())->contains(
            fn ($ruta) => str_starts_with($ruta->uri(), 'seguridad/bitacora') && array_diff($ruta->methods(), ['GET', 'HEAD']) !== []
        ));
        $this->assertArrayNotHasKey('bitacora', \App\Services\ReporteService::TIPOS, 'La bitácora no forma parte de Reportes (CU23).');
    }
}

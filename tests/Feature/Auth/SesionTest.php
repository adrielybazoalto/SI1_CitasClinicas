<?php

namespace Tests\Feature\Auth;

use App\Models\Bitacora;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * CU01 · Iniciar y cerrar sesión, bloqueo por intentos, cambio y
 * recuperación de contraseña. Reemplaza las pruebas genéricas de Breeze
 * (registro, verificación de correo, borrar cuenta), funciones que este
 * sistema no tiene por diseño.
 */
class SesionTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);

        $this->usuario = User::create([
            'id_rol' => Rol::where('nombre', Rol::RECEPCIONISTA)->value('id_rol'),
            'username' => 'jperez',
            'correo' => 'jperez@vidasalud.bo',
            'password_hash' => Hash::make('secreto123'),
            'debe_cambiar_clave' => false,
            'estado' => true,
        ]);
    }

    public function test_la_raiz_lleva_al_login_y_el_login_se_muestra(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Iniciar Sesión');
    }

    public function test_inicia_sesion_y_registra_inicio_sesion_en_la_bitacora(): void
    {
        $this->post('/login', ['login' => 'jperez', 'password' => 'secreto123'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->usuario);
        $this->assertDatabaseHas('bitacora', [
            'accion' => Bitacora::INICIO_SESION,
            'id_usuario' => $this->usuario->id_usuario,
            'detalle' => 'Inicio de sesión exitoso de jperez',
        ]);
    }

    public function test_tambien_inicia_sesion_con_el_correo(): void
    {
        $this->post('/login', ['login' => 'JPEREZ@vidasalud.bo', 'password' => 'secreto123']);

        $this->assertAuthenticatedAs($this->usuario);
    }

    public function test_contrasena_incorrecta_no_inicia_sesion(): void
    {
        $this->post('/login', ['login' => 'jperez', 'password' => 'incorrecta'])
            ->assertSessionHasErrors();

        $this->assertGuest();
        $this->assertDatabaseHas('bitacora', ['accion' => Bitacora::INICIO_SESION_FALLIDO]);
    }

    public function test_cinco_intentos_fallidos_bloquean_la_cuenta(): void
    {
        for ($i = 0; $i < (int) config('clinica.max_intentos_login'); $i++) {
            $this->post('/login', ['login' => 'jperez', 'password' => 'incorrecta']);
        }

        $this->assertTrue($this->usuario->fresh()->estaBloqueado());
        $this->assertDatabaseHas('bitacora', ['accion' => Bitacora::CUENTA_BLOQUEADA]);

        // Bloqueada: ni con la contraseña correcta entra.
        $this->post('/login', ['login' => 'jperez', 'password' => 'secreto123']);
        $this->assertGuest();
    }

    public function test_cierra_sesion_y_registra_cierre_sesion(): void
    {
        $this->actingAs($this->usuario)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('bitacora', [
            'accion' => Bitacora::CIERRE_SESION,
            'id_usuario' => $this->usuario->id_usuario,
            'detalle' => 'Cierre de sesión de jperez',
        ]);
    }

    public function test_paginas_internas_sin_sesion_redirigen_al_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect('/login');
    }

    public function test_cambia_su_contrasena_desde_mi_cuenta(): void
    {
        $this->actingAs($this->usuario)->get(route('profile.edit'))->assertOk();

        $this->actingAs($this->usuario)
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'current_password' => 'secreto123',
                'password' => 'Nueva#26',
                'password_confirmation' => 'Nueva#26',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('Nueva#26', $this->usuario->fresh()->password_hash));
        $this->assertDatabaseHas('bitacora', ['accion' => Bitacora::CAMBIO_CLAVE]);
    }

    public function test_no_cambia_la_contrasena_si_la_actual_es_incorrecta(): void
    {
        $this->actingAs($this->usuario)
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'current_password' => 'otra',
                'password' => 'Nueva#26',
                'password_confirmation' => 'Nueva#26',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');
    }

    public function test_solicitar_recuperacion_crea_un_enlace_de_un_solo_uso(): void
    {
        $this->post(route('password.email'), ['correo' => 'jperez@vidasalud.bo'])
            ->assertSessionHas('status');

        $token = DB::table('recuperacion_clave')->where('id_usuario', $this->usuario->id_usuario)->first();
        $this->assertNotNull($token);
        $this->assertNull($token->usado_en);
        $this->assertEqualsWithDelta(
            (int) config('clinica.recuperacion_clave_min'),
            now()->diffInMinutes($token->expira_en),
            1,
        );
        $this->assertDatabaseHas('bitacora', ['accion' => Bitacora::SOLICITUD_RECUPERACION_CLAVE]);
    }

    public function test_los_registros_antiguos_se_muestran_en_espanol(): void
    {
        DB::table('bitacora')->insert([
            'id_usuario' => $this->usuario->id_usuario, 'fecha_hora' => now(), 'ip' => null,
            'accion' => 'LOGIN', 'tabla' => 'usuario', 'detalle' => 'Inicio de sesión',
        ]);
        $admin = User::where('username', 'admin')->firstOrFail();
        $admin->forceFill(['debe_cambiar_clave' => false])->save();

        $this->actingAs($admin)
            ->get(route('bitacora.index', ['accion' => Bitacora::INICIO_SESION]))
            ->assertOk()
            ->assertSee('INICIO_SESION')
            ->assertSee('No disponible')
            ->assertDontSee('>LOGIN<', false);
    }
}

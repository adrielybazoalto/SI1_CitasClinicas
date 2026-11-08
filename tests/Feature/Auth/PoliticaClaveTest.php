<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\User;
use App\Rules\ClaveSegura;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * CU01/CU02/CU04 · Política de contraseñas (App\Rules\ClaveSegura) y
 * mensajes de validación en español (lang/es).
 */
class PoliticaClaveTest extends TestCase
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

    private function errores(string $clave): array
    {
        return Validator::make(['clave' => $clave], ['clave' => [new ClaveSegura()]])->errors()->get('clave');
    }

    public function test_acepta_una_clave_que_cumple_todos_los_requisitos(): void
    {
        $this->assertSame([], $this->errores('Nueva#26'));
    }

    public function test_rechaza_cada_requisito_incumplido_con_mensaje_en_espanol(): void
    {
        $this->assertContains('La contraseña debe tener al menos 8 caracteres.', $this->errores('Nueva#2'));
        $this->assertSame([], $this->errores('Nueva#2026'));
        $this->assertContains('La contraseña debe incluir al menos una letra mayúscula.', $this->errores('nueva#26'));
        $this->assertContains('La contraseña debe incluir al menos una letra minúscula.', $this->errores('NUEVA#26'));
        $this->assertContains('La contraseña debe incluir al menos un número.', $this->errores('Nueva#ab'));
        $this->assertContains(
            'La contraseña debe incluir al menos un carácter especial (por ejemplo @ # $ % & * ! ?).',
            $this->errores('Nueva126'),
        );
        $this->assertContains('La contraseña no puede contener espacios.', $this->errores('Nue a#26'));
    }

    public function test_la_clave_temporal_generada_cumple_la_politica(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertSame([], $this->errores(ClaveSegura::generar()));
        }
    }

    public function test_la_confirmacion_distinta_muestra_un_mensaje_en_espanol(): void
    {
        $this->actingAs($this->usuario)
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'current_password' => 'secreto123',
                'password' => 'Nueva#26',
                'password_confirmation' => 'Otra#260',
            ])
            ->assertSessionHasErrorsIn('updatePassword', [
                'password' => 'Las contraseñas no coinciden. Escriba la misma contraseña en ambos campos.',
            ]);
    }

    public function test_la_clave_actual_incorrecta_muestra_un_mensaje_en_espanol(): void
    {
        $this->actingAs($this->usuario)
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'current_password' => 'otra',
                'password' => 'Nueva#26',
                'password_confirmation' => 'Nueva#26',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'La contraseña actual es incorrecta.']);
    }

    public function test_no_guarda_una_clave_debil_en_el_cambio_obligatorio(): void
    {
        $this->usuario->forceFill(['debe_cambiar_clave' => true])->save();

        $this->actingAs($this->usuario)
            ->from(route('password.cambiar'))
            ->put(route('password.cambiar.guardar'), [
                'password' => 'clave1234',
                'password_confirmation' => 'clave1234',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($this->usuario->fresh()->debe_cambiar_clave);
    }

    public function test_el_formulario_muestra_los_requisitos(): void
    {
        $this->actingAs($this->usuario)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('La contraseña debe cumplir con:')
            ->assertDontSee('validation.');
    }
}

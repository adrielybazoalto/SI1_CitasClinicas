<?php

namespace Tests\Feature\Pacientes;

use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** P2 · CU04 Gestionar perfil del paciente. */
class PerfilTest extends TestCase
{
    use RefreshDatabase;

    private User $maria;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);
        $this->seed(DatosDemoSeeder::class);

        $this->maria = User::where('username', 'p6543210')->firstOrFail();
        $this->paciente = Paciente::where('id_usuario', $this->maria->id_usuario)->firstOrFail();
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'telefono' => '70123456',
            'correo' => 'maria.nueva@example.com',
            'direccion' => 'Calle Nueva 123',
            'contacto_nombre' => 'Pedro López',
            'contacto_parentesco' => 'Hermano',
            'contacto_telefono' => '71234567',
            'recibe_recordatorios' => '0',
        ], $cambios);
    }

    public function test_el_paciente_ve_su_perfil_y_sus_proximas_citas(): void
    {
        $this->actingAs($this->maria)->get(route('perfil.edit'))
            ->assertOk()
            ->assertSee('CU04 — Mi perfil')
            ->assertSee($this->paciente->ci)
            ->assertSee('Mis próximas citas')
            ->assertSee('acuda a recepción');
    }

    public function test_actualiza_contacto_recordatorios_y_contacto_de_emergencia(): void
    {
        $this->actingAs($this->maria)->put(route('perfil.update'), $this->datos())
            ->assertRedirect(route('perfil.edit'))->assertSessionHasNoErrors();

        $this->paciente->refresh();
        $this->assertSame('70123456', $this->paciente->telefono);
        $this->assertSame('maria.nueva@example.com', $this->paciente->correo);
        $this->assertFalse($this->paciente->recibe_recordatorios);
        $this->assertSame('maria.nueva@example.com', $this->maria->fresh()->correo);
        $this->assertSame('Pedro López', $this->paciente->antecedente->contacto_nombre);
        $this->assertSame('Penicilina', $this->paciente->antecedente->alergias, 'Los antecedentes clínicos no cambian');
        $this->assertDatabaseHas('bitacora', ['accion' => 'ACTUALIZAR_PERFIL']);
    }

    public function test_no_puede_cambiar_ci_ni_nombres(): void
    {
        $this->actingAs($this->maria)->put(route('perfil.update'), $this->datos(['ci' => '999', 'nombres' => 'Otra']));

        $this->assertSame('6543210', $this->paciente->fresh()->ci);
        $this->assertSame('María', $this->paciente->fresh()->nombres);
    }

    public function test_valida_telefono_y_correo_unico(): void
    {
        $this->actingAs($this->maria)->put(route('perfil.update'), $this->datos(['telefono' => '12']))->assertSessionHasErrors('telefono');
        $this->actingAs($this->maria)->put(route('perfil.update'), $this->datos(['correo' => 'carlos.perez@vidasalud.bo']))->assertSessionHasErrors('correo');
    }

    public function test_cambia_su_contrasena_verificando_la_actual(): void
    {
        $this->actingAs($this->maria)->from(route('perfil.edit'))->put(route('password.update'), [
            'current_password' => 'p6543210@2026', 'password' => 'Nueva#26', 'password_confirmation' => 'Nueva#26',
        ])->assertRedirect(route('perfil.edit'));

        $this->assertTrue(Hash::check('Nueva#26', $this->maria->fresh()->password_hash));
    }

    public function test_el_personal_no_tiene_perfil_de_paciente(): void
    {
        $this->actingAs(User::where('username', 'rmendoza')->firstOrFail())->get(route('perfil.edit'))->assertForbidden();
    }
}

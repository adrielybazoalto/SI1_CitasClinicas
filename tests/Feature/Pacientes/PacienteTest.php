<?php

namespace Tests\Feature\Pacientes;

use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * CU05 · Gestionar pacientes y CU06 · Gestionar antecedentes clínicos.
 */
class PacienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SeguridadSeeder::class);
    }

    private function administrador(): User
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        // El seeder obliga a cambiar la clave en el primer ingreso; para las
        // pruebas de este módulo se usa una sesión ya habilitada.
        if ($admin->debe_cambiar_clave) {
            $admin->forceFill(['debe_cambiar_clave' => false])->save();
        }

        return $admin;
    }

    /**
     * @param array<string, mixed> $cambios
     * @return array<string, mixed>
     */
    private function datos(array $cambios = []): array
    {
        return array_merge([
            'ci' => '12345678',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'fecha_nacimiento' => now()->subYears(30)->toDateString(),
            'sexo' => 'F',
            'telefono' => '70012345',
            'correo' => 'ana@example.com',
            'direccion' => 'Calle 1',
            'recibe_recordatorios' => '1',
            'tipo_sangre' => 'O+',
            'alergias' => 'Penicilina',
            'enfermedades_cronicas' => null,
            'medicamentos' => null,
            'antecedentes' => null,
            'contacto_nombre' => 'Luis Pérez',
            'contacto_parentesco' => 'Padre',
            'contacto_telefono' => '70000000',
        ], $cambios);
    }

    public function test_registra_paciente_con_cuenta_de_acceso_y_antecedentes(): void
    {
        $respuesta = $this->actingAs($this->administrador())
            ->post(route('pacientes.store'), $this->datos());

        $respuesta->assertRedirect(route('pacientes.index'));
        $respuesta->assertSessionHas('clave_temporal');

        $paciente = Paciente::where('ci', '12345678')->firstOrFail();

        $this->assertSame('PAC-000001', $paciente->codigo);
        $this->assertNotNull($paciente->id_usuario);
        $this->assertTrue($paciente->estado);

        $usuario = $paciente->usuario;
        $this->assertSame('12345678', $usuario->username);
        $this->assertSame('ana@example.com', $usuario->correo);
        $this->assertSame((int) Rol::where('nombre', Rol::PACIENTE)->value('id_rol'), (int) $usuario->id_rol);
        $this->assertTrue($usuario->debe_cambiar_clave);
        $this->assertTrue($usuario->estado);

        $claveTemporal = $respuesta->getSession()->get('clave_temporal');
        $this->assertSame('12345678', $claveTemporal['username']);
        $this->assertTrue(Hash::check($claveTemporal['clave'], $usuario->password_hash));

        $this->assertDatabaseHas('antecedente', [
            'id_paciente' => $paciente->id_paciente,
            'tipo_sangre' => 'O+',
            'alergias' => 'Penicilina',
            'contacto_nombre' => 'Luis Pérez',
        ]);
    }

    public function test_no_permite_registrar_un_ci_duplicado(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('pacientes.store'), $this->datos());

        $respuesta = $this->actingAs($this->administrador())
            ->post(route('pacientes.store'), $this->datos(['correo' => 'otra@example.com']));

        $respuesta->assertSessionHasErrors('ci');
        $this->assertSame(1, Paciente::where('ci', '12345678')->count());
    }

    public function test_actualiza_datos_del_paciente_y_sus_antecedentes(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('pacientes.store'), $this->datos());

        $paciente = Paciente::where('ci', '12345678')->firstOrFail();

        $respuesta = $this->actingAs($this->administrador())
            ->put(route('pacientes.update', $paciente), $this->datos([
                'ci' => '87654321',
                'nombres' => 'Ana María',
                'correo' => 'nueva@example.com',
                'tipo_sangre' => 'A+',
                'alergias' => 'Ninguna',
            ]));

        $respuesta->assertRedirect(route('pacientes.index'));

        $paciente->refresh();
        $this->assertSame('87654321', $paciente->ci);
        $this->assertSame('Ana María', $paciente->nombres);
        $this->assertSame('87654321', $paciente->usuario->username);
        $this->assertSame('nueva@example.com', $paciente->usuario->correo);

        $this->assertDatabaseHas('antecedente', [
            'id_paciente' => $paciente->id_paciente,
            'tipo_sangre' => 'A+',
            'alergias' => 'Ninguna',
        ]);
    }

    public function test_desactiva_paciente_sin_borrar_su_historial(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('pacientes.store'), $this->datos());

        $paciente = Paciente::where('ci', '12345678')->firstOrFail();

        $respuesta = $this->actingAs($this->administrador())
            ->delete(route('pacientes.destroy', $paciente));

        $respuesta->assertRedirect();

        $this->assertDatabaseHas('paciente', [
            'id_paciente' => $paciente->id_paciente,
            'estado' => false,
        ]);
        $this->assertDatabaseHas('usuario', [
            'id_usuario' => $paciente->id_usuario,
            'estado' => false,
        ]);
        $this->assertDatabaseHas('antecedente', [
            'id_paciente' => $paciente->id_paciente,
        ]);
    }

    public function test_busca_pacientes_por_ci_o_apellidos(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('pacientes.store'), $this->datos());

        $this->actingAs($this->administrador())
            ->get(route('pacientes.index', ['buscar' => 'Pérez']))
            ->assertOk()
            ->assertSee('Pérez');

        $this->actingAs($this->administrador())
            ->get(route('pacientes.index', ['buscar' => '12345678']))
            ->assertOk()
            ->assertSee('12345678');
    }

    public function test_genera_correo_interno_cuando_el_paciente_no_ingresa_correo(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('pacientes.store'), $this->datos(['correo' => null]));

        $paciente = Paciente::where('ci', '12345678')->firstOrFail();

        $this->assertNull($paciente->correo);
        $this->assertSame('12345678@pacientes.vidasalud.bo', $paciente->usuario->correo);
    }

    public function test_medico_puede_gestionar_antecedentes_pero_no_pacientes(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('pacientes.store'), $this->datos());

        $paciente = Paciente::where('ci', '12345678')->firstOrFail();

        $medico = User::create([
            'id_rol' => Rol::where('nombre', 'Medico')->value('id_rol'),
            'username' => 'medico1',
            'correo' => 'medico1@example.com',
            'password_hash' => Hash::make('clave-segura'),
            'debe_cambiar_clave' => false,
            'estado' => true,
        ]);

        // CU06: el médico ve la lista para elegir al paciente, sin las acciones de CU05.
        $this->actingAs($medico)
            ->get(route('pacientes.index', ['accion' => 'antecedentes']))
            ->assertOk()
            ->assertDontSee('Nuevo paciente');

        $this->actingAs($medico)
            ->get(route('pacientes.create'))
            ->assertForbidden();

        $this->actingAs($medico)
            ->get(route('pacientes.antecedentes', $paciente))
            ->assertOk()
            ->assertSee('Ficha clínica básica');

        $this->actingAs($medico)
            ->put(route('pacientes.antecedentes.update', $paciente), [
                'tipo_sangre' => 'B+',
                'alergias' => 'Polvo',
                'enfermedades_cronicas' => 'Asma',
                'medicamentos' => 'Salbutamol',
                'antecedentes' => 'Sin cirugías',
                'contacto_nombre' => 'Luis Pérez',
                'contacto_parentesco' => 'Padre',
                'contacto_telefono' => '70000000',
            ])
            ->assertRedirect(route('pacientes.antecedentes', $paciente));

        $this->assertDatabaseHas('antecedente', [
            'id_paciente' => $paciente->id_paciente,
            'tipo_sangre' => 'B+',
            'alergias' => 'Polvo',
            'enfermedades_cronicas' => 'Asma',
        ]);
    }
}

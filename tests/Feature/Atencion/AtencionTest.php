<?php

namespace Tests\Feature\Atencion;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Paquete 5 · CU21 Registrar atención médica (y su extensión CU06).
 * Hoy es miércoles 11/11/2026 08:30.
 */
class AtencionTest extends TestCase
{
    use RefreshDatabase;

    private const HOY = '2026-11-11';

    private Especialidad $especialidad;
    private Medico $perez;
    private User $usuarioPerez;
    private User $usuarioVaca;
    private Paciente $paciente;
    private Cita $confirmada;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::HOY.' 08:30');
        CarbonImmutable::setTestNow(self::HOY.' 08:30');
        $this->seed(SeguridadSeeder::class);

        $this->especialidad = Especialidad::create(['nombre' => 'Medicina general', 'duracion_min' => 20, 'estado' => true]);
        $this->usuarioPerez = $this->usuario('cperez', Rol::MEDICO);
        $this->perez = $this->medico('MED-0001', 'Pérez', $this->usuarioPerez);
        $this->usuarioVaca = $this->usuario('avaca', Rol::MEDICO);
        $this->medico('MED-0002', 'Vaca', $this->usuarioVaca);

        $this->paciente = Paciente::create([
            'codigo' => 'PAC-000001', 'ci' => '6543210', 'nombres' => 'María', 'apellidos' => 'López',
            'fecha_nacimiento' => '1990-03-15', 'sexo' => 'F', 'telefono' => '70000000', 'estado' => true,
        ]);
        DB::table('antecedente')->insert([
            'id_paciente' => $this->paciente->id_paciente, 'tipo_sangre' => 'O+', 'alergias' => 'Penicilina',
        ]);

        $this->confirmada = $this->cita('CM-000001', '08:20', 'Confirmada');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function usuario(string $username, string $rol): User
    {
        return User::create([
            'id_rol' => Rol::where('nombre', $rol)->value('id_rol'),
            'username' => $username,
            'correo' => "{$username}@vidasalud.bo",
            'password_hash' => Hash::make('secreto123'),
            'debe_cambiar_clave' => false,
            'estado' => true,
        ]);
    }

    private function medico(string $codigo, string $apellido, User $usuario): Medico
    {
        $medico = Medico::create([
            'id_usuario' => $usuario->id_usuario, 'codigo' => $codigo, 'matricula' => "MP-{$codigo}",
            'nombres' => 'Dr.', 'apellidos' => $apellido, 'estado' => true,
        ]);
        $medico->especialidades()->sync([$this->especialidad->id_especialidad]);

        return $medico;
    }

    private function cita(string $codigo, string $hora, string $estado): Cita
    {
        $inicio = CarbonImmutable::parse(self::HOY.' '.$hora);
        $id = DB::table('cita')->insertGetId([
            'id_paciente' => $this->paciente->id_paciente,
            'id_medico' => $this->perez->id_medico,
            'id_especialidad' => $this->especialidad->id_especialidad,
            'creado_por' => $this->usuarioPerez->id_usuario,
            'codigo' => $codigo,
            'fecha' => self::HOY,
            'hora_inicio' => $inicio->format('H:i:s'),
            'hora_fin' => $inicio->addMinutes(20)->format('H:i:s'),
            'nro_cita' => 1,
            'canal' => 'MOSTRADOR',
            'motivo' => 'Dolor de cabeza',
            'estado' => $estado,
            'estado_pago' => 'Pagado',
        ], 'id_cita');

        return Cita::findOrFail($id);
    }

    /** @return array<string, string> */
    private function datos(array $cambios = []): array
    {
        return array_merge([
            'diagnostico' => 'Cefalea tensional',
            'tratamiento' => 'Paracetamol 500 mg cada 8 horas',
            'peso_kg' => '62,5',
            'talla_cm' => '160',
            'presion_arterial' => '120/80',
            'temperatura_c' => '36.8',
        ], $cambios);
    }

    public function test_el_medico_ve_el_panel_clinico_con_las_alergias_resaltadas(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->get(route('atencion.create', $this->confirmada))
            ->assertOk()
            ->assertSee('alert-danger', false)
            ->assertSee('Penicilina')
            ->assertSee('Actualizar antecedentes');
    }

    public function test_registra_la_atencion_y_la_cita_queda_atendida(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->post(route('atencion.store', $this->confirmada), $this->datos())
            ->assertRedirect(route('agenda.index', ['fecha' => self::HOY]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('atencion', [
            'id_cita' => $this->confirmada->id_cita,
            'diagnostico' => 'Cefalea tensional',
            'presion_arterial' => '120/80',
        ]);
        $this->assertEquals(62.5, (float) DB::table('atencion')->value('peso_kg'));
        $this->assertSame('Atendida', $this->confirmada->fresh()->estado);
        $this->assertDatabaseHas('historial_estado_cita', [
            'id_cita' => $this->confirmada->id_cita, 'estado_anterior' => 'Confirmada', 'estado_nuevo' => 'Atendida',
        ]);
        $this->assertDatabaseHas('bitacora', ['accion' => 'ATENCION_REGISTRADA', 'id_usuario' => $this->usuarioPerez->id_usuario]);
    }

    public function test_el_diagnostico_es_obligatorio_y_se_validan_los_signos_vitales(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->post(route('atencion.store', $this->confirmada), $this->datos([
                'diagnostico' => '',
                'peso_kg' => '500',
                'talla_cm' => '10',
                'temperatura_c' => '50',
                'presion_arterial' => '12-8',
            ]))
            ->assertSessionHasErrors(['diagnostico', 'peso_kg', 'talla_cm', 'temperatura_c', 'presion_arterial']);

        $this->assertDatabaseCount('atencion', 0);
        $this->assertSame('Confirmada', $this->confirmada->fresh()->estado);
    }

    public function test_los_signos_vitales_son_opcionales(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->post(route('atencion.store', $this->confirmada), [
                'diagnostico' => 'Resfrío común',
                'peso_kg' => '', 'talla_cm' => '', 'presion_arterial' => '', 'temperatura_c' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('atencion', ['id_cita' => $this->confirmada->id_cita, 'peso_kg' => null]);
    }

    public function test_solo_se_atiende_una_cita_confirmada(): void
    {
        $programada = $this->cita('CM-000002', '09:00', 'Programada');

        $this->actingAs($this->usuarioPerez)
            ->get(route('atencion.create', $programada))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($this->usuarioPerez)
            ->post(route('atencion.store', $programada), $this->datos())
            ->assertSessionHasErrors('cita');

        $this->assertSame('Programada', $programada->fresh()->estado);
    }

    public function test_una_cita_atendida_no_se_vuelve_a_atender(): void
    {
        $this->actingAs($this->usuarioPerez)->post(route('atencion.store', $this->confirmada), $this->datos());

        $this->actingAs($this->usuarioPerez)
            ->post(route('atencion.store', $this->confirmada), $this->datos(['diagnostico' => 'Otro diagnóstico']))
            ->assertSessionHasErrors('cita');

        $this->assertDatabaseCount('atencion', 1);
    }

    public function test_otro_medico_no_puede_atender_la_cita(): void
    {
        $this->actingAs($this->usuarioVaca)
            ->get(route('atencion.create', $this->confirmada))
            ->assertForbidden();

        $this->actingAs($this->usuarioVaca)
            ->post(route('atencion.store', $this->confirmada), $this->datos())
            ->assertForbidden();

        $this->assertDatabaseCount('atencion', 0);
    }

    public function test_la_recepcionista_no_tiene_permiso(): void
    {
        $this->actingAs($this->usuario('rmendoza', Rol::RECEPCIONISTA))
            ->get(route('atencion.create', $this->confirmada))
            ->assertForbidden();
    }

    public function test_la_agenda_muestra_el_boton_atender_en_las_confirmadas(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->get(route('agenda.index'))
            ->assertOk()
            ->assertSee(route('atencion.create', $this->confirmada), false);
    }

    public function test_actualizar_antecedentes_vuelve_a_la_pantalla_de_atencion(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->get(route('pacientes.antecedentes', ['paciente' => $this->paciente, 'volver_cita' => $this->confirmada->id_cita]))
            ->assertOk()
            ->assertSee('name="volver_cita"', false);

        $this->actingAs($this->usuarioPerez)
            ->put(route('pacientes.antecedentes.update', $this->paciente), [
                'tipo_sangre' => 'O+',
                'alergias' => 'Penicilina, sulfas',
                'volver_cita' => $this->confirmada->id_cita,
            ])
            ->assertRedirect(route('atencion.create', $this->confirmada));
    }
}

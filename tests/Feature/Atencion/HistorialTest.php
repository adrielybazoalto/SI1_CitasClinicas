<?php

namespace Tests\Feature\Atencion;

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
 * Paquete 5 · CU22 Consultar historial del paciente.
 * Hoy es miércoles 11/11/2026 10:00.
 */
class HistorialTest extends TestCase
{
    use RefreshDatabase;

    private Especialidad $especialidad;
    private Medico $perez;
    private Medico $vaca;
    private User $usuarioPerez;
    private User $usuarioMaria;
    private Paciente $maria;
    private Paciente $juan;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-11 10:00');
        CarbonImmutable::setTestNow('2026-11-11 10:00');
        $this->seed(SeguridadSeeder::class);

        $this->especialidad = Especialidad::create(['nombre' => 'Medicina general', 'duracion_min' => 20, 'estado' => true]);
        $this->usuarioPerez = $this->usuario('cperez', Rol::MEDICO);
        $this->perez = $this->medico('MED-0001', 'Pérez', $this->usuarioPerez);
        $this->vaca = $this->medico('MED-0002', 'Vaca', $this->usuario('avaca', Rol::MEDICO));

        $this->usuarioMaria = $this->usuario('p6543210', Rol::PACIENTE);
        $this->maria = $this->paciente('PAC-000001', '6543210', 'María', 'López', $this->usuarioMaria);
        $this->juan = $this->paciente('PAC-000002', '7123456', 'Juan', 'Flores', $this->usuario('p7123456', Rol::PACIENTE));

        $atendida = $this->cita('CM-000001', $this->maria, $this->perez, '2026-10-05', 'Atendida');
        DB::table('atencion')->insert(['id_cita' => $atendida, 'diagnostico' => 'Cefalea tensional', 'tratamiento' => 'Paracetamol 500 mg']);
        $this->cita('CM-000002', $this->maria, $this->vaca, '2026-10-20', 'Cancelada', 'Viaje imprevisto');
        $this->cita('CM-000003', $this->maria, $this->perez, '2026-10-27', 'Inasistencia');
        $this->cita('CM-000004', $this->maria, $this->perez, '2026-11-20', 'Programada');
        $this->cita('CM-000005', $this->juan, $this->perez, '2026-10-06', 'Atendida');
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

    private function paciente(string $codigo, string $ci, string $nombres, string $apellidos, User $usuario): Paciente
    {
        return Paciente::create([
            'id_usuario' => $usuario->id_usuario, 'codigo' => $codigo, 'ci' => $ci, 'nombres' => $nombres,
            'apellidos' => $apellidos, 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'F', 'telefono' => '70000000', 'estado' => true,
        ]);
    }

    private function cita(string $codigo, Paciente $paciente, Medico $medico, string $fecha, string $estado, ?string $motivoCancelacion = null): int
    {
        return DB::table('cita')->insertGetId([
            'id_paciente' => $paciente->id_paciente,
            'id_medico' => $medico->id_medico,
            'id_especialidad' => $this->especialidad->id_especialidad,
            'creado_por' => $this->usuarioPerez->id_usuario,
            'codigo' => $codigo,
            'fecha' => $fecha,
            'hora_inicio' => '08:00:00',
            'hora_fin' => '08:20:00',
            'nro_cita' => 1,
            'canal' => 'MOSTRADOR',
            'estado' => $estado,
            'estado_pago' => 'Pagado',
            'motivo_cancelacion' => $motivoCancelacion,
            'creado_en' => '2026-10-01 09:00:00',
        ], 'id_cita');
    }

    /** Prueba mínima 7 · Consultar historial de un paciente. */
    public function test_consultar_historial_de_un_paciente(): void
    {
        $this->actingAs($this->usuario('rmendoza', Rol::RECEPCIONISTA))
            ->get(route('historial.show', $this->maria))
            ->assertOk()
            // Orden cronológico descendente, incluidas canceladas e inasistencias.
            ->assertSeeInOrder(['CM-000004', 'CM-000003', 'CM-000002', 'CM-000001'])
            ->assertSee('Viaje imprevisto')
            ->assertDontSee('CM-000005');
    }

    public function test_busca_pacientes_por_ci_o_nombre(): void
    {
        $recepcion = $this->usuario('rmendoza', Rol::RECEPCIONISTA);

        $this->actingAs($recepcion)->get(route('historial.index', ['buscar' => '654321']))
            ->assertOk()->assertSee('López, María')->assertDontSee('Flores, Juan');

        $this->actingAs($recepcion)->get(route('historial.index', ['buscar' => 'flores']))
            ->assertOk()->assertSee('Flores, Juan')->assertDontSee('López, María');
    }

    public function test_filtra_por_rango_de_fechas_medico_y_estado(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->get(route('historial.show', ['paciente' => $this->maria, 'desde' => '2026-10-01', 'hasta' => '2026-10-31', 'medico' => $this->perez->id_medico]))
            ->assertOk()
            ->assertSee('CM-000001')->assertSee('CM-000003')
            ->assertDontSee('CM-000002')->assertDontSee('CM-000004');

        $this->actingAs($this->usuarioPerez)
            ->get(route('historial.show', ['paciente' => $this->maria, 'estado' => 'Inasistencia']))
            ->assertOk()
            ->assertSee('CM-000003')->assertDontSee('CM-000001');
    }

    public function test_rechaza_un_rango_de_fechas_invertido(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->get(route('historial.show', ['paciente' => $this->maria, 'desde' => '2026-10-31', 'hasta' => '2026-10-01']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_el_medico_ve_el_detalle_clinico_y_la_recepcionista_no(): void
    {
        $url = route('historial.show', $this->maria);

        $this->actingAs($this->usuarioPerez)->get($url)
            ->assertOk()->assertSee('Cefalea tensional')->assertSee('Paracetamol 500 mg');

        $this->actingAs($this->usuario('rmendoza', Rol::RECEPCIONISTA))->get($url)
            ->assertOk()->assertDontSee('Cefalea tensional')->assertSee('CM-000001');
    }

    public function test_el_paciente_ve_su_propio_historial_sin_detalle_clinico(): void
    {
        $this->actingAs($this->usuarioMaria)
            ->get(route('historial.index'))
            ->assertOk()
            ->assertSee('Mi historial')
            ->assertSee('CM-000001')
            ->assertDontSee('Cefalea tensional')
            ->assertDontSee('CM-000005');
    }

    public function test_el_paciente_no_puede_ver_el_historial_de_otro(): void
    {
        $this->actingAs($this->usuarioMaria)
            ->get(route('historial.show', $this->juan))
            ->assertForbidden();
    }
}

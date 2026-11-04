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
 * Paquete 5 · CU20 Consultar agenda médica.
 * Hoy es miércoles 11/11/2026 10:00.
 */
class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private const HOY = '2026-11-11';

    private Especialidad $especialidad;
    private Medico $perez;
    private Medico $vaca;
    private User $usuarioPerez;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::HOY.' 10:00');
        CarbonImmutable::setTestNow(self::HOY.' 10:00');
        $this->seed(SeguridadSeeder::class);

        $this->especialidad = Especialidad::create(['nombre' => 'Medicina general', 'duracion_min' => 20, 'estado' => true]);
        $this->usuarioPerez = $this->usuario('cperez', Rol::MEDICO);
        $this->perez = $this->medico('MED-0001', 'Pérez', $this->usuarioPerez);
        $this->vaca = $this->medico('MED-0002', 'Vaca', $this->usuario('avaca', Rol::MEDICO));

        $this->paciente = Paciente::create([
            'codigo' => 'PAC-000001', 'ci' => '6543210', 'nombres' => 'María', 'apellidos' => 'López',
            'fecha_nacimiento' => '1990-03-15', 'sexo' => 'F', 'telefono' => '70000000', 'estado' => true,
        ]);

        // Agenda del Dr. Pérez hoy (desordenada a propósito) y de otros días / médicos.
        $this->cita('CM-000002', $this->perez, self::HOY, '08:20', 'Confirmada', 'Fiebre alta');
        $this->cita('CM-000001', $this->perez, self::HOY, '08:00', 'Atendida', 'Dolor de cabeza');
        $this->cita('CM-000003', $this->perez, self::HOY, '08:40', 'Programada', 'Control general');
        $this->cita('CM-000004', $this->perez, '2026-11-12', '08:00', 'Programada', 'Otro día');
        $this->cita('CM-000005', $this->vaca, self::HOY, '08:00', 'Programada', 'Otro médico');
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

    private function cita(string $codigo, Medico $medico, string $fecha, string $hora, string $estado, string $motivo): void
    {
        $inicio = CarbonImmutable::parse("{$fecha} {$hora}");
        DB::table('cita')->insert([
            'id_paciente' => $this->paciente->id_paciente,
            'id_medico' => $medico->id_medico,
            'id_especialidad' => $this->especialidad->id_especialidad,
            'creado_por' => $this->usuarioPerez->id_usuario,
            'codigo' => $codigo,
            'fecha' => $fecha,
            'hora_inicio' => $inicio->format('H:i:s'),
            'hora_fin' => $inicio->addMinutes(20)->format('H:i:s'),
            'nro_cita' => 1,
            'canal' => 'MOSTRADOR',
            'motivo' => $motivo,
            'estado' => $estado,
            'estado_pago' => 'Pagado',
        ]);
    }

    private function admin(): User
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $admin->forceFill(['debe_cambiar_clave' => false])->save();

        return $admin;
    }

    /** Prueba mínima 8 · Buscar citas de un médico por fecha. */
    public function test_buscar_citas_de_un_medico_por_fecha(): void
    {
        $recepcion = $this->usuario('rmendoza', Rol::RECEPCIONISTA);

        $this->actingAs($recepcion)
            ->get(route('agenda.index', ['medico' => $this->perez->id_medico, 'fecha' => self::HOY]))
            ->assertOk()
            ->assertSeeInOrder(['CM-000001', 'CM-000002', 'CM-000003'])
            ->assertDontSee('CM-000004')
            ->assertDontSee('CM-000005');
    }

    public function test_sin_medico_elegido_pide_seleccionarlo(): void
    {
        $this->actingAs($this->admin())
            ->get(route('agenda.index'))
            ->assertOk()
            ->assertSee('Seleccione un médico para ver su agenda.');
    }

    public function test_el_medico_solo_ve_su_propia_agenda(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->get(route('agenda.index', ['medico' => $this->vaca->id_medico]))
            ->assertOk()
            ->assertSee('Mi agenda')
            ->assertSee('CM-000001')
            ->assertDontSee('CM-000005');
    }

    public function test_la_recepcionista_no_ve_el_motivo_y_el_administrador_si(): void
    {
        $url = route('agenda.index', ['medico' => $this->perez->id_medico, 'fecha' => self::HOY]);

        $this->actingAs($this->usuario('rmendoza', Rol::RECEPCIONISTA))->get($url)
            ->assertOk()->assertDontSee('Fiebre alta');

        $this->actingAs($this->admin())->get($url)
            ->assertOk()->assertSee('Fiebre alta');
    }

    public function test_filtra_por_estado(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->get(route('agenda.index', ['estado' => 'confirmadas']))
            ->assertOk()
            ->assertSee('CM-000002')
            ->assertDontSee('CM-000001')
            ->assertDontSee('CM-000003');
    }

    public function test_vista_semanal_cuenta_citas_por_dia(): void
    {
        $respuesta = $this->actingAs($this->usuarioPerez)
            ->get(route('agenda.index', ['vista' => 'semana']))
            ->assertOk();

        $semana = $respuesta->viewData('semana');
        $this->assertCount(7, $semana);
        $this->assertSame(3, $semana[self::HOY]['total']);
        $this->assertSame(1, $semana[self::HOY]['estados']['Confirmada']);
        $this->assertSame(1, $semana['2026-11-12']['total']);
        $this->assertSame(0, $semana['2026-11-09']['total']);
    }

    public function test_el_paciente_no_puede_ver_la_agenda(): void
    {
        $this->actingAs($this->usuario('p6543210', Rol::PACIENTE))
            ->get(route('agenda.index'))
            ->assertForbidden();
    }

    public function test_rechaza_filtros_invalidos(): void
    {
        $this->actingAs($this->usuarioPerez)
            ->get(route('agenda.index', ['fecha' => '11-11-2026']))
            ->assertSessionHasErrors('fecha');
    }
}

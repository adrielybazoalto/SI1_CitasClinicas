<?php

namespace Tests\Feature\Caja;

use App\Models\Caja;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Pago;
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
 * P6 · CU18 Gestionar caja, CU19 Cobrar en mostrador y reembolsos (RN26).
 * Hoy es miércoles 11/11/2026 08:00.
 */
class CajaCobroTest extends TestCase
{
    use RefreshDatabase;

    private User $recepcion;
    private Cita $cita;
    private ?array $base = null;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-11 08:00');
        CarbonImmutable::setTestNow('2026-11-11 08:00');
        $this->seed(SeguridadSeeder::class);

        $this->recepcion = $this->usuario('rmendoza', Rol::RECEPCIONISTA);
        $this->cita = $this->cita('CM-000001', '2026-11-13', 150);
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
            'id_rol' => Rol::where('nombre', $rol)->value('id_rol'), 'username' => $username,
            'correo' => "{$username}@vidasalud.bo", 'password_hash' => Hash::make('secreto123'),
            'debe_cambiar_clave' => false, 'estado' => true,
        ]);
    }

    private function cita(string $codigo, string $fecha, ?float $monto, string $hora = '09:00:00'): Cita
    {
        $this->base ??= (function () {
            $esp = Especialidad::create(['nombre' => 'Cardiología', 'duracion_min' => 30, 'estado' => true]);
            $med = Medico::create(['codigo' => 'MED-0001', 'matricula' => 'MP-1', 'nombres' => 'Luis', 'apellidos' => 'Gutiérrez', 'estado' => true]);
            $pac = Paciente::create(['codigo' => 'PAC-000001', 'ci' => '6543210', 'nombres' => 'María', 'apellidos' => 'López',
                'fecha_nacimiento' => '1990-01-01', 'sexo' => 'F', 'telefono' => '70000000', 'correo' => 'maria@example.com', 'estado' => true]);

            return [$esp, $med, $pac];
        })();
        [$esp, $med, $pac] = $this->base;

        $id = DB::table('cita')->insertGetId([
            'id_paciente' => $pac->id_paciente, 'id_medico' => $med->id_medico, 'id_especialidad' => $esp->id_especialidad,
            'creado_por' => $this->recepcion->id_usuario, 'codigo' => $codigo, 'fecha' => $fecha,
            'hora_inicio' => $hora, 'hora_fin' => date('H:i:s', strtotime($hora) + 1800), 'nro_cita' => 1,
            'canal' => 'MOSTRADOR', 'estado' => 'Programada', 'estado_pago' => 'Pendiente', 'monto' => $monto,
        ], 'id_cita');

        return Cita::findOrFail($id);
    }

    private function abrirCaja(float $inicial = 200): void
    {
        $this->actingAs($this->recepcion)->post(route('caja.abrir'), ['monto_inicial' => $inicial])->assertSessionHasNoErrors();
    }

    /* ---------------- CU18 ---------------- */

    public function test_abre_una_sola_caja_por_usuario(): void
    {
        $this->abrirCaja();
        $this->abrirCaja(500);

        $this->assertSame(1, Caja::where('estado', 'ABIERTA')->count());
        $this->assertSame('200.00', Caja::first()->monto_inicial);
        $this->assertDatabaseHas('bitacora', ['accion' => 'ABRIR_CAJA']);
    }

    public function test_sin_caja_abierta_cobrar_redirige_a_abrirla(): void
    {
        $this->actingAs($this->recepcion)->get(route('cobros.index'))
            ->assertRedirect(route('caja.index'))
            ->assertSessionHas('error');
    }

    public function test_cierre_cuadrado_y_cierre_con_diferencia(): void
    {
        $this->abrirCaja(200);
        $this->actingAs($this->recepcion)->post(route('cobros.store', $this->cita), ['metodo' => 'EFECTIVO', 'recibido' => 200]);

        // Faltan Bs 10 en efectivo: sin observación no se puede cerrar.
        $contado = ['EFECTIVO' => 340, 'TARJETA' => 0, 'TRANSFERENCIA' => 0];
        $this->actingAs($this->recepcion)->post(route('caja.cerrar'), ['contado' => $contado])
            ->assertSessionHasErrors('observacion');

        $this->actingAs($this->recepcion)->post(route('caja.cerrar'), ['contado' => $contado, 'observacion' => 'Cambio mal entregado'])
            ->assertSessionHasNoErrors();

        $caja = Caja::first();
        $this->assertSame('CERRADA', $caja->estado);
        $this->assertSame('350.00', $caja->monto_esperado);
        $this->assertSame('-10.00', $caja->diferencia);
        $this->assertStringContainsString('faltante', $caja->observacion);
        $this->assertDatabaseHas('bitacora', ['accion' => 'CERRAR_CAJA']);

        // Una caja cerrada ya no se modifica: no hay caja abierta que cerrar.
        $this->actingAs($this->recepcion)->post(route('caja.cerrar'), ['contado' => $contado])->assertNotFound();
        $this->actingAs($this->recepcion)->get(route('caja.arqueo', $caja))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    /* ---------------- CU19 ---------------- */

    public function test_cobro_en_efectivo_con_cambio_y_comprobante_correlativo(): void
    {
        $this->abrirCaja();
        $otra = $this->cita('CM-000002', '2026-11-13', 150, '10:00:00');

        $this->actingAs($this->recepcion)->post(route('cobros.store', $this->cita), ['metodo' => 'EFECTIVO', 'recibido' => 200])
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'Cambio a devolver: Bs 50,00'));
        $this->actingAs($this->recepcion)->post(route('cobros.store', $otra), ['metodo' => 'TARJETA']);

        $this->assertSame('Pagado', $this->cita->fresh()->estado_pago);
        $numeros = Pago::with('comprobante')->orderBy('id_pago')->get()->pluck('comprobante.numero')->all();
        $this->assertSame([1001, 1002], $numeros);
        $this->assertNotNull(Pago::first()->id_caja);
        $this->assertDatabaseHas('bitacora', ['accion' => 'COBRAR_CITA']);

        $pago = Pago::first();
        $this->actingAs($this->recepcion)->get(route('cobros.comprobante', $pago))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_valida_el_monto_recibido_y_no_cobra_dos_veces(): void
    {
        $this->abrirCaja();

        $this->actingAs($this->recepcion)->post(route('cobros.store', $this->cita), ['metodo' => 'EFECTIVO', 'recibido' => 100])
            ->assertSessionHasErrors('recibido');
        $this->assertSame(0, Pago::count());

        $this->actingAs($this->recepcion)->post(route('cobros.store', $this->cita), ['metodo' => 'TRANSFERENCIA']);
        $this->actingAs($this->recepcion)->post(route('cobros.store', $this->cita), ['metodo' => 'TRANSFERENCIA'])
            ->assertSessionHasErrors('cita');
        $this->assertSame(1, Pago::count());
    }

    public function test_cita_sin_tarifa_no_se_cobra(): void
    {
        $this->abrirCaja();
        $sinPrecio = $this->cita('CM-000003', '2026-11-13', null, '11:00:00');

        $this->actingAs($this->recepcion)->get(route('cobros.create', $sinPrecio))->assertRedirect(route('cobros.index'));
    }

    /* ---------------- Reembolso (subflujo de CU16, RN26) ---------------- */

    public function test_cancelar_con_24_horas_reembolsa_y_la_caja_lo_descuenta(): void
    {
        $this->abrirCaja(200);
        $this->actingAs($this->recepcion)->post(route('cobros.store', $this->cita), ['metodo' => 'EFECTIVO', 'recibido' => 150]);

        $this->actingAs($this->recepcion)->patch(route('citas.cancelar', $this->cita), ['motivo_cancelacion' => 'Viaje imprevisto'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Reembolsado', $this->cita->fresh()->estado_pago);
        $this->assertDatabaseHas('pago', ['id_cita' => $this->cita->id_cita, 'tipo' => 'REEMBOLSO', 'metodo' => 'EFECTIVO']);

        // Esperado en efectivo = 200 + 150 − 150.
        $this->actingAs($this->recepcion)->post(route('caja.cerrar'), ['contado' => ['EFECTIVO' => 200, 'TARJETA' => 0, 'TRANSFERENCIA' => 0]])
            ->assertSessionHasNoErrors();
        $this->assertSame('0.00', Caja::first()->diferencia);
        $this->assertSame(2, Pago::where('id_caja', Caja::first()->id_caja)->count());
    }

    public function test_cancelar_con_menos_de_24_horas_no_reembolsa(): void
    {
        $manana = $this->cita('CM-000004', '2026-11-12', 150, '07:00:00');
        $this->abrirCaja();
        $this->actingAs($this->recepcion)->post(route('cobros.store', $manana), ['metodo' => 'TARJETA']);

        $this->actingAs($this->recepcion)->patch(route('citas.cancelar', $manana), ['motivo_cancelacion' => 'No puede asistir']);

        $this->assertSame('Cancelada', $manana->fresh()->estado);
        $this->assertSame('Pagado', $manana->fresh()->estado_pago);
        $this->assertSame(0, Pago::where('tipo', 'REEMBOLSO')->count());
    }

    /* ---------------- CU17 en el ciclo 2 ---------------- */

    public function test_la_llegada_exige_el_pago(): void
    {
        $hoy = $this->cita('CM-000005', '2026-11-11', 150, '08:20:00');

        $this->actingAs($this->recepcion)->patch(route('citas.llegada', $hoy))->assertSessionHasErrors('cita');
        $this->assertSame('Programada', $hoy->fresh()->estado);

        $this->abrirCaja();
        $this->actingAs($this->recepcion)->post(route('cobros.store', $hoy), ['metodo' => 'TARJETA']);
        $this->actingAs($this->recepcion)->patch(route('citas.llegada', $hoy))->assertSessionHasNoErrors();
        $this->assertSame('Confirmada', $hoy->fresh()->estado);
    }

    public function test_el_medico_no_cobra(): void
    {
        $this->actingAs($this->usuario('cperez', Rol::MEDICO))->get(route('cobros.index'))->assertForbidden();
    }
}

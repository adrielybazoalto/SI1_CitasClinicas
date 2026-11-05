<?php

namespace Tests\Feature\Citas;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Pago;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use App\Services\CitaService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** P4 · CU14 Pagar cita en línea (PayPal Sandbox y QR) y RN20. Hoy: 11/11/2026 10:00. */
class PagoEnLineaTest extends TestCase
{
    use RefreshDatabase;

    private User $maria;
    private User $otro;
    private Cita $cita;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-11 10:00');
        CarbonImmutable::setTestNow('2026-11-11 10:00');
        $this->seed(SeguridadSeeder::class);
        Mail::fake();

        $this->maria = $this->usuario('p6543210');
        $this->otro = $this->usuario('p7123456');
        $paciente = Paciente::create(['id_usuario' => $this->maria->id_usuario, 'codigo' => 'PAC-000001', 'ci' => '6543210', 'nombres' => 'María',
            'apellidos' => 'López', 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'F', 'telefono' => '70000000', 'correo' => 'maria@example.com', 'estado' => true]);
        Paciente::create(['id_usuario' => $this->otro->id_usuario, 'codigo' => 'PAC-000002', 'ci' => '7123456', 'nombres' => 'Juan',
            'apellidos' => 'Flores', 'fecha_nacimiento' => '1990-01-01', 'sexo' => 'M', 'telefono' => '70000001', 'estado' => true]);
        $esp = Especialidad::create(['nombre' => 'Dermatología', 'duracion_min' => 20, 'estado' => true]);
        $med = Medico::create(['codigo' => 'MED-0001', 'matricula' => 'MP-1', 'nombres' => 'Daniela', 'apellidos' => 'Rivero', 'estado' => true]);

        $id = DB::table('cita')->insertGetId([
            'id_paciente' => $paciente->id_paciente, 'id_medico' => $med->id_medico, 'id_especialidad' => $esp->id_especialidad,
            'creado_por' => $this->maria->id_usuario, 'codigo' => 'CM-000001', 'fecha' => '2026-11-13', 'hora_inicio' => '08:00:00',
            'hora_fin' => '08:20:00', 'nro_cita' => 1, 'canal' => 'LINEA', 'estado' => 'Programada', 'estado_pago' => 'Pendiente',
            'monto' => 139.20, 'vence_pago_en' => '2026-11-11 10:15:00', 'creado_en' => '2026-11-11 10:00:00',
        ], 'id_cita');
        $this->cita = Cita::findOrFail($id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function usuario(string $username): User
    {
        return User::create(['id_rol' => Rol::where('nombre', Rol::PACIENTE)->value('id_rol'), 'username' => $username,
            'correo' => "{$username}@example.com", 'password_hash' => Hash::make('secreto123'), 'debe_cambiar_clave' => false, 'estado' => true]);
    }

    public function test_pantalla_de_pago_con_contador_y_qr(): void
    {
        $this->actingAs($this->maria)->get(route('pagos.show', $this->cita))
            ->assertOk()
            ->assertSee('CU14 — Pagar cita en línea')
            ->assertSee('id="contador"', false)
            ->assertSee('data-qr="VIDASALUD|CM-000001|BOB|139.20', false)
            ->assertSee('Simulación de sandbox');
    }

    public function test_pago_con_qr_marca_pagado_y_emite_comprobante(): void
    {
        $this->actingAs($this->maria)->post(route('pagos.qr', $this->cita))
            ->assertRedirect(route('pagos.index'))
            ->assertSessionHas('status');

        $this->assertSame('Pagado', $this->cita->fresh()->estado_pago);
        $pago = Pago::with('comprobante')->firstOrFail();
        $this->assertSame('QR', $pago->metodo);
        $this->assertStringStartsWith('QR-SIM-CM-000001', $pago->id_transaccion);
        $this->assertSame(1001, $pago->comprobante->numero);
        $this->assertDatabaseHas('bitacora', ['accion' => 'PAGO_EN_LINEA']);

        $this->actingAs($this->maria)->get(route('pagos.comprobante', $this->cita))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_paypal_crea_la_orden_y_captura_al_volver(): void
    {
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto']);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            '*/v2/checkout/orders' => Http::response(['id' => 'ORDEN-1', 'links' => [['rel' => 'approve', 'href' => 'https://sandbox.paypal.com/aprobar?token=ORDEN-1']]], 201),
            '*/v2/checkout/orders/ORDEN-1/capture' => Http::response(['status' => 'COMPLETED', 'purchase_units' => [[
                'custom_id' => (string) $this->cita->id_cita,
                'payments' => ['captures' => [['id' => 'CAP-99', 'status' => 'COMPLETED']]],
            ]]]),
        ]);

        $this->actingAs($this->maria)->post(route('pagos.paypal', $this->cita))
            ->assertRedirect('https://sandbox.paypal.com/aprobar?token=ORDEN-1');

        // Monto en USD con el tipo de cambio: 139,20 / 6,96 = 20,00.
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v2/checkout/orders') && $r['purchase_units'][0]['amount']['value'] === '20.00');

        $this->actingAs($this->maria)->get(route('pagos.paypal.retorno', ['cita' => $this->cita, 'token' => 'ORDEN-1']))
            ->assertRedirect(route('pagos.index'));

        $this->assertSame('Pagado', $this->cita->fresh()->estado_pago);
        $this->assertDatabaseHas('pago', ['metodo' => 'PAYPAL', 'id_transaccion' => 'CAP-99']);
    }

    public function test_paypal_no_cobra_si_la_orden_no_coincide(): void
    {
        config(['services.paypal.client_id' => 'cliente', 'services.paypal.secret' => 'secreto']);
        Http::fake();

        $this->actingAs($this->maria)->withSession(['paypal.'.$this->cita->id_cita => 'ORDEN-1'])
            ->get(route('pagos.paypal.retorno', ['cita' => $this->cita, 'token' => 'OTRA']))
            ->assertRedirect(route('pagos.show', $this->cita));

        $this->assertSame(0, Pago::count());
        Http::assertNothingSent();
    }

    public function test_no_se_paga_la_cita_de_otro_paciente(): void
    {
        $this->actingAs($this->otro)->get(route('pagos.show', $this->cita))->assertForbidden();
        $this->actingAs($this->otro)->post(route('pagos.qr', $this->cita))->assertForbidden();
    }

    public function test_vencida_no_se_puede_pagar_y_el_temporizador_la_cancela(): void
    {
        Carbon::setTestNow('2026-11-11 10:16');
        CarbonImmutable::setTestNow('2026-11-11 10:16');

        $this->actingAs($this->maria)->get(route('pagos.show', $this->cita))->assertRedirect(route('pagos.index'));
        $this->actingAs($this->maria)->post(route('pagos.qr', $this->cita))->assertStatus(422);

        $this->assertSame(1, app(CitaService::class)->cancelarPagosVencidos());
        $cita = $this->cita->fresh();
        $this->assertSame('Cancelada', $cita->estado);
        $this->assertSame('Pago vencido', $cita->motivo_cancelacion);
        $this->assertDatabaseHas('historial_estado_cita', ['id_cita' => $cita->id_cita, 'estado_nuevo' => 'Cancelada', 'id_usuario' => null]);
        $this->assertDatabaseHas('bitacora', ['accion' => 'CANCELACION_PAGO_VENCIDO']);
    }

    public function test_el_temporizador_no_toca_las_pagadas_ni_las_vigentes(): void
    {
        $this->assertSame(0, app(CitaService::class)->cancelarPagosVencidos());

        $this->actingAs($this->maria)->post(route('pagos.qr', $this->cita));
        Carbon::setTestNow('2026-11-11 10:30');
        CarbonImmutable::setTestNow('2026-11-11 10:30');

        $this->assertSame(0, app(CitaService::class)->cancelarPagosVencidos());
        $this->assertSame('Programada', $this->cita->fresh()->estado);
    }
}

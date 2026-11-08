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

/** P4 · CU14 Pagar cita en línea (tarjeta con Stripe en Test Mode y QR) y RN20. Hoy: 11/11/2026 10:00. */
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

    /* ---------------- Stripe (Test Mode) ---------------- */

    private function configurarStripe(): void
    {
        config(['services.stripe.key' => 'pk_test_clave', 'services.stripe.secret' => 'sk_test_secreto', 'services.stripe.moneda' => 'bob']);
    }

    /** Respuesta de Stripe al consultar el PaymentIntent pi_123 de la cita. */
    private function intencion(string $estado, ?int $idCita = null, int $monto = 13920): array
    {
        return ['id' => 'pi_123', 'status' => $estado, 'amount' => $monto, 'currency' => 'bob',
            'metadata' => ['id_cita' => (string) ($idCita ?? $this->cita->id_cita), 'codigo' => 'CM-000001']];
    }

    public function test_pantalla_muestra_el_formulario_de_stripe_elements(): void
    {
        $this->configurarStripe();

        $this->actingAs($this->maria)->get(route('pagos.show', $this->cita))
            ->assertOk()
            ->assertSee('https://js.stripe.com/v3/', false)
            ->assertSee('id="stripe-numero"', false)
            ->assertSee('id="stripe-vencimiento"', false)
            ->assertSee('id="stripe-cvc"', false)
            ->assertSee('id="submit-button"', false)
            ->assertSee('Pagar BOB 139,20')
            ->assertSee('pk_test_clave')
            ->assertDontSee('sk_test_secreto')
            ->assertSee('Modo de pruebas de Stripe')
            ->assertSee('BOB 139,20');
    }

    public function test_sin_claves_de_stripe_solo_se_ofrece_el_qr(): void
    {
        config(['services.stripe.key' => null, 'services.stripe.secret' => null]);

        $this->actingAs($this->maria)->get(route('pagos.show', $this->cita))
            ->assertOk()
            ->assertSee('El pago con tarjeta no está configurado')
            ->assertDontSee('js.stripe.com', false);
    }

    public function test_stripe_autoriza_captura_marca_pagado_y_emite_comprobante(): void
    {
        $this->configurarStripe();
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response(['id' => 'pi_123', 'client_secret' => 'pi_123_secret_abc']),
            'api.stripe.com/v1/payment_intents/pi_123' => Http::response($this->intencion('requires_capture')),
            'api.stripe.com/v1/payment_intents/pi_123/capture' => Http::response(['id' => 'pi_123', 'status' => 'succeeded']),
        ]);

        // 1) Se crea el PaymentIntent: en bolivianos, en centavos y con captura manual.
        $this->actingAs($this->maria)->postJson(route('pagos.stripe.intencion', $this->cita))
            ->assertOk()
            ->assertJson(['client_secret' => 'pi_123_secret_abc']);
        Http::assertSent(fn ($r) => $r->url() === 'https://api.stripe.com/v1/payment_intents'
            && $r['amount'] == 13920 && $r['currency'] === 'bob' && $r['capture_method'] === 'manual'
            && $r['automatic_payment_methods']['allow_redirects'] === 'never' && ! isset($r['payment_method_types'])
            && $r['metadata']['id_cita'] == $this->cita->id_cita
            && $r->hasHeader('Authorization', 'Bearer sk_test_secreto'));

        // 2) Stripe Elements autorizó la tarjeta en el navegador; 3) el servidor captura.
        $this->actingAs($this->maria)->post(route('pagos.stripe.confirmar', $this->cita), ['payment_intent' => 'pi_123'])
            ->assertRedirect(route('pagos.index'))
            ->assertSessionHas('status');

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/payment_intents/pi_123/capture'));
        $this->assertSame('Pagado', $this->cita->fresh()->estado_pago);
        $pago = Pago::with('comprobante')->firstOrFail();
        $this->assertSame('STRIPE', $pago->metodo);
        $this->assertSame('pi_123', $pago->id_transaccion);
        $this->assertNull($pago->id_caja);
        $this->assertSame(1001, $pago->comprobante->numero);
        $this->assertDatabaseHas('bitacora', ['accion' => 'PAGO_EN_LINEA']);

        $this->actingAs($this->maria)->get(route('pagos.comprobante', $this->cita))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_stripe_no_cobra_si_el_payment_intent_no_es_el_de_la_sesion(): void
    {
        $this->configurarStripe();
        Http::fake();

        $this->actingAs($this->maria)->withSession(['stripe.'.$this->cita->id_cita => 'pi_123'])
            ->post(route('pagos.stripe.confirmar', $this->cita), ['payment_intent' => 'pi_otro'])
            ->assertRedirect(route('pagos.show', $this->cita));

        $this->assertSame(0, Pago::count());
        Http::assertNothingSent();
    }

    public function test_stripe_no_captura_si_la_tarjeta_no_fue_autorizada(): void
    {
        $this->configurarStripe();
        Http::fake(['api.stripe.com/v1/payment_intents/pi_123' => Http::response($this->intencion('requires_payment_method'))]);

        $this->actingAs($this->maria)->withSession(['stripe.'.$this->cita->id_cita => 'pi_123'])
            ->post(route('pagos.stripe.confirmar', $this->cita), ['payment_intent' => 'pi_123'])
            ->assertRedirect(route('pagos.show', $this->cita))
            ->assertSessionHas('error');

        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/capture'));
        $this->assertSame('Pendiente', $this->cita->fresh()->estado_pago);
    }

    public function test_stripe_libera_la_autorizacion_si_la_reserva_vencio(): void
    {
        $this->configurarStripe();
        Http::fake([
            'api.stripe.com/v1/payment_intents/pi_123' => Http::response($this->intencion('requires_capture')),
            'api.stripe.com/v1/payment_intents/pi_123/cancel' => Http::response(['id' => 'pi_123', 'status' => 'canceled']),
        ]);
        Carbon::setTestNow('2026-11-11 10:16');
        CarbonImmutable::setTestNow('2026-11-11 10:16');

        $this->actingAs($this->maria)->withSession(['stripe.'.$this->cita->id_cita => 'pi_123'])
            ->post(route('pagos.stripe.confirmar', $this->cita), ['payment_intent' => 'pi_123'])
            ->assertRedirect(route('pagos.index'));

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/payment_intents/pi_123/cancel'));
        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/capture'));
        $this->assertSame(0, Pago::count());
    }

    public function test_stripe_no_acepta_un_payment_intent_de_otra_cita(): void
    {
        $this->configurarStripe();
        Http::fake(['api.stripe.com/v1/payment_intents/pi_123' => Http::response($this->intencion('requires_capture', 999))]);

        $this->actingAs($this->maria)->withSession(['stripe.'.$this->cita->id_cita => 'pi_123'])
            ->post(route('pagos.stripe.confirmar', $this->cita), ['payment_intent' => 'pi_123'])
            ->assertRedirect(route('pagos.show', $this->cita));

        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/capture'));
        $this->assertSame(0, Pago::count());
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

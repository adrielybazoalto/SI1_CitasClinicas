<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * P4 · CU14 · Cliente de la API REST v2 de PayPal (Orders).
 *
 * Flujo: crear la orden → el paciente la aprueba en PayPal → al volver a
 * /pagos/{cita}/paypal/retorno se CAPTURA la orden y solo entonces la cita
 * queda pagada. Credenciales en .env (PAYPAL_CLIENT_ID, PAYPAL_SECRET,
 * PAYPAL_MODE=sandbox). PayPal no acepta bolivianos: se cobra el
 * equivalente en USD (config clinica.paypal_tipo_cambio).
 */
class PayPalService
{
    public function configurado(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    public function montoUsd(Cita $cita): string
    {
        return number_format((float) $cita->monto / (float) config('clinica.paypal_tipo_cambio', 6.96), 2, '.', '');
    }

    /**
     * Crea la orden y devuelve su id y el enlace de aprobación.
     *
     * @return array{id: string, aprobar: string}
     * @throws RuntimeException si PayPal no responde como se espera
     */
    public function crearOrden(Cita $cita, string $urlRetorno, string $urlCancelar): array
    {
        $respuesta = $this->cliente()->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $cita->codigo,
                'custom_id' => (string) $cita->id_cita,
                'description' => "Clínica Vida Salud · cita {$cita->codigo}",
                'amount' => ['currency_code' => 'USD', 'value' => $this->montoUsd($cita)],
            ]],
            'application_context' => [
                'brand_name' => 'Clínica Vida Salud',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $urlRetorno,
                'cancel_url' => $urlCancelar,
            ],
        ]);

        $aprobar = collect($respuesta->json('links', []))->firstWhere('rel', 'approve')['href']
            ?? collect($respuesta->json('links', []))->firstWhere('rel', 'payer-action')['href']
            ?? null;

        if (! $respuesta->successful() || ! $respuesta->json('id') || ! $aprobar) {
            Log::warning('PayPal: no se pudo crear la orden', ['status' => $respuesta->status(), 'cita' => $cita->id_cita]);
            throw new RuntimeException('No se pudo iniciar el pago con PayPal.');
        }

        return ['id' => $respuesta->json('id'), 'aprobar' => $aprobar];
    }

    /**
     * Captura la orden aprobada. Devuelve el id de la captura si quedó
     * COMPLETED y corresponde a esta cita; null en cualquier otro caso.
     */
    public function capturar(string $idOrden, Cita $cita): ?string
    {
        try {
            $respuesta = $this->cliente()->post("/v2/checkout/orders/{$idOrden}/capture", (object) []);
        } catch (Throwable $e) {
            Log::warning('PayPal: error al capturar', ['orden' => $idOrden, 'error' => $e->getMessage()]);

            return null;
        }

        $unidad = $respuesta->json('purchase_units.0', []);
        $captura = $unidad['payments']['captures'][0] ?? null;
        $citaOrden = $unidad['custom_id'] ?? $captura['custom_id'] ?? null;

        if ($respuesta->json('status') !== 'COMPLETED' || ! $captura || ($captura['status'] ?? null) !== 'COMPLETED'
            || ($citaOrden !== null && (string) $citaOrden !== (string) $cita->id_cita)) {
            Log::warning('PayPal: captura no completada', ['orden' => $idOrden, 'status' => $respuesta->status()]);

            return null;
        }

        return $captura['id'];
    }

    private function cliente()
    {
        if (! $this->configurado()) {
            throw new RuntimeException('PayPal no está configurado (PAYPAL_CLIENT_ID y PAYPAL_SECRET).');
        }

        $base = config('services.paypal.mode') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $token = Http::asForm()->timeout(15)
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        return Http::baseUrl($base)->withToken($token)->acceptJson()->asJson()->timeout(20);
    }
}

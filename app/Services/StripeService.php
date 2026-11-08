<?php

namespace App\Services;

use App\Models\Cita;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * P4 · CU14 · Cliente de la API de Stripe (PaymentIntents).
 *
 * Flujo con autorización y captura manual (capture_method = manual):
 *  1. El servidor crea el PaymentIntent y entrega su client_secret.
 *  2. El paciente escribe la tarjeta en Stripe Elements y Stripe.js la
 *     autoriza (incluida la verificación 3-D Secure si el banco la pide).
 *  3. Al volver, el servidor revisa que la cita siga pendiente y vigente y
 *     solo entonces CAPTURA el cobro; si ya no se puede pagar, CANCELA la
 *     autorización y no se debita nada.
 * Credenciales en .env (STRIPE_KEY pública y STRIPE_SECRET). Los datos de
 * la tarjeta nunca pasan por este servidor: viajan directo a Stripe.
 */
class StripeService
{
    private const API = 'https://api.stripe.com/v1';

    public function configurado(): bool
    {
        return filled(config('services.stripe.key')) && filled(config('services.stripe.secret'));
    }

    /** ¿Claves de prueba (Test Mode)? */
    public function modoPrueba(): bool
    {
        return str_starts_with((string) config('services.stripe.key'), 'pk_test_');
    }

    public function moneda(): string
    {
        return mb_strtolower((string) config('services.stripe.moneda', 'bob'));
    }

    /** Monto a cobrar en la moneda de Stripe (bolivianos o su equivalente en USD). */
    public function monto(Cita $cita): float
    {
        $monto = (float) $cita->monto;

        return round($this->moneda() === 'usd' ? $monto / (float) config('clinica.tipo_cambio_usd', 6.96) : $monto, 2);
    }

    /** Stripe trabaja en la unidad mínima de la moneda (centavos). */
    public function centavos(Cita $cita): int
    {
        return (int) round($this->monto($cita) * 100);
    }

    /**
     * Crea el PaymentIntent de la cita (solo autoriza; se captura después).
     *
     * @return array{id: string, client_secret: string}
     * @throws RuntimeException si Stripe no responde como se espera
     */
    public function crearIntencion(Cita $cita): array
    {
        $respuesta = $this->cliente()->post('/payment_intents', [
            'amount' => $this->centavos($cita),
            'currency' => $this->moneda(),
            'capture_method' => 'manual',
            // La API ya no acepta payment_method_types: los métodos se habilitan en el
            // panel de Stripe. Sin redirecciones, porque la tarjeta se confirma en la misma página.
            'automatic_payment_methods' => ['enabled' => 'true', 'allow_redirects' => 'never'],
            'description' => "Clínica Vida Salud · cita {$cita->codigo}",
            'metadata' => ['id_cita' => (string) $cita->id_cita, 'codigo' => $cita->codigo],
        ]);

        if (! $respuesta->successful() || ! $respuesta->json('id') || ! $respuesta->json('client_secret')) {
            Log::warning('Stripe: no se pudo crear el PaymentIntent', ['status' => $respuesta->status(), 'cita' => $cita->id_cita, 'error' => $respuesta->json('error.message')]);
            throw new RuntimeException('No se pudo iniciar el pago con tarjeta.');
        }

        return ['id' => $respuesta->json('id'), 'client_secret' => $respuesta->json('client_secret')];
    }

    /**
     * ¿El PaymentIntent está autorizado, es de esta cita y por el monto correcto?
     */
    public function autorizado(string $idIntencion, Cita $cita): bool
    {
        $intencion = $this->obtener($idIntencion);

        return $intencion
            && ($intencion['status'] ?? null) === 'requires_capture'
            && (string) ($intencion['metadata']['id_cita'] ?? '') === (string) $cita->id_cita
            && (int) ($intencion['amount'] ?? 0) === $this->centavos($cita)
            && ($intencion['currency'] ?? null) === $this->moneda();
    }

    /**
     * Captura el cobro autorizado. Devuelve el id del PaymentIntent si quedó
     * succeeded; null en cualquier otro caso.
     */
    public function capturar(string $idIntencion): ?string
    {
        try {
            $respuesta = $this->cliente()->post("/payment_intents/{$idIntencion}/capture");
        } catch (Throwable $e) {
            Log::warning('Stripe: error al capturar', ['intencion' => $idIntencion, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $respuesta->successful() || $respuesta->json('status') !== 'succeeded') {
            Log::warning('Stripe: captura no completada', ['intencion' => $idIntencion, 'status' => $respuesta->status(), 'error' => $respuesta->json('error.message')]);

            return null;
        }

        return $respuesta->json('id');
    }

    /** Libera la autorización sin cobrar (la cita ya no se puede pagar). */
    public function cancelar(string $idIntencion): void
    {
        try {
            $this->cliente()->post("/payment_intents/{$idIntencion}/cancel");
        } catch (Throwable $e) {
            Log::warning('Stripe: no se pudo cancelar la autorización', ['intencion' => $idIntencion, 'error' => $e->getMessage()]);
        }
    }

    private function obtener(string $idIntencion): ?array
    {
        try {
            $respuesta = $this->cliente()->get("/payment_intents/{$idIntencion}");
        } catch (Throwable $e) {
            Log::warning('Stripe: error al consultar', ['intencion' => $idIntencion, 'error' => $e->getMessage()]);

            return null;
        }

        return $respuesta->successful() ? $respuesta->json() : null;
    }

    private function cliente(): PendingRequest
    {
        if (! $this->configurado()) {
            throw new RuntimeException('Stripe no está configurado (STRIPE_KEY y STRIPE_SECRET).');
        }

        return Http::baseUrl(self::API)->withToken(config('services.stripe.secret'))->asForm()->acceptJson()->timeout(20);
    }
}

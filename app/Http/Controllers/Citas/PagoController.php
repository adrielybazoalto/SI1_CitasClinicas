<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Pago;
use App\Services\CitaService;
use App\Services\PagoService;
use App\Services\StripeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

/**
 * CU14 · Pagar cita en línea (tarjeta con Stripe en modo de pruebas o QR simulado).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class PagoController extends Controller
{
    public function __construct(
        private PagoService $pagos,
        private StripeService $stripe,
        private CitaService $citas,
    ) {
    }

    /**
     * Citas del paciente pendientes de pago y pagos ya hechos.
     * El administrador entra en modo supervisión: ve las reservas en línea
     * de todos los pacientes, sin poder pagar por ellos.
     */
    public function index(Request $request): View
    {
        $paciente = $this->citas->pacienteDe($request->user());
        $supervision = ! $paciente && $this->supervisa($request);
        abort_unless($paciente || $supervision, 403, 'Su cuenta no está vinculada a un paciente.');

        $citas = Cita::query()
            ->with(['paciente:id_paciente,nombres,apellidos,ci', 'medico:id_medico,nombres,apellidos', 'especialidad:id_especialidad,nombre', 'pagos.comprobante'])
            ->when($paciente, fn ($q) => $q->where('id_paciente', $paciente->id_paciente), fn ($q) => $q->where('canal', 'LINEA'))
            ->where('monto', '>', 0)
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->get();

        return view('citas.pagos', ['citas' => $citas, 'supervision' => $supervision]);
    }

    /** Pantalla de pago: contador de la retención, tarjeta (Stripe) y QR. */
    public function show(Request $request, Cita $cita): View|RedirectResponse
    {
        $this->asegurarPropia($request, $cita);

        if (! $this->pagos->cobrable($cita) || $this->pagos->vencida($cita)) {
            return redirect()->route('pagos.index')->with('error', $this->pagos->vencida($cita)
                ? "El tiempo para pagar la cita {$cita->codigo} venció: la reserva se liberó."
                : "La cita {$cita->codigo} no tiene un pago pendiente.");
        }

        return view('citas.pagar', [
            'cita' => $cita->load(['medico', 'especialidad', 'paciente']),
            'stripeActivo' => $this->stripe->configurado(),
            'stripeKey' => config('services.stripe.key'),
            'stripePrueba' => $this->stripe->modoPrueba(),
            'stripeMoneda' => mb_strtoupper($this->stripe->moneda()),
            'stripeMonto' => $this->stripe->monto($cita),
            'qr' => sprintf('VIDASALUD|%s|BOB|%s|%s', $cita->codigo, number_format((float) $cita->monto, 2, '.', ''), $cita->id_cita),
        ]);
    }

    /**
     * Stripe · crea el PaymentIntent y devuelve el client_secret para que
     * Stripe Elements autorice la tarjeta en el navegador.
     */
    public function stripeIntencion(Request $request, Cita $cita): JsonResponse
    {
        $this->asegurarPagable($request, $cita);

        try {
            $intencion = $this->stripe->crearIntencion($cita);
        } catch (RuntimeException $e) {
            return response()->json(['mensaje' => 'No se pudo conectar con Stripe. Intente de nuevo o use el pago con QR.'], 502);
        }

        $request->session()->put("stripe.{$cita->id_cita}", $intencion['id']);

        return response()->json(['client_secret' => $intencion['client_secret']]);
    }

    /** Stripe · la tarjeta quedó autorizada: se captura el cobro y se confirma el pago. */
    public function stripeConfirmar(Request $request, Cita $cita): RedirectResponse
    {
        $this->asegurarPropia($request, $cita);
        $request->validate(['payment_intent' => ['required', 'string', 'max:80']]);
        $intencion = $request->session()->pull("stripe.{$cita->id_cita}");

        // El PaymentIntent debe ser el que se creó para esta cita en esta sesión.
        if (! $intencion || $request->input('payment_intent') !== $intencion) {
            return redirect()->route('pagos.show', $cita)->with('error', 'No se reconoce la operación de Stripe. Intente de nuevo.');
        }

        if (! $this->stripe->autorizado($intencion, $cita)) {
            return redirect()->route('pagos.show', $cita)->with('error', 'Stripe no autorizó el pago con la tarjeta. No se realizó ningún cobro.');
        }

        // Antes de capturar se revisa que la cita siga pendiente y vigente: si no, se libera la autorización.
        if (! $this->pagos->cobrable($cita->fresh()) || $this->pagos->vencida($cita->fresh())) {
            $this->stripe->cancelar($intencion);

            return redirect()->route('pagos.index')->with('error', 'La reserva ya no está pendiente de pago; no se realizó ningún cobro.');
        }

        $idCobro = $this->stripe->capturar($intencion);
        if (! $idCobro) {
            return redirect()->route('pagos.show', $cita)->with('error', 'Stripe no confirmó el pago. No se realizó ningún cobro.');
        }

        return $this->confirmar($cita, 'STRIPE', $idCobro, $request);
    }

    /** QR · simulación de sandbox: "verificar" equivale a la confirmación del banco. */
    public function qr(Request $request, Cita $cita): RedirectResponse
    {
        $this->asegurarPagable($request, $cita);

        return $this->confirmar($cita, 'QR', 'QR-SIM-'.$cita->codigo.'-'.Str::upper(Str::random(6)), $request);
    }

    /** Comprobante en PDF del pago del propio paciente. */
    public function comprobante(Request $request, Cita $cita): Response
    {
        abort_unless($this->supervisa($request) || $this->citas->pacienteDe($request->user())?->id_paciente === $cita->id_paciente, 403);
        $pago = Pago::query()->where('id_cita', $cita->id_cita)->where('tipo', 'COBRO')->has('comprobante')->latest('fecha_hora')->firstOrFail();

        return Pdf::loadView('pdf.comprobante', ['pago' => $pago->load(['comprobante', 'cita.paciente', 'cita.medico', 'cita.especialidad', 'registrador'])])
            ->setPaper('a5', 'portrait')->download("comprobante-{$pago->comprobante->numero}.pdf");
    }

    private function confirmar(Cita $cita, string $metodo, string $idTransaccion, Request $request): RedirectResponse
    {
        $pago = $this->pagos->pagarEnLinea($cita, $metodo, $idTransaccion, $request->user());
        $enviado = $this->pagos->enviarComprobante($pago);

        return redirect()->route('pagos.index')->with('status', sprintf(
            'Pago confirmado. Comprobante N.º %d%s.', $pago->comprobante->numero, $enviado ? ' enviado a su correo' : '',
        ));
    }

    /** El administrador supervisa los pagos en línea (solo lectura). */
    private function supervisa(Request $request): bool
    {
        return (bool) $request->user()->rol?->esAdministrador();
    }

    private function asegurarPropia(Request $request, Cita $cita): void
    {
        abort_unless($this->citas->pacienteDe($request->user())?->id_paciente === $cita->id_paciente, 403, 'Solo puede pagar sus propias citas.');
    }

    private function asegurarPagable(Request $request, Cita $cita): void
    {
        $this->asegurarPropia($request, $cita);
        abort_if(! $this->pagos->cobrable($cita) || $this->pagos->vencida($cita), 422, 'La cita no está pendiente de pago o el tiempo venció.');
    }
}

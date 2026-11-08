<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Pago;
use App\Services\CitaService;
use App\Services\PagoService;
use App\Services\PayPalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

/**
 * CU14 · Pagar cita en línea (PayPal Sandbox o QR simulado).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class PagoController extends Controller
{
    public function __construct(
        private PagoService $pagos,
        private PayPalService $paypal,
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

    /** Pantalla de pago: contador de la retención, PayPal y QR. */
    public function show(Request $request, Cita $cita): View|RedirectResponse
    {
        $this->asegurarPropia($request, $cita);

        if (! $this->pagos->cobrable($cita) || $this->pagos->vencida($cita)) {
            return redirect()->route('pagos.index')->with('error', $this->pagos->vencida($cita)
                ? "El tiempo para pagar la cita {$cita->codigo} venció: la reserva se liberó."
                : "La cita {$cita->codigo} no tiene un pago pendiente.");
        }

        return view('citas.pagar', [
            'cita' => $cita->load(['medico', 'especialidad']),
            'paypalActivo' => $this->paypal->configurado(),
            'montoUsd' => $this->paypal->montoUsd($cita),
            'qr' => sprintf('VIDASALUD|%s|BOB|%s|%s', $cita->codigo, number_format((float) $cita->monto, 2, '.', ''), $cita->id_cita),
        ]);
    }

    /** PayPal · crea la orden y envía al paciente a aprobarla. */
    public function paypal(Request $request, Cita $cita): RedirectResponse
    {
        $this->asegurarPagable($request, $cita);

        try {
            $orden = $this->paypal->crearOrden($cita, route('pagos.paypal.retorno', $cita), route('pagos.paypal.cancelado', $cita));
        } catch (RuntimeException $e) {
            return back()->with('error', 'No se pudo conectar con PayPal. Intente de nuevo o use el pago con QR.');
        }

        $request->session()->put("paypal.{$cita->id_cita}", $orden['id']);

        return redirect()->away($orden['aprobar']);
    }

    /** PayPal · el paciente aprobó: se captura la orden y se confirma el pago. */
    public function paypalRetorno(Request $request, Cita $cita): RedirectResponse
    {
        $this->asegurarPropia($request, $cita);
        $orden = $request->session()->pull("paypal.{$cita->id_cita}");

        // La orden debe ser la que se creó para esta cita en esta sesión.
        if (! $orden || $request->query('token') !== $orden) {
            return redirect()->route('pagos.show', $cita)->with('error', 'No se reconoce la orden de PayPal. Intente de nuevo.');
        }

        // Antes de capturar se revisa que la cita siga pendiente y vigente: así no se cobra una reserva ya liberada.
        if (! $this->pagos->cobrable($cita->fresh()) || $this->pagos->vencida($cita->fresh())) {
            return redirect()->route('pagos.index')->with('error', 'La reserva ya no está pendiente de pago; no se realizó ningún cobro.');
        }

        $idCaptura = $this->paypal->capturar($orden, $cita);
        if (! $idCaptura) {
            return redirect()->route('pagos.show', $cita)->with('error', 'PayPal no confirmó el pago. No se realizó ningún cobro.');
        }

        return $this->confirmar($cita, 'PAYPAL', $idCaptura, $request);
    }

    public function paypalCancelado(Request $request, Cita $cita): RedirectResponse
    {
        $request->session()->forget("paypal.{$cita->id_cita}");

        return redirect()->route('pagos.show', $cita)->with('error', 'Canceló el pago en PayPal. Puede intentarlo de nuevo mientras no venza el tiempo.');
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

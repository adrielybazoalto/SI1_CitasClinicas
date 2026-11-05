<?php

namespace App\Http\Controllers\Caja;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Pago;
use App\Services\CajaService;
use App\Services\PagoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU19 · Cobrar cita en mostrador y emitir comprobante.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class CobroController extends Controller
{
    public function __construct(
        private CajaService $caja,
        private PagoService $pagos,
    ) {
    }

    /** Citas pendientes de cobro. Sin caja abierta, primero hay que abrirla. */
    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->caja->abierta($request->user())) {
            return $this->pedirCaja($request);
        }

        return view('caja.cobros', [
            'citas' => $this->caja->pendientesDeCobro($request->query('buscar')),
            'buscar' => $request->query('buscar'),
        ]);
    }

    public function create(Request $request, Cita $cita): View|RedirectResponse
    {
        if (! $this->caja->abierta($request->user())) {
            return $this->pedirCaja($request);
        }

        if (! $this->pagos->cobrable($cita)) {
            return redirect()->route('cobros.index')->with('error', "La cita {$cita->codigo} no se puede cobrar (estado {$cita->estado}, pago {$cita->estado_pago}).");
        }

        return view('caja.cobrar', ['cita' => $cita->load(['paciente', 'medico', 'especialidad'])]);
    }

    public function store(Request $request, Cita $cita): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', Rule::in(PagoService::METODOS_MOSTRADOR)],
            'recibido' => ['nullable', 'required_if:metodo,EFECTIVO', 'numeric', 'min:0'],
        ], [
            'metodo.required' => 'Elija el método de pago.',
            'recibido.required_if' => 'En efectivo, indique el monto recibido.',
        ]);

        $resultado = $this->caja->cobrar($cita, $datos['metodo'], isset($datos['recibido']) ? (float) $datos['recibido'] : null, $request->user());
        $pago = $resultado['pago'];

        return redirect()->route('cobros.comprobante.ver', $pago)->with('status', sprintf(
            'Cobro registrado. Comprobante N.º %d.%s',
            $pago->comprobante->numero,
            $resultado['cambio'] > 0 ? ' Cambio a devolver: Bs '.number_format($resultado['cambio'], 2, ',', '.').'.' : '',
        ));
    }

    /** Pantalla del comprobante con opciones de imprimir (PDF) y enviar por correo. */
    public function verComprobante(Pago $pago): View
    {
        return view('caja.comprobante', ['pago' => $pago->load(['comprobante', 'cita.paciente', 'cita.medico', 'cita.especialidad', 'registrador'])]);
    }

    public function comprobante(Pago $pago): Response
    {
        return $this->pdf($pago)->download("comprobante-{$pago->comprobante?->numero}.pdf");
    }

    /** Envía el comprobante en PDF al correo del paciente. */
    public function enviar(Pago $pago): RedirectResponse
    {
        $correo = $pago->cita?->paciente?->correo;

        if (! $correo) {
            return back()->with('error', 'El paciente no tiene correo registrado.');
        }

        return $this->pagos->enviarComprobante($pago)
            ? back()->with('status', "Comprobante enviado a {$correo}.")
            : back()->with('error', 'No se pudo enviar el correo. Intente más tarde.');
    }

    private function pdf(Pago $pago)
    {
        abort_unless($pago->comprobante()->exists(), 404);

        return Pdf::loadView('pdf.comprobante', [
            'pago' => $pago->load(['comprobante', 'cita.paciente', 'cita.medico', 'cita.especialidad', 'registrador']),
        ])->setPaper('a5', 'portrait');
    }

    private function pedirCaja(Request $request): RedirectResponse
    {
        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('caja.index')->with('error', 'Para cobrar, primero abra su caja.');
    }
}

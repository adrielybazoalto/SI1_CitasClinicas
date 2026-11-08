<?php

namespace App\Http\Controllers\Caja;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Services\CajaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CU18 · Gestionar caja (apertura, cierre y arqueo).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class CajaController extends Controller
{
    public function __construct(private CajaService $servicio)
    {
    }

    /** Caja abierta del usuario (con su resumen) o el formulario de apertura. */
    public function index(Request $request): View
    {
        $caja = $this->servicio->abierta($request->user());

        return view('caja.index', [
            'caja' => $caja,
            'resumen' => $caja ? $this->servicio->resumen($caja) : null,
            'historial' => $this->servicio->historial($request->user()),
        ]);
    }

    public function abrir(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            ['monto_inicial' => ['required', 'numeric', 'min:0', 'max:999999.99']],
            ['monto_inicial.required' => 'Indique el monto inicial (puede ser 0).', 'monto_inicial.min' => 'El monto inicial no puede ser negativo.'],
        );

        $this->servicio->abrir($request->user(), (float) $datos['monto_inicial']);

        return redirect()->intended(route('caja.index'))->with('status', 'Caja abierta.');
    }

    public function cerrar(Request $request): RedirectResponse
    {
        $caja = $this->servicio->abierta($request->user());
        abort_unless($caja, 404, 'No tiene una caja abierta.');

        // Un conteo por cada método del arqueo (Efectivo, Tarjeta y QR, más algún histórico si la caja lo tiene).
        $reglas = ['observacion' => ['nullable', 'string', 'max:300']];
        foreach (array_keys($this->servicio->resumen($caja)['metodos']) as $metodo) {
            $reglas["contado.{$metodo}"] = ['required', 'numeric', 'min:0'];
        }

        $datos = $request->validate($reglas, [
            'contado.*.required' => 'Ingrese el monto contado de cada método (0 si no hubo).',
            'contado.*.numeric' => 'Los montos contados deben ser números.',
            'contado.*.min' => 'Los montos contados no pueden ser negativos.',
        ]);

        $caja = $this->servicio->cerrar($caja, $datos['contado'], $datos['observacion'] ?? null, $request->user());

        return redirect()->route('caja.index')->with('status', sprintf(
            'Caja cerrada. Diferencia: Bs %s.', number_format((float) $caja->diferencia, 2, ',', '.'),
        ))->with('arqueo', $caja->id_caja);
    }

    /** Reporte de arqueo en PDF (la dueña de la caja o el administrador). */
    public function arqueo(Request $request, Caja $caja): Response
    {
        $usuario = $request->user();
        abort_unless((int) $caja->id_usuario === (int) $usuario->id_usuario || $usuario->tienePermiso('bitacora.ver'), 403);

        return Pdf::loadView('pdf.arqueo', [
            'caja' => $caja->load('usuario'),
            'resumen' => $this->servicio->resumen($caja),
            'emitido' => now(),
        ])->download("arqueo-caja-{$caja->id_caja}.pdf");
    }
}

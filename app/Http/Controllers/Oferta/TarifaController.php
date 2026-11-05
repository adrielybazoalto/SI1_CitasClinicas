<?php

namespace App\Http\Controllers\Oferta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oferta\TarifaRequest;
use App\Models\Especialidad;
use App\Services\TarifaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CU11 · Gestionar tarifario.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class TarifaController extends Controller
{
    public function __construct(private TarifaService $servicio)
    {
    }

    /** Especialidades con su precio vigente y el formulario de nuevo precio. */
    public function index(): View
    {
        return view('oferta.tarifas', ['filas' => $this->servicio->listar()]);
    }

    /** Historial de precios de una especialidad. */
    public function show(Especialidad $especialidad): View
    {
        return view('oferta.tarifa-historial', [
            'especialidad' => $especialidad,
            'tarifas' => $this->servicio->historial($especialidad),
        ]);
    }

    /** Registra el precio nuevo y cierra el anterior. */
    public function store(TarifaRequest $request): RedirectResponse
    {
        $tarifa = $this->servicio->registrar($request->validated(), $request->user());
        $tarifa->load('especialidad');

        return redirect()->route('tarifas.index')->with('status', sprintf(
            'Precio de %s actualizado a Bs %s desde el %s.',
            $tarifa->especialidad->nombre,
            number_format((float) $tarifa->monto, 2, ',', '.'),
            $tarifa->vigente_desde->format('d/m/Y'),
        ));
    }
}

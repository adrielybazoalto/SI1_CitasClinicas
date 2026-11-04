<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\RegistrarInasistenciaRequest;
use App\Models\Cita;
use App\Services\CitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CU17 · Registrar asistencia e inasistencia.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class AsistenciaController extends Controller
{
    public function __construct(private CitaService $servicio)
    {
    }

    /** Programada → Confirmada al llegar a recepción. */
    public function llegada(Request $request, Cita $cita): RedirectResponse
    {
        $cita = $this->servicio->registrarLlegada($cita, $request->user());

        return back()->with('status', "Llegada registrada: la cita {$cita->codigo} quedó Confirmada.");
    }

    /** Programada / Confirmada → Inasistencia. */
    public function inasistencia(RegistrarInasistenciaRequest $request, Cita $cita): RedirectResponse
    {
        $cita = $this->servicio->registrarInasistencia($cita, $request->validated('detalle'), $request->user());

        return back()->with('status', "Inasistencia registrada para la cita {$cita->codigo}.");
    }
}

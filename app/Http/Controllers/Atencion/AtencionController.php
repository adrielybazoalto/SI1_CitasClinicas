<?php

namespace App\Http\Controllers\Atencion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Atencion\RegistrarAtencionRequest;
use App\Models\Cita;
use App\Services\AtencionService;
use App\Services\CitaEstadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU21 · Registrar atención médica.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class AtencionController extends Controller
{
    public function __construct(private AtencionService $servicio)
    {
    }

    /** Pantalla de atención: panel clínico del paciente + formulario. */
    public function create(Request $request, Cita $cita): View|RedirectResponse
    {
        abort_unless($this->servicio->puedeAtender($cita, $request->user()), 403, 'Solo el médico de la cita puede atenderla.');

        if ($cita->estado !== CitaEstadoService::CONFIRMADA) {
            return redirect()->route('agenda.index', ['fecha' => $cita->fecha->toDateString()])
                ->with('error', "La cita {$cita->codigo} está {$cita->estado}: solo se atienden citas Confirmadas.");
        }

        return view('atencion.atender', array_merge(['cita' => $cita], $this->servicio->contextoClinico($cita)));
    }

    /** Guarda la atención y marca la cita como Atendida. */
    public function store(RegistrarAtencionRequest $request, Cita $cita): RedirectResponse
    {
        abort_unless($this->servicio->puedeAtender($cita, $request->user()), 403, 'Solo el médico de la cita puede atenderla.');

        $this->servicio->registrar($cita, $request->validated(), $request->user());

        return redirect()->route('agenda.index', ['fecha' => $cita->fecha->toDateString()])
            ->with('status', "Atención de la cita {$cita->codigo} registrada. La cita quedó Atendida.");
    }
}

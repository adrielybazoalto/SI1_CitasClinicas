<?php

namespace App\Http\Controllers\Atencion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Atencion\RegistrarAtencionRequest;
use App\Models\Atencion;
use App\Models\Cita;
use App\Services\AtencionService;
use App\Services\CitaEstadoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        // Cita ya atendida: se muestra la atención registrada con el botón de receta.
        if ($cita->estado === CitaEstadoService::ATENDIDA && $cita->atencion) {
            return view('atencion.atender', array_merge(
                ['cita' => $cita, 'atencion' => $cita->atencion],
                $this->servicio->contextoClinico($cita),
            ));
        }

        if ($cita->estado !== CitaEstadoService::CONFIRMADA) {
            return redirect()->route('agenda.index', ['fecha' => $cita->fecha->toDateString(), 'accion' => 'atender'])
                ->with('error', "La cita {$cita->codigo} está {$cita->estado}: solo se atienden citas Confirmadas.");
        }

        return view('atencion.atender', array_merge(['cita' => $cita], $this->servicio->contextoClinico($cita)));
    }

    /** Guarda la atención, marca la cita como Atendida y vuelve a CU21 para imprimir la receta. */
    public function store(RegistrarAtencionRequest $request, Cita $cita): RedirectResponse
    {
        abort_unless($this->servicio->puedeAtender($cita, $request->user()), 403, 'Solo el médico de la cita puede atenderla.');

        $this->servicio->registrar($cita, $request->validated(), $request->user());

        return redirect()->route('atencion.create', $cita)
            ->with('status', "Atención de la cita {$cita->codigo} registrada. La cita quedó Atendida.");
    }

    /** Receta médica en PDF (A5) de una atención ya registrada. */
    public function receta(Request $request, Atencion $atencion): Response
    {
        $atencion->load(['cita.paciente', 'cita.medico', 'cita.especialidad']);

        abort_unless($this->servicio->puedeAtender($atencion->cita, $request->user()), 403, 'Solo el médico de la cita puede imprimir su receta.');

        return Pdf::loadView('pdf.receta', ['atencion' => $atencion, 'cita' => $atencion->cita, 'emitido' => now()])
            ->setPaper('a5')
            ->stream("receta-{$atencion->cita->codigo}.pdf");
    }
}

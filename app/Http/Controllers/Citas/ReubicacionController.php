<?php

namespace App\Http\Controllers\Citas;

use App\Exceptions\FichaNoDisponibleException;
use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Indisponibilidad;
use App\Services\ReubicacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU10 (parte de Citas) · Reubicar o cancelar las citas afectadas por una
 * indisponibilidad médica.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class ReubicacionController extends Controller
{
    public function __construct(private ReubicacionService $servicio)
    {
    }

    public function index(Indisponibilidad $indisponibilidad): View
    {
        $afectadas = $this->servicio->afectadas($indisponibilidad);

        return view('oferta.indisponibilidad-citas', [
            'periodo' => $indisponibilidad->load(['medico', 'registrador']),
            'afectadas' => $afectadas,
            'opciones' => $afectadas->where('estado', 'Programada')
                ->mapWithKeys(fn (Cita $c) => [$c->id_cita => $this->servicio->opciones($c)]),
        ]);
    }

    public function reubicar(Request $request, Indisponibilidad $indisponibilidad, Cita $cita): RedirectResponse
    {
        $destino = $request->validate([
            'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'id_horario' => ['required', 'integer', 'exists:horario,id_horario'],
            'nro_cita' => ['required', 'integer', 'min:1'],
        ]);

        $this->asegurarAfectada($indisponibilidad, $cita);

        try {
            $cita = $this->servicio->reubicar($cita, $destino, $indisponibilidad, $request->user());
        } catch (FichaNoDisponibleException $e) {
            return back()->with('error', 'Ese horario acaba de ocuparse. Elija otra opción.');
        }

        return back()->with('status', "Cita {$cita->codigo} reubicada al {$cita->fecha->format('d/m/Y')} a las {$cita->hora_inicio_corta} con {$cita->medico->nombre_completo}. Se avisó al paciente.");
    }

    public function cancelar(Request $request, Indisponibilidad $indisponibilidad, Cita $cita): RedirectResponse
    {
        $this->asegurarAfectada($indisponibilidad, $cita);

        $cita = $this->servicio->cancelar($cita, $indisponibilidad, $request->user());

        return back()->with('status', "Cita {$cita->codigo} cancelada".($cita->estado_pago === 'Reembolsado' ? ' con reembolso total' : '').'. Se avisó al paciente.');
    }

    /** Solo se tocan citas que realmente caen en el periodo. */
    private function asegurarAfectada(Indisponibilidad $periodo, Cita $cita): void
    {
        abort_unless($this->servicio->afectadas($periodo)->contains('id_cita', $cita->id_cita), 404, 'La cita no está afectada por este periodo.');
    }
}

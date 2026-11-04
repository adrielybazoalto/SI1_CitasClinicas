<?php

namespace App\Http\Controllers\Atencion;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Services\AtencionService;
use App\Services\CitaEstadoService;
use App\Services\PacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU22 · Consultar historial del paciente.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class HistorialController extends Controller
{
    public function __construct(
        private AtencionService $servicio,
        private PacienteService $pacientes,
    ) {
    }

    /**
     * Búsqueda de paciente por CI o nombre (mismo buscador de CU05).
     * El paciente va directo a su propio historial.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $usuario = $request->user();

        if ($this->servicio->esPaciente($usuario)) {
            $propio = $this->servicio->pacienteDe($usuario);
            abort_unless($propio, 403, 'Su cuenta no está vinculada a un paciente.');

            return $this->show($request, $propio);
        }

        $buscar = trim((string) $request->query('buscar'));

        return view('atencion.historial-buscar', [
            'buscar' => $buscar,
            'pacientes' => mb_strlen($buscar) >= 2 ? $this->pacientes->listar($buscar, null) : null,
        ]);
    }

    /** Historial de un paciente con filtros. */
    public function show(Request $request, Paciente $paciente): View
    {
        $usuario = $request->user();
        $esPaciente = $this->servicio->esPaciente($usuario);

        if ($esPaciente) {
            abort_unless($this->servicio->pacienteDe($usuario)?->id_paciente === $paciente->id_paciente, 403, 'Solo puede ver su propio historial.');
        }

        $filtros = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'medico' => ['nullable', 'integer'],
            'estado' => ['nullable', 'in:'.implode(',', array_keys(CitaEstadoService::TRANSICIONES))],
        ], [
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'desde.date_format' => 'La fecha inicial no es válida.',
            'hasta.date_format' => 'La fecha final no es válida.',
        ]);

        $conDetalle = $this->servicio->puedeVerDetalleClinico($usuario);

        return view('atencion.historial', [
            'paciente' => $paciente,
            'esPaciente' => $esPaciente,
            'conDetalle' => $conDetalle,
            'filtros' => $filtros + ['desde' => null, 'hasta' => null, 'medico' => null, 'estado' => null],
            'citas' => $this->servicio->historial($paciente, $filtros, $conDetalle),
            'medicos' => $this->servicio->medicosDelPaciente($paciente),
            'estados' => array_keys(CitaEstadoService::TRANSICIONES),
        ]);
    }
}

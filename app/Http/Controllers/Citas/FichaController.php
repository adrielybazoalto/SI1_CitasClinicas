<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\ConsultarFichasRequest;
use App\Models\Cita;
use App\Models\Paciente;
use App\Services\CitaService;
use App\Services\DisponibilidadService;
use App\Services\PacienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU12 · Consultar fichas disponibles (responde JSON para fichas.js).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class FichaController extends Controller
{
    public function __construct(
        private DisponibilidadService $disponibilidad,
        private CitaService $citas,
    ) {
    }

    /** Pantalla especialidad → médico → fecha → fichas (y reserva, CU13). */
    public function index(Request $request): View
    {
        $usuario = $request->user();

        return view('citas.reservar', array_merge($this->citas->datosReserva(), [
            'cita' => null,
            'canal' => $this->citas->canalDe($usuario),
            'pacienteActual' => $this->citas->pacienteDe($usuario),
            // CU12 (?accion=consultar): solo consulta, sin el formulario de reserva de CU13.
            'puedeReservar' => $usuario->tienePermiso('cita.programar') && $request->query('accion') !== 'consultar',
            'pacienteInicial' => $request->integer('paciente')
                ? Paciente::query()->where('estado', true)->find($request->integer('paciente'))
                : null,
        ]));
    }

    /** JSON con los turnos y fichas del día. */
    public function disponibles(ConsultarFichasRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $idCita = null;

        // Al reprogramar, la propia cita no se muestra como ocupada.
        if ($request->filled('cita')) {
            $cita = Cita::query()->find($request->integer('cita'));
            $idCita = $cita && $this->citas->puedeVer($cita, $usuario) ? $cita->id_cita : null;
        }

        return response()->json($this->disponibilidad->consultar(
            $request->integer('id_medico'),
            $request->integer('id_especialidad'),
            $request->string('fecha')->toString(),
            $this->citas->canalDe($usuario),
            $idCita,
        ));
    }

    /** Autocompletado de pacientes activos por CI, nombre, apellido o código. */
    public function pacientes(Request $request, PacienteService $pacientes): JsonResponse
    {
        $texto = trim((string) $request->query('q'));

        if (mb_strlen($texto) < 2) {
            return response()->json([]);
        }

        return response()->json(
            $pacientes->listar($texto, '1')->getCollection()->take(10)->map(fn (Paciente $paciente) => [
                'id' => $paciente->id_paciente,
                'codigo' => $paciente->codigo,
                'ci' => $paciente->ci,
                'nombre' => trim($paciente->apellidos.', '.$paciente->nombres),
            ])->values()
        );
    }
}

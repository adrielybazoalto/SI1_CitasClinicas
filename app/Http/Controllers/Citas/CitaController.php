<?php

namespace App\Http\Controllers\Citas;

use App\Exceptions\FichaNoDisponibleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\CancelarCitaRequest;
use App\Http\Requests\Citas\ProgramarCitaRequest;
use App\Http\Requests\Citas\ReprogramarCitaRequest;
use App\Models\Cita;
use App\Services\CitaEstadoService;
use App\Services\CitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU13, CU15, CU16 · Programar, reprogramar y cancelar citas.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class CitaController extends Controller
{
    /** ?accion que llega desde el menú: CU15, CU16 y CU17 comparten esta pantalla. */
    private const ACCIONES = ['reprogramar', 'cancelar', 'asistencia'];

    public function __construct(private CitaService $servicio)
    {
    }

    /** Listado de citas con filtros (el paciente ve solo las suyas). */
    public function index(Request $request): View
    {
        $accion = in_array($request->query('accion'), self::ACCIONES, true) ? $request->query('accion') : null;

        $filtros = [
            'fecha' => $request->query('fecha'),
            'medico' => $request->integer('medico') ?: null,
            'estado' => $request->query('estado'),
            'buscar' => $request->query('buscar'),
        ];

        // Una sola pantalla para CU15, CU16 y CU17: ?accion solo fija filtros.
        if ($accion === 'asistencia') {
            $filtros['fecha'] = now()->toDateString();
            $filtros['estados'] = CitaEstadoService::ACTIVOS;
        } elseif ($accion !== null) {
            $filtros['estados'] = [CitaEstadoService::PROGRAMADA];
        }

        return view('citas.index', [
            'citas' => $this->servicio->listar($request->user(), $filtros),
            'accion' => $accion,
            'filtros' => $filtros,
            'medicos' => $this->servicio->datosReserva()['medicos'],
            'estados' => array_keys(CitaEstadoService::TRANSICIONES),
            'esPaciente' => $this->servicio->pacienteDe($request->user()) !== null,
        ]);
    }

    /** CU13 · Reserva la ficha elegida en la pantalla de fichas. */
    public function store(ProgramarCitaRequest $request): RedirectResponse
    {
        try {
            $cita = $this->servicio->programar($request->validated(), $request->user());
        } catch (FichaNoDisponibleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('citas.show', $cita)
            ->with('status', "Cita {$cita->codigo} programada: ficha {$cita->nro_cita}, {$cita->fecha->format('d/m/Y')} a las {$cita->hora_inicio_corta}.");
    }

    /** Detalle de la cita con su historial de estados. */
    public function show(Request $request, Cita $cita): View
    {
        abort_unless($this->servicio->puedeVer($cita, $request->user()), 403);

        $cita->load(['paciente', 'medico', 'especialidad', 'creador', 'historialEstados.usuario']);

        return view('citas.show', ['cita' => $cita]);
    }

    /** CU15 · Pantalla de fichas en modo reprogramación. */
    public function editReprogramar(Request $request, Cita $cita): View|RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($this->servicio->puedeVer($cita, $usuario), 403);

        if ($cita->estado !== CitaEstadoService::PROGRAMADA) {
            return redirect()->route('citas.show', $cita)->with('error', 'Solo se pueden reprogramar citas en estado Programada.');
        }

        $cita->load(['paciente', 'medico', 'especialidad']);

        return view('citas.reservar', array_merge($this->servicio->datosReserva(), [
            'cita' => $cita,
            'canal' => $this->servicio->canalDe($usuario),
            'pacienteActual' => $cita->paciente,
            'puedeReservar' => true,
            'pacienteInicial' => null,
        ]));
    }

    /** CU15 · Mueve la cita a la nueva ficha conservando el código. */
    public function reprogramar(ReprogramarCitaRequest $request, Cita $cita): RedirectResponse
    {
        abort_unless($this->servicio->puedeVer($cita, $request->user()), 403);

        try {
            $cita = $this->servicio->reprogramar($cita, $request->validated(), $request->user());
        } catch (FichaNoDisponibleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('citas.show', $cita)
            ->with('status', "Cita {$cita->codigo} reprogramada al {$cita->fecha->format('d/m/Y')} a las {$cita->hora_inicio_corta}.");
    }

    /** CU16 · Cancela la cita (nunca se borra). */
    public function cancelar(CancelarCitaRequest $request, Cita $cita): RedirectResponse
    {
        abort_unless($this->servicio->puedeVer($cita, $request->user()), 403);

        $cita = $this->servicio->cancelar($cita, $request->validated('motivo_cancelacion'), $request->user());

        return redirect()->route('citas.show', $cita)
            ->with('status', "Cita {$cita->codigo} cancelada. La ficha quedó libre.");
    }
}

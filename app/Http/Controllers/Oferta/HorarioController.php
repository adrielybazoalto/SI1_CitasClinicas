<?php

namespace App\Http\Controllers\Oferta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oferta\HorarioRequest;
use App\Models\Horario;
use App\Services\HorarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU09 · Gestionar horarios de atención.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class HorarioController extends Controller
{
    public function __construct(private HorarioService $servicio)
    {
    }

    public function index(Request $request): View
    {
        $filtros = [
            'medico' => $request->integer('medico') ?: null,
            'especialidad' => $request->integer('especialidad') ?: null,
            'dia' => $request->integer('dia') ?: null,
            'estado' => $request->query('estado'),
        ];

        return view('oferta.horarios', [
            'horarios' => $this->servicio->listar(
                $filtros['medico'],
                $filtros['especialidad'],
                $filtros['dia'],
                $filtros['estado'],
            ),
            'resumen' => $this->servicio->resumen(
                $filtros['medico'],
                $filtros['especialidad'],
                $filtros['dia'],
            ),
            'medicos' => $this->servicio->medicos(false),
            'especialidades' => $this->servicio->especialidades(false),
            'filtros' => $filtros,
        ]);
    }

    public function create(Request $request): View
    {
        $horario = new Horario([
            'id_medico' => $request->integer('medico') ?: null,
            'dia_semana' => 1,
            'estado' => true,
        ]);

        return view('oferta.horario-form', array_merge(
            ['horario' => $horario],
            $this->servicio->datosFormulario($horario),
        ));
    }

    public function store(HorarioRequest $request): RedirectResponse
    {
        $horario = $this->servicio->registrar($request->validated(), $request->user());
        $calculo = $this->servicio->capacidad($horario->hora_inicio, $horario->hora_fin, $horario->duracion_min);

        return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
            ->with('status', "Turno registrado: {$calculo['capacidad']} fichas ({$calculo['cupos_linea']} en línea / {$calculo['cupos_mostrador']} mostrador).");
    }

    public function edit(Horario $horario): View
    {
        $horario->load(['medico', 'especialidad']);

        return view('oferta.horario-form', array_merge(
            ['horario' => $horario],
            $this->servicio->datosFormulario($horario),
        ));
    }

    public function update(HorarioRequest $request, Horario $horario): RedirectResponse
    {
        $horario = $this->servicio->actualizar($horario, $request->validated(), $request->user());

        return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
            ->with('status', 'Turno actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, Horario $horario): RedirectResponse
    {
        $horario = $this->servicio->cambiarEstado($horario, ! $horario->estado, $request->user());

        return back()->with('status', 'Turno '.($horario->estado ? 'activado.' : 'desactivado.'));
    }
}

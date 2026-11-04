<?php

namespace App\Http\Controllers\Oferta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oferta\MedicoRequest;
use App\Models\Medico;
use App\Services\EspecialidadService;
use App\Services\MedicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU08 · Gestionar médicos.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class MedicoController extends Controller
{
    public function __construct(
        private MedicoService $servicio,
        private EspecialidadService $especialidades,
    ) {
    }

    public function index(Request $request): View
    {
        $filtros = [
            'buscar' => $request->query('buscar'),
            'especialidad' => $request->integer('especialidad') ?: null,
            'estado' => $request->query('estado'),
        ];

        return view('oferta.medicos', [
            'medicos' => $this->servicio->listar(
                $filtros['buscar'],
                $filtros['especialidad'],
                $filtros['estado'],
            ),
            'especialidades' => $this->especialidades->todas(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('oferta.medico-form', [
            'medico' => new Medico(['estado' => true]),
            'especialidades' => $this->especialidades->todas(),
            'usuarios' => $this->servicio->usuariosMedicoDisponibles(),
        ]);
    }

    public function store(MedicoRequest $request): RedirectResponse
    {
        $medico = $this->servicio->registrar($request->validated(), $request->user());

        return redirect()->route('medicos.index')
            ->with('status', "Médico {$medico->nombre_completo} registrado con el código {$medico->codigo}.");
    }

    public function edit(Medico $medico): View
    {
        $medico->load('especialidades');

        return view('oferta.medico-form', [
            'medico' => $medico,
            'especialidades' => $this->especialidades->todas(),
            'usuarios' => $this->servicio->usuariosMedicoDisponibles($medico->id_medico),
        ]);
    }

    public function update(MedicoRequest $request, Medico $medico): RedirectResponse
    {
        $medico = $this->servicio->actualizar($medico, $request->validated(), $request->user());

        return redirect()->route('medicos.index')
            ->with('status', "Médico {$medico->nombre_completo} actualizado correctamente.");
    }

    public function cambiarEstado(Request $request, Medico $medico): RedirectResponse
    {
        $medico = $this->servicio->cambiarEstado($medico, ! $medico->estado, $request->user());

        return back()->with('status', "Médico {$medico->nombre_completo} ".($medico->estado ? 'activado.' : 'desactivado.'));
    }
}

<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\StorePacienteRequest;
use App\Http\Requests\Pacientes\UpdatePacienteRequest;
use App\Models\Paciente;
use App\Services\PacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU05 · Gestionar pacientes.
 *
 * Controlador liviano: recibe la petición, delega en PacienteService y
 * devuelve la vista o la redirección. Sin reglas del negocio aquí.
 * Los antecedentes clínicos (CU06) se atienden en AntecedenteController.
 */
class PacienteController extends Controller
{
    public function __construct(private PacienteService $servicio)
    {
    }

    /** Listado con buscador por CI, apellidos, nombres o código. */
    public function index(Request $request): View
    {
        $pacientes = $this->servicio->listar(
            $request->query('buscar'),
            $request->query('estado'),
        );

        return view('pacientes.index', ['pacientes' => $pacientes]);
    }

    /** Formulario de alta. */
    public function create(): View
    {
        return view('pacientes.form', ['paciente' => new Paciente()]);
    }

    public function store(StorePacienteRequest $request): RedirectResponse
    {
        $resultado = $this->servicio->registrar($request->validated(), $request->user());

        return redirect()->route('pacientes.index')
            ->with('status', "Paciente {$resultado['paciente']->codigo} registrado correctamente.")
            ->with('clave_temporal', [
                'username' => $resultado['username'],
                'clave' => $resultado['clave_temporal'],
            ]);
    }

    /** Formulario de edición. */
    public function edit(Paciente $paciente): View
    {
        $paciente->load(['usuario', 'antecedente']);

        return view('pacientes.form', ['paciente' => $paciente]);
    }

    public function update(UpdatePacienteRequest $request, Paciente $paciente): RedirectResponse
    {
        $this->servicio->actualizar($paciente, $request->validated(), $request->user());

        return redirect()->route('pacientes.index')
            ->with('status', "Paciente {$paciente->codigo} actualizado correctamente.");
    }

    /** Baja lógica: estado = false, sin borrar el historial (RNF13). */
    public function destroy(Request $request, Paciente $paciente): RedirectResponse
    {
        $this->servicio->cambiarEstado($paciente, false, $request->user());

        return back()->with('status', "Paciente {$paciente->codigo} desactivado. Su historial se conserva.");
    }

    /** Reactivación de un paciente dado de baja lógica. */
    public function activar(Request $request, Paciente $paciente): RedirectResponse
    {
        $this->servicio->cambiarEstado($paciente, true, $request->user());

        return back()->with('status', "Paciente {$paciente->codigo} activado correctamente.");
    }
}

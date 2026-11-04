<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\AntecedenteRequest;
use App\Models\Paciente;
use App\Services\PacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CU06 · Gestionar antecedentes clínicos.
 *
 * Controlador liviano: recibe la petición, delega en PacienteService y
 * devuelve la vista o la redirección. Sin reglas del negocio aquí.
 */
class AntecedenteController extends Controller
{
    public function __construct(private PacienteService $servicio)
    {
    }

    /** Formulario de la ficha clínica básica del paciente. */
    public function edit(Paciente $paciente): View
    {
        $paciente->load('antecedente');

        return view('pacientes.antecedentes', ['paciente' => $paciente]);
    }

    /** Guarda o actualiza los antecedentes clínicos. */
    public function update(AntecedenteRequest $request, Paciente $paciente): RedirectResponse
    {
        $this->servicio->actualizarAntecedentes($paciente, $request->validated(), $request->user());

        return redirect()->route('pacientes.antecedentes', $paciente)
            ->with('status', 'Antecedentes clínicos actualizados correctamente.');
    }
}

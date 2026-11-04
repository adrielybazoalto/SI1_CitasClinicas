<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\AntecedenteRequest;
use App\Models\Cita;
use App\Models\Paciente;
use App\Services\PacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function edit(Request $request, Paciente $paciente): View
    {
        $paciente->load('antecedente');

        return view('pacientes.antecedentes', [
            'paciente' => $paciente,
            'volverCita' => $this->citaDeRetorno($request, $paciente),
        ]);
    }

    /** Guarda o actualiza los antecedentes clínicos. */
    public function update(AntecedenteRequest $request, Paciente $paciente): RedirectResponse
    {
        $this->servicio->actualizarAntecedentes($paciente, $request->validated(), $request->user());

        // Extensión desde CU21: vuelve a la pantalla de atención de la cita.
        if ($cita = $this->citaDeRetorno($request, $paciente)) {
            return redirect()->route('atencion.create', $cita)
                ->with('status', 'Antecedentes clínicos actualizados correctamente.');
        }

        return redirect()->route('pacientes.antecedentes', $paciente)
            ->with('status', 'Antecedentes clínicos actualizados correctamente.');
    }

    /** Cita de la que se vino (CU21), solo si es de este paciente. */
    private function citaDeRetorno(Request $request, Paciente $paciente): ?Cita
    {
        $id = $request->integer('volver_cita');

        return $id ? Cita::query()->whereKey($id)->where('id_paciente', $paciente->id_paciente)->first() : null;
    }
}

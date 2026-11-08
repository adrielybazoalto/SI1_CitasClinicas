<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\PerfilRequest;
use App\Models\Paciente;
use App\Services\PacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU04 · Gestionar perfil del paciente (solo su propio registro).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class PerfilController extends Controller
{
    public function __construct(private PacienteService $servicio)
    {
    }

    public function edit(Request $request): View|RedirectResponse
    {
        // El administrador no tiene perfil de paciente: supervisa los perfiles desde CU05.
        if ($request->user()->rol?->esAdministrador() && ! Paciente::query()->where('id_usuario', $request->user()->id_usuario)->exists()) {
            return redirect()->route('pacientes.index')
                ->with('status', 'CU04 «Mi perfil» lo usa cada paciente para su propio registro. Como administrador, consulte o edite los datos de un paciente aquí (CU05).');
        }

        $paciente = $this->propio($request)->load(['antecedente']);

        return view('pacientes.perfil', [
            'paciente' => $paciente,
            // Bloque "Mis próximas citas" (se leen por la relación del paciente).
            'proximas' => $paciente->citas()
                ->with(['medico:id_medico,nombres,apellidos', 'especialidad:id_especialidad,nombre'])
                ->whereIn('estado', ['Programada', 'Confirmada'])
                ->whereDate('fecha', '>=', now()->toDateString())
                ->orderBy('fecha')->orderBy('hora_inicio')
                ->get(),
        ]);
    }

    public function update(PerfilRequest $request): RedirectResponse
    {
        $this->servicio->actualizarPerfil($this->propio($request), $request->validated(), $request->user());

        return redirect()->route('perfil.edit')->with('status', 'Perfil actualizado.');
    }

    /** El paciente solo accede a su propio registro. */
    private function propio(Request $request): Paciente
    {
        $paciente = Paciente::query()->where('id_usuario', $request->user()->id_usuario)->first();
        abort_unless($paciente, 403, 'Su cuenta no está vinculada a un paciente.');

        return $paciente;
    }
}

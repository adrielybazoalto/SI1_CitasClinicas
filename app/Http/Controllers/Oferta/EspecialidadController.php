<?php

namespace App\Http\Controllers\Oferta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oferta\EspecialidadRequest;
use App\Models\Especialidad;
use App\Services\EspecialidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU07 · Gestionar especialidades.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class EspecialidadController extends Controller
{
    public function __construct(private EspecialidadService $servicio)
    {
    }

    public function index(Request $request): View
    {
        return view('oferta.especialidades', [
            'especialidades' => $this->servicio->listar(
                $request->query('buscar'),
                $request->query('estado'),
            ),
            'filtros' => [
                'buscar' => $request->query('buscar'),
                'estado' => $request->query('estado'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('oferta.especialidad-form', [
            'especialidad' => new Especialidad([
                'duracion_min' => 30,
                'estado' => true,
            ]),
        ]);
    }

    public function store(EspecialidadRequest $request): RedirectResponse
    {
        $especialidad = $this->servicio->registrar($request->validated(), $request->user());

        return redirect()->route('especialidades.index')
            ->with('status', "Especialidad {$especialidad->nombre} registrada correctamente.");
    }

    public function edit(Especialidad $especialidad): View
    {
        return view('oferta.especialidad-form', compact('especialidad'));
    }

    public function update(EspecialidadRequest $request, Especialidad $especialidad): RedirectResponse
    {
        $especialidad = $this->servicio->actualizar($especialidad, $request->validated(), $request->user());

        return redirect()->route('especialidades.index')
            ->with('status', "Especialidad {$especialidad->nombre} actualizada correctamente.");
    }

    public function cambiarEstado(Request $request, Especialidad $especialidad): RedirectResponse
    {
        $especialidad = $this->servicio->cambiarEstado($especialidad, ! $especialidad->estado, $request->user());

        return back()->with('status', "Especialidad {$especialidad->nombre} ".($especialidad->estado ? 'activada.' : 'desactivada.'));
    }
}

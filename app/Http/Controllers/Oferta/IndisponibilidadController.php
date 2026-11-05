<?php

namespace App\Http\Controllers\Oferta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oferta\IndisponibilidadRequest;
use App\Services\IndisponibilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU10 · Registrar indisponibilidad médica (las citas afectadas se
 * reubican en Citas\ReubicacionController).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class IndisponibilidadController extends Controller
{
    public function __construct(private IndisponibilidadService $servicio)
    {
    }

    public function index(Request $request): View
    {
        return view('oferta.indisponibilidades', [
            'periodos' => $this->servicio->listar(),
            'medicos' => $this->servicio->medicos(),
            'medicoInicial' => $request->integer('medico') ?: null,
        ]);
    }

    public function store(IndisponibilidadRequest $request): RedirectResponse
    {
        $periodo = $this->servicio->registrar($request->validated(), $request->user());

        return redirect()->route('indisponibilidades.citas', $periodo)
            ->with('status', 'Indisponibilidad registrada: esos horarios ya no se ofrecen. Revise las citas afectadas.');
    }
}

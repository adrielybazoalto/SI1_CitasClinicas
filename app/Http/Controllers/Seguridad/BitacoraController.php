<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * RF04 · Consulta de la bitácora (solo lectura).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class BitacoraController extends Controller
{
    public function __construct(private BitacoraService $servicio)
    {
    }

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'usuario' => ['nullable', 'integer'],
            'accion' => ['nullable', 'string', 'max:50'],
            'ip' => ['nullable', 'string', 'max:45', 'regex:/^[0-9a-fA-F.:]+$/'],
        ], [
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'ip.regex' => 'La dirección IP solo puede tener números, puntos o dos puntos.',
        ]);

        return view('seguridad.bitacora', array_merge($this->servicio->catalogos(), [
            'registros' => $this->servicio->listar($filtros),
            'filtros' => $filtros + ['desde' => null, 'hasta' => null, 'usuario' => null, 'accion' => null, 'ip' => null],
        ]));
    }
}

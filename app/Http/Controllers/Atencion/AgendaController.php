<?php

namespace App\Http\Controllers\Atencion;

use App\Http\Controllers\Controller;
use App\Services\AtencionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU20 · Consultar agenda médica.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class AgendaController extends Controller
{
    public function __construct(private AtencionService $servicio)
    {
    }

    /** Agenda del día (o resumen de la semana) de un médico. */
    public function index(Request $request): View
    {
        $datos = $request->validate([
            'medico' => ['nullable', 'integer'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', 'in:'.implode(',', array_keys(AtencionService::FILTROS_ESTADO))],
            'vista' => ['nullable', 'in:dia,semana'],
        ], [
            'fecha.date_format' => 'La fecha no es válida.',
            'estado.in' => 'El filtro de estado no es válido.',
        ]);

        $usuario = $request->user();
        $fecha = isset($datos['fecha']) ? CarbonImmutable::parse($datos['fecha']) : CarbonImmutable::today();
        $vista = $datos['vista'] ?? 'dia';
        $estado = $datos['estado'] ?? null;
        $esMedico = $this->servicio->esMedico($usuario);
        $medico = $this->servicio->medicoAgenda($usuario, isset($datos['medico']) ? (int) $datos['medico'] : null);

        return view('atencion.agenda', [
            'medico' => $medico,
            'esMedico' => $esMedico,
            'medicos' => $esMedico ? collect() : $this->servicio->medicosSeleccionables(),
            'fecha' => $fecha,
            'vista' => $vista,
            'estado' => $estado,
            'filtrosEstado' => array_keys(AtencionService::FILTROS_ESTADO),
            'verMotivo' => $this->servicio->puedeVerMotivo($usuario),
            'puedeAtender' => $usuario->tienePermiso('atencion.registrar'),
            'citas' => $medico && $vista === 'dia' ? $this->servicio->agendaDelDia($medico, $fecha, $estado) : collect(),
            'semana' => $medico && $vista === 'semana' ? $this->servicio->resumenSemanal($medico, $fecha) : [],
        ]);
    }
}

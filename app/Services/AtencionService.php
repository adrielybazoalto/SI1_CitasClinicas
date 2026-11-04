<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Rol;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Paquete 5 · Atención médica.
 *
 * CU20 Consultar agenda médica: agenda diaria y resumen semanal por médico.
 */
class AtencionService
{
    /** Filtro "pendientes" de la agenda = citas aún no atendidas ni cerradas. */
    public const FILTROS_ESTADO = [
        'pendientes' => [CitaEstadoService::PROGRAMADA],
        'confirmadas' => [CitaEstadoService::CONFIRMADA],
        'atendidas' => [CitaEstadoService::ATENDIDA],
        'canceladas' => [CitaEstadoService::CANCELADA],
        'inasistencias' => [CitaEstadoService::INASISTENCIA],
    ];

    /** Médico vinculado a la cuenta del usuario (null si no es médico). */
    public function medicoDe(User $usuario): ?Medico
    {
        return Medico::query()->where('id_usuario', $usuario->id_usuario)->first();
    }

    /** ¿El usuario es un médico? Solo puede ver su propia agenda. */
    public function esMedico(User $usuario): bool
    {
        return $usuario->rol?->nombre === Rol::MEDICO;
    }

    /**
     * Excepción de CU20: la recepcionista no ve el motivo de la consulta.
     * Lo ven el médico y el administrador.
     */
    public function puedeVerMotivo(User $usuario): bool
    {
        return $usuario->rol?->nombre !== Rol::RECEPCIONISTA;
    }

    /**
     * Médico cuya agenda se muestra. El médico siempre ve la suya, aunque
     * pida otra por la URL; el resto elige uno (null si aún no eligió).
     */
    public function medicoAgenda(User $usuario, ?int $idMedicoSolicitado): ?Medico
    {
        if ($this->esMedico($usuario)) {
            return $this->medicoDe($usuario);
        }

        return $idMedicoSolicitado ? Medico::query()->find($idMedicoSolicitado) : null;
    }

    /** Médicos que el administrador o la recepcionista pueden elegir. */
    public function medicosSeleccionables(): Collection
    {
        return Medico::query()->orderBy('apellidos')->orderBy('nombres')
            ->get(['id_medico', 'nombres', 'apellidos', 'estado']);
    }

    /**
     * CU20 · Citas de un médico en una fecha, ordenadas por hora.
     *
     * @return Collection<int, Cita>
     */
    public function agendaDelDia(Medico $medico, CarbonImmutable $fecha, ?string $filtroEstado = null): Collection
    {
        $estados = self::FILTROS_ESTADO[$filtroEstado] ?? null;

        return Cita::query()
            ->with(['paciente:id_paciente,codigo,ci,nombres,apellidos', 'especialidad:id_especialidad,nombre', 'atencion:id_atencion,id_cita'])
            ->where('id_medico', $medico->id_medico)
            ->whereDate('fecha', $fecha->toDateString())
            ->when($estados, fn ($q) => $q->whereIn('estado', $estados))
            ->orderBy('hora_inicio')
            ->orderBy('nro_cita')
            ->get();
    }

    /**
     * Cantidad de citas por estado en cada uno de los 7 días (lunes a
     * domingo) de la semana que contiene la fecha. Se agrupa en la base.
     *
     * @return array<string, array{fecha: CarbonImmutable, estados: array<string, int>, total: int}>
     */
    public function resumenSemanal(Medico $medico, CarbonImmutable $fecha): array
    {
        $lunes = $fecha->startOfWeek(CarbonImmutable::MONDAY);
        $domingo = $lunes->addDays(6);

        $conteos = Cita::query()
            ->selectRaw('fecha, estado, count(*) as total')
            ->where('id_medico', $medico->id_medico)
            ->whereBetween('fecha', [$lunes->toDateString(), $domingo->toDateString()])
            ->groupBy('fecha', 'estado')
            ->get();

        $semana = [];
        for ($dia = $lunes; $dia->lte($domingo); $dia = $dia->addDay()) {
            $semana[$dia->toDateString()] = [
                'fecha' => $dia,
                'estados' => array_fill_keys(array_keys(CitaEstadoService::TRANSICIONES), 0),
                'total' => 0,
            ];
        }

        foreach ($conteos as $fila) {
            $clave = CarbonImmutable::parse($fila->fecha)->toDateString();
            $semana[$clave]['estados'][$fila->estado] = (int) $fila->total;
            $semana[$clave]['total'] += (int) $fila->total;
        }

        return $semana;
    }
}

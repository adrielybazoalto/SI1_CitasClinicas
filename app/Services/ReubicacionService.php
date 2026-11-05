<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Indisponibilidad;
use App\Models\Medico;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * P4 · Citas afectadas por una indisponibilidad médica (CU10).
 *
 * Propone los primeros horarios libres del mismo médico o de otro de la
 * misma especialidad y reubica (CU15 forzado: sin el límite de RN27,
 * conserva el código) o cancela con reembolso total (RN28). Los pacientes
 * reciben el aviso por correo desde CitaService/NotificacionService.
 */
class ReubicacionService
{
    /** Días hacia adelante en los que se buscan horarios libres. */
    private const DIAS_BUSQUEDA = 21;

    /** Cantidad de opciones propuestas por cita. */
    private const OPCIONES = 3;

    public function __construct(
        private CitaService $citas,
        private DisponibilidadService $disponibilidad,
    ) {
    }

    /**
     * Citas Programadas o Confirmadas del médico que se cruzan con el periodo.
     *
     * @return Collection<int, Cita>
     */
    public function afectadas(Indisponibilidad $periodo): Collection
    {
        return Cita::query()
            ->with(['paciente:id_paciente,codigo,ci,nombres,apellidos,correo', 'especialidad:id_especialidad,nombre'])
            ->where('id_medico', $periodo->id_medico)
            ->whereIn('estado', CitaEstadoService::ACTIVOS)
            ->whereBetween('fecha', [$periodo->inicio->toDateString(), $periodo->fin->toDateString()])
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->get()
            ->filter(function (Cita $cita) use ($periodo) {
                $inicio = CarbonImmutable::parse($cita->fecha->toDateString().' '.substr((string) $cita->hora_inicio, 0, 8));
                $fin = CarbonImmutable::parse($cita->fecha->toDateString().' '.substr((string) $cita->hora_fin, 0, 8));

                return $inicio->lt($periodo->fin) && $periodo->inicio->lt($fin);
            })
            ->values();
    }

    /**
     * Primeros horarios libres: primero el mismo médico, luego los demás de
     * la especialidad. Los periodos bloqueados ya vienen excluidos por
     * DisponibilidadService.
     *
     * @return list<array{id_medico: int, medico: string, fecha: string, id_horario: int, nro: int, hora: string}>
     */
    public function opciones(Cita $cita): array
    {
        $medicos = Medico::query()
            ->where('estado', true)
            ->whereHas('especialidades', fn ($q) => $q->where('especialidad.id_especialidad', $cita->id_especialidad))
            ->get(['id_medico', 'nombres', 'apellidos'])
            ->sortBy(fn (Medico $m) => $m->id_medico === $cita->id_medico ? 0 : 1)
            ->values();

        $opciones = [];
        $hoy = CarbonImmutable::today();

        for ($d = 0; $d <= self::DIAS_BUSQUEDA && count($opciones) < self::OPCIONES; $d++) {
            $fecha = $hoy->addDays($d)->toDateString();

            foreach ($medicos as $medico) {
                $grilla = $this->disponibilidad->consultar($medico->id_medico, $cita->id_especialidad, $fecha, DisponibilidadService::CANAL_MOSTRADOR, $cita->id_cita);

                foreach ($grilla['turnos'] as $turno) {
                    foreach ($turno['fichas'] as $ficha) {
                        if ($ficha['disponible']) {
                            $opciones[] = [
                                'id_medico' => $medico->id_medico,
                                'medico' => $medico->nombre_completo,
                                'fecha' => $fecha,
                                'id_horario' => $turno['id_horario'],
                                'nro' => $ficha['nro'],
                                'hora' => $ficha['hora_inicio'],
                            ];
                            continue 3; // una opción por médico y día
                        }
                    }
                }
            }
        }

        return array_slice($opciones, 0, self::OPCIONES);
    }

    /** Reubica la cita en el horario elegido (reprogramación forzada). */
    public function reubicar(Cita $cita, array $destino, Indisponibilidad $periodo, User $actor): Cita
    {
        return $this->citas->reprogramar($cita, $destino + [
            'forzar' => true,
            'motivo_forzado' => 'Indisponibilidad médica: '.$periodo->motivo,
        ], $actor);
    }

    /** Cancela con reembolso total, sin importar la anticipación (RN28). */
    public function cancelar(Cita $cita, Indisponibilidad $periodo, User $actor): Cita
    {
        return $this->citas->cancelar($cita, 'Indisponibilidad médica: '.$periodo->motivo, $actor, reembolsoTotal: true);
    }
}

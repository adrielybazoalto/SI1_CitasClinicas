<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Medico;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CU09 · Gestionar horarios de atención.
 *
 * Cada fila de horario representa un turno semanal de un médico para una
 * especialidad. La capacidad se calcula en tiempo real:
 *   capacidad = minutos del turno / duración de la consulta
 * y se reparte según config/clinica.php (70% en línea / 30% mostrador).
 * Las fichas no se almacenan: se derivan del horario al reservar.
 */
class HorarioService
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /** Listado paginado de turnos con filtros y capacidad calculada. */
    public function listar(?int $idMedico, ?int $idEspecialidad, ?int $dia, ?string $estado): LengthAwarePaginator
    {
        $horarios = Horario::query()
            ->with(['medico', 'especialidad'])
            ->when($idMedico, fn ($q) => $q->where('id_medico', $idMedico))
            ->when($idEspecialidad, fn ($q) => $q->where('id_especialidad', $idEspecialidad))
            ->when($dia, fn ($q) => $q->where('dia_semana', $dia))
            ->when($estado !== null && $estado !== '', fn ($q) => $q->where('estado', $estado === '1'))
            ->orderBy('id_medico')
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->paginate(20)
            ->withQueryString();

        $horarios->getCollection()->transform(function (Horario $horario) {
            $calculo = $this->capacidad($horario->hora_inicio, $horario->hora_fin, $horario->duracion_min);

            $horario->setAttribute('capacidad', $calculo['capacidad']);
            $horario->setAttribute('cupos_linea', $calculo['cupos_linea']);
            $horario->setAttribute('cupos_mostrador', $calculo['cupos_mostrador']);

            return $horario;
        });

        return $horarios;
    }

    /**
     * Resumen semanal de los turnos activos según los filtros aplicados.
     *
     * @return array{turnos: int, capacidad: int, cupos_linea: int, cupos_mostrador: int}
     */
    public function resumen(?int $idMedico, ?int $idEspecialidad, ?int $dia): array
    {
        $horarios = Horario::query()
            ->where('estado', true)
            ->when($idMedico, fn ($q) => $q->where('id_medico', $idMedico))
            ->when($idEspecialidad, fn ($q) => $q->where('id_especialidad', $idEspecialidad))
            ->when($dia, fn ($q) => $q->where('dia_semana', $dia))
            ->get();

        $capacidad = 0;
        $linea = 0;
        $mostrador = 0;

        foreach ($horarios as $horario) {
            $calculo = $this->capacidad($horario->hora_inicio, $horario->hora_fin, $horario->duracion_min);
            $capacidad += $calculo['capacidad'];
            $linea += $calculo['cupos_linea'];
            $mostrador += $calculo['cupos_mostrador'];
        }

        return [
            'turnos' => $horarios->count(),
            'capacidad' => $capacidad,
            'cupos_linea' => $linea,
            'cupos_mostrador' => $mostrador,
        ];
    }

    /** Médicos para filtros y formularios. */
    public function medicos(bool $soloActivos = true, ?int $incluirId = null): Collection
    {
        return Medico::query()
            ->when($soloActivos, fn ($q) => $q->where(fn ($w) => $w->where('estado', true)
                ->when($incluirId, fn ($q2) => $q2->orWhere('id_medico', $incluirId))))
            ->with(['especialidades' => fn ($q) => $q->where('estado', true)])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /** Especialidades para filtros y formularios. */
    public function especialidades(bool $soloActivas = true, ?int $incluirId = null): Collection
    {
        return Especialidad::query()
            ->when($soloActivas, fn ($q) => $q->where(fn ($w) => $w->where('estado', true)
                ->when($incluirId, fn ($q2) => $q2->orWhere('id_especialidad', $incluirId))))
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Datos del formulario: médicos, especialidades y un mapa
     * especialidad => [ids de médicos que la ofrecen] para filtrar con JS.
     *
     * @return array{medicos: Collection, especialidades: Collection, mapa: array<int, array<int>>}
     */
    public function datosFormulario(?Horario $horario = null): array
    {
        $medicos = $this->medicos(true, $horario?->id_medico);
        $especialidades = $this->especialidades(true, $horario?->id_especialidad);
        $mapa = [];

        foreach ($especialidades as $especialidad) {
            $ids = $medicos
                ->filter(fn (Medico $medico) => $medico->especialidades
                    ->contains('id_especialidad', $especialidad->id_especialidad))
                ->pluck('id_medico')
                ->all();

            if ($horario && (int) $horario->id_especialidad === (int) $especialidad->id_especialidad) {
                $ids[] = (int) $horario->id_medico;
            }

            $mapa[$especialidad->id_especialidad] = array_values(array_unique(array_map('intval', $ids)));
        }

        return [
            'medicos' => $medicos,
            'especialidades' => $especialidades,
            'mapa' => $mapa,
        ];
    }

    public function registrar(array $datos, User $actor): Horario
    {
        return DB::transaction(function () use ($datos, $actor) {
            $activo = (bool) ($datos['estado'] ?? true);

            $this->validarFranja(
                (int) $datos['id_medico'],
                (int) $datos['dia_semana'],
                (string) $datos['hora_inicio'],
                (string) $datos['hora_fin'],
                (int) $datos['duracion_min'],
                $activo,
            );

            $horario = Horario::create([
                'id_medico' => $datos['id_medico'],
                'id_especialidad' => $datos['id_especialidad'],
                'dia_semana' => $datos['dia_semana'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'duracion_min' => $datos['duracion_min'],
                'consultorio' => $datos['consultorio'] ?? null,
                'estado' => $activo,
            ]);

            $calculo = $this->capacidad($horario->hora_inicio, $horario->hora_fin, $horario->duracion_min);

            $this->bitacora->registrar(
                Bitacora::REGISTRAR_HORARIO,
                'horario',
                $horario->id_horario,
                sprintf(
                    'Médico %d · especialidad %d · día %d · %s-%s · capacidad %d (%d en línea / %d mostrador)',
                    $horario->id_medico,
                    $horario->id_especialidad,
                    $horario->dia_semana,
                    $horario->hora_inicio_corta,
                    $horario->hora_fin_corta,
                    $calculo['capacidad'],
                    $calculo['cupos_linea'],
                    $calculo['cupos_mostrador'],
                ),
                $actor->id_usuario,
            );

            return $horario->load(['medico', 'especialidad']);
        });
    }

    public function actualizar(Horario $horario, array $datos, User $actor): Horario
    {
        return DB::transaction(function () use ($horario, $datos, $actor) {
            $activo = (bool) ($datos['estado'] ?? $horario->estado);

            $this->validarFranja(
                (int) $datos['id_medico'],
                (int) $datos['dia_semana'],
                (string) $datos['hora_inicio'],
                (string) $datos['hora_fin'],
                (int) $datos['duracion_min'],
                $activo,
                $horario->id_horario,
            );

            $horario->fill([
                'id_medico' => $datos['id_medico'],
                'id_especialidad' => $datos['id_especialidad'],
                'dia_semana' => $datos['dia_semana'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'duracion_min' => $datos['duracion_min'],
                'consultorio' => $datos['consultorio'] ?? null,
                'estado' => $activo,
            ]);

            $cambios = array_keys($horario->getDirty());
            $horario->save();

            $calculo = $this->capacidad($horario->hora_inicio, $horario->hora_fin, $horario->duracion_min);

            $this->bitacora->registrar(
                Bitacora::MODIFICAR_HORARIO,
                'horario',
                $horario->id_horario,
                sprintf(
                    'Horario %s-%s · capacidad %d (%d en línea / %d mostrador) · campos: %s',
                    $horario->hora_inicio_corta,
                    $horario->hora_fin_corta,
                    $calculo['capacidad'],
                    $calculo['cupos_linea'],
                    $calculo['cupos_mostrador'],
                    $cambios ? implode(', ', $cambios) : 'sin cambios',
                ),
                $actor->id_usuario,
            );

            return $horario->load(['medico', 'especialidad']);
        });
    }

    /** Activa o desactiva un turno. Al activar se revalida la superposición. */
    public function cambiarEstado(Horario $horario, bool $estado, User $actor): Horario
    {
        DB::transaction(function () use ($horario, $estado, $actor) {
            if ($estado) {
                $this->validarFranja(
                    (int) $horario->id_medico,
                    (int) $horario->dia_semana,
                    (string) $horario->hora_inicio,
                    (string) $horario->hora_fin,
                    (int) $horario->duracion_min,
                    true,
                    $horario->id_horario,
                );
            }

            $horario->forceFill(['estado' => $estado])->save();

            $this->bitacora->registrar(
                $estado ? Bitacora::ACTIVAR_HORARIO : Bitacora::DESACTIVAR_HORARIO,
                'horario',
                $horario->id_horario,
                $estado ? 'Horario activado' : 'Horario desactivado (baja lógica)',
                $actor->id_usuario,
            );
        });

        return $horario->refresh();
    }

    /**
     * Reglas de negocio de un turno: orden de horas, división exacta de la
     * duración y ausencia de superposición con otros turnos activos del
     * mismo médico en el mismo día.
     *
     * @throws ValidationException
     */
    public function validarFranja(
        int $idMedico,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        int $duracionMin,
        bool $activo = true,
        ?int $idHorarioExcluir = null,
    ): void {
        $minutos = $this->minutosTurno($horaInicio, $horaFin);

        if ($minutos <= 0) {
            throw ValidationException::withMessages([
                'hora_fin' => 'La hora de fin debe ser mayor que la hora de inicio.',
            ]);
        }

        if ($duracionMin < 10 || $duracionMin > 120) {
            throw ValidationException::withMessages([
                'duracion_min' => 'La duración debe estar entre 10 y 120 minutos.',
            ]);
        }

        if ($minutos < $duracionMin || $minutos % $duracionMin !== 0) {
            throw ValidationException::withMessages([
                'duracion_min' => 'La duración de la consulta debe dividir exactamente el turno, sin sobrantes.',
            ]);
        }

        if ($activo && $this->haySuperposicion($idMedico, $diaSemana, $horaInicio, $horaFin, $idHorarioExcluir)) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'El médico ya tiene un horario activo que se superpone en ese día y franja horaria.',
            ]);
        }
    }

    /** ¿Existe un turno activo del médico que se cruce con la franja? */
    public function haySuperposicion(
        int $idMedico,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $idHorarioExcluir = null,
    ): bool {
        $inicio = substr($horaInicio, 0, 5);
        $fin = substr($horaFin, 0, 5);

        return Horario::query()
            ->where('id_medico', $idMedico)
            ->where('dia_semana', $diaSemana)
            ->where('estado', true)
            ->when($idHorarioExcluir, fn ($q) => $q->where('id_horario', '!=', $idHorarioExcluir))
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio)
            ->exists();
    }

    /**
     * Capacidad del turno y reparto por canal según config/clinica.php.
     * Se redondea el cupo en línea al entero más cercano y el mostrador
     * recibe el resto para que la suma siempre sea la capacidad total.
     *
     * @return array{minutos: int, capacidad: int, cupos_linea: int, cupos_mostrador: int}
     */
    public function capacidad(string $horaInicio, string $horaFin, int $duracionMin): array
    {
        $minutos = max(0, $this->minutosTurno($horaInicio, $horaFin));
        $capacidad = $duracionMin > 0 ? intdiv($minutos, $duracionMin) : 0;

        $porcentajeLinea = (int) config('clinica.porcentaje_linea', 70);
        $cuposLinea = (int) round($capacidad * $porcentajeLinea / 100);
        $cuposLinea = max(0, min($capacidad, $cuposLinea));

        return [
            'minutos' => $minutos,
            'capacidad' => $capacidad,
            'cupos_linea' => $cuposLinea,
            'cupos_mostrador' => $capacidad - $cuposLinea,
        ];
    }

    public function minutosTurno(string $horaInicio, string $horaFin): int
    {
        return $this->aMinutos($horaFin) - $this->aMinutos($horaInicio);
    }

    private function aMinutos(string $hora): int
    {
        $partes = explode(':', $hora);
        $horas = (int) ($partes[0] ?? 0);
        $minutos = (int) ($partes[1] ?? 0);

        return ($horas * 60) + $minutos;
    }
}

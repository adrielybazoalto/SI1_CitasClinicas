<?php

namespace App\Services;

use App\Exceptions\FichaNoDisponibleException;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Indisponibilidad;
use App\Models\Medico;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * CU12 · Calcula las fichas libres de un médico en una fecha a partir de horario, restando citas no canceladas, indisponibilidad y horas pasadas (RN15).
 *
 * Algoritmo (las fichas nunca se guardan en una tabla):
 *   1. Turnos del día = horarios activos del médico y la especialidad cuyo
 *      dia_semana coincide con la fecha (1 = lunes … 7 = domingo).
 *   2. Por cada turno: capacidad = minutos del turno / duración, y la ficha n
 *      empieza en  hora_inicio + (n − 1) × duración.
 *   3. Cada ficha se descarta si: ya pasó, cae en una indisponibilidad del
 *      médico, se cruza con una cita no cancelada del médico (de cualquier
 *      especialidad) o, para el canal en línea, está fuera de la ventana de
 *      reserva (RN17) o pertenece al cupo de mostrador (RN16).
 *
 * Reparto por canal (RN16): las primeras "cupos_linea" fichas del turno son
 * para reservas en línea y las últimas para mostrador. La recepción puede
 * asignar cualquier ficha libre; el paciente solo las de su canal.
 */
class DisponibilidadService
{
    public const CANAL_LINEA = 'LINEA';
    public const CANAL_MOSTRADOR = 'MOSTRADOR';

    /** Estados posibles de una ficha en la grilla. */
    public const LIBRE = 'libre';
    public const OCUPADA = 'ocupada';
    public const BLOQUEADA = 'bloqueada';
    public const PASADA = 'pasada';
    public const FUERA_VENTANA = 'fuera_ventana';
    public const OTRO_CANAL = 'otro_canal';

    /** Motivo para mostrar cuando una ficha concreta no se puede reservar. */
    private const MOTIVOS = [
        self::OCUPADA => 'La ficha ya no está disponible.',
        self::BLOQUEADA => 'El médico no atiende en ese horario (indisponibilidad registrada).',
        self::PASADA => 'No se pueden reservar fichas en horas pasadas.',
        self::FUERA_VENTANA => 'La reserva en línea abre 7 días antes y cierra 1 hora antes del turno.',
        self::OTRO_CANAL => 'Esta ficha está reservada para atención en mostrador.',
    ];

    public function __construct(private HorarioService $horarios)
    {
    }

    /**
     * Grilla de fichas de un médico y especialidad en una fecha.
     *
     * @return array{fecha: string, turnos: array<int, array<string, mixed>>, libres: int, mensaje: ?string}
     */
    public function consultar(int $idMedico, int $idEspecialidad, string $fecha, string $canal, ?int $idCitaExcluir = null): array
    {
        $dia = CarbonImmutable::parse($fecha)->startOfDay();
        $resultado = ['fecha' => $dia->toDateString(), 'turnos' => [], 'libres' => 0, 'mensaje' => null];

        if ($dia->lt(CarbonImmutable::today())) {
            $resultado['mensaje'] = 'No se pueden consultar fichas de fechas pasadas.';

            return $resultado;
        }

        $turnos = $this->turnosDelDia($idMedico, $idEspecialidad, $dia);

        if ($turnos->isEmpty()) {
            $resultado['mensaje'] = 'El médico no atiende esta especialidad el día seleccionado.';

            return $resultado;
        }

        $citas = $this->citasOcupadas($idMedico, $dia, $idCitaExcluir);
        $bloqueos = $this->indisponibilidades($idMedico, $dia);

        foreach ($turnos as $horario) {
            $turno = $this->armarTurno($horario, $dia, $canal, $citas, $bloqueos);
            $resultado['libres'] += $turno['libres'];
            $resultado['turnos'][] = $turno;
        }

        if ($resultado['libres'] === 0) {
            $resultado['mensaje'] = 'No quedan fichas disponibles para esta fecha.';
        }

        return $resultado;
    }

    /**
     * Vuelve a verificar una ficha concreta justo antes de guardarla
     * (se llama dentro de la transacción de CitaService).
     *
     * @return array{id_horario: int, nro: int, hora_inicio: string, hora_fin: string, canal: string, consultorio: ?string}
     * @throws FichaNoDisponibleException
     */
    public function verificarFicha(
        int $idMedico,
        int $idEspecialidad,
        string $fecha,
        int $idHorario,
        int $nro,
        string $canal,
        ?int $idCitaExcluir = null,
    ): array {
        $dia = CarbonImmutable::parse($fecha)->startOfDay();
        $horario = $this->turnosDelDia($idMedico, $idEspecialidad, $dia)->firstWhere('id_horario', $idHorario);

        // RN04: la cita debe caer dentro de un horario activo del médico.
        if (! $horario) {
            throw new FichaNoDisponibleException('La fecha elegida no corresponde a un horario de atención del médico.');
        }

        $turno = $this->armarTurno(
            $horario,
            $dia,
            $canal,
            $this->citasOcupadas($idMedico, $dia, $idCitaExcluir),
            $this->indisponibilidades($idMedico, $dia),
        );

        $ficha = collect($turno['fichas'])->firstWhere('nro', $nro);

        if (! $ficha) {
            throw new FichaNoDisponibleException("El turno solo tiene {$turno['capacidad']} fichas.");
        }

        if ($ficha['estado'] !== self::LIBRE) {
            throw new FichaNoDisponibleException(self::MOTIVOS[$ficha['estado']] ?? self::MOTIVOS[self::OCUPADA]);
        }

        return [
            'id_horario' => $turno['id_horario'],
            'nro' => $ficha['nro'],
            'hora_inicio' => $ficha['hora_inicio'].':00',
            'hora_fin' => $ficha['hora_fin'].':00',
            'canal' => $ficha['canal'],
            'consultorio' => $turno['consultorio'],
        ];
    }

    /**
     * RN29 · Ficha de sobrecupo (urgencias). Se numera después de la última
     * ficha del turno y comparte la hora de la última ficha; la restricción
     * de exclusión no la considera (sobrecupo = TRUE).
     *
     * @return array{id_horario: int, nro: int, hora_inicio: string, hora_fin: string}
     * @throws FichaNoDisponibleException
     */
    public function sobrecupo(int $idMedico, int $idEspecialidad, string $fecha, int $idHorario): array
    {
        $dia = CarbonImmutable::parse($fecha)->startOfDay();
        $horario = $this->turnosDelDia($idMedico, $idEspecialidad, $dia)->firstWhere('id_horario', $idHorario);

        if (! $horario) {
            throw new FichaNoDisponibleException('La fecha elegida no corresponde a un horario de atención del médico.');
        }

        $capacidad = $this->horarios->capacidad($horario->hora_inicio, $horario->hora_fin, $horario->duracion_min)['capacidad'];
        $finTurno = $this->enFecha($dia, $horario->hora_fin);

        if ($finTurno->lte(CarbonImmutable::now())) {
            throw new FichaNoDisponibleException(self::MOTIVOS[self::PASADA]);
        }

        // Sobrecupos del turno: fichas numeradas por encima de la capacidad.
        $sobrecupos = Cita::query()
            ->where('id_medico', $idMedico)
            ->whereDate('fecha', $dia->toDateString())
            ->where('sobrecupo', true)
            ->where('nro_cita', '>', $capacidad)
            ->where('hora_inicio', '>=', $this->hms($horario->hora_inicio))
            ->where('hora_fin', '<=', $this->hms($horario->hora_fin));

        $activos = (clone $sobrecupos)->where('estado', '<>', CitaEstadoService::CANCELADA)->count();

        if ($activos >= (int) config('clinica.max_sobrecupos_turno', 2)) {
            throw new FichaNoDisponibleException('El turno ya alcanzó el máximo de sobrecupos permitidos.');
        }

        // El número nunca se repite, aunque haya sobrecupos cancelados.
        $nro = max($capacidad, (int) $sobrecupos->max('nro_cita')) + 1;

        return [
            'id_horario' => $horario->id_horario,
            'nro' => $nro,
            'hora_inicio' => $finTurno->subMinutes($horario->duracion_min)->format('H:i:s'),
            'hora_fin' => $finTurno->format('H:i:s'),
        ];
    }

    /**
     * Turnos activos del día. Solo cuentan si el médico y la especialidad
     * también están activos (RN05).
     *
     * @return Collection<int, Horario>
     */
    public function turnosDelDia(int $idMedico, int $idEspecialidad, CarbonImmutable $dia): Collection
    {
        $medicoActivo = Medico::query()->whereKey($idMedico)->where('estado', true)->exists();
        $especialidadActiva = Especialidad::query()->whereKey($idEspecialidad)->where('estado', true)->exists();

        if (! $medicoActivo || ! $especialidadActiva) {
            return collect();
        }

        return Horario::query()
            ->where('id_medico', $idMedico)
            ->where('id_especialidad', $idEspecialidad)
            ->where('dia_semana', $dia->dayOfWeekIso)
            ->where('estado', true)
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Construye las fichas de un turno con su estado.
     *
     * @param Collection<int, Cita> $citas
     * @param Collection<int, Indisponibilidad> $bloqueos
     * @return array<string, mixed>
     */
    private function armarTurno(Horario $horario, CarbonImmutable $dia, string $canal, Collection $citas, Collection $bloqueos): array
    {
        $calculo = $this->horarios->capacidad($horario->hora_inicio, $horario->hora_fin, $horario->duracion_min);
        $inicioTurno = $this->enFecha($dia, $horario->hora_inicio);
        $ahora = CarbonImmutable::now();
        $fichas = [];
        $libres = 0;

        // RN17: ventana de reserva en línea (abre N días antes, cierra N horas antes del turno).
        $abre = $dia->subDays((int) config('clinica.reserva_abre_dias_antes', 7));
        $cierra = $inicioTurno->subHours((int) config('clinica.reserva_cierra_horas_antes', 1));
        $dentroVentana = $ahora->gte($abre) && $ahora->lte($cierra);

        for ($nro = 1; $nro <= $calculo['capacidad']; $nro++) {
            // Hora de la ficha = inicio + (n − 1) × duración.
            $inicio = $inicioTurno->addMinutes(($nro - 1) * $horario->duracion_min);
            $fin = $inicio->addMinutes($horario->duracion_min);
            $canalFicha = $nro <= $calculo['cupos_linea'] ? self::CANAL_LINEA : self::CANAL_MOSTRADOR;

            $estado = match (true) {
                $inicio->lte($ahora) => self::PASADA,
                $this->hayBloqueo($bloqueos, $inicio, $fin) => self::BLOQUEADA,
                $this->hayCita($citas, $dia, $inicio, $fin) => self::OCUPADA,
                $canal === self::CANAL_LINEA && ! $dentroVentana => self::FUERA_VENTANA,
                $canal === self::CANAL_LINEA && $canalFicha !== self::CANAL_LINEA => self::OTRO_CANAL,
                default => self::LIBRE,
            };

            if ($estado === self::LIBRE) {
                $libres++;
            }

            $fichas[] = [
                'nro' => $nro,
                'hora_inicio' => $inicio->format('H:i'),
                'hora_fin' => $fin->format('H:i'),
                'canal' => $canalFicha,
                'estado' => $estado,
                'disponible' => $estado === self::LIBRE,
            ];
        }

        return [
            'id_horario' => $horario->id_horario,
            'hora_inicio' => $horario->hora_inicio_corta,
            'hora_fin' => $horario->hora_fin_corta,
            'duracion_min' => $horario->duracion_min,
            'consultorio' => $horario->consultorio,
            'capacidad' => $calculo['capacidad'],
            'cupos_linea' => $calculo['cupos_linea'],
            'cupos_mostrador' => $calculo['cupos_mostrador'],
            'libres' => $libres,
            'fichas' => $fichas,
        ];
    }

    /**
     * Citas del médico que ocupan tiempo ese día (RN09: las canceladas no
     * ocupan; los sobrecupos tampoco bloquean fichas normales).
     *
     * @return Collection<int, Cita>
     */
    private function citasOcupadas(int $idMedico, CarbonImmutable $dia, ?int $idCitaExcluir): Collection
    {
        return Cita::query()
            ->where('id_medico', $idMedico)
            ->whereDate('fecha', $dia->toDateString())
            ->where('estado', '<>', CitaEstadoService::CANCELADA)
            ->where('sobrecupo', false)
            ->when($idCitaExcluir, fn ($q) => $q->where('id_cita', '<>', $idCitaExcluir))
            ->get(['id_cita', 'hora_inicio', 'hora_fin']);
    }

    /** @return Collection<int, Indisponibilidad> */
    private function indisponibilidades(int $idMedico, CarbonImmutable $dia): Collection
    {
        return Indisponibilidad::query()
            ->where('id_medico', $idMedico)
            ->where('inicio', '<', $dia->addDay()->toDateTimeString())
            ->where('fin', '>', $dia->toDateTimeString())
            ->get(['inicio', 'fin']);
    }

    /** Dos intervalos [a, b) y [c, d) se cruzan si a < d y c < b. */
    private function hayCita(Collection $citas, CarbonImmutable $dia, CarbonImmutable $inicio, CarbonImmutable $fin): bool
    {
        return $citas->contains(fn (Cita $cita) => $this->enFecha($dia, $cita->hora_inicio)->lt($fin)
            && $inicio->lt($this->enFecha($dia, $cita->hora_fin)));
    }

    private function hayBloqueo(Collection $bloqueos, CarbonImmutable $inicio, CarbonImmutable $fin): bool
    {
        return $bloqueos->contains(fn (Indisponibilidad $bloqueo) => $bloqueo->inicio->lt($fin)
            && $inicio->lt($bloqueo->fin));
    }

    /** Combina una fecha con una hora "HH:MM[:SS]". */
    private function enFecha(CarbonImmutable $dia, string $hora): CarbonImmutable
    {
        [$h, $m] = array_map('intval', explode(':', $hora) + [0, 0]);

        return $dia->setTime($h, $m);
    }

    private function hms(string $hora): string
    {
        return substr($hora.':00', 0, 5).':00';
    }
}

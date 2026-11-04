<?php

namespace App\Services;

use App\Exceptions\FichaNoDisponibleException;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * CU13, CU15, CU16 · Programar, reprogramar y cancelar citas dentro de DB::transaction. Traduce SQLSTATE 23P01 a FichaNoDisponibleException.
 * CU17 · Registrar llegada (Confirmada) e inasistencia, manual o automática.
 *
 * Protección ante reservas simultáneas, en tres niveles:
 *   1. SELECT … FOR UPDATE sobre la fila del médico: dos reservas para el
 *      mismo médico se atienden de a una; la segunda espera, vuelve a
 *      verificar la ficha y recibe "La ficha ya no está disponible".
 *   2. La ficha se vuelve a calcular dentro de la transacción (nunca se
 *      confía en lo que mostró la pantalla).
 *   3. Última barrera en PostgreSQL: la restricción de exclusión
 *      ex_cita_sin_superposicion (btree_gist). Si igual se cuela un choque,
 *      la base rechaza el INSERT/UPDATE con SQLSTATE 23P01, la transacción
 *      se revierte y se traduce a FichaNoDisponibleException.
 */
class CitaService
{
    /** SQLSTATE de PostgreSQL para violación de una restricción EXCLUDE. */
    private const SQLSTATE_EXCLUSION = '23P01';

    /** Clave del candado de transacción que serializa los códigos CM-000123. */
    private const CANDADO_CODIGO_CITA = 4202613;

    public function __construct(
        private DisponibilidadService $disponibilidad,
        private CitaEstadoService $estados,
        private CodigoService $codigos,
        private NotificacionService $notificaciones,
        private BitacoraService $bitacora,
    ) {
    }

    /* ------------------------------------------------------------------
     | Consultas
     * ------------------------------------------------------------------ */

    /** Paciente vinculado a la cuenta, solo si el usuario tiene rol Paciente. */
    public function pacienteDe(User $usuario): ?Paciente
    {
        if ($usuario->rol?->nombre !== Rol::PACIENTE) {
            return null;
        }

        return Paciente::query()->where('id_usuario', $usuario->id_usuario)->first();
    }

    /** El paciente reserva en línea; el personal, en mostrador (RN16). */
    public function canalDe(User $usuario): string
    {
        return $this->pacienteDe($usuario)
            ? DisponibilidadService::CANAL_LINEA
            : DisponibilidadService::CANAL_MOSTRADOR;
    }

    /** Un paciente solo puede ver y operar sus propias citas. */
    public function puedeVer(Cita $cita, User $usuario): bool
    {
        if ($usuario->rol?->nombre !== Rol::PACIENTE) {
            return true;
        }

        return (int) $this->pacienteDe($usuario)?->id_paciente === (int) $cita->id_paciente;
    }

    /**
     * Listado de citas con filtros. El paciente ve solo las suyas.
     *
     * @param array{fecha?: ?string, medico?: ?int, estado?: ?string, buscar?: ?string} $filtros
     */
    public function listar(User $usuario, array $filtros): LengthAwarePaginator
    {
        $paciente = $this->pacienteDe($usuario);
        $esPaciente = $usuario->rol?->nombre === Rol::PACIENTE;

        return Cita::query()
            ->with(['paciente', 'medico', 'especialidad'])
            ->when($esPaciente, fn ($q) => $q->where('id_paciente', $paciente?->id_paciente ?? 0))
            ->when(filled($filtros['fecha'] ?? null), fn ($q) => $q->whereDate('fecha', $filtros['fecha']))
            ->when(filled($filtros['medico'] ?? null), fn ($q) => $q->where('id_medico', $filtros['medico']))
            ->when(filled($filtros['estado'] ?? null), fn ($q) => $q->where('estado', $filtros['estado']))
            ->when(filled($filtros['buscar'] ?? null), function ($q) use ($filtros) {
                $texto = '%'.mb_strtolower(trim($filtros['buscar'])).'%';

                $q->where(fn ($w) => $w
                    ->whereRaw('lower(codigo) like ?', [$texto])
                    ->orWhereHas('paciente', fn ($p) => $p
                        ->whereRaw('lower(ci) like ?', [$texto])
                        ->orWhereRaw('lower(codigo) like ?', [$texto])
                        ->orWhereRaw('lower(apellidos) like ?', [$texto])
                        ->orWhereRaw('lower(nombres) like ?', [$texto])));
            })
            ->orderByDesc('fecha')
            ->orderBy('hora_inicio')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Datos de la pantalla de fichas: especialidades activas y, por cada una,
     * los médicos activos que tienen al menos un turno activo (para el
     * selector encadenado especialidad → médico).
     *
     * También devuelve los días de la semana en que atiende cada par
     * médico-especialidad, para avisar en el calendario.
     *
     * @return array{especialidades: \Illuminate\Support\Collection, medicos: \Illuminate\Support\Collection, mapa: array<int, array<int>>, dias: array<string, array<int>>}
     */
    public function datosReserva(): array
    {
        $turnos = Horario::query()
            ->where('estado', true)
            ->whereHas('medico', fn ($q) => $q->where('estado', true))
            ->whereHas('especialidad', fn ($q) => $q->where('estado', true))
            ->get(['id_medico', 'id_especialidad', 'dia_semana']);

        $mapa = [];
        $dias = [];

        foreach ($turnos as $turno) {
            $mapa[$turno->id_especialidad][] = (int) $turno->id_medico;
            $dias[$turno->id_medico.'-'.$turno->id_especialidad][] = (int) $turno->dia_semana;
        }

        $mapa = array_map(fn ($ids) => array_values(array_unique($ids)), $mapa);
        $dias = array_map(fn ($d) => array_values(array_unique($d)), $dias);

        return [
            'especialidades' => Especialidad::query()->whereIn('id_especialidad', array_keys($mapa))->orderBy('nombre')->get(),
            'medicos' => Medico::query()->where('estado', true)->orderBy('apellidos')->orderBy('nombres')->get(),
            'mapa' => $mapa,
            'dias' => $dias,
        ];
    }

    /* ------------------------------------------------------------------
     | CU13 · Programar cita
     * ------------------------------------------------------------------ */

    /**
     * Reserva una ficha. $datos ya viene validado por ProgramarCitaRequest.
     *
     * @throws FichaNoDisponibleException si la ficha fue tomada por otra reserva
     * @throws ValidationException si se incumple alguna regla del negocio
     */
    public function programar(array $datos, User $actor): Cita
    {
        $pacienteActor = $this->pacienteDe($actor);

        if ($actor->rol?->nombre === Rol::PACIENTE && ! $pacienteActor) {
            throw ValidationException::withMessages(['id_paciente' => 'Su cuenta no está vinculada a un paciente.']);
        }

        // El paciente reserva para sí mismo y en línea; la recepción elige al paciente.
        $canal = $pacienteActor ? DisponibilidadService::CANAL_LINEA : DisponibilidadService::CANAL_MOSTRADOR;
        $idPaciente = $pacienteActor ? (int) $pacienteActor->id_paciente : (int) ($datos['id_paciente'] ?? 0);
        $sobrecupo = ! $pacienteActor && ! empty($datos['sobrecupo']);

        return $this->conProteccionConcurrencia(function () use ($datos, $actor, $canal, $idPaciente, $sobrecupo) {
            $idMedico = (int) $datos['id_medico'];
            $idEspecialidad = (int) $datos['id_especialidad'];
            $fecha = CarbonImmutable::parse($datos['fecha'])->toDateString();

            // Nivel 1: serializa las reservas del mismo médico y del mismo paciente.
            $this->bloquear($idMedico, $idPaciente);

            // Nivel 2: se recalcula la ficha con los datos confirmados.
            $ficha = $sobrecupo
                ? $this->disponibilidad->sobrecupo($idMedico, $idEspecialidad, $fecha, (int) $datos['id_horario'])
                : $this->disponibilidad->verificarFicha(
                    $idMedico, $idEspecialidad, $fecha, (int) $datos['id_horario'], (int) $datos['nro_cita'], $canal,
                );

            $this->validarPaciente($idPaciente, $idMedico, $fecha, $ficha['hora_inicio'], $ficha['hora_fin'], $canal);

            // Nivel 3: el INSERT pasa por la restricción de exclusión de PostgreSQL.
            $cita = Cita::create([
                'id_paciente' => $idPaciente,
                'id_medico' => $idMedico,
                'id_especialidad' => $idEspecialidad,
                'creado_por' => $actor->id_usuario,
                'codigo' => $this->siguienteCodigo(),
                'fecha' => $fecha,
                'hora_inicio' => $ficha['hora_inicio'],
                'hora_fin' => $ficha['hora_fin'],
                'nro_cita' => $ficha['nro'],
                'sobrecupo' => $sobrecupo,
                'canal' => $canal,
                'motivo' => $datos['motivo'] ?? null,
                'estado' => CitaEstadoService::PROGRAMADA,
                'estado_pago' => 'Pendiente',
                'veces_reprogramada' => 0,
            ]);

            $this->estados->registrarCreacion(
                $cita,
                sprintf('Reserva %s · ficha %d%s', mb_strtolower($canal), $cita->nro_cita, $sobrecupo ? ' (sobrecupo)' : ''),
                $actor->id_usuario,
            );

            $this->notificaciones->programarRecordatorio($cita);

            $this->bitacora->registrar(
                'CITA_PROGRAMADA',
                'cita',
                $cita->id_cita,
                sprintf('Cita %s · paciente %d · médico %d · %s %s · ficha %d', $cita->codigo, $idPaciente, $idMedico, $fecha, substr($ficha['hora_inicio'], 0, 5), $cita->nro_cita),
                $actor->id_usuario,
            );

            return $cita->load(['paciente', 'medico', 'especialidad']);
        });
    }

    /* ------------------------------------------------------------------
     | CU15 · Reprogramar cita
     * ------------------------------------------------------------------ */

    /**
     * Mueve la cita a otra ficha de la misma especialidad conservando el
     * código (RN27: una sola vez y con ≥ 24 h de anticipación). La recepción
     * puede forzar ambas reglas indicando un motivo.
     *
     * @throws FichaNoDisponibleException
     * @throws ValidationException
     */
    public function reprogramar(Cita $cita, array $datos, User $actor): Cita
    {
        $esPaciente = $actor->rol?->nombre === Rol::PACIENTE;
        $canal = $esPaciente ? DisponibilidadService::CANAL_LINEA : DisponibilidadService::CANAL_MOSTRADOR;
        $forzar = ! $esPaciente && ! empty($datos['forzar']);

        return $this->conProteccionConcurrencia(function () use ($cita, $datos, $actor, $canal, $forzar) {
            $cita = Cita::query()->lockForUpdate()->findOrFail($cita->id_cita);

            if ($cita->estado !== CitaEstadoService::PROGRAMADA) {
                throw ValidationException::withMessages(['cita' => 'Solo se pueden reprogramar citas en estado Programada.']);
            }

            if (! $forzar) {
                $horasMinimas = (int) config('clinica.reprogramacion_horas_antes', 24);

                if (CarbonImmutable::now()->addHours($horasMinimas)->gt($this->inicio($cita))) {
                    throw ValidationException::withMessages(['cita' => "Solo se puede reprogramar con al menos {$horasMinimas} horas de anticipación."]);
                }

                if ($cita->veces_reprogramada >= (int) config('clinica.reprogramacion_max_veces', 1)) {
                    throw ValidationException::withMessages(['cita' => 'Esta cita ya fue reprogramada el máximo de veces permitido.']);
                }
            }

            $idMedico = (int) $datos['id_medico'];
            $fecha = CarbonImmutable::parse($datos['fecha'])->toDateString();

            $this->bloquear($idMedico, (int) $cita->id_paciente);

            // RN27: misma especialidad; se excluye la propia cita al verificar.
            $ficha = $this->disponibilidad->verificarFicha(
                $idMedico, (int) $cita->id_especialidad, $fecha, (int) $datos['id_horario'], (int) $datos['nro_cita'], $canal, $cita->id_cita,
            );

            $this->validarPaciente((int) $cita->id_paciente, $idMedico, $fecha, $ficha['hora_inicio'], $ficha['hora_fin'], $canal, $cita->id_cita);

            $antes = sprintf('%s %s', $cita->fecha->format('d/m/Y'), substr((string) $cita->hora_inicio, 0, 5));
            $despues = sprintf('%s %s', CarbonImmutable::parse($fecha)->format('d/m/Y'), substr($ficha['hora_inicio'], 0, 5));
            $detalle = "Reprogramada de {$antes} a {$despues}".($forzar ? ' · forzada: '.trim((string) $datos['motivo_forzado']) : '');

            $this->estados->transicionar($cita, CitaEstadoService::PROGRAMADA, $detalle, $actor->id_usuario, [
                'id_medico' => $idMedico,
                'fecha' => $fecha,
                'hora_inicio' => $ficha['hora_inicio'],
                'hora_fin' => $ficha['hora_fin'],
                'nro_cita' => $ficha['nro'],
                'sobrecupo' => false,
                'veces_reprogramada' => $cita->veces_reprogramada + 1,
            ]);

            $this->notificaciones->programarRecordatorio($cita);
            $this->notificaciones->avisar($cita, NotificacionService::REPROGRAMACION);

            $this->bitacora->registrar('CITA_REPROGRAMADA', 'cita', $cita->id_cita, "Cita {$cita->codigo} · {$detalle}", $actor->id_usuario);

            return $cita->load(['paciente', 'medico', 'especialidad']);
        });
    }

    /* ------------------------------------------------------------------
     | CU16 · Cancelar cita
     * ------------------------------------------------------------------ */

    /**
     * Cancela una cita Programada con motivo obligatorio. La fila nunca se
     * borra: pasa a Cancelada y deja de ocupar la ficha (RN09), porque la
     * restricción de exclusión ignora las canceladas.
     * Ciclo 2: reembolso total si faltan ≥ 24 h (RN26).
     */
    public function cancelar(Cita $cita, string $motivo, User $actor): Cita
    {
        return DB::transaction(function () use ($cita, $motivo, $actor) {
            $cita = Cita::query()->lockForUpdate()->findOrFail($cita->id_cita);

            if ($cita->estado !== CitaEstadoService::PROGRAMADA) {
                throw ValidationException::withMessages(['motivo_cancelacion' => 'Solo se pueden cancelar citas en estado Programada.']);
            }

            if ($this->inicio($cita)->lte(CarbonImmutable::now())) {
                throw ValidationException::withMessages(['motivo_cancelacion' => 'La hora de la cita ya pasó: corresponde registrar asistencia o inasistencia.']);
            }

            $motivo = trim($motivo);
            $horasReembolso = (int) config('clinica.cancelacion_reembolso_horas', 24);
            $conAnticipacion = CarbonImmutable::now()->addHours($horasReembolso)->lte($this->inicio($cita));

            $this->estados->transicionar(
                $cita,
                CitaEstadoService::CANCELADA,
                $motivo.($conAnticipacion ? '' : " (menos de {$horasReembolso} h de anticipación)"),
                $actor->id_usuario,
                ['motivo_cancelacion' => mb_substr($motivo, 0, 200)],
            );

            $this->notificaciones->avisar($cita, NotificacionService::CANCELACION);

            $this->bitacora->registrar('CITA_CANCELADA', 'cita', $cita->id_cita, "Cita {$cita->codigo} cancelada: {$motivo}", $actor->id_usuario);

            return $cita;
        });
    }

    /* ------------------------------------------------------------------
     | CU17 · Registrar asistencia e inasistencia
     * ------------------------------------------------------------------ */

    /**
     * El paciente llega a recepción: Programada → Confirmada (RN24).
     * Se acepta el mismo día hasta que vence la tolerancia de inasistencia;
     * después de la hora límite de llegada se anota como llegada tardía.
     */
    public function registrarLlegada(Cita $cita, User $actor): Cita
    {
        return DB::transaction(function () use ($cita, $actor) {
            $cita = Cita::query()->lockForUpdate()->findOrFail($cita->id_cita);
            $ahora = CarbonImmutable::now();
            $inicio = $this->inicio($cita);

            if ($cita->estado !== CitaEstadoService::PROGRAMADA) {
                throw ValidationException::withMessages(['cita' => 'Solo se puede registrar la llegada de una cita Programada.']);
            }

            if (! $cita->fecha->isSameDay($ahora)) {
                throw ValidationException::withMessages(['cita' => 'La llegada solo se registra el día de la cita.']);
            }

            if ($ahora->gt($inicio->addMinutes((int) config('clinica.inasistencia_min_despues', 15)))) {
                throw ValidationException::withMessages(['cita' => 'Pasó la tolerancia de llegada: corresponde registrar inasistencia.']);
            }

            $tardia = $ahora->gt($inicio->subMinutes((int) config('clinica.llegada_min_antes', 15)));
            $detalle = 'Llegada a recepción '.$ahora->format('H:i').($tardia ? ' (tardía)' : '');

            $this->estados->transicionar($cita, CitaEstadoService::CONFIRMADA, $detalle, $actor->id_usuario, [
                'hora_llegada' => $ahora,
            ]);

            $this->bitacora->registrar('CITA_CONFIRMADA', 'cita', $cita->id_cita, "Cita {$cita->codigo} · {$detalle}", $actor->id_usuario);

            return $cita;
        });
    }

    /**
     * Inasistencia manual: Programada (ya pasó la hora) o Confirmada (el
     * paciente se retiró sin ser atendido) → Inasistencia.
     */
    public function registrarInasistencia(Cita $cita, ?string $detalle, User $actor): Cita
    {
        return DB::transaction(function () use ($cita, $detalle, $actor) {
            $cita = Cita::query()->lockForUpdate()->findOrFail($cita->id_cita);

            if ($cita->estado === CitaEstadoService::PROGRAMADA && $this->inicio($cita)->gt(CarbonImmutable::now())) {
                throw ValidationException::withMessages(['cita' => 'Aún no llega la hora de la cita.']);
            }

            $detalle = trim((string) $detalle) ?: ($cita->estado === CitaEstadoService::CONFIRMADA
                ? 'Se retiró sin ser atendido'
                : 'No se presentó');

            $this->estados->transicionar($cita, CitaEstadoService::INASISTENCIA, $detalle, $actor->id_usuario);

            $this->bitacora->registrar('CITA_INASISTENCIA', 'cita', $cita->id_cita, "Cita {$cita->codigo} · {$detalle}", $actor->id_usuario);

            return $cita;
        });
    }

    /**
     * Temporizador (RN25): marca Inasistencia las citas Programada cuya hora
     * más la tolerancia ya pasó. id_usuario = NULL en el historial.
     *
     * @return int cantidad de citas marcadas
     */
    public function marcarInasistenciasAutomaticas(): int
    {
        $limite = CarbonImmutable::now()->subMinutes((int) config('clinica.inasistencia_min_despues', 15));
        $marcadas = 0;

        $candidatas = Cita::query()
            ->where('estado', CitaEstadoService::PROGRAMADA)
            ->whereDate('fecha', '<=', $limite->toDateString())
            ->get();

        foreach ($candidatas as $cita) {
            if ($this->inicio($cita)->gte($limite)) {
                continue;
            }

            try {
                DB::transaction(function () use ($cita) {
                    $cita = Cita::query()->lockForUpdate()->find($cita->id_cita);

                    if ($cita?->estado !== CitaEstadoService::PROGRAMADA) {
                        return;
                    }

                    $this->estados->transicionar($cita, CitaEstadoService::INASISTENCIA, 'Inasistencia automática (temporizador)', null);
                    $this->bitacora->registrar('CITA_INASISTENCIA_AUTO', 'cita', $cita->id_cita, "Cita {$cita->codigo} sin llegada");
                });
                $marcadas++;
            } catch (Throwable $e) {
                Log::error('No se pudo marcar la inasistencia automática', ['id_cita' => $cita->id_cita, 'error' => $e->getMessage()]);
            }
        }

        return $marcadas;
    }

    /* ------------------------------------------------------------------
     | Reglas internas
     * ------------------------------------------------------------------ */

    /**
     * Reglas del paciente: activo (RN05), límites de RN19, penalización de
     * RN31 para reservas en línea y sin dos citas a la misma hora.
     *
     * @throws ValidationException
     */
    private function validarPaciente(
        int $idPaciente,
        int $idMedico,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        string $canal,
        ?int $idCitaExcluir = null,
    ): void {
        $paciente = Paciente::query()->find($idPaciente);

        if (! $paciente || ! $paciente->estado) {
            throw ValidationException::withMessages(['id_paciente' => 'El paciente no existe o está inactivo.']);
        }

        $activas = Cita::query()
            ->where('id_paciente', $idPaciente)
            ->whereIn('estado', CitaEstadoService::ACTIVOS)
            ->when($idCitaExcluir, fn ($q) => $q->where('id_cita', '<>', $idCitaExcluir));

        $maxMedicoDia = (int) config('clinica.max_citas_medico_dia', 1);
        if ((clone $activas)->where('id_medico', $idMedico)->whereDate('fecha', $fecha)->count() >= $maxMedicoDia) {
            throw ValidationException::withMessages(['id_paciente' => "El paciente ya tiene {$maxMedicoDia} cita(s) con este médico ese día."]);
        }

        $maxActivas = (int) config('clinica.max_citas_activas', 3);
        if ((clone $activas)->whereDate('fecha', '>=', CarbonImmutable::today()->toDateString())->count() >= $maxActivas) {
            throw ValidationException::withMessages(['id_paciente' => "El paciente ya tiene {$maxActivas} citas activas (máximo permitido)."]);
        }

        $choca = (clone $activas)
            ->whereDate('fecha', $fecha)
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->exists();

        if ($choca) {
            throw ValidationException::withMessages(['id_paciente' => 'El paciente ya tiene otra cita a esa misma hora.']);
        }

        if ($canal === DisponibilidadService::CANAL_LINEA) {
            $dias = (int) config('clinica.penalizacion_dias', 90);
            $limite = (int) config('clinica.penalizacion_inasistencias', 3);

            $inasistencias = Cita::query()
                ->where('id_paciente', $idPaciente)
                ->where('estado', CitaEstadoService::INASISTENCIA)
                ->whereDate('fecha', '>=', CarbonImmutable::today()->subDays($dias)->toDateString())
                ->count();

            if ($inasistencias >= $limite) {
                throw ValidationException::withMessages(['id_paciente' => "Tiene {$inasistencias} inasistencias en los últimos {$dias} días: solo puede reservar en recepción."]);
            }
        }
    }

    /**
     * SELECT … FOR UPDATE sobre médico y paciente, siempre en ese orden
     * para no provocar interbloqueos entre transacciones.
     */
    private function bloquear(int $idMedico, int $idPaciente): void
    {
        Medico::query()->whereKey($idMedico)->lockForUpdate()->first();
        Paciente::query()->whereKey($idPaciente)->lockForUpdate()->first();
    }

    /**
     * CM-000123. En PostgreSQL se toma un candado de transacción para que
     * dos reservas simultáneas no calculen el mismo correlativo.
     */
    private function siguienteCodigo(): string
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select('SELECT pg_advisory_xact_lock(?)', [self::CANDADO_CODIGO_CITA]);
        }

        return $this->codigos->cita();
    }

    /**
     * Ejecuta la operación en una transacción y traduce la violación de la
     * restricción de exclusión (23P01) al mensaje de negocio.
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    private function conProteccionConcurrencia(callable $operacion): mixed
    {
        try {
            return DB::transaction($operacion);
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? (string) $e->getCode();

            if ($sqlState === self::SQLSTATE_EXCLUSION) {
                throw new FichaNoDisponibleException();
            }

            throw $e;
        }
    }

    /** Fecha y hora de inicio de la cita. */
    private function inicio(Cita $cita): CarbonImmutable
    {
        return CarbonImmutable::parse($cita->fecha->toDateString().' '.substr((string) $cita->hora_inicio, 0, 8));
    }
}

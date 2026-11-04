<?php

namespace App\Services;

use App\Models\Atencion;
use App\Models\Cita;
use App\Models\Medico;
use App\Models\Rol;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Paquete 5 · Atención médica.
 *
 * CU20 Consultar agenda médica: agenda diaria y resumen semanal por médico.
 * CU21 Registrar atención médica: diagnóstico, tratamiento y signos vitales.
 */
class AtencionService
{
    public function __construct(
        private CitaEstadoService $estados,
        private BitacoraService $bitacora,
    ) {
    }

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

    /* ------------------------------------------------------------------
     | CU21 · Registrar atención médica
     * ------------------------------------------------------------------ */

    /**
     * ¿Puede este usuario atender esta cita? Debe ser el médico de la cita
     * (el permiso atencion.registrar lo verifica el middleware).
     */
    public function puedeAtender(Cita $cita, User $usuario): bool
    {
        $medico = $this->medicoDe($usuario);

        return $medico !== null && (int) $medico->id_medico === (int) $cita->id_medico;
    }

    /**
     * Datos del panel lateral: paciente, antecedentes y últimas atenciones.
     *
     * @return array{paciente: \App\Models\Paciente, antecedente: ?\App\Models\Antecedente, ultimas: Collection<int, Cita>}
     */
    public function contextoClinico(Cita $cita): array
    {
        $cita->loadMissing(['paciente.antecedente', 'especialidad', 'medico']);

        $ultimas = Cita::query()
            ->with(['atencion', 'medico:id_medico,nombres,apellidos', 'especialidad:id_especialidad,nombre'])
            ->where('id_paciente', $cita->id_paciente)
            ->where('id_cita', '<>', $cita->id_cita)
            ->where('estado', CitaEstadoService::ATENDIDA)
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->limit(5)
            ->get();

        return [
            'paciente' => $cita->paciente,
            'antecedente' => $cita->paciente?->antecedente,
            'ultimas' => $ultimas,
        ];
    }

    /**
     * Registra la atención y marca la cita como Atendida (RN30: solo una
     * cita Confirmada; RN10: una Atendida ya no cambia). $datos ya viene
     * validado por RegistrarAtencionRequest.
     *
     * @throws ValidationException
     */
    public function registrar(Cita $cita, array $datos, User $usuario): Atencion
    {
        if (! $this->puedeAtender($cita, $usuario)) {
            throw ValidationException::withMessages(['cita' => 'Solo el médico de la cita puede registrar su atención.']);
        }

        return DB::transaction(function () use ($cita, $datos, $usuario) {
            // Se bloquea la fila y se vuelve a leer el estado: evita dos registros simultáneos.
            $cita = Cita::query()->whereKey($cita->id_cita)->lockForUpdate()->firstOrFail();

            if ($cita->estado !== CitaEstadoService::CONFIRMADA) {
                throw ValidationException::withMessages([
                    'cita' => "Solo se puede atender una cita Confirmada (estado actual: {$cita->estado}).",
                ]);
            }

            if (Atencion::query()->where('id_cita', $cita->id_cita)->exists()) {
                throw ValidationException::withMessages(['cita' => 'Esta cita ya tiene una atención registrada.']);
            }

            $atencion = Atencion::create([
                'id_cita' => $cita->id_cita,
                'diagnostico' => $datos['diagnostico'],
                'tratamiento' => $datos['tratamiento'] ?? null,
                'peso_kg' => $datos['peso_kg'] ?? null,
                'talla_cm' => $datos['talla_cm'] ?? null,
                'presion_arterial' => $datos['presion_arterial'] ?? null,
                'temperatura_c' => $datos['temperatura_c'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $this->estados->transicionar($cita, CitaEstadoService::ATENDIDA, 'Atención registrada por el médico', $usuario->id_usuario);

            $this->bitacora->registrar(
                'ATENCION_REGISTRADA',
                'atencion',
                $atencion->id_atencion,
                sprintf('Cita %s · paciente %d · diagnóstico: %s', $cita->codigo, $cita->id_paciente, mb_substr($datos['diagnostico'], 0, 120)),
                $usuario->id_usuario,
            );

            return $atencion;
        });
    }
}

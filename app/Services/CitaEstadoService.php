<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\HistorialEstadoCita;
use Illuminate\Validation\ValidationException;

/**
 * Máquina de estados de la cita (RF10).
 *
 * Todo cambio de estado pasa por transicionar(): valida la transición
 * contra TRANSICIONES y escribe historial_estado_cita.
 */
class CitaEstadoService
{
    public const PROGRAMADA   = 'Programada';
    public const CONFIRMADA   = 'Confirmada';
    public const ATENDIDA     = 'Atendida';
    public const CANCELADA    = 'Cancelada';
    public const INASISTENCIA = 'Inasistencia';

    /** Estados que ocupan la ficha y cuentan como citas activas (RN19). */
    public const ACTIVOS = [self::PROGRAMADA, self::CONFIRMADA];

    /** Estado actual => estados a los que puede pasar. */
    public const TRANSICIONES = [
        self::PROGRAMADA   => [self::PROGRAMADA, self::CONFIRMADA, self::CANCELADA, self::INASISTENCIA],
        self::CONFIRMADA   => [self::ATENDIDA, self::INASISTENCIA],
        self::ATENDIDA     => [],
        self::CANCELADA    => [],
        self::INASISTENCIA => [],
    ];

    /** ¿Está permitida la transición desde el estado actual? */
    public function puede(Cita $cita, string $nuevo): bool
    {
        return in_array($nuevo, self::TRANSICIONES[$cita->estado] ?? [], true);
    }

    /**
     * Cambia el estado de la cita y deja constancia en el historial.
     * $idUsuario = null significa que la transición la hizo el temporizador.
     * Debe llamarse dentro de la transacción del servicio que lo invoca.
     *
     * @param array<string, mixed> $cambios columnas extra a guardar junto con el estado
     * @throws ValidationException si la transición no está permitida
     */
    public function transicionar(Cita $cita, string $nuevo, ?string $detalle, ?int $idUsuario, array $cambios = []): Cita
    {
        if (! $this->puede($cita, $nuevo)) {
            throw ValidationException::withMessages([
                'estado' => "No se puede pasar una cita de {$cita->estado} a {$nuevo}.",
            ]);
        }

        $anterior = $cita->estado;

        $cita->forceFill(array_merge($cambios, ['estado' => $nuevo]))->save();

        $this->registrarHistorial($cita, $anterior, $nuevo, $detalle, $idUsuario);

        return $cita;
    }

    /** Primera fila del historial de una cita recién reservada: (nueva) → Programada. */
    public function registrarCreacion(Cita $cita, ?string $detalle, ?int $idUsuario): void
    {
        $this->registrarHistorial($cita, null, $cita->estado, $detalle, $idUsuario);
    }

    private function registrarHistorial(Cita $cita, ?string $anterior, string $nuevo, ?string $detalle, ?int $idUsuario): void
    {
        HistorialEstadoCita::create([
            'id_cita' => $cita->id_cita,
            'id_usuario' => $idUsuario,
            'estado_anterior' => $anterior,
            'estado_nuevo' => $nuevo,
            'detalle' => $detalle !== null ? mb_substr($detalle, 0, 200) : null,
        ]);
    }
}

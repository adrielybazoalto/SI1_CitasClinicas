<?php

namespace App\Services;

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

    /** Estado actual => estados a los que puede pasar. */
    public const TRANSICIONES = [
        self::PROGRAMADA   => [self::PROGRAMADA, self::CONFIRMADA, self::CANCELADA, self::INASISTENCIA],
        self::CONFIRMADA   => [self::ATENDIDA, self::INASISTENCIA],
        self::ATENDIDA     => [],
        self::CANCELADA    => [],
        self::INASISTENCIA => [],
    ];
}

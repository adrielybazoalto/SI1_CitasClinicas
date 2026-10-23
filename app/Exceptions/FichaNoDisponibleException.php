<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando la ficha ya fue tomada por otra reserva
 * (violación de la restricción de exclusión, SQLSTATE 23P01).
 */
class FichaNoDisponibleException extends RuntimeException
{
    public function __construct(string $mensaje = 'La ficha ya no está disponible.')
    {
        parent::__construct($mensaje);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cobro o reembolso de una cita (ciclo 2).
 * Tabla: pago (ver vida_salud.sql).
 */
class Pago extends Model
{
    protected $table = 'pago';
    protected $primaryKey = 'id_pago';
    public $timestamps = false;
}

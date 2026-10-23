<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Comprobante correlativo de un pago (ciclo 2).
 * Tabla: comprobante (ver vida_salud.sql).
 */
class Comprobante extends Model
{
    protected $table = 'comprobante';
    protected $primaryKey = 'id_comprobante';
    public $timestamps = false;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Caja de recepción: una sola ABIERTA por usuario (ciclo 2).
 * Tabla: caja (ver vida_salud.sql).
 */
class Caja extends Model
{
    protected $table = 'caja';
    protected $primaryKey = 'id_caja';
    public $timestamps = false;
}

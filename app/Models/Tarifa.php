<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tarifa vigente por especialidad (ciclo 2).
 * Tabla: tarifa (ver vida_salud.sql).
 */
class Tarifa extends Model
{
    protected $table = 'tarifa';
    protected $primaryKey = 'id_tarifa';
    public $timestamps = false;
}

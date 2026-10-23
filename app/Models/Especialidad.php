<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Especialidad médica con duración de consulta (10–120 min).
 * Tabla: especialidad (ver vida_salud.sql).
 */
class Especialidad extends Model
{
    protected $table = 'especialidad';
    protected $primaryKey = 'id_especialidad';
    public $timestamps = false;
}

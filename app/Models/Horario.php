<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Días y horas en que un médico atiende una especialidad; genera las fichas.
 * Tabla: horario (ver vida_salud.sql).
 */
class Horario extends Model
{
    protected $table = 'horario';
    protected $primaryKey = 'id_horario';
    public $timestamps = false;
}

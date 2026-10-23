<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Paciente registrado presencialmente (código PAC-000001).
 * Tabla: paciente (ver vida_salud.sql).
 */
class Paciente extends Model
{
    protected $table = 'paciente';
    protected $primaryKey = 'id_paciente';
    public $timestamps = false;
}

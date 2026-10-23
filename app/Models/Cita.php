<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ficha asignada a un paciente (código CM-000123).
 * Tabla: cita (ver vida_salud.sql).
 */
class Cita extends Model
{
    protected $table = 'cita';
    protected $primaryKey = 'id_cita';
    public $timestamps = false;
}

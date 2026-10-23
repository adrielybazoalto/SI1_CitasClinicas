<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rol del sistema (Administrador, Recepcionista, Médico, Paciente).
 * Tabla: rol (ver vida_salud.sql).
 */
class Rol extends Model
{
    protected $table = 'rol';
    protected $primaryKey = 'id_rol';
    public $timestamps = false;
}

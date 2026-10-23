<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permiso identificado por código, ej. cita.programar.
 * Tabla: permiso (ver vida_salud.sql).
 */
class Permiso extends Model
{
    protected $table = 'permiso';
    protected $primaryKey = 'id_permiso';
    public $timestamps = false;
}

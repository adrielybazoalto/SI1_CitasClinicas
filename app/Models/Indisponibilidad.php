<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bloqueo de agenda de un médico.
 * Tabla: indisponibilidad (ver vida_salud.sql).
 */
class Indisponibilidad extends Model
{
    protected $table = 'indisponibilidad';
    protected $primaryKey = 'id_indisponibilidad';
    public $timestamps = false;
}

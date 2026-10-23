<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Atención médica registrada sobre una cita Confirmada.
 * Tabla: atencion (ver vida_salud.sql).
 */
class Atencion extends Model
{
    protected $table = 'atencion';
    protected $primaryKey = 'id_atencion';
    public $timestamps = false;
}

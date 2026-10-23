<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Médico (código MED-0001, matrícula única).
 * Tabla: medico (ver vida_salud.sql).
 */
class Medico extends Model
{
    protected $table = 'medico';
    protected $primaryKey = 'id_medico';
    public $timestamps = false;
}

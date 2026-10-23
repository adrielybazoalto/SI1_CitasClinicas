<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Antecedentes clínicos del paciente (PK = FK a paciente).
 * Tabla: antecedente (ver vida_salud.sql).
 */
class Antecedente extends Model
{
    protected $table = 'antecedente';
    protected $primaryKey = 'id_paciente';
    public $timestamps = false;
}

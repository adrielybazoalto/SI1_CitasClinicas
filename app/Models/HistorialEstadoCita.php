<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Historial de transiciones de estado de una cita.
 * Tabla: historial_estado_cita (ver vida_salud.sql).
 */
class HistorialEstadoCita extends Model
{
    protected $table = 'historial_estado_cita';
    protected $primaryKey = 'id_historial';
    public $timestamps = false;
}

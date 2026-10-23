<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Recordatorios programados (−24 h) y otros avisos por correo.
 * Tabla: notificacion (ver vida_salud.sql).
 */
class Notificacion extends Model
{
    protected $table = 'notificacion';
    protected $primaryKey = 'id_notificacion';
    public $timestamps = false;
}

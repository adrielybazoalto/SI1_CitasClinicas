<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro inmutable de acciones (trigger tg_bitacora_inmutable).
 * Tabla: bitacora (ver vida_salud.sql).
 */
class Bitacora extends Model
{
    protected $table = 'bitacora';
    protected $primaryKey = 'id_bitacora';
    public $timestamps = false;
}

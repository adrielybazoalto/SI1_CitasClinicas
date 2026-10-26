<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permiso identificado por código, ej. cita.programar.
 * Tabla: permiso (ver vida_salud.sql).
 */
class Permiso extends Model
{
    /** Permiso que el rol Administrador no puede perder (CU03). */
    const GESTION_ROLES = 'seguridad.roles';

    protected $table = 'permiso';
    protected $primaryKey = 'id_permiso';
    public $timestamps = false;

    protected $fillable = ['codigo', 'descripcion', 'paquete'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'id_permiso', 'id_rol');
    }
}

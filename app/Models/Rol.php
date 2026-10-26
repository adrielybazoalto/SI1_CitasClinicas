<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rol del sistema (Administrador, Recepcionista, Médico, Paciente).
 * Tabla: rol (ver vida_salud.sql).
 */
class Rol extends Model
{
    /** Nombre del rol protegido: nunca pierde la gestión de roles (CU03). */
    const ADMINISTRADOR = 'Administrador';

    protected $table = 'rol';
    protected $primaryKey = 'id_rol';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    protected function casts(): array
    {
        return ['estado' => 'boolean'];
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'id_rol', 'id_permiso');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'id_rol', 'id_rol');
    }

    public function esAdministrador(): bool
    {
        return $this->nombre === self::ADMINISTRADOR;
    }
}

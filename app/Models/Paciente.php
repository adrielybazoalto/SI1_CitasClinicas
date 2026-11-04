<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Paciente registrado presencialmente (código PAC-000001).
 * Tabla: paciente (ver vida_salud.sql).
 */
class Paciente extends Model
{
    protected $table = 'paciente';
    protected $primaryKey = 'id_paciente';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_usuario',
        'codigo',
        'ci',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'correo',
        'direccion',
        'recibe_recordatorios',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'recibe_recordatorios' => 'boolean',
            'estado' => 'boolean',
            'creado_en' => 'datetime',
        ];
    }

    /** Cuenta de acceso asociada (rol Paciente). */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /** Antecedentes clínicos (relación 1:1, PK compartida). */
    public function antecedente(): HasOne
    {
        return $this->hasOne(Antecedente::class, 'id_paciente', 'id_paciente');
    }

    /** Citas del paciente (incluye canceladas e inasistencias). */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente', 'id_paciente');
    }

    /** Recordatorios y avisos programados. */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_paciente', 'id_paciente');
    }
}

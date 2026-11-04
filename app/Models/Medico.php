<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Médico (código MED-0001, matrícula única).
 * Tabla: medico (ver vida_salud.sql).
 */
class Medico extends Model
{
    protected $table = 'medico';
    protected $primaryKey = 'id_medico';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'codigo',
        'matricula',
        'nombres',
        'apellidos',
        'telefono',
        'correo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    /** Nombre para mostrar en listados y selectores. */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->apellidos.', '.$this->nombres);
    }

    /** Cuenta de acceso del médico (rol Médico). */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /** Especialidades que atiende (mínimo una, CU08). */
    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(
            Especialidad::class,
            'medico_especialidad',
            'id_medico',
            'id_especialidad',
        );
    }

    /** Horarios semanales de atención. */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_medico', 'id_medico');
    }

    /** Bloqueos de agenda (vacaciones, licencias, etc.). */
    public function indisponibilidades(): HasMany
    {
        return $this->hasMany(Indisponibilidad::class, 'id_medico', 'id_medico');
    }

    /** Citas históricas y futuras del médico. */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_medico', 'id_medico');
    }
}

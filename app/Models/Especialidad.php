<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Especialidad médica con duración de consulta (10–120 min).
 * Tabla: especialidad (ver vida_salud.sql).
 */
class Especialidad extends Model
{
    protected $table = 'especialidad';
    protected $primaryKey = 'id_especialidad';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'duracion_min',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'duracion_min' => 'integer',
            'estado' => 'boolean',
        ];
    }

    /** Médicos que ofrecen esta especialidad. */
    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(
            Medico::class,
            'medico_especialidad',
            'id_especialidad',
            'id_medico',
        );
    }

    /** Horarios que atienden esta especialidad. */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_especialidad', 'id_especialidad');
    }

    /** Tarifas vigentes e históricas (ciclo 2). */
    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class, 'id_especialidad', 'id_especialidad');
    }

    /** Citas registradas para esta especialidad. */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_especialidad', 'id_especialidad');
    }
}

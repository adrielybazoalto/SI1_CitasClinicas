<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Días y horas en que un médico atiende una especialidad; genera las fichas.
 * Tabla: horario (ver vida_salud.sql).
 */
class Horario extends Model
{
    protected $table = 'horario';
    protected $primaryKey = 'id_horario';
    public $timestamps = false;

    protected $fillable = [
        'id_medico',
        'id_especialidad',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'duracion_min',
        'consultorio',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'duracion_min' => 'integer',
            'estado' => 'boolean',
        ];
    }

    /** Días de la semana según el CHECK dia_semana BETWEEN 1 AND 7. */
    public const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public function getDiaNombreAttribute(): string
    {
        return self::DIAS[$this->dia_semana] ?? '—';
    }

    /** Hora en formato HH:MM para formularios y listados. */
    public function getHoraInicioCortaAttribute(): string
    {
        return substr((string) $this->hora_inicio, 0, 5);
    }

    public function getHoraFinCortaAttribute(): string
    {
        return substr((string) $this->hora_fin, 0, 5);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }
}

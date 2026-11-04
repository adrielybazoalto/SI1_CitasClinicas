<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloqueo de agenda de un médico.
 * Tabla: indisponibilidad (ver vida_salud.sql).
 */
class Indisponibilidad extends Model
{
    protected $table = 'indisponibilidad';
    protected $primaryKey = 'id_indisponibilidad';
    public $timestamps = false;

    protected $fillable = [
        'id_medico',
        'registrado_por',
        'inicio',
        'fin',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fin' => 'datetime',
        ];
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    /** Usuario que registró el bloqueo (admin o recepción). */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por', 'id_usuario');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de transiciones de estado de una cita.
 * Tabla: historial_estado_cita (ver vida_salud.sql).
 */
class HistorialEstadoCita extends Model
{
    protected $table = 'historial_estado_cita';
    protected $primaryKey = 'id_historial';

    const CREATED_AT = 'fecha_hora';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_cita',
        'id_usuario',
        'estado_anterior',
        'estado_nuevo',
        'detalle',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    /** Autor de la transición (NULL si la hizo el temporizador). */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}

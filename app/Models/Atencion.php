<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Atención médica registrada sobre una cita Confirmada.
 * Tabla: atencion (ver vida_salud.sql).
 */
class Atencion extends Model
{
    protected $table = 'atencion';
    protected $primaryKey = 'id_atencion';

    const CREATED_AT = 'registrado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_cita',
        'diagnostico',
        'tratamiento',
        'peso_kg',
        'talla_cm',
        'presion_arterial',
        'temperatura_c',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'peso_kg' => 'decimal:2',
            'talla_cm' => 'decimal:1',
            'temperatura_c' => 'decimal:1',
            'registrado_en' => 'datetime',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }
}

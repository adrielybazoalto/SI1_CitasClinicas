<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarifa vigente por especialidad (ciclo 2).
 * Tabla: tarifa (ver vida_salud.sql).
 */
class Tarifa extends Model
{
    protected $table = 'tarifa';
    protected $primaryKey = 'id_tarifa';
    public $timestamps = false;

    protected $fillable = [
        'id_especialidad',
        'monto',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }
}

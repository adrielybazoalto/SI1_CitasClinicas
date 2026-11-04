<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comprobante correlativo de un pago (ciclo 2).
 * Tabla: comprobante (ver vida_salud.sql).
 */
class Comprobante extends Model
{
    protected $table = 'comprobante';
    protected $primaryKey = 'id_comprobante';

    const CREATED_AT = 'fecha_emision';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_pago',
        'numero',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'fecha_emision' => 'datetime',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pago', 'id_pago');
    }
}

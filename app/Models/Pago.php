<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Cobro o reembolso de una cita (ciclo 2).
 * Tabla: pago (ver vida_salud.sql).
 */
class Pago extends Model
{
    protected $table = 'pago';
    protected $primaryKey = 'id_pago';

    const CREATED_AT = 'fecha_hora';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_cita',
        'id_caja',
        'registrado_por',
        'tipo',
        'metodo',
        'monto',
        'id_transaccion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_hora' => 'datetime',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    /** Usuario que registró el movimiento (NULL si fue la pasarela). */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por', 'id_usuario');
    }

    public function comprobante(): HasOne
    {
        return $this->hasOne(Comprobante::class, 'id_pago', 'id_pago');
    }
}

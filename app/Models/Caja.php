<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Caja de recepción: una sola ABIERTA por usuario (ciclo 2).
 * Tabla: caja (ver vida_salud.sql).
 */
class Caja extends Model
{
    protected $table = 'caja';
    protected $primaryKey = 'id_caja';

    const CREATED_AT = 'apertura';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_usuario',
        'cierre',
        'monto_inicial',
        'monto_esperado',
        'monto_contado',
        'diferencia',
        'observacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'apertura' => 'datetime',
            'cierre' => 'datetime',
            'monto_inicial' => 'decimal:2',
            'monto_esperado' => 'decimal:2',
            'monto_contado' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    /** Recepcionista responsable de la caja. */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /** Cobros y reembolsos registrados en esta caja. */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_caja', 'id_caja');
    }
}

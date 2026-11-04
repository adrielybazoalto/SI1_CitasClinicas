<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Ficha asignada a un paciente (código CM-000123).
 * Tabla: cita (ver vida_salud.sql).
 */
class Cita extends Model
{
    protected $table = 'cita';
    protected $primaryKey = 'id_cita';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_paciente',
        'id_medico',
        'id_especialidad',
        'creado_por',
        'codigo',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'nro_cita',
        'sobrecupo',
        'canal',
        'motivo',
        'estado',
        'estado_pago',
        'monto',
        'vence_pago_en',
        'veces_reprogramada',
        'motivo_cancelacion',
        'hora_llegada',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'nro_cita' => 'integer',
            'sobrecupo' => 'boolean',
            'monto' => 'decimal:2',
            'vence_pago_en' => 'datetime',
            'veces_reprogramada' => 'integer',
            'hora_llegada' => 'datetime',
            'creado_en' => 'datetime',
        ];
    }

    /** Hora en formato HH:MM para listados. */
    public function getHoraInicioCortaAttribute(): string
    {
        return substr((string) $this->hora_inicio, 0, 5);
    }

    public function getHoraFinCortaAttribute(): string
    {
        return substr((string) $this->hora_fin, 0, 5);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }

    /** Usuario que registró la cita (recepción o el propio paciente). */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por', 'id_usuario');
    }

    /** Atención médica (solo si la cita fue Atendida). */
    public function atencion(): HasOne
    {
        return $this->hasOne(Atencion::class, 'id_cita', 'id_cita');
    }

    /** Cobros y reembolsos asociados (ciclo 2). */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_cita', 'id_cita');
    }

    /** Historial de transiciones de estado (RF10). */
    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoCita::class, 'id_cita', 'id_cita');
    }

    /** Recordatorios y avisos de esta cita. */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_cita', 'id_cita');
    }
}

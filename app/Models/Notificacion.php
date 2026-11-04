<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recordatorios programados (−24 h) y otros avisos por correo.
 * Tabla: notificacion (ver vida_salud.sql).
 */
class Notificacion extends Model
{
    protected $table = 'notificacion';
    protected $primaryKey = 'id_notificacion';
    public $timestamps = false;

    protected $fillable = [
        'id_paciente',
        'id_cita',
        'tipo',
        'programada_para',
        'enviada_en',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'programada_para' => 'datetime',
            'enviada_en' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }
}

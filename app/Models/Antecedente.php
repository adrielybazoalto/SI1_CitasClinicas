<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Antecedentes clínicos del paciente (PK = FK a paciente).
 * Tabla: antecedente (ver vida_salud.sql).
 */
class Antecedente extends Model
{
    protected $table = 'antecedente';
    protected $primaryKey = 'id_paciente';
    public $incrementing = false;
    protected $keyType = 'int';

    /** No hay columna de creación; solo se actualiza "actualizado_en". */
    const CREATED_AT = null;
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'id_paciente',
        'actualizado_por',
        'tipo_sangre',
        'alergias',
        'enfermedades_cronicas',
        'medicamentos',
        'antecedentes',
        'contacto_nombre',
        'contacto_parentesco',
        'contacto_telefono',
    ];

    protected function casts(): array
    {
        return [
            'actualizado_en' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    /** Usuario que realizó la última modificación (CU06). */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por', 'id_usuario');
    }
}

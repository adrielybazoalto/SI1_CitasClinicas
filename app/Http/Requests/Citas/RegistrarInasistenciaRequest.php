<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU17 · Inasistencia manual con observación opcional.
 */
class RegistrarInasistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:cita.asistencia
    }

    public function rules(): array
    {
        return [
            'detalle' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return ['detalle' => 'observación'];
    }
}

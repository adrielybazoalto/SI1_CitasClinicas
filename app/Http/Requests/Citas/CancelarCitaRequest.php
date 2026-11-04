<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU16 · El motivo de cancelación es obligatorio.
 */
class CancelarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:cita.cancelar
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['motivo_cancelacion' => trim((string) $this->input('motivo_cancelacion'))]);
    }

    public function rules(): array
    {
        return [
            'motivo_cancelacion' => ['required', 'string', 'min:5', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_cancelacion.required' => 'Indique el motivo de la cancelación.',
            'motivo_cancelacion.min' => 'El motivo debe tener al menos 5 caracteres.',
            'motivo_cancelacion.max' => 'El motivo no puede superar los 200 caracteres.',
        ];
    }
}

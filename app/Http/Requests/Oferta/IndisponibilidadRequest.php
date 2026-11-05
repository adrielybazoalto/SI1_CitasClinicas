<?php

namespace App\Http\Requests\Oferta;

use Illuminate\Foundation\Http\FormRequest;

/** CU10 · Periodo de indisponibilidad de un médico (validación del servidor). */
class IndisponibilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:indisponibilidad.registrar
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            'inicio' => ['required', 'date'],
            'fin' => ['required', 'date', 'after:inicio'],
            'motivo' => ['required', 'string', 'min:3', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_medico.required' => 'Seleccione el médico.',
            'inicio.required' => 'Indique el inicio del periodo.',
            'fin.required' => 'Indique el fin del periodo.',
            'fin.after' => 'El fin debe ser posterior al inicio.',
            'motivo.required' => 'Indique el motivo.',
            'motivo.min' => 'El motivo debe tener al menos 3 caracteres.',
        ];
    }
}

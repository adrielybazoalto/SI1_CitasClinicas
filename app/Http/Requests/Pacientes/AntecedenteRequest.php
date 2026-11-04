<?php

namespace App\Http\Requests\Pacientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU06 · Validación de los antecedentes clínicos del paciente.
 */
class AntecedenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:antecedente.gestionar
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipo_sangre' => $this->filled('tipo_sangre') ? mb_strtoupper(trim((string) $this->input('tipo_sangre'))) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo_sangre' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'alergias' => ['nullable', 'string', 'max:2000'],
            'enfermedades_cronicas' => ['nullable', 'string', 'max:2000'],
            'medicamentos' => ['nullable', 'string', 'max:2000'],
            'antecedentes' => ['nullable', 'string', 'max:2000'],
            'contacto_nombre' => ['nullable', 'string', 'max:100'],
            'contacto_parentesco' => ['nullable', 'string', 'max:30'],
            'contacto_telefono' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_sangre' => 'tipo de sangre',
            'alergias' => 'alergias',
            'enfermedades_cronicas' => 'enfermedades crónicas',
            'medicamentos' => 'medicamentos',
            'antecedentes' => 'antecedentes',
            'contacto_nombre' => 'nombre del contacto de emergencia',
            'contacto_parentesco' => 'parentesco del contacto',
            'contacto_telefono' => 'teléfono de emergencia',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_sangre.in' => 'El tipo de sangre no es válido.',
        ];
    }
}

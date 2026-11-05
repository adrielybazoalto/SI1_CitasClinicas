<?php

namespace App\Http\Requests\Oferta;

use Illuminate\Foundation\Http\FormRequest;

/** CU11 · Nuevo precio de una especialidad (validación del servidor). */
class TarifaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:tarifa.gestionar
    }

    /** Acepta coma decimal (120,50). */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('monto'))) {
            $this->merge(['monto' => str_replace(',', '.', trim($this->input('monto')))]);
        }
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'vigente_desde' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_especialidad.required' => 'Seleccione la especialidad.',
            'monto.required' => 'Ingrese el precio.',
            'monto.gt' => 'El precio debe ser mayor a cero.',
            'monto.numeric' => 'El precio debe ser un número.',
            'monto.regex' => 'El precio admite como máximo dos decimales.',
            'vigente_desde.required' => 'Indique desde cuándo rige el precio.',
            'vigente_desde.after_or_equal' => 'El nuevo precio no puede empezar en una fecha pasada.',
        ];
    }
}

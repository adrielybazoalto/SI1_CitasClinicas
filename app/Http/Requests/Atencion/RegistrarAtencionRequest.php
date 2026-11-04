<?php

namespace App\Http\Requests\Atencion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU21 · Validación de la atención médica (servidor).
 * Rangos de signos vitales iguales a los CHECK de la tabla atencion.
 */
class RegistrarAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:atencion.registrar
    }

    /** Acepta coma decimal (36,5) y deja vacíos como null. */
    protected function prepareForValidation(): void
    {
        $normalizar = fn ($valor) => is_string($valor) ? (trim(str_replace(',', '.', $valor)) ?: null) : $valor;

        $this->merge([
            'peso_kg' => $normalizar($this->input('peso_kg')),
            'talla_cm' => $normalizar($this->input('talla_cm')),
            'temperatura_c' => $normalizar($this->input('temperatura_c')),
            'presion_arterial' => is_string($this->input('presion_arterial'))
                ? (preg_replace('/\s+/', '', $this->input('presion_arterial')) ?: null)
                : $this->input('presion_arterial'),
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'diagnostico' => ['required', 'string', 'min:3', 'max:2000'],
            'tratamiento' => ['nullable', 'string', 'max:2000'],
            'peso_kg' => ['nullable', 'numeric', 'between:0.5,400'],
            'talla_cm' => ['nullable', 'numeric', 'between:20,250'],
            'presion_arterial' => ['nullable', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'temperatura_c' => ['nullable', 'numeric', 'between:30,45'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'diagnostico.required' => 'El diagnóstico es obligatorio.',
            'diagnostico.min' => 'El diagnóstico debe tener al menos 3 caracteres.',
            'peso_kg.between' => 'El peso debe estar entre 0,5 y 400 kg.',
            'peso_kg.numeric' => 'El peso debe ser un número.',
            'talla_cm.between' => 'La talla debe estar entre 20 y 250 cm.',
            'talla_cm.numeric' => 'La talla debe ser un número.',
            'presion_arterial.regex' => 'La presión debe tener el formato 120/80.',
            'temperatura_c.between' => 'La temperatura debe estar entre 30 y 45 °C.',
            'temperatura_c.numeric' => 'La temperatura debe ser un número.',
        ];
    }
}

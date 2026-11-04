<?php

namespace App\Http\Requests\Citas;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU15 · Validación de la nueva ficha de una cita reprogramada.
 * La especialidad no se recibe: siempre es la de la cita (RN27).
 */
class ReprogramarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:cita.reprogramar
    }

    protected function prepareForValidation(): void
    {
        $motivo = trim((string) $this->input('motivo_forzado'));

        $this->merge([
            'forzar' => $this->boolean('forzar'),
            'motivo_forzado' => $motivo !== '' ? $motivo : null,
        ]);
    }

    public function rules(): array
    {
        $esPaciente = $this->user()?->rol?->nombre === Rol::PACIENTE;

        return [
            'id_medico' => ['required', 'integer', Rule::exists('medico', 'id_medico')->where('estado', true)],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'id_horario' => ['required', 'integer', 'exists:horario,id_horario'],
            'nro_cita' => ['required', 'integer', 'min:1'],
            // Solo la recepción puede forzar la regla de 24 h / una sola vez.
            'forzar' => ['boolean', Rule::prohibitedIf($esPaciente && $this->boolean('forzar'))],
            'motivo_forzado' => [Rule::requiredIf($this->boolean('forzar')), 'nullable', 'string', 'min:5', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_medico' => 'médico',
            'fecha' => 'fecha',
            'id_horario' => 'turno',
            'nro_cita' => 'ficha',
            'motivo_forzado' => 'motivo',
        ];
    }

    public function messages(): array
    {
        return [
            'id_horario.required' => 'Seleccione la nueva ficha.',
            'nro_cita.required' => 'Seleccione la nueva ficha.',
            'fecha.after_or_equal' => 'No se puede reprogramar a una fecha pasada.',
            'forzar.prohibited' => 'Solo la recepción puede forzar una reprogramación.',
            'motivo_forzado.required' => 'Indique el motivo para forzar la reprogramación.',
        ];
    }
}

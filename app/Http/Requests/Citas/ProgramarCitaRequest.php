<?php

namespace App\Http\Requests\Citas;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU13 · Validación de la reserva de una ficha.
 *
 * Aquí se valida el formato de los datos. Las reglas del negocio (ficha
 * libre, RN17, RN19, RN31, sobrecupos) se verifican en CitaService dentro
 * de la transacción, porque dependen del estado de la base en ese instante.
 */
class ProgramarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:cita.programar
    }

    protected function prepareForValidation(): void
    {
        $motivo = trim((string) $this->input('motivo'));

        $this->merge([
            'motivo' => $motivo !== '' ? $motivo : null,
            'sobrecupo' => $this->boolean('sobrecupo'),
        ]);
    }

    public function rules(): array
    {
        $esPaciente = $this->user()?->rol?->nombre === Rol::PACIENTE;

        return [
            // El paciente reserva para sí mismo: el servicio toma su propio id.
            'id_paciente' => [
                Rule::requiredIf(! $esPaciente), 'nullable', 'integer',
                Rule::exists('paciente', 'id_paciente')->where('estado', true),
            ],
            'id_especialidad' => ['required', 'integer', Rule::exists('especialidad', 'id_especialidad')->where('estado', true)],
            'id_medico' => ['required', 'integer', Rule::exists('medico', 'id_medico')->where('estado', true)],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'id_horario' => ['required', 'integer', 'exists:horario,id_horario'],
            'nro_cita' => [Rule::requiredIf(! $this->boolean('sobrecupo')), 'nullable', 'integer', 'min:1'],
            'motivo' => ['nullable', 'string', 'max:200'],
            // Solo la recepción puede asignar sobrecupos (RN29).
            'sobrecupo' => ['boolean', Rule::prohibitedIf($esPaciente && $this->boolean('sobrecupo'))],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_paciente' => 'paciente',
            'id_especialidad' => 'especialidad',
            'id_medico' => 'médico',
            'fecha' => 'fecha',
            'id_horario' => 'turno',
            'nro_cita' => 'ficha',
            'motivo' => 'motivo de consulta',
        ];
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Seleccione el paciente.',
            'id_paciente.exists' => 'El paciente no existe o está inactivo.',
            'id_especialidad.exists' => 'La especialidad no existe o está inactiva.',
            'id_medico.exists' => 'El médico no existe o está inactivo.',
            'fecha.after_or_equal' => 'No se pueden reservar fichas en fechas pasadas.',
            'id_horario.required' => 'Seleccione una ficha disponible.',
            'nro_cita.required' => 'Seleccione una ficha disponible.',
            'sobrecupo.prohibited' => 'Solo la recepción puede asignar sobrecupos.',
        ];
    }
}

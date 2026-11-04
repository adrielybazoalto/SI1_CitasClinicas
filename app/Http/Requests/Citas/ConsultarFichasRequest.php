<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU12 · Parámetros de la consulta de fichas (especialidad → médico → fecha).
 * Se usa en la petición fetch de la pantalla de fichas.
 */
class ConsultarFichasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:cita.consultar
    }

    public function rules(): array
    {
        return [
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
            'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            // Al reprogramar, la propia cita no cuenta como ocupada.
            'cita' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_especialidad.required' => 'Seleccione una especialidad.',
            'id_medico.required' => 'Seleccione un médico.',
            'fecha.required' => 'Seleccione una fecha.',
            'fecha.date_format' => 'La fecha no es válida.',
            'fecha.after_or_equal' => 'No se pueden consultar fechas pasadas.',
        ];
    }
}

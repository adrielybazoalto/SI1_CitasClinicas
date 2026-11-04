<?php

namespace App\Http\Requests\Oferta;

use App\Services\HorarioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * CU09 · Validación de la grilla semanal de atención.
 *
 * Reglas: hora_fin > hora_inicio, la duración de la consulta debe dividir
 * exactamente el turno y no puede haber superposición con otro turno activo
 * del mismo médico en el mismo día. La capacidad y el reparto 70/30 se
 * calculan en HorarioService.
 */
class HorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:horario.gestionar
    }

    protected function prepareForValidation(): void
    {
        $normalizarHora = function (mixed $valor): ?string {
            if (! is_string($valor)) {
                return null;
            }

            $valor = trim($valor);

            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $valor, $coincidencia)) {
                return str_pad($coincidencia[1], 2, '0', STR_PAD_LEFT).':'.$coincidencia[2];
            }

            return $valor;
        };

        $consultorio = trim((string) $this->input('consultorio'));

        $this->merge([
            'hora_inicio' => $normalizarHora($this->input('hora_inicio')),
            'hora_fin' => $normalizarHora($this->input('hora_fin')),
            'consultorio' => $consultorio !== '' ? $consultorio : null,
        ]);
    }

    public function rules(): array
    {
        $idMedico = $this->input('id_medico');
        $horarioActual = $this->route('horario');
        $idMedicoActual = $horarioActual?->id_medico;
        $idEspecialidadActual = $horarioActual?->id_especialidad;

        return [
            'id_medico' => [
                'required', 'integer',
                Rule::exists('medico', 'id_medico')->where(function ($query) use ($idMedicoActual) {
                    $query->where('estado', true);

                    // Al editar se conserva el médico actual aunque esté inactivo.
                    if ($idMedicoActual) {
                        $query->orWhere('id_medico', $idMedicoActual);
                    }
                }),
            ],
            'id_especialidad' => [
                'required', 'integer',
                Rule::exists('especialidad', 'id_especialidad')->where(function ($query) use ($idEspecialidadActual) {
                    $query->where('estado', true);

                    // Al editar se conserva la especialidad actual aunque esté inactiva.
                    if ($idEspecialidadActual) {
                        $query->orWhere('id_especialidad', $idEspecialidadActual);
                    }
                }),
                Rule::exists('medico_especialidad', 'id_especialidad')
                    ->where(fn ($query) => $query->where('id_medico', $idMedico)),
            ],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracion_min' => ['required', 'integer', 'between:10,120'],
            'consultorio' => ['nullable', 'string', 'max:20'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $horarioActual = $this->route('horario');
            $activo = $this->has('estado')
                ? $this->boolean('estado')
                : (bool) ($horarioActual?->estado ?? true);

            try {
                app(HorarioService::class)->validarFranja(
                    (int) $this->input('id_medico'),
                    (int) $this->input('dia_semana'),
                    (string) $this->input('hora_inicio'),
                    (string) $this->input('hora_fin'),
                    (int) $this->input('duracion_min'),
                    $activo,
                    $horarioActual?->id_horario,
                );
            } catch (ValidationException $e) {
                foreach ($e->errors() as $campo => $mensajes) {
                    foreach ($mensajes as $mensaje) {
                        $validator->errors()->add($campo, $mensaje);
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'id_medico' => 'médico',
            'id_especialidad' => 'especialidad',
            'dia_semana' => 'día de la semana',
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora de fin',
            'duracion_min' => 'duración de la consulta',
            'consultorio' => 'consultorio',
            'estado' => 'estado',
        ];
    }

    public function messages(): array
    {
        return [
            'id_medico.required' => 'Seleccione un médico.',
            'id_medico.exists' => 'El médico seleccionado no existe o está inactivo.',
            'id_especialidad.required' => 'Seleccione una especialidad.',
            'id_especialidad.exists' => 'La especialidad seleccionada no está activa o no pertenece al médico.',
            'dia_semana.required' => 'Seleccione un día de la semana.',
            'dia_semana.between' => 'Seleccione un día válido (1 = lunes, 7 = domingo).',
            'hora_inicio.required' => 'Indique la hora de inicio.',
            'hora_inicio.date_format' => 'Use el formato HH:MM para la hora de inicio.',
            'hora_fin.required' => 'Indique la hora de fin.',
            'hora_fin.date_format' => 'Use el formato HH:MM para la hora de fin.',
            'hora_fin.after' => 'La hora de fin debe ser mayor que la hora de inicio.',
            'duracion_min.required' => 'Indique la duración de la consulta.',
            'duracion_min.between' => 'La duración debe estar entre 10 y 120 minutos.',
        ];
    }
}

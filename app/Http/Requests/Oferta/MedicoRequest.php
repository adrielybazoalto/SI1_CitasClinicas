<?php

namespace App\Http\Requests\Oferta;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Rol;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * CU08 · Validación del alta y la edición de médicos.
 *
 * La matrícula profesional es única (se normaliza a mayúsculas y sin espacios
 * repetidos). El médico debe tener al menos una especialidad activa asociada.
 * La cuenta de acceso es opcional: se elige entre los usuarios con rol Medico
 * que todavía no estén vinculados a otro médico.
 */
class MedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:medico.gestionar
    }

    protected function prepareForValidation(): void
    {
        $telefono = trim((string) $this->input('telefono'));
        $correo = mb_strtolower(trim((string) $this->input('correo')));

        $this->merge([
            'matricula' => mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $this->input('matricula'))) ?? ''),
            'nombres' => preg_replace('/\s+/u', ' ', trim((string) $this->input('nombres'))) ?? '',
            'apellidos' => preg_replace('/\s+/u', ' ', trim((string) $this->input('apellidos'))) ?? '',
            'telefono' => $telefono !== '' ? $telefono : null,
            'correo' => $correo !== '' ? $correo : null,
        ]);
    }

    public function rules(): array
    {
        $medicoActual = $this->route('medico');
        $idMedico = $medicoActual?->id_medico;
        $idUsuarioActual = $medicoActual?->id_usuario;
        $idRolMedico = Rol::where('nombre', Rol::MEDICO)->value('id_rol');

        return [
            'matricula' => [
                'required', 'string', 'min:3', 'max:30',
                function (string $attribute, mixed $value, Closure $fail) use ($idMedico): void {
                    $existe = Medico::query()
                        ->whereRaw('lower(trim(matricula)) = ?', [mb_strtolower((string) $value)])
                        ->when($idMedico, fn ($q) => $q->where('id_medico', '!=', $idMedico))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un médico con esa matrícula profesional.');
                    }
                },
            ],
            'nombres' => ['required', 'string', 'min:2', 'max:60'],
            'apellidos' => ['required', 'string', 'min:2', 'max:60'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:100'],
            'id_usuario' => [
                'nullable', 'integer',
                Rule::exists('usuario', 'id_usuario')->where(function ($query) use ($idRolMedico, $idUsuarioActual) {
                    $query->where('id_rol', $idRolMedico)
                        ->where(function ($q) use ($idUsuarioActual) {
                            $q->where('estado', true);

                            // La cuenta ya vinculada se conserva aunque esté inactiva.
                            if ($idUsuarioActual) {
                                $q->orWhere('id_usuario', $idUsuarioActual);
                            }
                        });
                }),
                function (string $attribute, mixed $value, Closure $fail) use ($idMedico): void {
                    if (! $value) {
                        return;
                    }

                    $ocupado = Medico::query()
                        ->where('id_usuario', $value)
                        ->when($idMedico, fn ($q) => $q->where('id_medico', '!=', $idMedico))
                        ->exists();

                    if ($ocupado) {
                        $fail('Esa cuenta de acceso ya está vinculada a otro médico.');
                    }
                },
            ],
            'especialidades' => ['required', 'array', 'min:1'],
            'especialidades.*' => ['integer', 'distinct', Rule::exists('especialidad', 'id_especialidad')],
            'estado' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = $this->input('especialidades');

            if (! is_array($ids) || $ids === []) {
                return;
            }

            $ids = array_map('intval', $ids);
            $especialidades = Especialidad::whereIn('id_especialidad', $ids)
                ->get(['id_especialidad', 'estado']);

            // Si hay ids inválidos, la regla exists ya reporta el error.
            if ($especialidades->count() !== count($ids)) {
                return;
            }

            if (! $especialidades->contains(fn (Especialidad $especialidad) => $especialidad->estado)) {
                $validator->errors()->add('especialidades', 'Debe asignar al menos una especialidad activa.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'matricula' => 'matrícula profesional',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'telefono' => 'teléfono',
            'correo' => 'correo',
            'id_usuario' => 'cuenta de acceso',
            'especialidades' => 'especialidades',
            'estado' => 'estado',
        ];
    }

    public function messages(): array
    {
        return [
            'matricula.required' => 'La matrícula profesional es obligatoria.',
            'matricula.min' => 'La matrícula debe tener al menos 3 caracteres.',
            'nombres.required' => 'Los nombres del médico son obligatorios.',
            'apellidos.required' => 'Los apellidos del médico son obligatorios.',
            'id_usuario.exists' => 'La cuenta seleccionada no está activa o no tiene el rol Médico.',
            'especialidades.required' => 'Debe asignar al menos una especialidad.',
            'especialidades.min' => 'Debe asignar al menos una especialidad.',
            'especialidades.*.exists' => 'Una de las especialidades seleccionadas no existe.',
            'especialidades.*.distinct' => 'No puede repetir una especialidad.',
        ];
    }
}

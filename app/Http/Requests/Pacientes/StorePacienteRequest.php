<?php

namespace App\Http\Requests\Pacientes;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU05 · Validación del alta de pacientes.
 *
 * También se reutiliza para la edición (ver UpdatePacienteRequest): cuando
 * existe el modelo en la ruta se ignoran sus propios valores únicos.
 */
class StorePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:paciente.gestionar
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ci' => trim((string) $this->input('ci')),
            'nombres' => trim((string) $this->input('nombres')),
            'apellidos' => trim((string) $this->input('apellidos')),
            'sexo' => mb_strtoupper(trim((string) $this->input('sexo'))),
            'telefono' => trim((string) $this->input('telefono')),
            'correo' => $this->filled('correo') ? mb_strtolower(trim((string) $this->input('correo'))) : null,
            'direccion' => $this->filled('direccion') ? trim((string) $this->input('direccion')) : null,
            'tipo_sangre' => $this->filled('tipo_sangre') ? mb_strtoupper(trim((string) $this->input('tipo_sangre'))) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $paciente = $this->route('paciente');
        $idPaciente = $paciente?->id_paciente;
        $idUsuario = $paciente?->id_usuario;

        return [
            // RN01 · CI único en paciente y también como username de la cuenta.
            'ci' => [
                'required', 'string', 'max:15',
                Rule::unique('paciente', 'ci')->ignore($idPaciente, 'id_paciente'),
                function (string $attribute, mixed $value, \Closure $fail) use ($idUsuario): void {
                    $existe = User::query()
                        ->whereRaw('lower(username) = ?', [mb_strtolower(trim((string) $value))])
                        ->when($idUsuario, fn ($q) => $q->where('id_usuario', '!=', $idUsuario))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe una cuenta de acceso con ese CI.');
                    }
                },
            ],
            'nombres' => ['required', 'string', 'max:60'],
            'apellidos' => ['required', 'string', 'max:60'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'sexo' => ['required', Rule::in(['M', 'F', 'O'])],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => [
                'nullable', 'email', 'max:100',
                Rule::unique('usuario', 'correo')->ignore($idUsuario, 'id_usuario'),
            ],
            'direccion' => ['nullable', 'string', 'max:150'],
            'recibe_recordatorios' => ['sometimes', 'boolean'],

            // CU06 · Antecedentes clínicos (opcionales en el alta).
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
            'ci' => 'CI',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'sexo' => 'sexo',
            'telefono' => 'teléfono',
            'correo' => 'correo',
            'direccion' => 'dirección',
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
            'ci.unique' => 'Ese CI ya está registrado como paciente.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser posterior a hoy.',
            'sexo.in' => 'El sexo debe ser M, F u O.',
            'correo.unique' => 'Ese correo ya está asociado a otra cuenta.',
            'tipo_sangre.in' => 'El tipo de sangre no es válido.',
        ];
    }
}

<?php

namespace App\Http\Requests\Pacientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** CU04 · Datos que el paciente puede cambiar de su perfil (validación del servidor). */
class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:perfil.gestionar
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['recibe_recordatorios' => $this->boolean('recibe_recordatorios')]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'telefono' => ['required', 'string', 'regex:/^[0-9]{7,15}$/'],
            'correo' => ['nullable', 'email', 'max:100', Rule::unique('usuario', 'correo')->ignore($this->user()->id_usuario, 'id_usuario')],
            'direccion' => ['nullable', 'string', 'max:150'],
            'contacto_nombre' => ['nullable', 'string', 'max:100'],
            'contacto_parentesco' => ['nullable', 'string', 'max:30'],
            'contacto_telefono' => ['nullable', 'string', 'regex:/^[0-9]{7,15}$/'],
            'recibe_recordatorios' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe tener entre 7 y 15 dígitos.',
            'contacto_telefono.regex' => 'El teléfono del contacto debe tener entre 7 y 15 dígitos.',
            'correo.email' => 'El correo no es válido.',
            'correo.unique' => 'Ese correo ya lo usa otra cuenta.',
        ];
    }
}

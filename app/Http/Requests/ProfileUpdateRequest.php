<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del perfil propio: username y correo únicos en la tabla usuario,
 * con las mismas reglas que CU02 (Seguridad\UsuarioRequest).
 */
class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'correo' => mb_strtolower(trim((string) $this->input('correo'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $idUsuario = $this->user()->id_usuario;

        return [
            'username' => [
                'required', 'string', 'min:4', 'max:50', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('usuario', 'username')->ignore($idUsuario, 'id_usuario'),
            ],
            'correo' => [
                'required', 'email', 'max:100',
                Rule::unique('usuario', 'correo')->ignore($idUsuario, 'id_usuario'),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['username' => 'nombre de usuario', 'correo' => 'correo'];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'El nombre de usuario solo admite letras minúsculas, números, punto, guion y guion bajo.',
            'username.unique' => 'Ese nombre de usuario ya está registrado.',
            'correo.unique' => 'Ese correo ya está registrado.',
        ];
    }
}

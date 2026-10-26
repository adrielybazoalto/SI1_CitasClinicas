<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * CU02 · Validación del alta y la edición de usuarios.
 * username y correo se normalizan a minúsculas y deben ser únicos.
 * La contraseña (≥ 8) solo se pide en el alta.
 */
class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:usuario.gestionar
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'correo' => mb_strtolower(trim((string) $this->input('correo'))),
        ]);
    }

    public function rules(): array
    {
        $idUsuario = $this->route('usuario')?->id_usuario;
        $esAlta = $idUsuario === null;

        return [
            'username' => [
                'required', 'string', 'min:4', 'max:50', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('usuario', 'username')->ignore($idUsuario, 'id_usuario'),
            ],
            'correo' => [
                'required', 'email', 'max:100',
                Rule::unique('usuario', 'correo')->ignore($idUsuario, 'id_usuario'),
            ],
            'id_rol' => ['required', 'integer', Rule::exists('rol', 'id_rol')->where('estado', true)],
            'password' => $esAlta ? ['required', 'confirmed', Password::min(8)] : ['prohibited'],
            'debe_cambiar_clave' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => 'nombre de usuario',
            'correo' => 'correo',
            'id_rol' => 'rol',
            'password' => 'contraseña',
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'El nombre de usuario solo admite letras minúsculas, números, punto, guion y guion bajo.',
            'username.unique' => 'Ese nombre de usuario ya está registrado.',
            'correo.unique' => 'Ese correo ya está registrado.',
            'password.prohibited' => 'Para cambiar la contraseña use "Restablecer clave".',
        ];
    }
}

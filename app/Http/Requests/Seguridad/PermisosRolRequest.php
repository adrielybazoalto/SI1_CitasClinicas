<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CU03 · Validación de la matriz de permisos enviada para un rol.
 */
class PermisosRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:rol.gestionar
    }

    public function rules(): array
    {
        return [
            'permisos' => ['array'],
            'permisos.*' => ['integer', 'distinct', 'exists:permiso,id_permiso'],
        ];
    }
}

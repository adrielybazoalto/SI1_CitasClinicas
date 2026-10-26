<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CU03 · Validación del alta de roles (nombre único, sin distinguir mayúsculas).
 */
class RolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:seguridad.roles
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nombre' => preg_replace('/\s+/', ' ', trim((string) $this->input('nombre')))]);
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'min:3', 'max:30',
                function ($atributo, $valor, $fallar) {
                    if (\App\Models\Rol::whereRaw('lower(nombre) = ?', [mb_strtolower($valor)])->exists()) {
                        $fallar('Ya existe un rol con ese nombre.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre del rol', 'descripcion' => 'descripción'];
    }
}

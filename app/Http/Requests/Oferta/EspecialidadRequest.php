<?php

namespace App\Http\Requests\Oferta;

use App\Models\Especialidad;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * CU07 · Validación del alta y la edición de especialidades.
 *
 * El nombre se normaliza a minúsculas y con espacios simples, igual que el
 * índice único ux_especialidad_nombre (lower(trim(nombre))) de la base.
 * La duración de consulta debe estar entre 10 y 120 minutos (RF04).
 */
class EspecialidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:especialidad.gestionar
    }

    protected function prepareForValidation(): void
    {
        $nombre = preg_replace('/\s+/u', ' ', trim((string) $this->input('nombre'))) ?? '';
        $descripcion = preg_replace('/\s+/u', ' ', trim((string) $this->input('descripcion'))) ?? '';

        $this->merge([
            'nombre' => mb_strtolower($nombre),
            'descripcion' => $descripcion !== '' ? $descripcion : null,
        ]);
    }

    public function rules(): array
    {
        $idEspecialidad = $this->route('especialidad')?->id_especialidad;

        return [
            'nombre' => [
                'required', 'string', 'min:3', 'max:60',
                function (string $attribute, mixed $value, Closure $fail) use ($idEspecialidad): void {
                    $existe = Especialidad::query()
                        ->whereRaw('lower(trim(nombre)) = ?', [$value])
                        ->when($idEspecialidad, fn ($q) => $q->where('id_especialidad', '!=', $idEspecialidad))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe una especialidad con ese nombre.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'duracion_min' => ['required', 'integer', 'between:10,120'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la especialidad',
            'descripcion' => 'descripción',
            'duracion_min' => 'duración de la consulta',
            'estado' => 'estado',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la especialidad es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede superar los 60 caracteres.',
            'duracion_min.required' => 'La duración de la consulta es obligatoria.',
            'duracion_min.between' => 'La duración debe estar entre 10 y 120 minutos.',
        ];
    }
}

<?php

namespace App\Http\Requests\Reportes;

use App\Services\ReporteService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * CU23 · Validación de los filtros de un reporte (servidor).
 */
class GenerarReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware permiso:reporte.generar
    }

    /** Valores por defecto: mes en curso hasta hoy, o la fecha de hoy. */
    protected function prepareForValidation(): void
    {
        $hoy = CarbonImmutable::today();

        $this->merge([
            'tipo' => $this->input('tipo', 'por_estado'),
            'desde' => $this->input('desde') ?: $hoy->startOfMonth()->toDateString(),
            'hasta' => $this->input('hasta') ?: $hoy->toDateString(),
            'fecha' => $this->input('fecha') ?: $hoy->toDateString(),
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:'.implode(',', array_keys(ReporteService::TIPOS))],
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'medico' => ['nullable', 'integer', 'exists:medico,id_medico'],
            'especialidad' => ['nullable', 'integer', 'exists:especialidad,id_especialidad'],
            'estado' => ['nullable', 'in:Programada,Confirmada,Atendida,Cancelada,Inasistencia'],
        ];
    }

    /** Los reportes del administrador requieren además el permiso bitacora.ver. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tipo = $this->input('tipo');
                if (isset(ReporteService::TIPOS[$tipo]) && ReporteService::TIPOS[$tipo][1]
                    && ! $this->user()->tienePermiso(ReporteService::PERMISO_ADMIN)) {
                    $validator->errors()->add('tipo', 'Este reporte es exclusivo del administrador.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tipo.in' => 'Seleccione un reporte válido.',
            'hasta.after_or_equal' => 'La fecha de inicio no puede ser posterior a la fecha de fin.',
            'desde.date_format' => 'La fecha de inicio no es válida.',
            'hasta.date_format' => 'La fecha de fin no es válida.',
            'fecha.date_format' => 'La fecha no es válida.',
            'medico.exists' => 'El médico seleccionado no existe.',
            'especialidad.exists' => 'La especialidad seleccionada no existe.',
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Indisponibilidad;
use App\Models\Medico;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * P3 · CU10 Registrar indisponibilidad médica.
 *
 * Desde que se registra, DisponibilidadService ya no ofrece los horarios
 * del periodo. La reubicación o cancelación de las citas afectadas es del
 * paquete de Citas (CitaService), que depende de este y no al revés.
 */
class IndisponibilidadService
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /** Periodos vigentes y futuros primero; luego los pasados. */
    public function listar(): LengthAwarePaginator
    {
        return Indisponibilidad::query()
            ->with(['medico:id_medico,nombres,apellidos', 'registrador:id_usuario,username'])
            ->orderByRaw('case when fin >= ? then 0 else 1 end', [now()])
            ->orderBy('inicio')
            ->paginate(15);
    }

    /** @return \Illuminate\Support\Collection<int, Medico> */
    public function medicos()
    {
        return Medico::query()->orderBy('apellidos')->get(['id_medico', 'nombres', 'apellidos', 'estado']);
    }

    /**
     * $datos ya viene validado por IndisponibilidadRequest (inicio < fin).
     *
     * @throws ValidationException
     */
    public function registrar(array $datos, User $actor): Indisponibilidad
    {
        $inicio = CarbonImmutable::parse($datos['inicio']);
        $fin = CarbonImmutable::parse($datos['fin']);

        if ($fin->lte(CarbonImmutable::now())) {
            throw ValidationException::withMessages(['fin' => 'El periodo ya terminó: registre una indisponibilidad actual o futura.']);
        }

        $indisponibilidad = Indisponibilidad::create([
            'id_medico' => $datos['id_medico'],
            'registrado_por' => $actor->id_usuario,
            'inicio' => $inicio,
            'fin' => $fin,
            'motivo' => trim($datos['motivo']),
        ]);

        $medico = Medico::query()->find($datos['id_medico']);
        $this->bitacora->registrar(
            Bitacora::REGISTRAR_INDISPONIBILIDAD,
            'indisponibilidad',
            $indisponibilidad->id_indisponibilidad,
            sprintf('%s: del %s al %s · %s', $medico?->nombre_completo, $inicio->format('d/m/Y H:i'), $fin->format('d/m/Y H:i'), trim($datos['motivo'])),
            $actor->id_usuario,
        );

        return $indisponibilidad;
    }
}

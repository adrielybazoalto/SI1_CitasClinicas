<?php

namespace App\Services;

use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CU07 · Gestionar especialidades médicas.
 *
 * El nombre se guarda normalizado (minúsculas y espacios simples) para
 * respetar el índice único ux_especialidad_nombre. La baja es lógica:
 * nunca se elimina una especialidad con médicos u horarios asociados.
 */
class EspecialidadService
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /** Listado paginado con búsqueda por nombre y filtro de estado. */
    public function listar(?string $buscar, ?string $estado): LengthAwarePaginator
    {
        return Especialidad::query()
            ->withCount([
                'medicos',
                'horarios',
                'horarios as horarios_activos_count' => fn ($q) => $q->where('estado', true),
            ])
            ->when(filled($buscar), function ($query) use ($buscar) {
                $texto = '%'.mb_strtolower(trim($buscar)).'%';
                $query->whereRaw('lower(nombre) like ?', [$texto]);
            })
            ->when($estado !== null && $estado !== '', fn ($q) => $q->where('estado', $estado === '1'))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();
    }

    /** Especialidades activas para selectores. */
    public function activas(): Collection
    {
        return Especialidad::where('estado', true)->orderBy('nombre')->get();
    }

    /** Todas las especialidades (para conservar asignaciones históricas). */
    public function todas(): Collection
    {
        return Especialidad::orderBy('nombre')->get();
    }

    public function registrar(array $datos, User $actor): Especialidad
    {
        return DB::transaction(function () use ($datos, $actor) {
            $especialidad = Especialidad::create([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'duracion_min' => $datos['duracion_min'],
                'estado' => (bool) ($datos['estado'] ?? true),
            ]);

            $this->bitacora->registrar(
                'ESPECIALIDAD_CREADA',
                'especialidad',
                $especialidad->id_especialidad,
                "Especialidad {$especialidad->nombre} · {$especialidad->duracion_min} min",
                $actor->id_usuario,
            );

            return $especialidad;
        });
    }

    public function actualizar(Especialidad $especialidad, array $datos, User $actor): Especialidad
    {
        return DB::transaction(function () use ($especialidad, $datos, $actor) {
            $especialidad->fill([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'duracion_min' => $datos['duracion_min'],
                'estado' => (bool) ($datos['estado'] ?? $especialidad->estado),
            ]);

            $cambios = array_keys($especialidad->getDirty());
            $especialidad->save();

            if ($cambios) {
                $this->bitacora->registrar(
                    'ESPECIALIDAD_MODIFICADA',
                    'especialidad',
                    $especialidad->id_especialidad,
                    'Campos modificados: '.implode(', ', $cambios),
                    $actor->id_usuario,
                );
            }

            return $especialidad;
        });
    }

    /** Activa o desactiva la especialidad sin borrar el registro (RNF13). */
    public function cambiarEstado(Especialidad $especialidad, bool $estado, User $actor): Especialidad
    {
        DB::transaction(function () use ($especialidad, $estado, $actor) {
            $especialidad->forceFill(['estado' => $estado])->save();

            $this->bitacora->registrar(
                $estado ? 'ESPECIALIDAD_ACTIVADA' : 'ESPECIALIDAD_DESACTIVADA',
                'especialidad',
                $especialidad->id_especialidad,
                $estado ? 'Especialidad activada' : 'Especialidad desactivada (baja lógica)',
                $actor->id_usuario,
            );
        });

        return $especialidad->refresh();
    }
}

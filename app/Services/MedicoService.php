<?php

namespace App\Services;

use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Medico;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CU08 · Gestionar médicos.
 *
 * El alta genera el código correlativo MED-0001 (CodigoService), asigna al
 * menos una especialidad en la tabla pivote medico_especialidad y puede
 * vincular una cuenta existente con rol Medico. La baja es lógica: el médico
 * conserva sus horarios y citas históricas (RNF13).
 */
class MedicoService
{
    public function __construct(
        private CodigoService $codigos,
        private BitacoraService $bitacora,
    ) {
    }

    /** Listado paginado con búsqueda por matrícula, código o nombre. */
    public function listar(?string $buscar, ?int $idEspecialidad, ?string $estado): LengthAwarePaginator
    {
        return Medico::query()
            ->with(['usuario', 'especialidades'])
            ->withCount([
                'horarios',
                'horarios as horarios_activos_count' => fn ($q) => $q->where('estado', true),
            ])
            ->when(filled($buscar), function ($query) use ($buscar) {
                $texto = '%'.mb_strtolower(trim($buscar)).'%';

                $query->where(fn ($w) => $w
                    ->whereRaw('lower(matricula) like ?', [$texto])
                    ->orWhereRaw('lower(codigo) like ?', [$texto])
                    ->orWhereRaw('lower(nombres) like ?', [$texto])
                    ->orWhereRaw('lower(apellidos) like ?', [$texto])
                    ->orWhereRaw("lower(coalesce(correo, '')) like ?", [$texto]));
            })
            ->when($idEspecialidad, fn ($q) => $q->whereHas(
                'especialidades',
                fn ($w) => $w->where('especialidad.id_especialidad', $idEspecialidad),
            ))
            ->when($estado !== null && $estado !== '', fn ($q) => $q->where('estado', $estado === '1'))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15)
            ->withQueryString();
    }

    /** Todos los médicos para filtros históricos. */
    public function todos(): Collection
    {
        return Medico::orderBy('apellidos')->orderBy('nombres')->get();
    }

    /**
     * Médicos activos para el formulario de horarios. Si se edita un horario
     * de un médico ya inactivo, se incluye para no perder la referencia.
     */
    public function paraHorario(?int $incluirId = null): Collection
    {
        return Medico::query()
            ->where(fn ($q) => $q->where('estado', true)
                ->when($incluirId, fn ($q2) => $q2->orWhere('id_medico', $incluirId)))
            ->with(['especialidades' => fn ($q) => $q->where('estado', true)])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Cuentas con rol Medico que todavía no están vinculadas a otro médico.
     * En edición se conserva la cuenta actual aunque esté inactiva.
     */
    public function usuariosMedicoDisponibles(?int $idMedico = null): Collection
    {
        $idRol = Rol::where('nombre', Rol::MEDICO)->value('id_rol');

        if (! $idRol) {
            return new Collection();
        }

        $vinculados = Medico::whereNotNull('id_usuario')
            ->when($idMedico, fn ($q) => $q->where('id_medico', '!=', $idMedico))
            ->pluck('id_usuario')
            ->all();

        $usuarios = User::where('id_rol', $idRol)
            ->where('estado', true)
            ->whereNotIn('id_usuario', $vinculados)
            ->orderBy('username')
            ->get();

        if ($idMedico) {
            $actual = Medico::with('usuario')->find($idMedico)?->usuario;

            if ($actual && ! $usuarios->contains('id_usuario', $actual->id_usuario)) {
                $usuarios->push($actual);
            }
        }

        return $usuarios->sortBy('username')->values();
    }

    public function registrar(array $datos, User $actor): Medico
    {
        return DB::transaction(function () use ($datos, $actor) {
            $medico = Medico::create([
                'id_usuario' => $datos['id_usuario'] ?? null,
                'codigo' => $this->codigos->medico(),
                'matricula' => $datos['matricula'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'telefono' => $datos['telefono'] ?? null,
                'correo' => $datos['correo'] ?? null,
                'estado' => (bool) ($datos['estado'] ?? true),
            ]);

            $this->sincronizarEspecialidades($medico, $datos['especialidades']);

            $this->bitacora->registrar(
                'MEDICO_CREADO',
                'medico',
                $medico->id_medico,
                "Médico {$medico->codigo} · matrícula {$medico->matricula} · {$medico->nombre_completo}",
                $actor->id_usuario,
            );

            return $medico->load('especialidades');
        });
    }

    public function actualizar(Medico $medico, array $datos, User $actor): Medico
    {
        return DB::transaction(function () use ($medico, $datos, $actor) {
            $medico->fill([
                'id_usuario' => array_key_exists('id_usuario', $datos)
                    ? $datos['id_usuario']
                    : $medico->id_usuario,
                'matricula' => $datos['matricula'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'telefono' => $datos['telefono'] ?? null,
                'correo' => $datos['correo'] ?? null,
                'estado' => (bool) ($datos['estado'] ?? $medico->estado),
            ]);

            $cambios = array_keys($medico->getDirty());
            $medico->save();

            $this->sincronizarEspecialidades($medico, $datos['especialidades']);

            $this->bitacora->registrar(
                'MEDICO_MODIFICADO',
                'medico',
                $medico->id_medico,
                $cambios ? 'Campos modificados: '.implode(', ', $cambios) : 'Datos del médico actualizados',
                $actor->id_usuario,
            );

            return $medico->load('especialidades');
        });
    }

    /** Baja lógica: el médico y su historial se conservan (RNF13). */
    public function cambiarEstado(Medico $medico, bool $estado, User $actor): Medico
    {
        DB::transaction(function () use ($medico, $estado, $actor) {
            $medico->forceFill(['estado' => $estado])->save();

            $this->bitacora->registrar(
                $estado ? 'MEDICO_ACTIVADO' : 'MEDICO_DESACTIVADO',
                'medico',
                $medico->id_medico,
                $estado ? 'Médico activado' : 'Médico desactivado (baja lógica; conserva historial)',
                $actor->id_usuario,
            );
        });

        return $medico->refresh();
    }

    /**
     * Sincroniza la tabla pivote. No permite quitar una especialidad que ya
     * tiene horarios porque la FK de horario es RESTRICT y se perdería la
     * configuración histórica.
     */
    private function sincronizarEspecialidades(Medico $medico, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            throw ValidationException::withMessages([
                'especialidades' => 'Debe asignar al menos una especialidad.',
            ]);
        }

        $actuales = $medico->especialidades()->pluck('especialidad.id_especialidad')->all();
        $quitadas = array_values(array_diff($actuales, $ids));

        if ($quitadas !== []) {
            $tieneHorarios = Horario::query()
                ->where('id_medico', $medico->id_medico)
                ->whereIn('id_especialidad', $quitadas)
                ->exists();

            if ($tieneHorarios) {
                $nombres = Especialidad::whereIn('id_especialidad', $quitadas)
                    ->orderBy('nombre')
                    ->pluck('nombre')
                    ->implode(', ');

                $etiqueta = count($quitadas) === 1 ? 'la especialidad' : 'las especialidades';

                throw ValidationException::withMessages([
                    'especialidades' => "No se puede quitar {$etiqueta} {$nombres} porque tiene horarios registrados. Desactive o elimine primero esos horarios.",
                ]);
            }
        }

        $medico->especialidades()->sync($ids);
    }
}

<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Escribe en la tabla bitacora: registrar(accion, tabla, id, detalle).
 * Consulta de solo lectura (CU24): listar(filtros). La tabla es inmutable (trigger RNF08).
 */
class BitacoraService
{
    /**
     * Registra una acción. Por defecto el autor es el usuario autenticado;
     * $idUsuario permite indicarlo cuando aún no hay sesión (LOGIN_FALLIDO).
     * Un fallo al auditar se deja en el log y no interrumpe la operación.
     */
    public function registrar(
        string $accion,
        ?string $tabla = null,
        ?int $idRegistro = null,
        ?string $detalle = null,
        ?int $idUsuario = null,
    ): void {
        try {
            Bitacora::create([
                'id_usuario' => $idUsuario ?? Auth::id(),
                'fecha_hora' => now(),
                'ip' => request()?->ip(),
                'accion' => $accion,
                'tabla' => $tabla,
                'id_registro' => $idRegistro,
                'detalle' => $detalle !== null ? mb_substr($detalle, 0, 1000) : null,
            ]);
        } catch (Throwable $e) {
            Log::error('No se pudo registrar en bitácora', ['accion' => $accion, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Consulta paginada de la bitácora, más reciente primero (20 por página).
     * El filtro de acción usa el nombre en español e incluye los registros
     * antiguos guardados con el nombre en inglés.
     *
     * @param array{desde?: ?string, hasta?: ?string, usuario?: ?int, accion?: ?string, ip?: ?string} $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        return Bitacora::query()
            ->with(['usuario:id_usuario,username,id_rol', 'usuario.rol:id_rol,nombre'])
            ->when(filled($filtros['desde'] ?? null), fn ($q) => $q->where('fecha_hora', '>=', $filtros['desde'].' 00:00:00'))
            ->when(filled($filtros['hasta'] ?? null), fn ($q) => $q->where('fecha_hora', '<=', $filtros['hasta'].' 23:59:59'))
            ->when(filled($filtros['usuario'] ?? null), fn ($q) => $q->where('id_usuario', $filtros['usuario']))
            ->when(filled($filtros['accion'] ?? null), fn ($q) => $q->whereIn('accion', Bitacora::nombresDe($filtros['accion'])))
            ->when(filled($filtros['ip'] ?? null), fn ($q) => $q->where('ip', 'like', trim($filtros['ip']).'%'))
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id_bitacora')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Opciones de los filtros: usuarios (por nombre, no por ID) y las
     * acciones en español que existen en la bitácora.
     *
     * @return array{usuarios: Collection<int, User>, acciones: array<string>}
     */
    public function catalogos(): array
    {
        $acciones = DB::table('bitacora')->distinct()->pluck('accion')
            ->map(fn ($accion) => Bitacora::accionLegible($accion))
            ->unique()->sort()->values()->all();

        return [
            'usuarios' => User::query()->with('rol:id_rol,nombre')->orderBy('username')->get(['id_usuario', 'username', 'id_rol']),
            'acciones' => $acciones,
        ];
    }
}

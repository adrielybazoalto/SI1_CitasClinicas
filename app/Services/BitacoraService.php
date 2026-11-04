<?php

namespace App\Services;

use App\Models\Bitacora;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Escribe en la tabla bitacora: registrar(accion, tabla, id, detalle).
 * Consulta de solo lectura (RF04): listar(filtros). La tabla es inmutable (trigger RNF08).
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
     * Consulta paginada de la bitácora, más reciente primero.
     *
     * @param array{desde?: ?string, hasta?: ?string, usuario?: ?string, accion?: ?string, tabla?: ?string} $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        return Bitacora::query()
            ->with('usuario:id_usuario,username')
            ->when(filled($filtros['desde'] ?? null), fn ($q) => $q->where('fecha_hora', '>=', $filtros['desde'].' 00:00:00'))
            ->when(filled($filtros['hasta'] ?? null), fn ($q) => $q->where('fecha_hora', '<=', $filtros['hasta'].' 23:59:59'))
            ->when(filled($filtros['usuario'] ?? null), fn ($q) => $q->whereHas('usuario',
                fn ($u) => $u->whereRaw('lower(username) like ?', ['%'.mb_strtolower(trim($filtros['usuario'])).'%'])))
            ->when(filled($filtros['accion'] ?? null), fn ($q) => $q->where('accion', $filtros['accion']))
            ->when(filled($filtros['tabla'] ?? null), fn ($q) => $q->where('tabla', $filtros['tabla']))
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id_bitacora')
            ->paginate(25)
            ->withQueryString();
    }

    /** Valores distintos de acción y tabla para los filtros. @return array{acciones: array<string>, tablas: array<string>} */
    public function catalogos(): array
    {
        return [
            'acciones' => DB::table('bitacora')->distinct()->orderBy('accion')->pluck('accion')->all(),
            'tablas' => DB::table('bitacora')->whereNotNull('tabla')->distinct()->orderBy('tabla')->pluck('tabla')->all(),
        ];
    }
}

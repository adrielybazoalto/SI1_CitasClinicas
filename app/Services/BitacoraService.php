<?php

namespace App\Services;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Escribe en la tabla bitacora: registrar(accion, tabla, id, detalle).
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
}

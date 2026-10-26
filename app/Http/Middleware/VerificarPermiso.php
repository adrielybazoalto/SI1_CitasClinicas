<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica en el servidor que el rol del usuario tenga el permiso indicado.
 * Uso en rutas: ->middleware('permiso:cita.programar')
 * Varios códigos (basta con tener uno): ->middleware('permiso:agenda.ver,cita.asistencia')
 * Se basa en las tablas rol, permiso y rol_permiso (CU03).
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string ...$codigos): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->guest(route('login'));
        }

        foreach ($codigos as $codigo) {
            if ($usuario->tienePermiso($codigo)) {
                return $next($request);
            }
        }

        abort(403, 'No tiene permiso para realizar esta acción.');
    }
}

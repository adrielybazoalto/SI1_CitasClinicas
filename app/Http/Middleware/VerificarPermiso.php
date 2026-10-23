<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica en el servidor que el rol del usuario tenga el permiso indicado.
 * Uso en rutas: ->middleware('permiso:cita.programar')
 * Se basa en las tablas rol, permiso y rol_permiso (CU03).
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $codigo): Response
    {
        // TODO (CU03): consultar rol_permiso del rol del usuario y abortar con 403 si no tiene $codigo.
        return $next($request);
    }
}

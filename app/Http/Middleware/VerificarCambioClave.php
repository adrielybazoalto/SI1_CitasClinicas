<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CU01 · Si el usuario tiene debe_cambiar_clave = true (clave temporal o
 * restablecida por el administrador), solo puede cambiar su clave o salir.
 * Se aplica a todo el grupo web (bootstrap/app.php).
 */
class VerificarCambioClave
{
    /** Rutas permitidas mientras la clave está pendiente de cambio. */
    private const RUTAS_PERMITIDAS = ['password.cambiar', 'password.cambiar.guardar', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->debe_cambiar_clave && ! $request->routeIs(...self::RUTAS_PERMITIDAS)) {
            if ($request->expectsJson()) {
                abort(403, 'Debe cambiar su contraseña antes de continuar.');
            }

            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}

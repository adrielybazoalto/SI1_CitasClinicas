<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CU01 · Iniciar y cerrar sesión.
 * La caducidad por inactividad la controla SESSION_LIFETIME=15 (config/session.php).
 */
class AuthenticatedSessionController extends Controller
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Autentica al usuario. Si debe cambiar su clave, el middleware
     * VerificarCambioClave lo lleva al formulario antes del panel.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()->debe_cambiar_clave) {
            return redirect()->route('password.cambiar');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Cierra la sesión: registra LOGOUT y destruye la sesión del servidor.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->bitacora->registrar('LOGOUT', 'usuario', Auth::id(), 'Cierre de sesión');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CU01 · Cambio de contraseña del usuario autenticado.
 */
class PasswordController extends Controller
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /**
     * Cambio voluntario desde el perfil (pide la clave actual).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        $this->guardarClave($request, $validated['password']);

        return back()->with('status', 'password-updated');
    }

    /**
     * Formulario obligatorio cuando debe_cambiar_clave = true.
     */
    public function mostrarCambioObligatorio(Request $request): View|RedirectResponse
    {
        if (! $request->user()->debe_cambiar_clave) {
            return redirect()->route('dashboard');
        }

        return view('auth.cambiar-clave');
    }

    /**
     * Guarda la nueva clave y libera el acceso al panel principal.
     */
    public function cambiarObligatorio(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', Password::min(8), 'confirmed'],
        ], [], ['password' => 'nueva contraseña']);

        if (Hash::check($validated['password'], $request->user()->password_hash)) {
            throw ValidationException::withMessages([
                'password' => 'La nueva contraseña debe ser distinta de la temporal.',
            ]);
        }

        $this->guardarClave($request, $validated['password']);

        return redirect()->route('dashboard')->with('status', 'Contraseña actualizada correctamente.');
    }

    private function guardarClave(Request $request, string $clave): void
    {
        $usuario = $request->user();
        $usuario->forceFill([
            'password_hash' => Hash::make($clave),
            'debe_cambiar_clave' => false,
        ])->save();

        $this->bitacora->registrar('CLAVE_CAMBIADA', 'usuario', $usuario->id_usuario, 'El usuario cambió su contraseña');
    }
}

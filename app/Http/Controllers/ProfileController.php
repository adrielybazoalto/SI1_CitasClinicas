<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Datos de acceso del usuario autenticado (username y correo de la tabla usuario).
 * No existe la eliminación de la cuenta: solo el administrador la desactiva (CU02).
 */
class ProfileController extends Controller
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /**
     * Muestra el formulario del perfil.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Actualiza username y correo del propio usuario.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $usuario->fill($request->validated());

        $cambios = array_keys($usuario->getDirty());
        $usuario->save();

        if ($cambios) {
            $this->bitacora->registrar('USUARIO_MODIFICADO', 'usuario', $usuario->id_usuario,
                'Perfil propio. Campos modificados: '.implode(', ', $cambios));
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}

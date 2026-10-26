<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\UsuarioRequest;
use App\Models\Rol;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU02 · Gestionar usuarios del personal.
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class UsuarioController extends Controller
{
    public function __construct(private UsuarioService $servicio)
    {
    }

    /** Lista con búsqueda y filtros (rol, estado). */
    public function listarUsuarios(Request $request): View
    {
        $usuarios = $this->servicio->listar(
            $request->query('buscar'),
            $request->integer('id_rol') ?: null,
            $request->query('estado'),
        );

        return view('seguridad.usuarios', [
            'usuarios' => $usuarios,
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    /** Formulario de alta. */
    public function formularioRegistro(): View
    {
        return view('seguridad.usuario-form', [
            'usuario' => new User(),
            'roles' => Rol::where('estado', true)->orderBy('nombre')->get(),
        ]);
    }

    public function registrarUsuario(UsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->servicio->registrar($request->validated());

        return redirect()->route('usuarios.index')
            ->with('status', "Usuario {$usuario->username} registrado correctamente.");
    }

    /** Formulario de edición. */
    public function formularioEdicion(User $usuario): View
    {
        return view('seguridad.usuario-form', [
            'usuario' => $usuario,
            'roles' => Rol::where('estado', true)->orderBy('nombre')->get(),
        ]);
    }

    public function actualizarUsuario(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->servicio->actualizar($usuario, $request->validated(), $request->user());

        return redirect()->route('usuarios.index')
            ->with('status', "Usuario {$usuario->username} actualizado correctamente.");
    }

    /** Activa o desactiva (lógico, sin borrar el registro). */
    public function cambiarEstado(Request $request, User $usuario): RedirectResponse
    {
        $this->servicio->cambiarEstado($usuario, $request->user());

        return back()->with('status', "Usuario {$usuario->username} ".($usuario->estado ? 'activado.' : 'desactivado.'));
    }

    /** Genera una clave temporal y la muestra una sola vez (dato flash). */
    public function restablecerClave(User $usuario): RedirectResponse
    {
        $temporal = $this->servicio->restablecerClave($usuario);

        return back()
            ->with('status', "Contraseña de {$usuario->username} restablecida. Deberá cambiarla al iniciar sesión.")
            ->with('clave_temporal', ['username' => $usuario->username, 'clave' => $temporal]);
    }
}

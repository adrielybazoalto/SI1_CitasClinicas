<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\PermisosRolRequest;
use App\Http\Requests\Seguridad\RolRequest;
use App\Models\Permiso;
use App\Models\Rol;
use App\Services\RolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CU03 · Gestionar roles y permisos (matriz rol × permiso).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class RolController extends Controller
{
    public function __construct(private RolService $servicio)
    {
    }

    public function listarRoles(): View
    {
        return view('seguridad.roles', ['roles' => $this->servicio->listar()]);
    }

    public function registrarRol(RolRequest $request): RedirectResponse
    {
        $rol = $this->servicio->registrar($request->validated());

        return redirect()->route('roles.permisos', $rol)
            ->with('status', "Rol {$rol->nombre} creado. Asigne sus permisos.");
    }

    /** Matriz de permisos del rol agrupada por paquete. */
    public function cargarPermisos(Rol $rol): View
    {
        [$catalogo, $asignados] = $this->servicio->permisosDelRol($rol);

        return view('seguridad.rol-permisos', [
            'rol' => $rol,
            'catalogo' => $catalogo,
            'asignados' => $asignados,
            'codigoProtegido' => $rol->esAdministrador() ? Permiso::GESTION_ROLES : null,
        ]);
    }

    public function actualizarPermisos(PermisosRolRequest $request, Rol $rol): RedirectResponse
    {
        $this->servicio->actualizarPermisos($rol, $request->validated('permisos', []));

        return redirect()->route('roles.permisos', $rol)
            ->with('status', "Permisos del rol {$rol->nombre} actualizados.");
    }
}

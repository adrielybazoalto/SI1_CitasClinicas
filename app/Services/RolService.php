<?php

namespace App\Services;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CU03 · Gestionar roles y la matriz rol × permiso.
 */
class RolService
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /** Roles con la cantidad de usuarios y permisos asignados. */
    public function listar(): Collection
    {
        return Rol::withCount(['usuarios', 'permisos'])->orderBy('id_rol')->get();
    }

    public function registrar(array $datos): Rol
    {
        $rol = Rol::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'estado' => true,
        ]);

        $this->bitacora->registrar('ROL_CREADO', 'rol', $rol->id_rol, "Rol {$rol->nombre}");

        return $rol;
    }

    /**
     * Catálogo de permisos agrupado por paquete y los ids que ya tiene el rol.
     *
     * @return array{0: Collection, 1: array<int>}
     */
    public function permisosDelRol(Rol $rol): array
    {
        $catalogo = Permiso::orderBy('paquete')->orderBy('codigo')->get()->groupBy('paquete');
        $asignados = $rol->permisos()->pluck('permiso.id_permiso')->all();

        return [$catalogo, $asignados];
    }

    /**
     * Reemplaza los permisos del rol. Protección crítica: el rol
     * Administrador nunca pierde el permiso de gestión de roles y permisos.
     *
     * @param  array<int>  $idsPermiso
     */
    public function actualizarPermisos(Rol $rol, array $idsPermiso): void
    {
        $idsPermiso = array_values(array_unique(array_map('intval', $idsPermiso)));

        if ($rol->esAdministrador()) {
            $idGestionRoles = Permiso::where('codigo', Permiso::GESTION_ROLES)->value('id_permiso');

            if ($idGestionRoles && ! in_array($idGestionRoles, $idsPermiso, true)) {
                throw ValidationException::withMessages([
                    'permisos' => 'Al rol Administrador no se le puede retirar el permiso de gestión de roles y permisos.',
                ]);
            }
        }

        $antes = $rol->permisos()->pluck('codigo')->all();

        DB::transaction(fn () => $rol->permisos()->sync($idsPermiso));

        $despues = $rol->permisos()->pluck('codigo')->all();
        $agregados = array_diff($despues, $antes);
        $quitados = array_diff($antes, $despues);

        $this->bitacora->registrar('PERMISOS_ACTUALIZADOS', 'rol_permiso', $rol->id_rol, sprintf(
            'Rol %s. Agregados: %s. Quitados: %s.',
            $rol->nombre,
            $agregados ? implode(', ', $agregados) : 'ninguno',
            $quitados ? implode(', ', $quitados) : 'ninguno',
        ));
    }
}

<?php

namespace App\Services;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CU02 · Gestionar usuarios: alta, edición, activación/desactivación lógica
 * y restablecimiento administrativo de la contraseña.
 */
class UsuarioService
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /**
     * Lista paginada con búsqueda por username/correo y filtros de rol y estado.
     */
    public function listar(?string $buscar, ?int $idRol, ?string $estado): LengthAwarePaginator
    {
        return User::with('rol')
            ->when($buscar, function ($q) use ($buscar) {
                $texto = '%'.mb_strtolower(trim($buscar)).'%';
                $q->where(fn ($w) => $w->whereRaw('lower(username) like ?', [$texto])
                    ->orWhereRaw('lower(correo) like ?', [$texto]));
            })
            ->when($idRol, fn ($q) => $q->where('id_rol', $idRol))
            ->when($estado !== null && $estado !== '', fn ($q) => $q->where('estado', $estado === '1'))
            ->orderBy('username')
            ->paginate(15)
            ->withQueryString();
    }

    public function registrar(array $datos): User
    {
        $usuario = User::create([
            'id_rol' => $datos['id_rol'],
            'username' => $datos['username'],
            'correo' => $datos['correo'],
            'password_hash' => Hash::make($datos['password']),
            'debe_cambiar_clave' => (bool) ($datos['debe_cambiar_clave'] ?? true),
            'estado' => true,
        ]);

        $this->bitacora->registrar('USUARIO_CREADO', 'usuario', $usuario->id_usuario,
            "Usuario {$usuario->username} con rol ".$usuario->rol->nombre);

        return $usuario;
    }

    /**
     * Actualiza username, correo y rol. Un administrador no puede quitarse
     * a sí mismo el rol Administrador (evita quedar sin acceso).
     */
    public function actualizar(User $usuario, array $datos, User $actor): User
    {
        if ($usuario->is($actor) && (int) $datos['id_rol'] !== $usuario->id_rol && $usuario->rol->esAdministrador()) {
            throw ValidationException::withMessages(['id_rol' => 'No puede quitarse a sí mismo el rol Administrador.']);
        }

        $usuario->fill([
            'username' => $datos['username'],
            'correo' => $datos['correo'],
            'id_rol' => $datos['id_rol'],
        ]);

        $cambios = array_keys($usuario->getDirty());
        $usuario->save();

        if ($cambios) {
            $this->bitacora->registrar('USUARIO_MODIFICADO', 'usuario', $usuario->id_usuario,
                'Campos modificados: '.implode(', ', $cambios));
        }

        return $usuario;
    }

    /**
     * Desactivación lógica (nunca se borra). Al desactivar se cierran las
     * sesiones abiertas del usuario. Se protege la propia cuenta y al último
     * administrador activo.
     */
    public function cambiarEstado(User $usuario, User $actor): User
    {
        $nuevoEstado = ! $usuario->estado;

        if (! $nuevoEstado) {
            if ($usuario->is($actor)) {
                throw ValidationException::withMessages(['estado' => 'No puede desactivar su propia cuenta.']);
            }

            if ($usuario->rol->esAdministrador() && $this->administradoresActivos() <= 1) {
                throw ValidationException::withMessages(['estado' => 'No se puede desactivar al único administrador activo.']);
            }
        }

        DB::transaction(function () use ($usuario, $nuevoEstado) {
            $usuario->forceFill(['estado' => $nuevoEstado])->save();

            if (! $nuevoEstado) {
                DB::table('sessions')->where('user_id', $usuario->id_usuario)->delete();
            }
        });

        $this->bitacora->registrar('USUARIO_ESTADO', 'usuario', $usuario->id_usuario,
            $nuevoEstado ? 'Usuario activado' : 'Usuario desactivado');

        return $usuario;
    }

    /**
     * Genera una contraseña temporal, la guarda cifrada, obliga a cambiarla
     * en el próximo inicio de sesión y quita cualquier bloqueo.
     * Devuelve la clave en texto plano para mostrarla UNA sola vez.
     */
    public function restablecerClave(User $usuario): string
    {
        $temporal = Str::password(10, symbols: false);

        $usuario->forceFill([
            'password_hash' => Hash::make($temporal),
            'debe_cambiar_clave' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ])->save();

        $this->bitacora->registrar('CLAVE_RESTABLECIDA', 'usuario', $usuario->id_usuario,
            "Clave temporal generada para {$usuario->username}");

        return $temporal;
    }

    private function administradoresActivos(): int
    {
        return User::where('estado', true)
            ->whereHas('rol', fn ($q) => $q->where('nombre', Rol::ADMINISTRADOR))
            ->count();
    }
}

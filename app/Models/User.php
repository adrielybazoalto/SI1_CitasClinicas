<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Cuenta de acceso al sistema (personal y pacientes).
 * Tabla: usuario (ver database/sql/01_script.sql).
 *
 * La contraseña vive en la columna password_hash y la tabla no tiene
 * remember_token: la sesión siempre caduca por inactividad (CU01).
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    /** Columna que usa el guard para verificar la contraseña. */
    protected $authPasswordName = 'password_hash';

    /** Sin "recordarme": la tabla no tiene remember_token. */
    protected $rememberTokenName = '';

    protected $fillable = [
        'id_rol',
        'username',
        'correo',
        'password_hash',
        'intentos_fallidos',
        'bloqueado_hasta',
        'debe_cambiar_clave',
        'estado',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /** Códigos de permiso del rol, cargados una sola vez por petición. */
    private ?array $codigosPermiso = null;

    protected function casts(): array
    {
        return [
            'intentos_fallidos' => 'integer',
            'bloqueado_hasta' => 'datetime',
            'debe_cambiar_clave' => 'boolean',
            'estado' => 'boolean',
            'creado_en' => 'datetime',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    /** Correo usado por las notificaciones de Laravel. */
    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

    /** ¿La cuenta está bloqueada temporalmente por intentos fallidos? */
    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }

    /**
     * Verifica si el rol del usuario (activo) tiene el permiso indicado
     * en rol_permiso (CU03).
     */
    public function tienePermiso(string $codigo): bool
    {
        if ($this->codigosPermiso === null) {
            $rol = $this->rol;
            $this->codigosPermiso = ($rol && $rol->estado)
                ? $rol->permisos()->pluck('codigo')->all()
                : [];
        }

        return in_array($codigo, $this->codigosPermiso, true);
    }
}

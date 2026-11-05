<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de auditoría (RF04). La tabla es inmutable (trigger RNF08):
 * solo se inserta, nunca se modifica ni se borra.
 *
 * Las acciones se escriben con las constantes de esta clase (en español).
 * Los registros antiguos, guardados con los nombres en inglés (LOGIN,
 * LOGOUT…), NO se actualizan: se traducen al mostrarlos con EQUIVALENCIAS.
 */
class Bitacora extends Model
{
    protected $table = 'bitacora';
    protected $primaryKey = 'id_bitacora';
    public $timestamps = false;

    protected $fillable = ['id_usuario', 'fecha_hora', 'ip', 'accion', 'tabla', 'id_registro', 'detalle'];

    // P1 · Seguridad y accesos
    public const INICIO_SESION = 'INICIO_SESION';
    public const INICIO_SESION_FALLIDO = 'INICIO_SESION_FALLIDO';
    public const CIERRE_SESION = 'CIERRE_SESION';
    public const CIERRE_SESION_INACTIVIDAD = 'CIERRE_SESION_INACTIVIDAD';
    public const CUENTA_BLOQUEADA = 'CUENTA_BLOQUEADA';
    public const SOLICITUD_RECUPERACION_CLAVE = 'SOLICITUD_RECUPERACION_CLAVE';
    public const RECUPERACION_CLAVE = 'RECUPERACION_CLAVE';
    public const CAMBIO_CLAVE = 'CAMBIO_CLAVE';
    public const CREAR_USUARIO = 'CREAR_USUARIO';
    public const MODIFICAR_USUARIO = 'MODIFICAR_USUARIO';
    public const DESACTIVAR_USUARIO = 'DESACTIVAR_USUARIO';
    public const ACTIVAR_USUARIO = 'ACTIVAR_USUARIO';
    public const RESTABLECER_CLAVE = 'RESTABLECER_CLAVE';
    public const CREAR_ROL = 'CREAR_ROL';
    public const MODIFICAR_PERMISOS_ROL = 'MODIFICAR_PERMISOS_ROL';

    // P2 · Pacientes
    public const REGISTRAR_PACIENTE = 'REGISTRAR_PACIENTE';
    public const MODIFICAR_PACIENTE = 'MODIFICAR_PACIENTE';
    public const DESACTIVAR_PACIENTE = 'DESACTIVAR_PACIENTE';
    public const ACTIVAR_PACIENTE = 'ACTIVAR_PACIENTE';
    public const ACTUALIZAR_ANTECEDENTES = 'ACTUALIZAR_ANTECEDENTES';

    // P3 · Oferta médica
    public const REGISTRAR_ESPECIALIDAD = 'REGISTRAR_ESPECIALIDAD';
    public const MODIFICAR_ESPECIALIDAD = 'MODIFICAR_ESPECIALIDAD';
    public const ACTIVAR_ESPECIALIDAD = 'ACTIVAR_ESPECIALIDAD';
    public const DESACTIVAR_ESPECIALIDAD = 'DESACTIVAR_ESPECIALIDAD';
    public const REGISTRAR_MEDICO = 'REGISTRAR_MEDICO';
    public const MODIFICAR_MEDICO = 'MODIFICAR_MEDICO';
    public const ACTIVAR_MEDICO = 'ACTIVAR_MEDICO';
    public const DESACTIVAR_MEDICO = 'DESACTIVAR_MEDICO';
    public const REGISTRAR_HORARIO = 'REGISTRAR_HORARIO';
    public const MODIFICAR_HORARIO = 'MODIFICAR_HORARIO';
    public const ACTIVAR_HORARIO = 'ACTIVAR_HORARIO';
    public const DESACTIVAR_HORARIO = 'DESACTIVAR_HORARIO';

    // P4 · Citas
    public const PROGRAMAR_CITA = 'PROGRAMAR_CITA';
    public const REPROGRAMAR_CITA = 'REPROGRAMAR_CITA';
    public const CANCELAR_CITA = 'CANCELAR_CITA';
    public const REGISTRAR_LLEGADA = 'REGISTRAR_LLEGADA';
    public const REGISTRAR_INASISTENCIA = 'REGISTRAR_INASISTENCIA';
    public const INASISTENCIA_AUTOMATICA = 'INASISTENCIA_AUTOMATICA';

    // P5 · Atención y P6 · Reportes
    public const REGISTRAR_ATENCION = 'REGISTRAR_ATENCION';
    public const EXPORTAR_REPORTE = 'EXPORTAR_REPORTE';

    /** Nombre antiguo (registros ya guardados) => acción en español. */
    public const EQUIVALENCIAS = [
        'LOGIN' => self::INICIO_SESION,
        'LOGIN_FALLIDO' => self::INICIO_SESION_FALLIDO,
        'LOGOUT' => self::CIERRE_SESION,
        'RECUPERACION_SOLICITADA' => self::SOLICITUD_RECUPERACION_CLAVE,
        'CLAVE_RECUPERADA' => self::RECUPERACION_CLAVE,
        'RECUPERACION_CLAVE' => self::RECUPERACION_CLAVE,
        'CLAVE_CAMBIADA' => self::CAMBIO_CLAVE,
        'CLAVE_RESTABLECIDA' => self::RESTABLECER_CLAVE,
        'USUARIO_CREADO' => self::CREAR_USUARIO,
        'USUARIO_MODIFICADO' => self::MODIFICAR_USUARIO,
        'ROL_CREADO' => self::CREAR_ROL,
        'PERMISOS_ACTUALIZADOS' => self::MODIFICAR_PERMISOS_ROL,
        'PACIENTE_CREADO' => self::REGISTRAR_PACIENTE,
        'PACIENTE_MODIFICADO' => self::MODIFICAR_PACIENTE,
        'PACIENTE_ACTIVADO' => self::ACTIVAR_PACIENTE,
        'PACIENTE_DESACTIVADO' => self::DESACTIVAR_PACIENTE,
        'ANTECEDENTE_ACTUALIZADO' => self::ACTUALIZAR_ANTECEDENTES,
        'ESPECIALIDAD_CREADA' => self::REGISTRAR_ESPECIALIDAD,
        'ESPECIALIDAD_MODIFICADA' => self::MODIFICAR_ESPECIALIDAD,
        'ESPECIALIDAD_ACTIVADA' => self::ACTIVAR_ESPECIALIDAD,
        'ESPECIALIDAD_DESACTIVADA' => self::DESACTIVAR_ESPECIALIDAD,
        'MEDICO_CREADO' => self::REGISTRAR_MEDICO,
        'MEDICO_MODIFICADO' => self::MODIFICAR_MEDICO,
        'MEDICO_ACTIVADO' => self::ACTIVAR_MEDICO,
        'MEDICO_DESACTIVADO' => self::DESACTIVAR_MEDICO,
        'HORARIO_CREADO' => self::REGISTRAR_HORARIO,
        'HORARIO_MODIFICADO' => self::MODIFICAR_HORARIO,
        'HORARIO_ACTIVADO' => self::ACTIVAR_HORARIO,
        'HORARIO_DESACTIVADO' => self::DESACTIVAR_HORARIO,
        'CITA_PROGRAMADA' => self::PROGRAMAR_CITA,
        'CITA_REPROGRAMADA' => self::REPROGRAMAR_CITA,
        'CITA_CANCELADA' => self::CANCELAR_CITA,
        'CITA_CONFIRMADA' => self::REGISTRAR_LLEGADA,
        'CITA_INASISTENCIA' => self::REGISTRAR_INASISTENCIA,
        'CITA_INASISTENCIA_AUTO' => self::INASISTENCIA_AUTOMATICA,
        'ATENCION_REGISTRADA' => self::REGISTRAR_ATENCION,
        'REPORTE_EXPORTADO' => self::EXPORTAR_REPORTE,
        'CAJA_ABIERTA' => 'ABRIR_CAJA',
        'TARIFA_ACTUALIZADA' => 'ACTUALIZAR_TARIFA',
        'INDISPONIBILIDAD_REGISTRADA' => 'REGISTRAR_INDISPONIBILIDAD',
        'MIGRACION' => 'CAMBIO_ESQUEMA',
    ];

    /** Acción en español, también para los registros antiguos. */
    public static function accionLegible(?string $accion): string
    {
        return self::EQUIVALENCIAS[$accion] ?? (string) $accion;
    }

    /** Nombres guardados que corresponden a una acción en español (la nueva y las antiguas). */
    public static function nombresDe(string $accion): array
    {
        return array_values(array_unique(array_merge(
            [$accion],
            array_keys(array_filter(self::EQUIVALENCIAS, fn ($nueva) => $nueva === $accion)),
        )));
    }

    public function getAccionLegibleAttribute(): string
    {
        return self::accionLegible($this->accion);
    }

    protected function casts(): array
    {
        return ['fecha_hora' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}

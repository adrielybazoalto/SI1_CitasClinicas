<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Paquete Seguridad: roles base, catálogo de permisos, matriz inicial
 * rol × permiso y la cuenta del administrador.
 *
 * Es idempotente: solo crea lo que falta y no pisa permisos que el
 * administrador ya haya cambiado desde CU03.
 */
class SeguridadSeeder extends Seeder
{
    /**
     * codigo => [descripcion, paquete]
     * Mismo catálogo que los datos cargados en Supabase (23 permisos).
     */
    private const PERMISOS = [
        'usuario.gestionar' => ['Registrar, modificar y desactivar usuarios', 'Seguridad'],
        'rol.gestionar' => ['Gestionar roles y permisos', 'Seguridad'],
        'bitacora.ver' => ['Consultar la bitácora', 'Seguridad'],

        'paciente.gestionar' => ['Registrar y modificar pacientes', 'Pacientes'],
        'antecedente.gestionar' => ['Registrar antecedentes clínicos', 'Pacientes'],
        'perfil.gestionar' => ['Gestionar su propio perfil', 'Pacientes'],

        'especialidad.gestionar' => ['Gestionar especialidades', 'Oferta medica'],
        'medico.gestionar' => ['Gestionar médicos', 'Oferta medica'],
        'horario.gestionar' => ['Gestionar horarios de atención', 'Oferta medica'],
        'indisponibilidad.registrar' => ['Registrar indisponibilidad médica', 'Oferta medica'],
        'tarifa.gestionar' => ['Gestionar tarifario', 'Oferta medica'],

        'cita.consultar' => ['Consultar citas disponibles', 'Citas'],
        'cita.programar' => ['Programar citas', 'Citas'],
        'cita.reprogramar' => ['Reprogramar citas', 'Citas'],
        'cita.cancelar' => ['Cancelar citas', 'Citas'],
        'cita.asistencia' => ['Registrar asistencia e inasistencia', 'Citas'],
        'pago.en_linea' => ['Pagar citas en línea', 'Citas'],

        'caja.gestionar' => ['Abrir y cerrar caja', 'Caja'],
        'caja.cobrar' => ['Cobrar citas en mostrador', 'Caja'],

        'agenda.consultar' => ['Consultar agenda médica', 'Atencion'],
        'atencion.registrar' => ['Registrar atención médica', 'Atencion'],
        'historial.consultar' => ['Consultar historial del paciente', 'Atencion'],

        'reporte.generar' => ['Generar reportes', 'Reportes'],
    ];

    /** Permisos iniciales por rol (misma matriz que los datos de Supabase). */
    private const ROLES = [
        'Administrador' => ['Configura y supervisa el sistema', [
            'usuario.gestionar', 'rol.gestionar', 'bitacora.ver', 'paciente.gestionar',
            'antecedente.gestionar', 'especialidad.gestionar', 'medico.gestionar',
            'horario.gestionar', 'indisponibilidad.registrar', 'tarifa.gestionar',
            'cita.consultar', 'cita.programar', 'cita.reprogramar', 'cita.cancelar',
            'cita.asistencia', 'caja.gestionar', 'caja.cobrar', 'agenda.consultar',
            'historial.consultar', 'reporte.generar',
        ]],
        'Recepcionista' => ['Gestiona pacientes, citas y caja', [
            'paciente.gestionar', 'antecedente.gestionar', 'indisponibilidad.registrar',
            'cita.consultar', 'cita.programar', 'cita.reprogramar', 'cita.cancelar',
            'cita.asistencia', 'caja.gestionar', 'caja.cobrar', 'agenda.consultar',
            'historial.consultar', 'reporte.generar',
        ]],
        'Medico' => ['Consulta su agenda y registra atenciones', [
            'agenda.consultar', 'antecedente.gestionar', 'atencion.registrar', 'historial.consultar',
        ]],
        'Paciente' => ['Reserva y consulta sus citas', [
            'perfil.gestionar', 'cita.consultar', 'cita.programar', 'cita.reprogramar',
            'cita.cancelar', 'pago.en_linea', 'historial.consultar',
        ]],
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $codigo => [$descripcion, $paquete]) {
            Permiso::firstOrCreate(['codigo' => $codigo], ['descripcion' => $descripcion, 'paquete' => $paquete]);
        }

        foreach (self::ROLES as $nombre => [$descripcion, $codigos]) {
            $rol = Rol::firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion, 'estado' => true]);

            if ($rol->wasRecentlyCreated) {
                $permisos = Permiso::whereIn('codigo', $codigos);
                $rol->permisos()->sync($permisos->pluck('id_permiso'));
            }
        }

        // El Administrador siempre conserva la gestión de roles (CU03).
        $admin = Rol::where('nombre', Rol::ADMINISTRADOR)->first();
        $admin->permisos()->syncWithoutDetaching(
            Permiso::where('codigo', Permiso::GESTION_ROLES)->pluck('id_permiso')
        );

        $this->crearAdministrador($admin);
    }

    /**
     * Cuenta inicial "admin". La clave sale de ADMIN_PASSWORD o se genera
     * al azar; en ambos casos debe cambiarse en el primer inicio de sesión.
     */
    private function crearAdministrador(Rol $admin): void
    {
        if (User::where('username', 'admin')->exists()) {
            return;
        }

        $clave = env('ADMIN_PASSWORD') ?: Str::password(12, symbols: false);

        User::create([
            'id_rol' => $admin->id_rol,
            'username' => 'admin',
            'correo' => env('ADMIN_EMAIL', 'admin@vidasalud.bo'),
            'password_hash' => Hash::make($clave),
            'debe_cambiar_clave' => true,
            'estado' => true,
        ]);

        $this->command?->warn("Usuario administrador creado: admin / {$clave} (debe cambiarla al ingresar)");
    }
}

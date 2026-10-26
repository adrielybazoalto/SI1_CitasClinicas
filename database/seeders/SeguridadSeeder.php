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
    /** codigo => [descripcion, paquete] */
    private const PERMISOS = [
        'seguridad.usuarios' => ['Gestionar usuarios', 'Seguridad'],
        'seguridad.roles' => ['Gestionar roles y permisos', 'Seguridad'],
        'seguridad.bitacora' => ['Consultar bitácora', 'Seguridad'],

        'paciente.gestionar' => ['Gestionar pacientes', 'Pacientes'],
        'antecedente.ver' => ['Ver antecedentes clínicos', 'Pacientes'],
        'antecedente.editar' => ['Editar antecedentes clínicos', 'Pacientes'],
        'perfil.gestionar' => ['Gestionar perfil propio del paciente', 'Pacientes'],

        'especialidad.gestionar' => ['Gestionar especialidades', 'Oferta médica'],
        'medico.gestionar' => ['Gestionar médicos', 'Oferta médica'],
        'horario.gestionar' => ['Gestionar horarios de atención', 'Oferta médica'],
        'indisponibilidad.gestionar' => ['Registrar indisponibilidad médica', 'Oferta médica'],
        'tarifa.gestionar' => ['Gestionar tarifario', 'Oferta médica'],

        'ficha.consultar' => ['Consultar fichas disponibles', 'Citas'],
        'cita.programar' => ['Programar cita', 'Citas'],
        'cita.reprogramar' => ['Reprogramar cita', 'Citas'],
        'cita.cancelar' => ['Cancelar cita', 'Citas'],
        'cita.asistencia' => ['Registrar asistencia e inasistencia', 'Citas'],
        'cita.pagar' => ['Pagar ficha en línea', 'Citas'],
        'cita.atender' => ['Marcar cita como atendida', 'Citas'],

        'agenda.ver' => ['Consultar agenda médica', 'Atención'],
        'atencion.registrar' => ['Registrar atención médica', 'Atención'],
        'historial.ver' => ['Consultar historial del paciente', 'Atención'],

        'caja.gestionar' => ['Gestionar caja', 'Caja'],
        'caja.cobrar' => ['Cobrar ficha en mostrador', 'Caja'],

        'reporte.generar' => ['Generar reportes', 'Reportes'],
    ];

    /** Permisos iniciales por rol ('*' = todos). */
    private const ROLES = [
        'Administrador' => ['Acceso total al sistema', ['*']],
        'Recepcionista' => ['Atención en mostrador y gestión de citas', [
            'paciente.gestionar', 'antecedente.ver', 'antecedente.editar', 'ficha.consultar',
            'cita.programar', 'cita.reprogramar', 'cita.cancelar', 'cita.asistencia',
            'indisponibilidad.gestionar', 'agenda.ver', 'historial.ver', 'caja.gestionar',
            'caja.cobrar', 'reporte.generar',
        ]],
        'Médico' => ['Agenda y registro de atenciones', [
            'agenda.ver', 'antecedente.ver', 'antecedente.editar', 'atencion.registrar',
            'cita.atender', 'historial.ver',
        ]],
        'Paciente' => ['Reserva y seguimiento de sus citas', [
            'perfil.gestionar', 'ficha.consultar', 'cita.programar', 'cita.reprogramar',
            'cita.cancelar', 'cita.pagar', 'historial.ver',
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
                $permisos = $codigos === ['*'] ? Permiso::query() : Permiso::whereIn('codigo', $codigos);
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
            'correo' => env('ADMIN_EMAIL', 'admin@vidasalud.com'),
            'password_hash' => Hash::make($clave),
            'debe_cambiar_clave' => true,
            'estado' => true,
        ]);

        $this->command?->warn("Usuario administrador creado: admin / {$clave} (debe cambiarla al ingresar)");
    }
}

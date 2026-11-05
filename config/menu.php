<?php

/*
|--------------------------------------------------------------------------
| Menú por los 6 paquetes del documento PUDS (4.5.1)
|--------------------------------------------------------------------------
| Lo usan el menú lateral, el encabezado de cada página y el panel de inicio
| (App\Support\MenuModulos).
| permiso:     código de la tabla permiso (string, o array = basta con uno).
|              Debe coincidir con el middleware de la ruta, para que nadie
|              vea un enlace que le daría 403.
| activo:      patrones de nombre de ruta que marcan el ítem como actual.
| icono:       nombre del ícono de contorno (<x-icono>).
| descripcion: una línea; se muestra en las tarjetas del inicio.
| Si la ruta todavía no existe, el ítem aparece en gris como "Pronto".
*/
return [
    [
        'paquete' => 'P1', 'titulo' => 'Seguridad y Accesos',
        'items' => [
            ['cu' => 'CU02', 'titulo' => 'Usuarios', 'ruta' => 'usuarios.index', 'activo' => ['usuarios.*'], 'permiso' => 'usuario.gestionar',
                'icono' => 'usuarios', 'descripcion' => 'Cuentas del personal: registrar, modificar, desactivar y restablecer claves.'],
            ['cu' => 'CU03', 'titulo' => 'Roles y permisos', 'ruta' => 'roles.index', 'activo' => ['roles.*'], 'permiso' => 'rol.gestionar',
                'icono' => 'escudo', 'descripcion' => 'Matriz de permisos de cada rol del sistema.'],
            ['cu' => 'RF04', 'titulo' => 'Bitácora', 'ruta' => 'bitacora.index', 'activo' => ['bitacora.*'], 'permiso' => 'bitacora.ver',
                'icono' => 'bitacora', 'descripcion' => 'Registro de solo lectura de todas las operaciones.'],
        ],
    ],
    [
        'paquete' => 'P2', 'titulo' => 'Gestión de Pacientes',
        'items' => [
            // La lista exige paciente.gestionar; el médico llega a los antecedentes desde la atención (CU21).
            ['cu' => 'CU05 · CU06', 'titulo' => 'Pacientes y antecedentes', 'ruta' => 'pacientes.index', 'activo' => ['pacientes.*'], 'permiso' => 'paciente.gestionar',
                'icono' => 'paciente', 'descripcion' => 'Registro de pacientes, cuenta de acceso y antecedentes clínicos.'],
            ['cu' => 'CU04', 'titulo' => 'Mi perfil', 'ruta' => 'perfil.edit', 'activo' => ['perfil.*'], 'permiso' => 'perfil.gestionar',
                'icono' => 'perfil', 'descripcion' => 'Datos de contacto, contraseña y recordatorios.'],
        ],
    ],
    [
        'paquete' => 'P3', 'titulo' => 'Oferta Médica',
        'items' => [
            ['cu' => 'CU07', 'titulo' => 'Especialidades', 'ruta' => 'especialidades.index', 'activo' => ['especialidades.*'], 'permiso' => 'especialidad.gestionar',
                'icono' => 'especialidad', 'descripcion' => 'Especialidades y duración de la consulta.'],
            ['cu' => 'CU08', 'titulo' => 'Médicos', 'ruta' => 'medicos.index', 'activo' => ['medicos.*'], 'permiso' => 'medico.gestionar',
                'icono' => 'medico', 'descripcion' => 'Médicos, matrícula y especialidades que atienden.'],
            ['cu' => 'CU09', 'titulo' => 'Horarios de atención', 'ruta' => 'horarios.index', 'activo' => ['horarios.*'], 'permiso' => 'horario.gestionar',
                'icono' => 'reloj', 'descripcion' => 'Turnos semanales de cada médico y consultorio.'],
            ['cu' => 'CU10', 'titulo' => 'Indisponibilidad médica', 'ruta' => 'indisponibilidades.index', 'activo' => ['indisponibilidades.*'], 'permiso' => 'indisponibilidad.registrar',
                'icono' => 'bloqueo', 'descripcion' => 'Ausencias del médico y reubicación de sus citas.'],
            ['cu' => 'CU11', 'titulo' => 'Tarifario', 'ruta' => 'tarifas.index', 'activo' => ['tarifas.*'], 'permiso' => 'tarifa.gestionar',
                'icono' => 'tarifa', 'descripcion' => 'Precio vigente de cada especialidad e historial de precios.'],
        ],
    ],
    [
        'paquete' => 'P4', 'titulo' => 'Gestión de Citas',
        'items' => [
            ['cu' => 'CU12 · CU13', 'titulo' => 'Reservar cita', 'ruta' => 'fichas.index', 'activo' => ['fichas.*'], 'permiso' => 'cita.consultar',
                'icono' => 'calendario', 'descripcion' => 'Consultar horarios disponibles y programar una cita.'],
            ['cu' => 'CU15 · CU16 · CU17', 'titulo' => 'Citas', 'ruta' => 'citas.index', 'activo' => ['citas.*'], 'permiso' => ['cita.consultar', 'cita.asistencia'],
                'icono' => 'citas', 'descripcion' => 'Reprogramar, cancelar y registrar llegada o inasistencia.'],
            ['cu' => 'CU14', 'titulo' => 'Pagar cita en línea', 'ruta' => 'pagos.index', 'activo' => ['pagos.*'], 'permiso' => 'pago.en_linea',
                'icono' => 'tarjeta', 'descripcion' => 'Pago con PayPal o QR antes de que venza la reserva.'],
        ],
    ],
    [
        'paquete' => 'P5', 'titulo' => 'Atención Médica',
        'items' => [
            ['cu' => 'CU20 · CU21', 'titulo' => 'Agenda y atención', 'ruta' => 'agenda.index', 'activo' => ['agenda.*', 'atencion.*'], 'permiso' => 'agenda.consultar',
                'icono' => 'agenda', 'descripcion' => 'Agenda diaria y semanal; registrar la atención de la cita.'],
            ['cu' => 'CU22', 'titulo' => 'Historial del paciente', 'ruta' => 'historial.index', 'activo' => ['historial.*'], 'permiso' => 'historial.consultar',
                'icono' => 'historial', 'descripcion' => 'Todas las citas del paciente, incluidas canceladas e inasistencias.'],
        ],
    ],
    [
        'paquete' => 'P6', 'titulo' => 'Gestión Financiera y Reportes',
        'items' => [
            ['cu' => 'CU18', 'titulo' => 'Caja', 'ruta' => 'caja.index', 'activo' => ['caja.*'], 'permiso' => 'caja.gestionar',
                'icono' => 'caja', 'descripcion' => 'Apertura, cierre y arqueo de caja.'],
            ['cu' => 'CU19', 'titulo' => 'Cobros en mostrador', 'ruta' => 'cobros.index', 'activo' => ['cobros.*'], 'permiso' => 'caja.cobrar',
                'icono' => 'recibo', 'descripcion' => 'Cobro de citas y emisión de comprobantes.'],
            ['cu' => 'CU23', 'titulo' => 'Reportes', 'ruta' => 'reportes.index', 'activo' => ['reportes.*'], 'permiso' => 'reporte.generar',
                'icono' => 'reporte', 'descripcion' => 'Reportes con subtotales, exportables a PDF y CSV.'],
        ],
    ],
];

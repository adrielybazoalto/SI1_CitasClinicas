<?php

/*
|--------------------------------------------------------------------------
| Menú lateral organizado por los 6 paquetes del documento PUDS (4.5.1)
|--------------------------------------------------------------------------
| permiso: código de la tabla permiso (string, o array = basta con uno).
|          Debe coincidir con el middleware de la ruta, para que nadie vea
|          un enlace que le daría 403.
| activo:  patrones de nombre de ruta que marcan el ítem como actual.
| Si la ruta todavía no existe, el ítem aparece en gris como "Pronto".
*/
return [
    [
        'paquete' => 'P1', 'titulo' => 'Seguridad y Accesos',
        'items' => [
            ['cu' => 'CU02', 'titulo' => 'Usuarios', 'ruta' => 'usuarios.index', 'activo' => ['usuarios.*'], 'permiso' => 'usuario.gestionar'],
            ['cu' => 'CU03', 'titulo' => 'Roles y permisos', 'ruta' => 'roles.index', 'activo' => ['roles.*'], 'permiso' => 'rol.gestionar'],
            ['cu' => 'RF04', 'titulo' => 'Bitácora', 'ruta' => 'bitacora.index', 'activo' => ['bitacora.*'], 'permiso' => 'bitacora.ver'],
        ],
    ],
    [
        'paquete' => 'P2', 'titulo' => 'Gestión de Pacientes',
        'items' => [
            // La lista exige paciente.gestionar; el médico llega a los antecedentes desde la atención (CU21).
            ['cu' => 'CU05 · CU06', 'titulo' => 'Pacientes y antecedentes', 'ruta' => 'pacientes.index', 'activo' => ['pacientes.*'], 'permiso' => 'paciente.gestionar'],
            ['cu' => 'CU04', 'titulo' => 'Mi perfil', 'ruta' => 'perfil.edit', 'activo' => ['perfil.*'], 'permiso' => 'perfil.gestionar'],
        ],
    ],
    [
        'paquete' => 'P3', 'titulo' => 'Oferta Médica',
        'items' => [
            ['cu' => 'CU07', 'titulo' => 'Especialidades', 'ruta' => 'especialidades.index', 'activo' => ['especialidades.*'], 'permiso' => 'especialidad.gestionar'],
            ['cu' => 'CU08', 'titulo' => 'Médicos', 'ruta' => 'medicos.index', 'activo' => ['medicos.*'], 'permiso' => 'medico.gestionar'],
            ['cu' => 'CU09', 'titulo' => 'Horarios de atención', 'ruta' => 'horarios.index', 'activo' => ['horarios.*'], 'permiso' => 'horario.gestionar'],
            ['cu' => 'CU10', 'titulo' => 'Indisponibilidad médica', 'ruta' => 'indisponibilidades.index', 'activo' => ['indisponibilidades.*'], 'permiso' => 'indisponibilidad.registrar'],
            ['cu' => 'CU11', 'titulo' => 'Tarifario', 'ruta' => 'tarifas.index', 'activo' => ['tarifas.*'], 'permiso' => 'tarifa.gestionar'],
        ],
    ],
    [
        'paquete' => 'P4', 'titulo' => 'Gestión de Citas',
        'items' => [
            ['cu' => 'CU12 · CU13', 'titulo' => 'Citas disponibles y reservar', 'ruta' => 'fichas.index', 'activo' => ['fichas.*'], 'permiso' => 'cita.consultar'],
            ['cu' => 'CU15 · CU16 · CU17', 'titulo' => 'Citas: reprogramar, cancelar, asistencia', 'ruta' => 'citas.index', 'activo' => ['citas.*'], 'permiso' => ['cita.consultar', 'cita.asistencia']],
            ['cu' => 'CU14', 'titulo' => 'Pagar cita en línea', 'ruta' => 'pagos.index', 'activo' => ['pagos.*'], 'permiso' => 'pago.en_linea'],
        ],
    ],
    [
        'paquete' => 'P5', 'titulo' => 'Atención Médica',
        'items' => [
            ['cu' => 'CU20 · CU21', 'titulo' => 'Agenda y atención', 'ruta' => 'agenda.index', 'activo' => ['agenda.*', 'atencion.*'], 'permiso' => 'agenda.consultar'],
            ['cu' => 'CU22', 'titulo' => 'Historial del paciente', 'ruta' => 'historial.index', 'activo' => ['historial.*'], 'permiso' => 'historial.consultar'],
        ],
    ],
    [
        'paquete' => 'P6', 'titulo' => 'Gestión Financiera y Reportes',
        'items' => [
            ['cu' => 'CU18', 'titulo' => 'Caja', 'ruta' => 'caja.index', 'activo' => ['caja.*'], 'permiso' => 'caja.gestionar'],
            ['cu' => 'CU19', 'titulo' => 'Cobros en mostrador', 'ruta' => 'cobros.index', 'activo' => ['cobros.*'], 'permiso' => 'caja.cobrar'],
            ['cu' => 'CU23', 'titulo' => 'Reportes', 'ruta' => 'reportes.index', 'activo' => ['reportes.*'], 'permiso' => 'reporte.generar'],
        ],
    ],
];

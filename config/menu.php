<?php

/*
|--------------------------------------------------------------------------
| Menú: un ítem por caso de uso, agrupado en los 6 paquetes del PUDS (4.5.1)
|--------------------------------------------------------------------------
| Lo usan el menú lateral, el encabezado y el aviso de cada página y el
| panel de inicio (App\Support\MenuModulos).
|
| permiso:     código de la tabla permiso (string, o array = basta con uno).
| ruta:        nombre de la ruta; si no existe todavía, el ítem sale "Pronto".
| parametros:  parámetros de la ruta. Cuando dos CU comparten pantalla, el
|              ítem lleva ['accion' => '...'] y la pantalla se filtra sola.
| activo:      patrones de ruta de la pantalla (se exige el mismo ?accion).
| tambien:     rutas a las que se llega desde otra pantalla y que siempre
|              son de este CU, aunque la URL no lleve ?accion.
| aviso:       qué hacer, cuando se entra con ?accion (<x-aviso-modulo>).
| icono:       ícono de contorno (<x-icono>).
| descripcion: una línea para las tarjetas del inicio.
*/
return [
    [
        'paquete' => 'P1', 'titulo' => 'Seguridad y Accesos',
        'items' => [
            ['cu' => 'CU02', 'titulo' => 'Gestionar usuarios', 'ruta' => 'usuarios.index', 'activo' => ['usuarios.*'], 'permiso' => 'usuario.gestionar',
                'icono' => 'usuarios', 'descripcion' => 'Cuentas del personal: registrar, modificar, desactivar y restablecer claves.'],
            ['cu' => 'CU03', 'titulo' => 'Gestionar roles y permisos', 'ruta' => 'roles.index', 'activo' => ['roles.*'], 'permiso' => 'rol.gestionar',
                'icono' => 'escudo', 'descripcion' => 'Matriz de permisos de cada rol del sistema.'],
            ['cu' => 'CU24', 'titulo' => 'Consultar bitácora', 'ruta' => 'bitacora.index', 'activo' => ['bitacora.*'], 'permiso' => 'bitacora.ver',
                'icono' => 'bitacora', 'descripcion' => 'Registro de solo lectura de todas las operaciones del sistema.'],
        ],
    ],
    [
        'paquete' => 'P2', 'titulo' => 'Gestión de Pacientes',
        'items' => [
            ['cu' => 'CU04', 'titulo' => 'Mi perfil', 'ruta' => 'perfil.edit', 'activo' => ['perfil.*'], 'permiso' => 'perfil.gestionar',
                'icono' => 'perfil', 'descripcion' => 'Datos de contacto, contraseña y recordatorios.'],
            ['cu' => 'CU05', 'titulo' => 'Gestionar pacientes', 'ruta' => 'pacientes.index', 'activo' => ['pacientes.*'], 'permiso' => 'paciente.gestionar',
                'icono' => 'paciente', 'descripcion' => 'Registro de pacientes y de su cuenta de acceso.'],
            ['cu' => 'CU06', 'titulo' => 'Antecedentes clínicos', 'ruta' => 'pacientes.index', 'parametros' => ['accion' => 'antecedentes'],
                'activo' => ['pacientes.index'], 'tambien' => ['pacientes.antecedentes', 'pacientes.antecedentes.*'], 'permiso' => 'antecedente.gestionar',
                'aviso' => 'Seleccione un paciente para registrar o actualizar sus antecedentes clínicos.',
                'icono' => 'historial', 'descripcion' => 'Tipo de sangre, alergias, enfermedades crónicas y contacto de emergencia.'],
        ],
    ],
    [
        'paquete' => 'P3', 'titulo' => 'Oferta Médica',
        'items' => [
            ['cu' => 'CU07', 'titulo' => 'Gestionar especialidades', 'ruta' => 'especialidades.index', 'activo' => ['especialidades.*'], 'permiso' => 'especialidad.gestionar',
                'icono' => 'especialidad', 'descripcion' => 'Especialidades y duración de la consulta.'],
            ['cu' => 'CU08', 'titulo' => 'Gestionar médicos', 'ruta' => 'medicos.index', 'activo' => ['medicos.*'], 'permiso' => 'medico.gestionar',
                'icono' => 'medico', 'descripcion' => 'Médicos, matrícula y especialidades que atienden.'],
            ['cu' => 'CU09', 'titulo' => 'Gestionar horarios', 'ruta' => 'horarios.index', 'activo' => ['horarios.*'], 'permiso' => 'horario.gestionar',
                'icono' => 'reloj', 'descripcion' => 'Turnos semanales de cada médico y consultorio.'],
            ['cu' => 'CU10', 'titulo' => 'Indisponibilidad médica', 'ruta' => 'indisponibilidades.index', 'activo' => ['indisponibilidades.*'], 'permiso' => 'indisponibilidad.registrar',
                'icono' => 'bloqueo', 'descripcion' => 'Ausencias del médico y reubicación de sus citas.'],
            ['cu' => 'CU11', 'titulo' => 'Gestionar tarifario', 'ruta' => 'tarifas.index', 'activo' => ['tarifas.*'], 'permiso' => 'tarifa.gestionar',
                'icono' => 'tarifa', 'descripcion' => 'Precio vigente de cada especialidad e historial de precios.'],
        ],
    ],
    [
        'paquete' => 'P4', 'titulo' => 'Gestión de Citas',
        'items' => [
            ['cu' => 'CU12', 'titulo' => 'Consultar citas disponibles', 'ruta' => 'fichas.index', 'parametros' => ['accion' => 'consultar'],
                'activo' => ['fichas.*'], 'permiso' => 'cita.consultar',
                'aviso' => 'Elija especialidad, médico y fecha para ver los horarios libres. Para reservar, use «Programar cita».',
                'icono' => 'calendario', 'descripcion' => 'Horarios libres por especialidad, médico y fecha.'],
            ['cu' => 'CU13', 'titulo' => 'Programar cita', 'ruta' => 'fichas.index', 'activo' => ['fichas.*'], 'permiso' => 'cita.programar',
                'icono' => 'agenda', 'descripcion' => 'Elegir un horario libre y reservar la cita.'],
            ['cu' => 'CU14', 'titulo' => 'Pagar cita en línea', 'ruta' => 'pagos.index', 'activo' => ['pagos.*'], 'permiso' => 'pago.en_linea',
                'icono' => 'tarjeta', 'descripcion' => 'Pago con PayPal o QR antes de que venza la reserva.'],
            ['cu' => 'CU15', 'titulo' => 'Reprogramar cita', 'ruta' => 'citas.index', 'parametros' => ['accion' => 'reprogramar'],
                'activo' => ['citas.*'], 'tambien' => ['citas.reprogramar', 'citas.reprogramar.update'], 'permiso' => 'cita.reprogramar',
                'aviso' => 'Se muestran las citas programadas. Pulse «Reprogramar» en la cita que desea mover a otro horario.',
                'icono' => 'reloj', 'descripcion' => 'Mover una cita programada a otro horario libre.'],
            ['cu' => 'CU16', 'titulo' => 'Cancelar cita', 'ruta' => 'citas.index', 'parametros' => ['accion' => 'cancelar'],
                'activo' => ['citas.*'], 'permiso' => 'cita.cancelar',
                'aviso' => 'Se muestran las citas programadas. Pulse «Cancelar» en la cita e indique el motivo.',
                'icono' => 'bloqueo', 'descripcion' => 'Anular una cita programada indicando el motivo.'],
            ['cu' => 'CU17', 'titulo' => 'Registrar asistencia', 'ruta' => 'citas.index', 'parametros' => ['accion' => 'asistencia'],
                'activo' => ['citas.*'], 'permiso' => 'cita.asistencia',
                'aviso' => 'Citas de hoy. Pulse «Registrar llegada» cuando el paciente llegue, o «Inasistencia» si no se presentó.',
                'icono' => 'citas', 'descripcion' => 'Registrar la llegada o la inasistencia del paciente.'],
        ],
    ],
    [
        'paquete' => 'P5', 'titulo' => 'Atención Médica',
        'items' => [
            ['cu' => 'CU20', 'titulo' => 'Consultar agenda médica', 'ruta' => 'agenda.index', 'activo' => ['agenda.*'], 'permiso' => 'agenda.consultar',
                'icono' => 'agenda', 'descripcion' => 'Agenda diaria y semanal del médico.'],
            ['cu' => 'CU21', 'titulo' => 'Registrar atención médica', 'ruta' => 'agenda.index', 'parametros' => ['accion' => 'atender'],
                'activo' => ['agenda.*'], 'tambien' => ['atencion.*'], 'permiso' => 'atencion.registrar',
                'aviso' => 'Se muestran sus citas confirmadas (el paciente ya llegó). Pulse «Atender» para registrar la consulta.',
                'icono' => 'medico', 'descripcion' => 'Diagnóstico, tratamiento y signos vitales de la consulta.'],
            ['cu' => 'CU22', 'titulo' => 'Consultar historial', 'ruta' => 'historial.index', 'activo' => ['historial.*'], 'permiso' => 'historial.consultar',
                'icono' => 'historial', 'descripcion' => 'Todas las citas del paciente, incluidas canceladas e inasistencias.'],
        ],
    ],
    [
        'paquete' => 'P6', 'titulo' => 'Gestión Financiera y Reportes',
        'items' => [
            ['cu' => 'CU18', 'titulo' => 'Gestionar caja', 'ruta' => 'caja.index', 'activo' => ['caja.*'], 'permiso' => 'caja.gestionar',
                'icono' => 'caja', 'descripcion' => 'Apertura, cierre y arqueo de caja.'],
            ['cu' => 'CU19', 'titulo' => 'Cobrar en mostrador', 'ruta' => 'cobros.index', 'activo' => ['cobros.*'], 'permiso' => 'caja.cobrar',
                'icono' => 'recibo', 'descripcion' => 'Cobro de citas y emisión de comprobantes.'],
            ['cu' => 'CU23', 'titulo' => 'Generar reportes', 'ruta' => 'reportes.index', 'activo' => ['reportes.*'], 'permiso' => 'reporte.generar',
                'icono' => 'reporte', 'descripcion' => 'Reportes con subtotales, exportables a PDF y CSV.'],
        ],
    ],
];

<?php

/*
|--------------------------------------------------------------------------
| Reglas del negocio configurables (RN16–RN31)
|--------------------------------------------------------------------------
| Valores a confirmar con la clínica. Usar siempre config('clinica.xxx'),
| nunca valores fijos en el código.
*/

return [

    // RN16 · Reparto de fichas por canal (porcentaje)
    'porcentaje_linea'     => 70,
    'porcentaje_mostrador' => 30,

    // RN17 · Ventana de reserva en línea
    'reserva_abre_dias_antes'  => 7,
    'reserva_cierra_horas_antes' => 1,

    // RN19 · Límites por paciente
    'max_citas_medico_dia' => 1,
    'max_citas_activas'    => 3,

    // RN20 · Retención para pago en línea (ciclo 2), en minutos
    'retencion_pago_min' => 15,

    // RN23 · Recordatorio por correo, horas antes de la cita
    'recordatorio_horas_antes' => 24,

    // RN24 · Llegada: minutos antes de la hora para confirmar
    'llegada_min_antes' => 15,

    // RN25 · Inasistencia automática: minutos después de la hora
    'inasistencia_min_despues' => 15,

    // RN26 · Cancelación con reembolso total si faltan al menos estas horas
    'cancelacion_reembolso_horas' => 24,

    // RN27 · Reprogramación
    'reprogramacion_max_veces'     => 1,
    'reprogramacion_horas_antes'   => 24,

    // RN29 · Sobrecupos por turno
    'max_sobrecupos_turno' => 2,

    // RN31 · Penalización por inasistencias
    'penalizacion_inasistencias' => 3,
    'penalizacion_dias'          => 90,

    // CU01 · Seguridad de inicio de sesión
    'max_intentos_login' => 5,
    'bloqueo_login_min'  => 15,

    // Formato de códigos correlativos
    'codigos' => [
        'paciente' => ['prefijo' => 'PAC-', 'digitos' => 6],
        'medico'   => ['prefijo' => 'MED-', 'digitos' => 4],
        'cita'     => ['prefijo' => 'CM-',  'digitos' => 6],
    ],

];

{{--
    RNF20 · Botón de comandos de voz (resources/js/voz.js).
    El servidor decide a qué pantallas se puede navegar según los permisos del rol.
    Uso: <x-voz :campos="['especialidad' => 'sel_especialidad', 'fecha' => 'sel_fecha']" />
--}}
@props(['campos' => []])
@php
    $u = Auth::user();
    $rutas = array_filter([
        'inicio' => route('dashboard'),
        'agenda' => $u->tienePermiso('agenda.consultar') ? route('agenda.index') : null,
        'nueva cita' => $u->tienePermiso('cita.programar') ? route('fichas.index') : null,
        'programar cita' => $u->tienePermiso('cita.programar') ? route('fichas.index') : null,
        'citas disponibles' => $u->tienePermiso('cita.consultar') ? route('fichas.index', ['accion' => 'consultar']) : null,
        'mis citas' => $u->tienePermiso('cita.consultar') || $u->tienePermiso('cita.asistencia') ? route('citas.index') : null,
        'historial' => $u->tienePermiso('historial.consultar') ? route('historial.index') : null,
    ]);
    $ejemplos = array_values(array_filter([
        isset($campos['especialidad']) ? '«especialidad pediatría»' : null,
        isset($campos['medico']) ? '«médico Pérez»' : null,
        isset($campos['fecha']) ? '«fecha mañana»' : null,
        isset($campos['motivo']) ? '«motivo dolor de cabeza»' : null,
        isset($campos['estado']) ? '«estado confirmadas»' : null,
        isset($rutas['agenda']) ? '«abrir agenda»' : '«nueva cita»',
    ]));
@endphp
<div data-voz data-rutas='@json($rutas)' data-campos='@json($campos)'
     {{ $attributes->merge(['class' => 'flex items-start gap-3 p-3 bg-white border border-purple-100 rounded-2xl shadow-sm']) }}>
    <button type="button" data-voz-boton aria-pressed="false" title="Comandos de voz"
            class="shrink-0 w-11 h-11 rounded-full bg-purple-800 hover:bg-purple-900 text-white flex items-center justify-center transition-colors cursor-pointer">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
        </svg>
        <span class="sr-only">Hablar</span>
    </button>
    <div class="min-w-0 text-sm">
        <p class="font-semibold text-gray-900">Comandos de voz <span data-voz-texto class="font-normal italic text-purple-800"></span></p>
        <p data-voz-estado class="text-xs text-gray-500">Pulse el micrófono y diga, por ejemplo, {{ implode(', ', $ejemplos) }}. Nunca se envía nada sin que usted lo confirme.</p>
    </div>
</div>

{{--
    RNF20 · Micrófono flotante de todos los roles (esquina superior derecha).
    Entiende frases naturales para ir a otra pantalla («quiero sacar una cita»,
    «reporte de citas por médico», «abrir la caja») y, en las pantallas que
    declaran campos con <x-voz> (CU13, CU20, CU23), también los llena.
    El servidor entrega solo los destinos que el rol tiene permitidos
    (MenuModulos::destinosVoz); resources/js/voz.js interpreta la frase.
--}}
@php
    $u = Auth::user();
    $destinos = \App\Support\MenuModulos::destinosVoz($u);
    $rutas = array_filter([
        'inicio' => route('dashboard'),
        'mis citas' => $u->tienePermiso('cita.consultar') || $u->tienePermiso('cita.asistencia') ? route('citas.index') : null,
    ]);
    // Tres ejemplos de lo que este rol puede pedir.
    $ejemplos = collect([
        'CU13' => '«quiero sacar una cita»',
        'CU23' => '«reporte de citas por médico del mes pasado»',
        'CU20' => '«abrir la agenda»',
        'CU19' => '«cobrar una cita»',
        'CU14' => '«pagar mi cita»',
        'CU16' => '«cancelar una cita»',
        'CU24' => '«abrir la bitácora»',
        'CU04' => '«ver mi perfil»',
    ])->only(array_keys($destinos))->take(3)->implode(', ');
@endphp
@if ($destinos)
    <div data-voz data-lang="es-BO" data-destinos='@json($destinos)' data-rutas='@json($rutas)' data-campos='{}'
         x-data="{ abierto: false, escuchando: false }"
         x-on:voz-estado="escuchando = $event.detail.activo; if (escuchando) abierto = true"
         x-on:keydown.escape.window="abierto = false"
         class="fixed top-2 right-4 lg:top-4 lg:right-6 z-30">
        <button type="button" data-voz-boton aria-pressed="false" title="Comandos de voz" aria-label="Comandos de voz"
                class="w-10 h-10 rounded-full bg-purple-800 hover:bg-purple-900 text-white shadow-lg ring-4 ring-white flex items-center justify-center transition-colors cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
        </button>

        {{-- Panel de escucha: se abre al pulsar el micrófono y muestra lo que se entendió --}}
        <div x-show="abierto" x-transition.opacity.duration.150ms x-on:click.outside="if (!escuchando) abierto = false" style="display: none"
             role="status" aria-live="polite"
             class="absolute right-0 mt-3 w-72 sm:w-80 p-4 bg-white border border-purple-100 rounded-2xl shadow-xl">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="relative flex w-3 h-3">
                        <span x-show="escuchando" class="absolute inline-flex w-full h-full rounded-full bg-rose-400 opacity-75 animate-ping"></span>
                        <span class="relative inline-flex w-3 h-3 rounded-full" :class="escuchando ? 'bg-rose-600' : 'bg-gray-300'"></span>
                    </span>
                    <p class="font-semibold text-gray-900" x-text="escuchando ? 'Escuchando…' : 'Comandos de voz'"></p>
                </div>
                <button type="button" x-on:click="abierto = false" class="text-gray-400 hover:text-gray-700" aria-label="Cerrar">✕</button>
            </div>
            <p data-voz-texto class="mt-2 text-sm italic text-purple-800 min-h-[1.25rem]"></p>
            <p data-voz-estado class="text-xs text-gray-500">Hable con naturalidad, por ejemplo: {{ $ejemplos }}.</p>
        </div>
    </div>
@endif

{{--
    Etiqueta del módulo sobre el título de la página:
    "P1 · SEGURIDAD Y ACCESOS • RF04 — BITÁCORA"
    Sin parámetros, la deduce de la ruta actual con config/menu.php.
    También se puede indicar a mano: <x-encabezado-modulo paquete="P1" titulo-paquete="Seguridad y Accesos" cu="CU01" />
--}}
@props(['paquete' => null, 'tituloPaquete' => null, 'cu' => null, 'tituloCu' => null])
@php
    if ($paquete === null) {
        $actual = \App\Support\MenuModulos::actual();
        if ($actual) {
            $paquete = $actual['paquete']['paquete'];
            $tituloPaquete = $actual['paquete']['titulo'];
            $cu = str_replace(' · ', '·', $actual['item']['cu']);
            $tituloCu = $actual['item']['titulo'];
        }
    }
@endphp
@if ($paquete)
    <span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-x-1.5 mb-2 rounded-md border border-purple-200 bg-purple-50 text-purple-800 text-xs font-semibold uppercase tracking-wide px-2.5 py-1']) }}>
        <span>{{ $paquete }} · {{ $tituloPaquete }}</span>
        @if ($cu)
            <span aria-hidden="true">•</span>
            <span>{{ $cu }}{{ $tituloCu ? ' — '.$tituloCu : '' }}</span>
        @endif
    </span>
@endif

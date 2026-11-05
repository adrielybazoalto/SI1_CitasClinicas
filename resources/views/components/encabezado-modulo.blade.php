{{--
    Etiqueta del módulo sobre el título de la página:
    "P1 · SEGURIDAD Y ACCESOS • CU24 — CONSULTAR BITÁCORA"
    Sin parámetros, la deduce de la ruta y de ?accion (App\Support\MenuModulos::actual()),
    igual que el ítem resaltado del menú. También se puede indicar a mano:
    <x-encabezado-modulo paquete="P1" titulo-paquete="Seguridad y Accesos" cu="CU01" />
--}}
@props(['paquete' => null, 'tituloPaquete' => null, 'cu' => null, 'tituloCu' => null])
@php
    if ($paquete === null && ($actual = \App\Support\MenuModulos::actual())) {
        $paquete = $actual['paquete']['paquete'];
        $tituloPaquete = $actual['paquete']['titulo'];
        $cu = $actual['item']['cu'] ?? null;
        $tituloCu = $actual['item']['titulo'] ?? null;
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

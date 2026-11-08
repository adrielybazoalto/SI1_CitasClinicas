{{--
    RNF20 · Campos de una pantalla que se pueden dictar con el micrófono flotante
    (<x-voz-global>, en el layout). No dibuja otro micrófono: solo declara los
    campos y resources/js/voz.js los llena (por campo o con una frase compuesta).
    Uso: <x-voz :campos="['especialidad' => 'sel_especialidad', 'fecha' => 'sel_fecha']" />
--}}
@props(['campos' => []])
<div hidden data-voz-campos='@json($campos)'></div>

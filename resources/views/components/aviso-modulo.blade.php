{{-- Aviso de una línea con qué hacer, cuando se entra a una pantalla compartida con ?accion (config/menu.php → aviso) --}}
@php
    $actual = \App\Support\MenuModulos::actual();
    $aviso = request()->query('accion') ? ($actual['item']['aviso'] ?? null) : null;
@endphp
@if ($aviso)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2.5 p-3.5 bg-purple-50 border border-purple-200 text-purple-900 rounded-xl text-sm']) }} role="note">
        <svg class="w-5 h-5 shrink-0 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span><span class="font-semibold">{{ $actual['item']['cu'] }} · {{ $actual['item']['titulo'] }}.</span> {{ $aviso }}</span>
    </div>
@endif

@props(['showText' => true, 'size' => 'md'])

@php
    $iconSizes = [
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10',
        'lg' => 'w-14 h-14',
        'xl' => 'w-18 h-18',
    ];
    $currentSize = $iconSizes[$size] ?? 'w-10 h-10';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 select-none']) }}>
    <!-- Emblema de la Cruz Médica Vida Salud -->
    <div class="relative shrink-0 flex items-center justify-center">
        <svg class="{{ $currentSize }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="hc-purple-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#7E22CE" />
                    <stop offset="50%" stop-color="#6B21A8" />
                    <stop offset="100%" stop-color="#4C1D95" />
                </linearGradient>
                <linearGradient id="hc-accent-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#F3E8FF" />
                    <stop offset="100%" stop-color="#FFFFFF" />
                </linearGradient>
                <filter id="hc-shadow" x="-10%" y="-10%" width="130%" height="130%" filterUnits="userSpaceOnUse">
                    <feDropShadow dx="0" dy="4" stdDeviation="3" flood-color="#4C1D95" flood-opacity="0.25" />
                </filter>
            </defs>

            <!-- Fondo suave redondeado con sombra sutil -->
            <rect width="64" height="64" rx="18" fill="url(#hc-purple-grad)" filter="url(#hc-shadow)" />

            <!-- Borde interior sutil -->
            <rect x="1" y="1" width="62" height="62" rx="17" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" />

            <!-- Cruz médica estilizada en blanco brillante -->
            <!-- Barra vertical -->
            <rect x="26" y="14" width="12" height="36" rx="5" fill="url(#hc-accent-grad)" />
            <!-- Barra horizontal -->
            <rect x="14" y="26" width="36" height="12" rx="5" fill="url(#hc-accent-grad)" />

            <!-- Detalle central de pulso vital / corazón médico -->
            <path d="M22 32h5l2.5-4.5 4.5 9 3-5.5 2 2h5" 
                  stroke="#7E22CE" 
                  stroke-width="2.5" 
                  stroke-linecap="round" 
                  stroke-linejoin="round" />
        </svg>
    </div>

    @if ($showText)
        <div class="flex flex-col text-left leading-tight">
            <div class="flex items-center gap-1.5">
                <span class="font-extrabold text-xl tracking-tight text-purple-950">VIDA</span>
                <span class="font-extrabold text-xl tracking-tight text-purple-700">SALUD</span>
            </div>
            <span class="text-[10px] tracking-widest font-semibold uppercase text-purple-600/80 -mt-0.5">
                CLÍNICA & CITAS MÉDICAS
            </span>
        </div>
    @endif
</div>

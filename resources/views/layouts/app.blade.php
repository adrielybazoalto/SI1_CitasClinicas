<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Vida Salud') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-[#FBF6FB] min-h-screen selection:bg-purple-200 selection:text-purple-900"
          x-data="{ menu: false }" @keydown.escape.window="menu = false">

        @include('layouts.navigation')

        {{-- Fondo oscuro detrás del menú en celular --}}
        <div x-show="menu" x-transition.opacity @click="menu = false"
             class="fixed inset-0 z-30 bg-purple-950/40 lg:hidden" style="display: none"></div>

        <div class="lg:pl-72 min-h-screen flex flex-col">
            {{-- Celular: barra mínima con ☰ y el logo (en escritorio no hay barra superior) --}}
            <header class="lg:hidden sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-purple-100">
                <div class="flex items-center gap-3 h-14 px-4">
                    <button type="button" @click="menu = true" class="p-2 -ml-2 rounded-lg text-purple-800 hover:bg-purple-50" aria-label="Abrir menú">
                        <x-icono nombre="menu" class="w-6 h-6" />
                    </button>
                    <a href="{{ route('dashboard') }}" class="min-w-0"><x-application-logo size="sm" :showText="true" /></a>
                </div>
            </header>

            {{-- Encabezado de la página (cada vista lo define en su slot header) --}}
            @isset($header)
                <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-8">
                    {{ $header }}
                </div>
            @endisset

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="py-5 text-center text-xs text-gray-500 border-t border-purple-100/60 bg-white/40">
                &copy; 2026 Vida Salud. Todos los derechos reservados.
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Vida Salud') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

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
            {{-- Barra superior: botón de menú (celular), título de la página y usuario --}}
            <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-md border-b border-purple-100">
                <div class="flex items-center gap-3 min-h-16 px-4 sm:px-6 lg:px-8 py-3">
                    <button type="button" @click="menu = true" class="lg:hidden p-2 -ml-2 rounded-lg text-purple-800 hover:bg-purple-50" aria-label="Abrir menú">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    <div class="flex-1 min-w-0">
                        @isset($header)
                            {{ $header }}
                        @endisset
                    </div>

                    @include('layouts.partials.menu-usuario')
                </div>
            </header>

            <main class="flex-1 py-2">
                {{ $slot }}
            </main>

            <footer class="py-5 text-center text-xs text-purple-900/60 border-t border-purple-100/60 bg-white/40">
                &copy; 2026 Vida Salud. Todos los derechos reservados.
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>

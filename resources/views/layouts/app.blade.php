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
    <body class="font-sans antialiased text-gray-900 bg-[#FAF9FD] min-h-screen selection:bg-purple-200 selection:text-purple-900">
        <div class="min-h-screen flex flex-col justify-between">
            <div>
                @include('layouts.navigation')

                <!-- Page Heading con borde púrpura sutil -->
                @isset($header)
                    <header class="bg-white/80 backdrop-blur-md border-b border-purple-100/80 shadow-xs">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="py-2">
                    {{ $slot }}
                </main>
            </div>

            <!-- Footer interno -->
            <footer class="mt-auto py-5 text-center text-xs text-purple-900/60 border-t border-purple-100/60 bg-white/40">
                <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="flex items-center gap-2 font-medium">
                        <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                        <span>Vida Salud · Sistema Clínico</span>
                    </div>
                    <div>
                        &copy; 2026 Vida Salud. Todos los derechos reservados.
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>

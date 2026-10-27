<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
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
    <body class="font-sans text-gray-900 antialiased min-h-screen flex flex-col justify-between bg-gradient-to-br from-purple-50/80 via-[#FAF7FD] to-purple-100/50 relative overflow-x-hidden selection:bg-purple-200 selection:text-purple-900">
        
        <!-- Elementos decorativos de fondo con tonos púrpura -->
        <div class="absolute top-0 right-1/4 -mt-24 w-96 h-96 bg-purple-300/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-10 -mb-24 w-80 h-80 bg-purple-400/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Barra superior decorativa morada -->
        <div class="h-1.5 w-full bg-gradient-to-r from-purple-900 via-purple-700 to-indigo-800"></div>

        <!-- Contenido Central -->
        <div class="flex-1 flex flex-col justify-center items-center px-4 py-8 sm:py-12 relative z-10">
            <!-- Logotipo y Nombre de la Clínica -->
            <div class="mb-6 transform transition hover:scale-[1.02] duration-200">
                <a href="/" class="flex flex-col items-center gap-2 group">
                    <x-application-logo size="lg" :showText="true" />
                </a>
            </div>

            <!-- Tarjeta de Contenido / Formulario -->
            <div class="w-full sm:max-w-md bg-white/95 backdrop-blur-md px-6 py-8 sm:px-8 shadow-2xl shadow-purple-950/10 border border-purple-100 rounded-2xl sm:rounded-3xl transition-all">
                {{ $slot }}
            </div>

            <!-- Credenciales de seguridad o mensaje inferior -->
            <div class="mt-6 text-center text-xs text-purple-900/60 font-medium flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-purple-600 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Acceso Seguro · Plataforma Médica Protegida</span>
            </div>
        </div>

        <!-- Pie de página corporativo -->
        <footer class="py-4 text-center text-xs text-gray-500 border-t border-purple-100/60 bg-white/40 relative z-10">
            <p>&copy; 2026 Vida Salud. Todos los derechos reservados.</p>
        </footer>
    </body>
</html>

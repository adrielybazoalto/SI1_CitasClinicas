<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">Panel Principal</h2>
                <p class="text-xs text-purple-800/70">Resumen y acceso a los módulos del sistema de gestión clínica</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-900 border border-purple-200">
                    <span class="w-2 h-2 rounded-full bg-purple-700 animate-pulse"></span>
                    Sistema en línea
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Banner de Bienvenida Vida Salud con Gradiente Púrpura Corporativo -->
            <div class="relative overflow-hidden bg-gradient-to-r from-purple-950 via-purple-900 to-indigo-950 text-white rounded-3xl p-6 sm:p-10 shadow-xl shadow-purple-950/15 border border-purple-800/40">
                <!-- Efectos de luz ambiental -->
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 -bottom-20 w-80 h-80 bg-fuchsia-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs font-medium text-purple-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                            Portal Clínico Vida Salud
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                            ¡Bienvenido(a), {{ Auth::user()->username }}!
                        </h1>
                        <p class="text-sm text-purple-200/90 leading-relaxed">
                            Sistema integral para la programación y reserva de citas médicas, administración de turnos y control de acceso.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row md:flex-col gap-3 shrink-0">
                        <div class="bg-white/10 backdrop-blur-md border border-white/15 rounded-2xl p-4 text-center min-w-[180px]">
                            <p class="text-xs text-purple-200 font-medium">Rol Asignado</p>
                            <p class="text-lg font-bold text-white mt-0.5">{{ Auth::user()->rol?->nombre ?? 'Usuario' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accesos Rápidos a Módulos según permisos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {{-- Módulo de Usuarios (CU02) --}}
                @if (Auth::user()->tienePermiso('usuario.gestionar'))
                    <a href="{{ route('usuarios.index') }}" class="group bg-white p-6 rounded-2xl border border-purple-100 shadow-xs hover:shadow-lg hover:shadow-purple-950/10 hover:border-purple-300 transition-all duration-200 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center group-hover:bg-purple-800 group-hover:text-white transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-purple-950 group-hover:text-purple-700 transition-colors">Gestión de Usuarios</h3>
                                <p class="text-xs text-gray-500 mt-1">Alta, edición, estados de cuentas y restablecimiento de contraseñas de acceso.</p>
                            </div>
                        </div>
                        <div class="mt-5 pt-3 border-t border-purple-50 flex items-center justify-between text-xs font-semibold text-purple-700 group-hover:text-purple-900">
                            <span>Acceder al módulo</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                @endif

                {{-- Módulo de Roles y Permisos (CU03) --}}
                @if (Auth::user()->tienePermiso('rol.gestionar'))
                    <a href="{{ route('roles.index') }}" class="group bg-white p-6 rounded-2xl border border-purple-100 shadow-xs hover:shadow-lg hover:shadow-purple-950/10 hover:border-purple-300 transition-all duration-200 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center group-hover:bg-purple-800 group-hover:text-white transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-purple-950 group-hover:text-purple-700 transition-colors">Roles y Permisos</h3>
                                <p class="text-xs text-gray-500 mt-1">Configuración de perfiles de usuario, matriz de permisos y privilegios del sistema.</p>
                            </div>
                        </div>
                        <div class="mt-5 pt-3 border-t border-purple-50 flex items-center justify-between text-xs font-semibold text-purple-700 group-hover:text-purple-900">
                            <span>Acceder al módulo</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                @endif

                {{-- Mi Perfil --}}
                <a href="{{ route('profile.edit') }}" class="group bg-white p-6 rounded-2xl border border-purple-100 shadow-xs hover:shadow-lg hover:shadow-purple-950/10 hover:border-purple-300 transition-all duration-200 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center group-hover:bg-purple-800 group-hover:text-white transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-purple-950 group-hover:text-purple-700 transition-colors">Mi Perfil</h3>
                            <p class="text-xs text-gray-500 mt-1">Actualización de datos personales, correo electrónico y cambio de contraseña.</p>
                        </div>
                    </div>
                    <div class="mt-5 pt-3 border-t border-purple-50 flex items-center justify-between text-xs font-semibold text-purple-700 group-hover:text-purple-900">
                        <span>Configurar cuenta</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            </div>

            <!-- Información del Sistema de Citas y Normas Clínicas -->
            <div class="bg-purple-50/60 border border-purple-200/80 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-200/80 text-purple-900 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-purple-950">Sistema Clínico Vida Salud</h4>
                        <p class="text-xs text-purple-900/70">Atención y reserva de fichas médicas en turnos programados.</p>
                    </div>
                </div>
                <div class="text-xs text-purple-800 font-medium">
                    Horario de atención: Lun – Vie 08:00 – 18:00
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

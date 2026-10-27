<nav x-data="{ open: false }" class="bg-white/95 backdrop-blur-md border-b border-purple-100 sticky top-0 z-40 shadow-sm shadow-purple-950/5">
    <!-- Menú de Navegación Principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logotipo de Vida Salud -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="group flex items-center gap-2">
                        <x-application-logo size="sm" :showText="true" />
                    </a>
                </div>

                <!-- Enlaces de Navegación Desktop -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex h-full">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-purple-700' : 'text-gray-400 group-hover:text-purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Inicio
                        </span>
                    </x-nav-link>

                    {{-- Menú según permisos del rol (CU03) --}}
                    @if (Auth::user()->tienePermiso('usuario.gestionar'))
                        <x-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 {{ request()->routeIs('usuarios.*') ? 'text-purple-700' : 'text-gray-400 group-hover:text-purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Usuarios
                            </span>
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->tienePermiso('rol.gestionar'))
                        <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 {{ request()->routeIs('roles.*') ? 'text-purple-700' : 'text-gray-400 group-hover:text-purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Roles y permisos
                            </span>
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Menú Desplegable de Usuario (Desktop) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2.5 px-3.5 py-1.5 border border-purple-200/90 rounded-full text-sm font-semibold text-purple-950 bg-purple-50/70 hover:bg-purple-100 hover:border-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-600/30 transition duration-150 cursor-pointer shadow-xs">
                            <!-- Avatar circular con inicial -->
                            <div class="w-6 h-6 rounded-full bg-purple-700 text-white flex items-center justify-center text-xs font-bold uppercase shadow-xs">
                                {{ substr(Auth::user()->username, 0, 1) }}
                            </div>

                            <span class="text-sm font-medium text-purple-950">{{ Auth::user()->username }}</span>

                            <svg class="h-4 w-4 text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Cabecera del usuario en dropdown -->
                        <div class="px-4 py-2.5 border-b border-purple-100 bg-purple-50/40 rounded-t-lg">
                            <p class="text-xs text-purple-900/70 font-medium">Conectado como</p>
                            <p class="text-sm font-bold text-purple-950 truncate">{{ Auth::user()->username }}</p>
                            <span class="inline-block px-2 py-0.5 mt-1 text-[10px] font-semibold bg-purple-100 text-purple-800 rounded-full">
                                {{ Auth::user()->rol?->nombre ?? 'Usuario' }}
                            </span>
                        </div>

                        <div class="p-1">
                            <x-dropdown-link :href="route('profile.edit')" class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Mi Perfil</span>
                            </x-dropdown-link>

                            <!-- Formulario Cerrar Sesión -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        class="flex items-center gap-2 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span>Cerrar sesión</span>
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Botón Hamburguesa (Móvil) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2.5 rounded-xl text-purple-700 hover:text-purple-900 hover:bg-purple-100/70 focus:outline-none focus:bg-purple-100 transition duration-150">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú Responsive (Móvil y Tablets) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-purple-100 bg-white/95 backdrop-blur-md">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-purple-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Inicio
                </span>
            </x-responsive-nav-link>

            @if (Auth::user()->tienePermiso('usuario.gestionar'))
                <x-responsive-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 {{ request()->routeIs('usuarios.*') ? 'text-purple-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Usuarios
                    </span>
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->tienePermiso('rol.gestionar'))
                <x-responsive-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 {{ request()->routeIs('roles.*') ? 'text-purple-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Roles y permisos
                    </span>
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Tarjeta de usuario en Menú Responsive -->
        <div class="pt-4 pb-3 border-t border-purple-100 bg-purple-50/40 px-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-purple-700 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                    {{ substr(Auth::user()->username, 0, 1) }}
                </div>
                <div class="overflow-hidden">
                    <div class="font-bold text-sm text-purple-950 truncate">{{ Auth::user()->username }}</div>
                    <div class="font-medium text-xs text-purple-800/70 truncate">{{ Auth::user()->correo }}</div>
                    <div class="text-[10px] text-purple-600 font-semibold uppercase mt-0.5">{{ Auth::user()->rol?->nombre ?? 'Usuario' }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Perfil
                </x-responsive-nav-link>

                <!-- Cerrar sesión -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            class="text-rose-600"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

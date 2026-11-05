{{-- Menú de usuario (barra superior): Mi Perfil y Cerrar sesión --}}
<div class="shrink-0">
    <x-dropdown align="right" width="56">
        <x-slot name="trigger">
            <button class="inline-flex items-center gap-2.5 px-3.5 py-1.5 border border-purple-200/90 rounded-full text-sm font-semibold text-purple-950 bg-purple-50/70 hover:bg-purple-100 hover:border-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-600/30 transition duration-150 cursor-pointer shadow-xs">
                <!-- Avatar circular con inicial -->
                <div class="w-6 h-6 rounded-full bg-purple-700 text-white flex items-center justify-center text-xs font-bold uppercase shadow-xs">
                    {{ substr(Auth::user()->username, 0, 1) }}
                </div>

                <span class="hidden sm:inline text-sm font-medium text-purple-950 max-w-[10rem] truncate">{{ Auth::user()->username }}</span>

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

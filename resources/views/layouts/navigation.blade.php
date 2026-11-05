{{-- Menú lateral por paquetes (config/menu.php), filtrado por los permisos del rol (CU03) --}}
@php
    $usuario = Auth::user();
    $paquetes = \App\Support\MenuModulos::paraUsuario($usuario);
    $nombre = $usuario->nombreParaMostrar();
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-72 flex flex-col bg-white border-r border-purple-100 shadow-xl shadow-purple-950/10 lg:shadow-none transition-transform duration-200 -translate-x-full lg:translate-x-0"
       :class="menu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    {{-- Logo --}}
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-purple-100">
        <a href="{{ route('dashboard') }}" class="flex items-center min-w-0">
            <x-application-logo size="sm" :showText="true" />
        </a>
        <button type="button" @click="menu = false" class="lg:hidden p-2 rounded-lg text-purple-700 hover:bg-purple-50" aria-label="Cerrar menú">
            <x-icono nombre="cerrar" />
        </button>
    </div>

    {{-- Lista de módulos: tiene su propio scroll entre el logo y el bloque del usuario --}}
    <nav class="flex-1 min-h-0 overflow-y-auto px-3 py-4 space-y-4" aria-label="Menú principal">
        <a href="{{ route('dashboard') }}"
           @if (request()->routeIs('dashboard')) aria-current="page" @endif
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors
                  {{ request()->routeIs('dashboard') ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-gray-700 hover:bg-purple-50' }}">
            <x-icono nombre="casa" class="w-5 h-5 shrink-0" />
            Inicio
        </a>

        @foreach ($paquetes as $paquete)
            <div x-data="{ open: @js($paquete['abierto']) }">
                {{-- Cabecera del paquete --}}
                <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 hover:text-purple-900 transition-colors">
                    <span>{{ $paquete['paquete'] }} · {{ $paquete['titulo'] }}</span>
                    <x-icono nombre="flecha" class="w-4 h-4 shrink-0 transition-transform duration-200" ::class="open && 'rotate-180'" />
                </button>

                {{-- Casos de uso del paquete --}}
                <ul x-show="open" x-transition.opacity.duration.150ms class="mt-1 space-y-0.5"
                    @unless ($paquete['abierto']) style="display: none" @endunless>
                    @foreach ($paquete['visibles'] as $item)
                        <li>
                            @if ($item['disponible'])
                                <a href="{{ route($item['ruta']) }}"
                                   @if ($item['actual']) aria-current="page" @endif
                                   class="flex items-start gap-3 px-3 py-2 rounded-xl text-sm transition-colors
                                          {{ $item['actual'] ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-gray-700 hover:bg-purple-50' }}">
                                    <x-icono :nombre="$item['icono']" class="w-5 h-5 shrink-0 mt-px {{ $item['actual'] ? 'text-purple-800' : 'text-gray-400' }}" />
                                    <span class="leading-snug">{{ $item['etiqueta'] }}</span>
                                </a>
                            @else
                                <span class="flex items-start gap-3 px-3 py-2 rounded-xl text-sm text-gray-400 cursor-not-allowed" title="En desarrollo">
                                    <x-icono :nombre="$item['icono']" class="w-5 h-5 shrink-0 mt-px" />
                                    <span class="flex-1 leading-snug">{{ $item['etiqueta'] }}</span>
                                    <span class="shrink-0 mt-0.5 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">Pronto</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Usuario, Mi cuenta y Cerrar sesión: fijo abajo --}}
    <div class="shrink-0 border-t border-purple-100 p-3 space-y-1">
        <div class="flex items-center gap-3 px-2 py-2">
            <span class="w-10 h-10 shrink-0 rounded-full bg-purple-800 text-white flex items-center justify-center text-sm font-bold uppercase">
                {{ mb_substr($nombre, 0, 1) }}
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 leading-snug break-words">{{ $nombre }}</p>
                <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-semibold uppercase tracking-wide">
                    {{ $usuario->rol?->nombre === 'Medico' ? 'Médico' : ($usuario->rol?->nombre ?? 'Usuario') }}
                </span>
            </div>
        </div>
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors
                  {{ request()->routeIs('profile.*') ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-gray-700 hover:bg-purple-50' }}">
            <x-icono nombre="cuenta" class="w-5 h-5 shrink-0 text-gray-400" />
            Mi cuenta
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                <x-icono nombre="salir" class="w-5 h-5 shrink-0" />
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>

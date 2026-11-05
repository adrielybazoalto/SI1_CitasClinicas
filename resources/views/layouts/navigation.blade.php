{{-- Menú lateral por paquetes (config/menu.php), filtrado por los permisos del rol (CU03) --}}
@php
    $usuario = Auth::user();

    $paquetes = collect(config('menu'))->map(function ($paquete) use ($usuario) {
        $items = collect($paquete['items'])
            ->filter(fn ($i) => collect((array) $i['permiso'])->contains(fn ($c) => $usuario->tienePermiso($c)))
            ->map(function ($i) {
                $i['disponible'] = Route::has($i['ruta']);
                $i['actual'] = request()->routeIs(...(array) $i['activo']);

                return $i;
            })
            ->values();

        return $paquete + ['visibles' => $items, 'abierto' => $items->contains('actual', true)];
    })->filter(fn ($p) => $p['visibles']->isNotEmpty())->values();
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-72 flex flex-col bg-white border-r border-purple-100 shadow-xl shadow-purple-950/10 lg:shadow-none transition-transform duration-200 -translate-x-full lg:translate-x-0"
       :class="menu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    {{-- Logo --}}
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-purple-100">
        <a href="{{ route('dashboard') }}" class="flex items-center min-w-0">
            <x-application-logo size="sm" :showText="true" />
        </a>
        <button type="button" @click="menu = false" class="lg:hidden p-2 rounded-lg text-purple-700 hover:bg-purple-50" aria-label="Cerrar menú">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" aria-label="Menú principal">
        {{-- Inicio --}}
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors
                  {{ request()->routeIs('dashboard') ? 'bg-purple-800 text-white shadow-sm' : 'text-gray-700 hover:bg-purple-50 hover:text-purple-900' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Inicio
        </a>

        <p class="px-3 pt-5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-purple-900/50">Paquetes</p>

        @foreach ($paquetes as $paquete)
            <div x-data="{ open: @js($paquete['abierto']) }">
                {{-- Cabecera del paquete --}}
                <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors
                               {{ $paquete['abierto'] ? 'bg-purple-50 text-purple-950' : 'text-gray-700 hover:bg-purple-50 hover:text-purple-950' }}">
                    <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center text-xs font-bold
                                 {{ $paquete['abierto'] ? 'bg-purple-800 text-white' : 'bg-purple-100 text-purple-800' }}">
                        {{ $paquete['paquete'] }}
                    </span>
                    <span class="flex-1 text-left leading-tight">{{ $paquete['titulo'] }}</span>
                    <svg class="w-4 h-4 shrink-0 text-purple-400 transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                {{-- Casos de uso del paquete --}}
                <ul x-show="open" x-transition.opacity.duration.150ms
                    class="mt-1 mb-2 ml-7 pl-4 border-l-2 border-purple-100 space-y-0.5"
                    @unless ($paquete['abierto']) style="display: none" @endunless>
                    @foreach ($paquete['visibles'] as $item)
                        <li>
                            @if ($item['disponible'])
                                <a href="{{ route($item['ruta']) }}"
                                   @if ($item['actual']) aria-current="page" @endif
                                   class="block px-3 py-2 rounded-lg text-sm transition-colors
                                          {{ $item['actual'] ? 'bg-purple-100 text-purple-950 font-semibold' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-950' }}">
                                    <span class="block leading-snug">{{ $item['titulo'] }}</span>
                                    <span class="block text-[11px] font-medium text-purple-700/60">{{ $item['cu'] }}</span>
                                </a>
                            @else
                                <span class="block px-3 py-2 rounded-lg text-sm text-gray-400 cursor-not-allowed" title="En desarrollo">
                                    <span class="flex items-center justify-between gap-2 leading-snug">
                                        {{ $item['titulo'] }}
                                        <span class="shrink-0 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">Pronto</span>
                                    </span>
                                    <span class="block text-[11px] font-medium">{{ $item['cu'] }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 px-5 py-3 border-t border-purple-100 text-[11px] text-purple-900/50">
        Vida Salud · Sistema clínico
    </div>
</aside>

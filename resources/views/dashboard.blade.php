{{-- Panel de inicio: saludo y tarjetas de los módulos del rol, agrupadas por paquete --}}
@php
    $usuario = Auth::user();
    $paquetes = \App\Support\MenuModulos::paraUsuario($usuario);
    $rol = $usuario->rol?->nombre === 'Medico' ? 'Médico' : ($usuario->rol?->nombre ?? 'Usuario');
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-encabezado-modulo paquete="P1" titulo-paquete="Seguridad y Accesos" cu="CU01" />
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-3xl font-bold text-gray-900 leading-tight">Hola, {{ $usuario->nombreParaMostrar() }}</h2>
            <span class="px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 text-xs font-semibold uppercase tracking-wide">{{ $rol }}</span>
        </div>
        <p class="mt-1 text-sm text-gray-500">Bienvenido a Vida Salud. Accede a los módulos disponibles para tu rol.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            @foreach ($paquetes as $paquete)
                <section>
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $paquete['paquete'] }} · {{ $paquete['titulo'] }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($paquete['visibles'] as $item)
                            <article class="flex flex-col bg-white rounded-2xl shadow-sm border border-purple-100 p-5 {{ $item['disponible'] ? '' : 'opacity-60' }}">
                                <div class="flex items-start gap-3">
                                    <span class="w-11 h-11 shrink-0 rounded-xl bg-purple-50 text-purple-800 flex items-center justify-center">
                                        <x-icono :nombre="$item['icono']" class="w-6 h-6" />
                                    </span>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-gray-900 leading-snug">{{ $item['etiqueta'] }}</h4>
                                        <p class="mt-1 text-sm text-gray-500">{{ $item['descripcion'] }}</p>
                                    </div>
                                </div>
                                <div class="mt-auto pt-4">
                                    @if ($item['disponible'])
                                        <a href="{{ route($item['ruta']) }}"
                                           class="flex items-center justify-center gap-1.5 w-full px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold transition-colors">
                                            Ingresar <span aria-hidden="true">→</span>
                                        </a>
                                    @else
                                        <button type="button" disabled
                                                class="w-full px-4 py-2.5 bg-gray-100 text-gray-500 text-sm rounded-xl font-semibold cursor-not-allowed">
                                            Pronto
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>

@php
    $tabsOferta = [
        ['permiso' => 'especialidad.gestionar', 'ruta' => 'especialidades.index', 'activo' => 'especialidades.*', 'texto' => 'Especialidades'],
        ['permiso' => 'medico.gestionar', 'ruta' => 'medicos.index', 'activo' => 'medicos.*', 'texto' => 'Médicos'],
        ['permiso' => 'horario.gestionar', 'ruta' => 'horarios.index', 'activo' => 'horarios.*', 'texto' => 'Horarios'],
        ['permiso' => 'tarifa.gestionar', 'ruta' => 'tarifas.index', 'activo' => 'tarifas.*', 'texto' => 'Tarifario'],
        ['permiso' => 'indisponibilidad.registrar', 'ruta' => 'indisponibilidades.index', 'activo' => 'indisponibilidades.*', 'texto' => 'Indisponibilidad'],
    ];
@endphp

<div class="flex flex-wrap items-center gap-1 bg-white p-1.5 rounded-xl border border-purple-100 shadow-xs">
    @foreach ($tabsOferta as $tab)
        @if (Auth::user()->tienePermiso($tab['permiso']) && Route::has($tab['ruta']))
            <a href="{{ route($tab['ruta']) }}"
               class="px-4 py-2 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs($tab['activo']) ? 'bg-purple-800 text-white shadow-sm' : 'text-purple-800 hover:bg-purple-50' }}">
                {{ $tab['texto'] }}
            </a>
        @endif
    @endforeach
</div>

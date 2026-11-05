{{-- CU24 · Consultar bitácora (solo lectura; la tabla es inmutable por el trigger RNF08) --}}
@php
    $claseCampo = 'mt-1 block w-full px-3 py-2.5 bg-white border border-purple-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20';
    $etiqueta = 'text-xs font-semibold text-gray-600';
    $nombreRol = fn ($rol) => $rol === 'Medico' ? 'Médico' : $rol;
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-encabezado-modulo />
        <h2 class="text-3xl font-bold text-gray-900 leading-tight">Bitácora</h2>
        <p class="text-sm text-gray-500 mt-0.5">Registro de las operaciones del sistema · solo lectura</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            {{-- Filtros --}}
            <form method="GET" action="{{ route('bitacora.index') }}" id="form-bitacora" class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-purple-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-2">
                        <label for="desde" class="{{ $etiqueta }}">Desde</label>
                        <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] }}" class="{{ $claseCampo }}">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="hasta" class="{{ $etiqueta }}">Hasta</label>
                        <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] }}" class="{{ $claseCampo }}">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="usuario" class="{{ $etiqueta }}">Usuario</label>
                        <select id="usuario" name="usuario" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($usuarios as $opcion)
                                <option value="{{ $opcion->id_usuario }}" @selected((int) $filtros['usuario'] === $opcion->id_usuario)>{{ $opcion->username }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label for="accion" class="{{ $etiqueta }}">Tipo de acción</label>
                        <select id="accion" name="accion" class="{{ $claseCampo }}">
                            <option value="">Todas</option>
                            @foreach ($acciones as $accion)
                                <option value="{{ $accion }}" @selected($filtros['accion'] === $accion)>{{ $accion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label for="ip" class="{{ $etiqueta }}">Dirección IP</label>
                        <input id="ip" name="ip" type="text" maxlength="45" value="{{ $filtros['ip'] }}" placeholder="Ej.: 192.168." class="{{ $claseCampo }} font-mono">
                    </div>
                    <div class="lg:col-span-2 flex gap-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold cursor-pointer">Filtrar</button>
                        <a href="{{ route('bitacora.index') }}" class="px-3 py-2.5 text-sm font-semibold text-purple-800 hover:bg-purple-50 rounded-xl">Limpiar</a>
                    </div>
                </div>
                <p id="error-bitacora" class="hidden mt-2 text-xs text-rose-600"></p>
            </form>

            {{-- Registros --}}
            <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100 text-sm">
                        <thead class="bg-purple-50/60">
                            <tr>
                                @foreach (['Fecha y hora', 'Usuario', 'Acción', 'IP de origen', 'Descripción'] as $columna)
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $columna }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($registros as $registro)
                                <tr class="align-top hover:bg-purple-50/30">
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 tabular-nums">
                                        {{ $registro->fecha_hora?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($registro->usuario)
                                            <p class="font-semibold text-gray-900">{{ $registro->usuario->username }}</p>
                                            <p class="text-xs text-gray-500">{{ $nombreRol($registro->usuario->rol?->nombre) }}</p>
                                        @else
                                            <p class="font-semibold text-gray-500">Sistema</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-bold text-gray-900">{{ $registro->accion_legible }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-xs {{ $registro->ip ? 'text-gray-700' : 'text-gray-400' }}">{{ $registro->ip ?: 'No disponible' }}</td>
                                    <td class="px-4 py-3 text-gray-700 min-w-64">{{ $registro->detalle }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No hay registros con esos filtros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($registros->hasPages())
                    <div class="px-4 py-4 border-t border-purple-100">{{ $registros->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Validación en el cliente: rango de fechas e IP.
        document.getElementById('form-bitacora').addEventListener('submit', function (e) {
            const desde = document.getElementById('desde').value;
            const hasta = document.getElementById('hasta').value;
            const ip = document.getElementById('ip').value.trim();
            let mensaje = '';
            if (desde && hasta && hasta < desde) mensaje = 'La fecha final no puede ser anterior a la inicial.';
            else if (ip && !/^[0-9a-fA-F.:]+$/.test(ip)) mensaje = 'La dirección IP solo puede tener números, puntos o dos puntos.';
            if (mensaje) {
                e.preventDefault();
                const error = document.getElementById('error-bitacora');
                error.textContent = mensaje;
                error.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>

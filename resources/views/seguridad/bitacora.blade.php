{{-- RF04 · Consulta de la bitácora (solo lectura; la tabla es inmutable) --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150';
    $etiqueta = 'text-xs font-semibold uppercase text-purple-950/80';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">Bitácora</h2>
                <p class="text-xs text-purple-800/70 mt-0.5">Registro de operaciones del sistema · solo lectura</p>
            </div>
            @if (Auth::user()->tienePermiso('reporte.generar'))
                <a href="{{ route('reportes.index', ['tipo' => 'bitacora'] + array_filter(['desde' => $filtros['desde'], 'hasta' => $filtros['hasta']])) }}"
                   class="text-xs font-semibold text-purple-800 hover:underline">Exportar en Reportes →</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            <form method="GET" action="{{ route('bitacora.index') }}" id="form-bitacora" class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-2">
                        <x-input-label for="desde" value="Desde" class="{{ $etiqueta }}" />
                        <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] }}" class="{{ $claseCampo }}">
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="hasta" value="Hasta" class="{{ $etiqueta }}" />
                        <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] }}" class="{{ $claseCampo }}">
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="usuario" value="Usuario" class="{{ $etiqueta }}" />
                        <input id="usuario" name="usuario" type="text" maxlength="50" value="{{ $filtros['usuario'] }}" placeholder="username" class="{{ $claseCampo }}">
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="accion" value="Acción" class="{{ $etiqueta }}" />
                        <select id="accion" name="accion" class="{{ $claseCampo }}">
                            <option value="">Todas</option>
                            @foreach ($acciones as $accion)
                                <option value="{{ $accion }}" @selected($filtros['accion'] === $accion)>{{ $accion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="tabla" value="Tabla" class="{{ $etiqueta }}" />
                        <select id="tabla" name="tabla" class="{{ $claseCampo }}">
                            <option value="">Todas</option>
                            @foreach ($tablas as $tabla)
                                <option value="{{ $tabla }}" @selected($filtros['tabla'] === $tabla)>{{ $tabla }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider cursor-pointer">Filtrar</button>
                        <a href="{{ route('bitacora.index') }}" class="px-3 py-2.5 text-xs font-semibold text-purple-800 hover:bg-purple-50 rounded-xl">Limpiar</a>
                    </div>
                </div>
                <p id="error-bitacora" class="hidden mt-2 text-xs text-red-600"></p>
            </form>

            <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100 text-sm">
                        <thead class="bg-purple-50/60">
                            <tr>
                                @foreach (['Fecha y hora', 'Usuario', 'Acción', 'Tabla', 'Registro', 'IP', 'Detalle'] as $columna)
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">{{ $columna }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($registros as $registro)
                                <tr class="align-top hover:bg-purple-50/30">
                                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-700">{{ $registro->fecha_hora?->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap font-semibold text-purple-950">{{ $registro->usuario?->username ?? 'Sistema' }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap"><span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-800 text-[11px] font-mono">{{ $registro->accion }}</span></td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{{ $registro->tabla ?? '—' }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-600">{{ $registro->id_registro ?? '—' }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-500 font-mono text-xs">{{ $registro->ip ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-gray-700 max-w-md">{{ $registro->detalle }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">No hay registros con esos filtros.</td></tr>
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
        document.getElementById('form-bitacora').addEventListener('submit', function (e) {
            const desde = document.getElementById('desde').value;
            const hasta = document.getElementById('hasta').value;
            if (desde && hasta && hasta < desde) {
                e.preventDefault();
                const error = document.getElementById('error-bitacora');
                error.textContent = 'La fecha final no puede ser anterior a la inicial.';
                error.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>

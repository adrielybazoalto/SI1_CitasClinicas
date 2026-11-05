{{-- CU23 · Generar reportes con subtotales, total general y exportación PDF / CSV --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150';
    $etiqueta = 'text-xs font-semibold uppercase text-purple-950/80';
    $consulta = request()->only(['tipo', 'desde', 'hasta', 'fecha', 'medico', 'especialidad', 'estado']) + ['tipo' => $filtros['tipo']];
    $formato = fn ($valor, $clave) => $reporte['moneda'] && in_array($clave, $reporte['numericas'], true)
        ? number_format((float) $valor, 2, ',', '.')
        : $valor;
    $conGrupo = collect($reporte['grupos'])->contains(fn ($g) => $g['nombre'] !== null);
    $sinFiltrosCita = in_array($filtros['tipo'], ['pacientes', 'bitacora', 'ingresos'], true);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Reportes</h2>
                <p class="text-sm text-gray-500 mt-0.5">Seleccione el reporte y los filtros; la exportación respeta los filtros aplicados</p>
            </div>
            @unless ($reporte['vacio'])
                <div class="flex gap-2">
                    <a href="{{ route('reportes.exportar', ['formato' => 'pdf'] + $consulta) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-700 hover:bg-rose-800 rounded-xl font-semibold text-xs text-white uppercase tracking-wider">PDF</a>
                    <a href="{{ route('reportes.exportar', ['formato' => 'csv'] + $consulta) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 rounded-xl font-semibold text-xs text-white uppercase tracking-wider">CSV</a>
                </div>
            @endunless
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            <!-- Selector y filtros -->
            <form method="GET" action="{{ route('reportes.index') }}" id="form-reporte" class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-4">
                        <x-input-label for="tipo" value="Reporte" class="{{ $etiqueta }}" />
                        <select id="tipo" name="tipo" class="{{ $claseCampo }}" onchange="this.form.submit()">
                            @foreach ($tipos as $clave => $nombre)
                                <option value="{{ $clave }}" @selected($filtros['tipo'] === $clave)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($usaRango)
                        <div class="sm:col-span-2">
                            <x-input-label for="desde" value="Desde" class="{{ $etiqueta }}" />
                            <input id="desde" name="desde" type="date" required value="{{ $filtros['desde'] }}" class="{{ $claseCampo }}">
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="hasta" value="Hasta" class="{{ $etiqueta }}" />
                            <input id="hasta" name="hasta" type="date" required value="{{ $filtros['hasta'] }}" class="{{ $claseCampo }}">
                        </div>
                    @else
                        <div class="sm:col-span-4">
                            <x-input-label for="fecha" value="Fecha" class="{{ $etiqueta }}" />
                            <input id="fecha" name="fecha" type="date" required value="{{ $filtros['fecha'] }}" class="{{ $claseCampo }}">
                        </div>
                    @endif
                    <div class="sm:col-span-4 flex items-end">
                        <button type="submit" class="inline-flex w-full items-center justify-center px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider cursor-pointer">Generar</button>
                    </div>

                    @unless ($sinFiltrosCita)
                        <div class="sm:col-span-4">
                            <x-input-label for="medico" value="Médico" class="{{ $etiqueta }}" />
                            <select id="medico" name="medico" class="{{ $claseCampo }}">
                                <option value="">Todos</option>
                                @foreach ($medicos as $medico)
                                    <option value="{{ $medico->id_medico }}" @selected((int) ($filtros['medico'] ?? 0) === $medico->id_medico)>{{ $medico->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-4">
                            <x-input-label for="especialidad" value="Especialidad" class="{{ $etiqueta }}" />
                            <select id="especialidad" name="especialidad" class="{{ $claseCampo }}">
                                <option value="">Todas</option>
                                @foreach ($especialidades as $especialidad)
                                    <option value="{{ $especialidad->id_especialidad }}" @selected((int) ($filtros['especialidad'] ?? 0) === $especialidad->id_especialidad)>{{ ucfirst($especialidad->nombre) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-4">
                            <x-input-label for="estado" value="Estado" class="{{ $etiqueta }}" />
                            <select id="estado" name="estado" class="{{ $claseCampo }}">
                                <option value="">Todos</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado }}" @selected(($filtros['estado'] ?? null) === $estado)>{{ $estado }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless
                </div>
                <p id="error-reporte" class="hidden mt-2 text-xs text-red-600"></p>
            </form>

            <!-- Resultado -->
            <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-purple-100">
                    <h3 class="font-bold text-lg text-purple-950">{{ $reporte['titulo'] }}</h3>
                    <p class="text-xs text-gray-500">{{ $reporte['subtitulo'] }}</p>
                </div>

                @if ($reporte['vacio'])
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-purple-950">No hay resultados para los filtros seleccionados</p>
                        <p class="text-xs text-gray-500 mt-1">Amplíe el rango de fechas o quite algún filtro.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-purple-100 text-sm">
                            <thead class="bg-purple-50/60">
                                <tr>
                                    @foreach ($reporte['columnas'] as $clave => $columna)
                                        <th class="px-4 py-3 text-[10px] font-bold text-purple-900/70 uppercase tracking-wider {{ in_array($clave, $reporte['numericas'], true) ? 'text-right' : 'text-left' }}">{{ $columna }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-purple-50">
                                @foreach ($reporte['grupos'] as $grupo)
                                    @if ($conGrupo)
                                        <tr class="bg-purple-50/40">
                                            <td colspan="{{ count($reporte['columnas']) }}" class="px-4 py-2 font-bold text-purple-950">{{ $grupo['nombre'] }}</td>
                                        </tr>
                                    @endif
                                    @foreach ($grupo['filas'] as $fila)
                                        <tr>
                                            @foreach ($reporte['columnas'] as $clave => $columna)
                                                <td class="px-4 py-2.5 text-gray-800 {{ in_array($clave, $reporte['numericas'], true) ? 'text-right tabular-nums' : '' }}">{{ $formato($fila[$clave] ?? '', $clave) }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    @if ($conGrupo && $grupo['subtotal'])
                                        <tr class="bg-gray-50 font-semibold">
                                            @foreach ($reporte['columnas'] as $clave => $columna)
                                                <td class="px-4 py-2 text-gray-700 {{ in_array($clave, $reporte['numericas'], true) ? 'text-right tabular-nums' : '' }}">
                                                    {{ $loop->first ? 'Subtotal: ' : '' }}{{ $formato($grupo['subtotal'][$clave] ?? '', $clave) }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-purple-100/60 font-bold text-purple-950">
                                    @foreach ($reporte['columnas'] as $clave => $columna)
                                        <td class="px-4 py-3 {{ in_array($clave, $reporte['numericas'], true) ? 'text-right tabular-nums' : '' }}">
                                            {{ $loop->first ? 'Total general: ' : '' }}{{ $formato($reporte['total'][$clave] ?? '', $clave) }}
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Validación en el cliente: la fecha de inicio no puede ser posterior a la de fin.
        document.getElementById('form-reporte').addEventListener('submit', function (e) {
            const desde = document.getElementById('desde');
            const hasta = document.getElementById('hasta');
            if (desde && hasta && desde.value && hasta.value && desde.value > hasta.value) {
                e.preventDefault();
                const error = document.getElementById('error-reporte');
                error.textContent = 'La fecha de inicio no puede ser posterior a la fecha de fin.';
                error.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>

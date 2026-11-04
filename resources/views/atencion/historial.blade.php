{{-- CU22 · Historial de citas del paciente (incluye canceladas e inasistencias) --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150';
    $etiqueta = 'text-xs font-semibold uppercase text-purple-950/80';
    $columnas = $conDetalle ? 6 : 5;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            @unless ($esPaciente)
                <a href="{{ route('historial.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors" title="Buscar otro paciente">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
            @endunless
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">{{ $esPaciente ? 'Mi historial' : 'Historial del paciente' }}</h2>
                <p class="text-xs text-purple-800/70">{{ $paciente->apellidos }}, {{ $paciente->nombres }} · {{ $paciente->codigo }} · CI {{ $paciente->ci }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <!-- Filtros -->
            <form method="GET" action="{{ $esPaciente ? route('historial.index') : route('historial.show', $paciente) }}" id="form-historial"
                  class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-2">
                        <x-input-label for="desde" value="Desde" class="{{ $etiqueta }}" />
                        <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] }}" class="{{ $claseCampo }}">
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="hasta" value="Hasta" class="{{ $etiqueta }}" />
                        <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] }}" class="{{ $claseCampo }}">
                    </div>
                    <div class="sm:col-span-4">
                        <x-input-label for="medico" value="Médico" class="{{ $etiqueta }}" />
                        <select id="medico" name="medico" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($medicos as $medico)
                                <option value="{{ $medico->id_medico }}" @selected((int) $filtros['medico'] === $medico->id_medico)>{{ $medico->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="estado" value="Estado" class="{{ $etiqueta }}" />
                        <select id="estado" name="estado" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado }}" @selected($filtros['estado'] === $estado)>{{ $estado }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider cursor-pointer">Filtrar</button>
                        <a href="{{ $esPaciente ? route('historial.index') : route('historial.show', $paciente) }}" class="px-3 py-2.5 text-xs font-semibold text-purple-800 hover:bg-purple-50 rounded-xl">Limpiar</a>
                    </div>
                </div>
                <p id="error-historial" class="{{ $errors->any() ? '' : 'hidden' }} mt-2 text-xs text-red-600">{{ $errors->first() }}</p>
            </form>

            @if ($esPaciente || ! $conDetalle)
                <p class="text-xs text-gray-500">
                    {{ $esPaciente ? 'Para conocer el detalle clínico de una consulta, solicítelo a su médico.' : 'El detalle clínico de las atenciones solo lo ve el personal médico.' }}
                </p>
            @endif

            <!-- Citas -->
            <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100">
                        <thead class="bg-purple-50/60">
                            <tr>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Fecha y hora</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Médico</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Especialidad</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Estado</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Registrada</th>
                                @if ($conDetalle)
                                    <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Atención</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($citas as $cita)
                                <tr class="align-top hover:bg-purple-50/30 transition-colors">
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-semibold text-purple-950">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->hora_inicio_corta }}</p>
                                        <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ $cita->codigo }}</p>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-sm text-gray-800">{{ $cita->medico?->nombre_completo }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-sm text-gray-700">{{ ucfirst($cita->especialidad?->nombre) }}</td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        @include('citas._estado', ['estado' => $cita->estado])
                                        @if ($cita->motivo_cancelacion)
                                            <p class="text-[11px] text-gray-500 mt-1">{{ $cita->motivo_cancelacion }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-xs text-gray-500">{{ $cita->creado_en?->format('d/m/Y H:i') }}</td>
                                    @if ($conDetalle)
                                        <td class="px-4 sm:px-6 py-4 text-sm min-w-64">
                                            @if ($cita->atencion)
                                                <details class="group">
                                                    <summary class="cursor-pointer text-xs font-semibold text-purple-800 hover:text-purple-950">{{ \Illuminate\Support\Str::limit($cita->atencion->diagnostico, 40) }}</summary>
                                                    <dl class="mt-2 space-y-1 text-xs text-gray-700">
                                                        <div><dt class="inline font-semibold">Diagnóstico:</dt> <dd class="inline">{{ $cita->atencion->diagnostico }}</dd></div>
                                                        <div><dt class="inline font-semibold">Tratamiento:</dt> <dd class="inline">{{ $cita->atencion->tratamiento ?? '—' }}</dd></div>
                                                        <div>
                                                            <dt class="inline font-semibold">Signos:</dt>
                                                            <dd class="inline">
                                                                {{ collect([
                                                                    $cita->atencion->peso_kg ? $cita->atencion->peso_kg.' kg' : null,
                                                                    $cita->atencion->talla_cm ? $cita->atencion->talla_cm.' cm' : null,
                                                                    $cita->atencion->presion_arterial ? 'PA '.$cita->atencion->presion_arterial : null,
                                                                    $cita->atencion->temperatura_c ? $cita->atencion->temperatura_c.' °C' : null,
                                                                ])->filter()->join(' · ') ?: '—' }}
                                                            </dd>
                                                        </div>
                                                        @if ($cita->atencion->observaciones)
                                                            <div><dt class="inline font-semibold">Observaciones:</dt> <dd class="inline">{{ $cita->atencion->observaciones }}</dd></div>
                                                        @endif
                                                    </dl>
                                                </details>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $columnas }}" class="px-6 py-12 text-center">
                                        <p class="text-sm font-semibold text-purple-950">No hay citas con esos filtros</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($citas->hasPages())
                    <div class="px-4 sm:px-6 py-4 border-t border-purple-100">{{ $citas->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Validación en el cliente: la fecha final no puede ser anterior a la inicial.
        document.getElementById('form-historial').addEventListener('submit', function (e) {
            const desde = document.getElementById('desde').value;
            const hasta = document.getElementById('hasta').value;
            if (desde && hasta && hasta < desde) {
                e.preventDefault();
                const error = document.getElementById('error-historial');
                error.textContent = 'La fecha final no puede ser anterior a la inicial.';
                error.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>

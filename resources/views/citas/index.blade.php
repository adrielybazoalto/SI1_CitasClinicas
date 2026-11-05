{{-- Listado de citas · punto de entrada de CU13, CU15, CU16 y CU17 --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150';
    $usuario = Auth::user();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $esPaciente ? 'Mis citas' : 'Citas' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">Programadas, confirmadas, atendidas, canceladas e inasistencias</p>
            </div>
            @if ($usuario->tienePermiso('cita.consultar'))
                <a href="{{ route('fichas.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 transition-all duration-150">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>{{ $usuario->tienePermiso('cita.programar') ? 'Programar cita' : 'Citas disponibles' }}</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-aviso-modulo />
            @include('citas._alertas')

            <!-- Filtros -->
            <form method="GET" action="{{ route('citas.index') }}" class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                @if ($accion)
                    <input type="hidden" name="accion" value="{{ $accion }}">
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    @unless ($esPaciente)
                        <div class="sm:col-span-3">
                            <x-input-label for="buscar" value="Buscar" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="buscar" name="buscar" type="text" value="{{ $filtros['buscar'] }}" placeholder="Código, CI o apellido" class="{{ $claseCampo }}" />
                        </div>
                    @endunless
                    <div class="sm:col-span-2">
                        <x-input-label for="fecha" value="Fecha" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="fecha" name="fecha" type="date" value="{{ $filtros['fecha'] }}" class="{{ $claseCampo }}" />
                    </div>
                    <div class="{{ $esPaciente ? 'sm:col-span-5' : 'sm:col-span-3' }}">
                        <x-input-label for="medico" value="Médico" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="medico" name="medico" class="{{ $claseCampo }}">
                            <option value="">Todos los médicos</option>
                            @foreach ($medicos as $medico)
                                <option value="{{ $medico->id_medico }}" @selected((int) $filtros['medico'] === (int) $medico->id_medico)>{{ $medico->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="estado" value="Estado" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="estado" name="estado" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado }}" @selected($filtros['estado'] === $estado)>{{ $estado }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 flex items-end gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors cursor-pointer">
                            Filtrar
                        </button>
                        <a href="{{ route('citas.index', $accion ? ['accion' => $accion] : []) }}" class="inline-flex items-center justify-center px-3 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">Limpiar</a>
                    </div>
                </div>
            </form>

            <!-- Tabla -->
            <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100">
                        <thead class="bg-purple-50/60">
                            <tr>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Código</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Fecha y hora</th>
                                @unless ($esPaciente)
                                    <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Paciente</th>
                                @endunless
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Médico</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Ficha</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Estado</th>
                                <th class="px-4 sm:px-6 py-3.5 text-right text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($citas as $cita)
                                <tr class="hover:bg-purple-50/30 transition-colors">
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap font-mono text-sm font-semibold text-purple-950">{{ $cita->codigo }}</td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-semibold text-purple-950">{{ $cita->fecha->format('d/m/Y') }}</p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $cita->hora_inicio_corta }} – {{ $cita->hora_fin_corta }}</p>
                                    </td>
                                    @unless ($esPaciente)
                                        <td class="px-4 sm:px-6 py-4">
                                            <p class="text-sm font-semibold text-purple-950">{{ $cita->paciente?->apellidos }}, {{ $cita->paciente?->nombres }}</p>
                                            <p class="text-[11px] text-gray-400 mt-0.5">CI {{ $cita->paciente?->ci }}</p>
                                        </td>
                                    @endunless
                                    <td class="px-4 sm:px-6 py-4">
                                        <p class="text-sm text-gray-800">{{ $cita->medico?->nombre_completo }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ ucfirst($cita->especialidad?->nombre) }}</p>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        N.º {{ $cita->nro_cita }}
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $cita->canal === 'LINEA' ? 'En línea' : 'Mostrador' }}{{ $cita->sobrecupo ? ' · sobrecupo' : '' }}</p>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">@include('citas._estado', ['estado' => $cita->estado])</td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1">
                                            @php $destacado = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors cursor-pointer'; @endphp
                                            @if ($accion === 'reprogramar' && $cita->estado === 'Programada' && $usuario->tienePermiso('cita.reprogramar'))
                                                {{-- CU15 --}}
                                                <a href="{{ route('citas.reprogramar', $cita) }}" class="{{ $destacado }} bg-purple-800 hover:bg-purple-900 text-white">Reprogramar</a>
                                            @elseif ($accion === 'cancelar' && $cita->estado === 'Programada' && $usuario->tienePermiso('cita.cancelar'))
                                                {{-- CU16: el motivo se pide en el detalle de la cita --}}
                                                <a href="{{ route('citas.show', $cita) }}#cancelar" class="{{ $destacado }} bg-rose-600 hover:bg-rose-700 text-white">Cancelar</a>
                                            @elseif ($accion === 'asistencia' && $usuario->tienePermiso('cita.asistencia'))
                                                {{-- CU17 --}}
                                                @if ($cita->estado === 'Programada')
                                                    <form method="POST" action="{{ route('citas.llegada', $cita) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="{{ $destacado }} bg-sky-600 hover:bg-sky-700 text-white">Registrar llegada</button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('citas.inasistencia', $cita) }}"
                                                      onsubmit="return confirm('¿Registrar la inasistencia de la cita {{ $cita->codigo }}?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="{{ $destacado }} bg-amber-500 hover:bg-amber-600 text-white">Inasistencia</button>
                                                </form>
                                            @elseif ($cita->estado === 'Programada' && $cita->fecha->isToday() && $usuario->tienePermiso('cita.asistencia'))
                                                <form method="POST" action="{{ route('citas.llegada', $cita) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-50 rounded-lg transition-colors cursor-pointer">Llegó</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('citas.show', $cita) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-lg transition-colors">Ver</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <p class="text-sm font-semibold text-purple-950">No hay citas con esos filtros</p>
                                        <p class="text-xs text-gray-500 mt-1">Reserve una ficha desde la pantalla de fichas disponibles.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($citas->hasPages())
                    <div class="px-4 sm:px-6 py-4 border-t border-purple-100">
                        {{ $citas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

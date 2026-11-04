{{-- CU08 · Gestionar médicos: listado, especialidades, cuenta y baja lógica --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">Médicos</h2>
                <p class="text-xs text-purple-800/70 mt-0.5">Profesionales, matrículas, especialidades y cuentas de acceso</p>
            </div>
            <a href="{{ route('medicos.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 transition-all duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nuevo médico</span>
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('oferta._tabs')

            @if (session('status'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl shadow-xs flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-900 rounded-xl shadow-xs flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-8.47 14.14A1 1 0 002.69 20h18.62a1 1 0 00.87-1.5L13.71 3.86a1 1 0 00-1.42 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Filtros -->
            <form method="GET" action="{{ route('medicos.index') }}" class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-5">
                        <x-input-label for="buscar" value="Buscar" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] }}"
                               placeholder="matrícula, código o nombre"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                    </div>
                    <div class="sm:col-span-3">
                        <x-input-label for="especialidad" value="Especialidad" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="especialidad" name="especialidad"
                                class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                            <option value="">Todas</option>
                            @foreach ($especialidades as $especialidad)
                                <option value="{{ $especialidad->id_especialidad }}" @selected((int) $filtros['especialidad'] === (int) $especialidad->id_especialidad)>
                                    {{ $especialidad->nombre }}{{ $especialidad->estado ? '' : ' (inactiva)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="estado" value="Estado" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="estado" name="estado"
                                class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                            <option value="">Todos</option>
                            <option value="1" @selected($filtros['estado'] === '1')>Activos</option>
                            <option value="0" @selected($filtros['estado'] === '0')>Inactivos</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2 flex items-end gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Filtrar
                        </button>
                        <a href="{{ route('medicos.index') }}" class="inline-flex items-center justify-center px-3 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors" title="Limpiar filtros">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>

            <!-- Tabla -->
            <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100">
                        <thead class="bg-purple-50/60">
                            <tr>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Médico</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Especialidades</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Horarios</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Cuenta</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Estado</th>
                                <th class="px-4 sm:px-6 py-3.5 text-right text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($medicos as $medico)
                                <tr class="hover:bg-purple-50/30 transition-colors">
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center shrink-0 font-bold text-sm">
                                                {{ mb_substr($medico->nombres, 0, 1) }}{{ mb_substr($medico->apellidos, 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-purple-950">{{ $medico->nombre_completo }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    <span class="font-mono">{{ $medico->codigo }}</span>
                                                    <span class="mx-1 text-gray-300">·</span>
                                                    Mat. {{ $medico->matricula }}
                                                </p>
                                                @if ($medico->correo || $medico->telefono)
                                                    <p class="text-[11px] text-gray-400 mt-0.5">
                                                        {{ $medico->correo }}
                                                        @if ($medico->correo && $medico->telefono) · @endif
                                                        {{ $medico->telefono }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        @if ($medico->especialidades->isEmpty())
                                            <span class="text-xs text-gray-400 italic">Sin especialidades</span>
                                        @else
                                            <div class="flex flex-wrap gap-1.5 max-w-xs">
                                                @foreach ($medico->especialidades as $especialidad)
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold border {{ $especialidad->estado ? 'bg-purple-50 text-purple-800 border-purple-100' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                                                        @unless ($especialidad->estado)
                                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                        @endunless
                                                        {{ $especialidad->nombre }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purple-50 text-purple-800 text-xs font-semibold border border-purple-100">
                                            {{ $medico->horarios_activos_count }} activos
                                        </span>
                                        <p class="text-[11px] text-gray-400 mt-1">{{ $medico->horarios_count }} en total</p>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        @if ($medico->usuario)
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $medico->usuario->estado ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                                <span class="text-xs font-semibold text-gray-700">{{ $medico->usuario->username }}</span>
                                            </div>
                                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $medico->usuario->correo }}</p>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Sin cuenta</span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        @if ($medico->estado)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('medicos.edit', $medico) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-lg transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Editar
                                            </a>
                                            @if (Auth::user()->tienePermiso('horario.gestionar'))
                                                <a href="{{ route('horarios.index', ['medico' => $medico->id_medico]) }}"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    Horarios
                                                </a>
                                            @endif
                                            <form method="POST" action="{{ route('medicos.estado', $medico) }}"
                                                  onsubmit="return confirm('¿{{ $medico->estado ? 'Desactivar' : 'Activar' }} este médico? {{ $medico->estado ? 'Conservará su historial y horarios.' : '' }}')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors cursor-pointer {{ $medico->estado ? 'text-rose-700 hover:bg-rose-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                                    {{ $medico->estado ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="mx-auto w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-purple-950">No hay médicos registrados</p>
                                        <p class="text-xs text-gray-500 mt-1">Registre al primer profesional y asigne sus especialidades.</p>
                                        <a href="{{ route('medicos.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors">
                                            Nuevo médico
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($medicos->hasPages())
                    <div class="px-4 sm:px-6 py-4 border-t border-purple-100">
                        {{ $medicos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

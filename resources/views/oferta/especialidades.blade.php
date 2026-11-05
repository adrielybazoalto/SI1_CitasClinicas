{{-- CU07 · Gestionar especialidades: listado, filtros, estado y duración --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Especialidades</h2>
                <p class="text-sm text-gray-500 mt-0.5">Catálogo de especialidades médicas y duración base de la consulta</p>
            </div>
            <a href="{{ route('especialidades.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 transition-all duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nueva especialidad</span>
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
            <form method="GET" action="{{ route('especialidades.index') }}" class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-7">
                        <x-input-label for="buscar" value="Buscar por nombre" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] }}"
                               placeholder="ej. cardiología"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                    </div>
                    <div class="sm:col-span-3">
                        <x-input-label for="estado" value="Estado" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="estado" name="estado"
                                class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                            <option value="">Todas</option>
                            <option value="1" @selected($filtros['estado'] === '1')>Activas</option>
                            <option value="0" @selected($filtros['estado'] === '0')>Inactivas</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2 flex items-end gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Filtrar
                        </button>
                        <a href="{{ route('especialidades.index') }}" class="inline-flex items-center justify-center px-3 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors" title="Limpiar filtros">
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
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Especialidad</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Duración</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Médicos</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Horarios activos</th>
                                <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Estado</th>
                                <th class="px-4 sm:px-6 py-3.5 text-right text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($especialidades as $especialidad)
                                <tr class="hover:bg-purple-50/30 transition-colors">
                                    <td class="px-4 sm:px-6 py-4">
                                        <p class="text-sm font-semibold text-purple-950">{{ $especialidad->nombre }}</p>
                                        @if ($especialidad->descripcion)
                                            <p class="text-xs text-gray-500 mt-0.5 max-w-md">{{ $especialidad->descripcion }}</p>
                                        @else
                                            <p class="text-xs text-gray-400 mt-0.5 italic">Sin descripción</p>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purple-50 text-purple-800 text-xs font-semibold border border-purple-100">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ $especialidad->duracion_min }} min
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $especialidad->medicos_count }}</td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $especialidad->horarios_activos_count }}</td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                        @if ($especialidad->estado)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Activa
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                Inactiva
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ route('especialidades.edit', $especialidad) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-lg transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Editar
                                            </a>
                                            <form method="POST" action="{{ route('especialidades.estado', $especialidad) }}"
                                                  onsubmit="return confirm('¿{{ $especialidad->estado ? 'Desactivar' : 'Activar' }} esta especialidad?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors cursor-pointer {{ $especialidad->estado ? 'text-rose-700 hover:bg-rose-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                                    {{ $especialidad->estado ? 'Desactivar' : 'Activar' }}
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-purple-950">No hay especialidades registradas</p>
                                        <p class="text-xs text-gray-500 mt-1">Cree la primera especialidad para empezar a configurar la oferta médica.</p>
                                        <a href="{{ route('especialidades.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors">
                                            Nueva especialidad
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($especialidades->hasPages())
                    <div class="px-4 sm:px-6 py-4 border-t border-purple-100">
                        {{ $especialidades->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

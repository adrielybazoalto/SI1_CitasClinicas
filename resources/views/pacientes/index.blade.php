{{-- CU05 · Gestionar pacientes: listado, búsqueda por CI/apellidos, estado y baja lógica --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Pacientes</h2>
                <p class="text-sm text-gray-500 mt-0.5">Registro, búsqueda y gestión de pacientes y sus antecedentes clínicos</p>
            </div>
            <a href="{{ route('pacientes.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 transition-all duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nuevo paciente</span>
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            <!-- Mensajes de estado -->
            @if (session('status'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl shadow-xs flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            {{-- Clave temporal: se muestra una sola vez, tras el alta --}}
            @if (session('clave_temporal'))
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl shadow-xs">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <div class="text-sm">
                            <p class="font-medium">Contraseña temporal de <strong>{{ session('clave_temporal.username') }}</strong>:</p>
                            <p class="mt-1 font-mono text-base font-bold tracking-wider bg-white/70 inline-block px-3 py-1 rounded-lg border border-amber-200">
                                {{ session('clave_temporal.clave') }}
                            </p>
                            <p class="mt-2 text-xs text-amber-800/80">Anótela y entréguela al paciente. No se volverá a mostrar y deberá cambiarla en su primer inicio de sesión.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-xs">
                    <div class="flex items-center gap-2 font-semibold text-sm mb-1">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Por favor verifique los siguientes errores:
                    </div>
                    @foreach ($errors->all() as $error)
                        <div class="text-xs ml-6">• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Filtros -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                <form method="GET" action="{{ route('pacientes.index') }}" class="flex flex-col sm:flex-row items-end gap-3">
                    <div class="flex-1 w-full">
                        <x-input-label for="buscar" value="Buscar" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                                </svg>
                            </span>
                            <input id="buscar" name="buscar" type="text"
                                   value="{{ request('buscar') }}"
                                   placeholder="CI, apellidos, nombres o código"
                                   class="block w-full pl-9 pr-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        </div>
                    </div>

                    <div class="w-full sm:w-44">
                        <x-input-label for="estado" value="Estado" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="estado" name="estado"
                                class="mt-1 block w-full py-2.5 px-3 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                            <option value="">Todos</option>
                            <option value="1" @selected(request('estado') === '1')>Activos</option>
                            <option value="0" @selected(request('estado') === '0')>Inactivos</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm transition-all duration-150 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                            </svg>
                            Buscar
                        </button>
                        @if (request()->hasAny(['buscar', 'estado']))
                            <a href="{{ route('pacientes.index') }}"
                               class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Tabla de pacientes -->
            <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100">
                        <thead class="bg-purple-50/60">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-950 uppercase tracking-wider">Código</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-950 uppercase tracking-wider">CI</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-950 uppercase tracking-wider">Paciente</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-950 uppercase tracking-wider">Sexo</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-950 uppercase tracking-wider">Teléfono</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-950 uppercase tracking-wider">Correo</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-950 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold text-purple-950 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($pacientes as $paciente)
                                <tr class="hover:bg-purple-50/30 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-xs font-mono font-semibold text-purple-800">
                                        {{ $paciente->codigo }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $paciente->ci }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-gray-900">{{ $paciente->apellidos }}, {{ $paciente->nombres }}</div>
                                        <div class="text-[11px] text-gray-500">
                                            {{ $paciente->fecha_nacimiento?->format('d/m/Y') ?? 'Sin fecha' }}
                                            @if ($paciente->antecedente)
                                                · <span class="text-purple-700 font-medium">Antecedentes registrados</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                        {{ ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'][$paciente->sexo] ?? $paciente->sexo }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                        {{ $paciente->telefono }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                        {{ $paciente->correo ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($paciente->estado)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('pacientes.edit', $paciente) }}"
                                               class="p-1.5 text-purple-700 hover:text-purple-950 hover:bg-purple-100 rounded-lg transition-colors"
                                               title="Editar paciente">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>

                                            @if (Auth::user()->tienePermiso('antecedente.gestionar'))
                                                <a href="{{ route('pacientes.antecedentes', $paciente) }}"
                                                   class="p-1.5 text-purple-700 hover:text-purple-950 hover:bg-purple-100 rounded-lg transition-colors"
                                                   title="Antecedentes clínicos">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </a>
                                            @endif

                                            @if ($paciente->estado)
                                                <form method="POST" action="{{ route('pacientes.destroy', $paciente) }}"
                                                      onsubmit="return confirm('¿Desactivar al paciente {{ $paciente->codigo }}? Su historial se conservará.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                            title="Desactivar paciente">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('pacientes.activar', $paciente) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            class="p-1.5 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded-lg transition-colors cursor-pointer"
                                                            title="Activar paciente">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2 text-gray-400">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <p class="text-sm font-medium">No se encontraron pacientes.</p>
                                            @if (request()->hasAny(['buscar', 'estado']))
                                                <a href="{{ route('pacientes.index') }}" class="text-xs font-semibold text-purple-700 hover:text-purple-900">Ver todos los pacientes</a>
                                            @else
                                                <a href="{{ route('pacientes.create') }}" class="text-xs font-semibold text-purple-700 hover:text-purple-900">Registrar el primer paciente</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($pacientes->hasPages())
                    <div class="px-4 py-3 border-t border-purple-100 bg-purple-50/30">
                        {{ $pacientes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

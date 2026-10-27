{{-- CU02 · Gestionar usuarios: listado, filtros, estado y restablecer clave --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">Usuarios</h2>
                <p class="text-xs text-purple-800/70 mt-0.5">Gestión de cuentas, roles asignados y estados de acceso al sistema clínico</p>
            </div>
            <a href="{{ route('usuarios.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 transition-all duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nuevo usuario</span>
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

            {{-- Clave temporal como dato flash (se muestra una sola vez) --}}
            @if (session('clave_temporal'))
                <div class="p-4 bg-purple-50 border border-purple-200 text-purple-950 rounded-xl shadow-xs">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <div class="space-y-1">
                            <p class="text-sm font-medium">Contraseña temporal de <strong>{{ session('clave_temporal.username') }}</strong>:</p>
                            <div class="pt-1">
                                <code class="px-3 py-1.5 bg-white border border-purple-300 text-purple-900 font-mono font-bold rounded-lg text-lg select-all shadow-xs inline-block">
                                    {{ session('clave_temporal.clave') }}
                                </code>
                            </div>
                            <p class="text-xs text-purple-800/80 pt-1">Cópiela ahora: no se volverá a mostrar. El usuario deberá cambiarla obligatoriamente al iniciar sesión.</p>
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

            {{-- Tarjeta de Filtros con la nueva paleta de colores morados --}}
            <form method="GET" action="{{ route('usuarios.index') }}" class="bg-white p-5 shadow-xs border border-purple-100 rounded-2xl flex flex-wrap gap-4 items-end">
                <!-- Filtro Buscar -->
                <div class="flex-1 min-w-[200px]">
                    <x-input-label for="buscar" value="Buscar" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-purple-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input id="buscar" 
                               name="buscar" 
                               type="text" 
                               value="{{ request('buscar') }}" 
                               placeholder="usuario o correo" 
                               class="block w-full pl-9 pr-3 py-2 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                    </div>
                </div>

                <!-- Filtro Rol -->
                <div class="w-full sm:w-48">
                    <x-input-label for="id_rol" value="Rol" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <select id="id_rol" 
                            name="id_rol" 
                            class="mt-1 block w-full py-2 px-3 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                        <option value="">Todos los roles</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->id_rol }}" @selected(request('id_rol') == $rol->id_rol)>{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro Estado -->
                <div class="w-full sm:w-40">
                    <x-input-label for="estado" value="Estado" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <select id="estado" 
                            name="estado" 
                            class="mt-1 block w-full py-2 px-3 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                        <option value="">Todos</option>
                        <option value="1" @selected(request('estado') === '1')>Activos</option>
                        <option value="0" @selected(request('estado') === '0')>Inactivos</option>
                    </select>
                </div>

                <!-- Botón de acción principal: Filtrar -->
                <div class="flex items-center gap-2">
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-xs hover:shadow-md hover:shadow-purple-900/20 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-150 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span>Filtrar</span>
                    </button>

                    @if (request()->hasAny(['buscar', 'id_rol', 'estado']))
                        <a href="{{ route('usuarios.index') }}" 
                           class="inline-flex items-center px-3 py-2 text-xs font-semibold text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-lg transition-colors">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>

            {{-- Tabla de Usuarios con estilo visual limpio, moderno y responsive --}}
            <div class="bg-white shadow-xs border border-purple-100 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-purple-50/80 border-b border-purple-100 text-left text-xs font-bold text-purple-950 uppercase tracking-wider">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Usuario</th>
                                <th scope="col" class="px-5 py-3.5">Correo</th>
                                <th scope="col" class="px-5 py-3.5">Rol</th>
                                <th scope="col" class="px-5 py-3.5">Estado</th>
                                <th scope="col" class="px-5 py-3.5">Creado</th>
                                <th scope="col" class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-100/60 bg-white">
                            @forelse ($usuarios as $u)
                                <tr class="hover:bg-purple-50/30 transition-colors">
                                    <!-- Usuario con avatar e indicadores -->
                                    <td class="px-5 py-3.5 font-medium whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-900 flex items-center justify-center font-bold text-xs uppercase shrink-0 border border-purple-200">
                                                {{ substr($u->username, 0, 1) }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-purple-950">{{ $u->username }}</span>
                                                @if ($u->debe_cambiar_clave)
                                                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                        clave temporal
                                                    </span>
                                                @endif
                                                @if ($u->estaBloqueado())
                                                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                        bloqueado hasta {{ $u->bloqueado_hasta->format('H:i') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Correo -->
                                    <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap">{{ $u->correo }}</td>

                                    <!-- Rol -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-800 border border-purple-200/80">
                                            {{ $u->rol?->nombre ?? 'Sin rol' }}
                                        </span>
                                    </td>

                                    <!-- Estado con la paleta de morados para "Activo" -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        @if ($u->estado)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-900 border border-purple-200">
                                                <span class="w-2 h-2 rounded-full bg-purple-700 animate-pulse"></span>
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Creado -->
                                    <td class="px-5 py-3.5 text-xs text-gray-500 whitespace-nowrap">{{ $u->creado_en?->format('d/m/Y') }}</td>

                                    <!-- Acciones -->
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3 text-xs">
                                            <!-- Editar -->
                                            <a href="{{ route('usuarios.edit', $u) }}" 
                                               class="font-semibold text-purple-700 hover:text-purple-950 hover:underline flex items-center gap-1 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Editar
                                            </a>

                                            <!-- Cambiar Estado (Desactivar / Activar) -->
                                            <form method="POST" action="{{ route('usuarios.estado', $u) }}"
                                                  onsubmit="return confirm('¿{{ $u->estado ? 'Desactivar' : 'Activar' }} al usuario {{ $u->username }}?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="{{ $u->estado ? 'text-rose-600 hover:text-rose-800' : 'text-emerald-700 hover:text-emerald-900' }} font-semibold hover:underline cursor-pointer transition-colors">
                                                    {{ $u->estado ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>

                                            <!-- Restablecer clave -->
                                            <form method="POST" action="{{ route('usuarios.restablecer', $u) }}"
                                                  onsubmit="return confirm('¿Generar una contraseña temporal para {{ $u->username }}?')">
                                                @csrf
                                                <button type="submit" 
                                                        class="text-gray-600 hover:text-purple-900 font-medium hover:underline cursor-pointer transition-colors">
                                                    Restablecer clave
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-12 h-12 rounded-full bg-purple-50 text-purple-400 flex items-center justify-center mb-3">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                                </svg>
                                            </div>
                                            <p class="font-medium text-gray-700">No se encontraron usuarios.</p>
                                            <p class="text-xs text-gray-400 mt-1">Intente ajustar los filtros de búsqueda o registre un nuevo usuario.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación con estilo integrado -->
            <div class="pt-2">
                {{ $usuarios->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

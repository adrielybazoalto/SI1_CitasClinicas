{{-- CU02 · Gestionar usuarios: listado, filtros, estado y restablecer clave --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuarios</h2>
            <a href="{{ route('usuarios.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Nuevo usuario
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            {{-- La clave temporal llega como dato flash: se muestra una sola vez --}}
            @if (session('clave_temporal'))
                <div class="p-4 bg-yellow-50 border border-yellow-300 text-yellow-900 rounded">
                    Contraseña temporal de <strong>{{ session('clave_temporal.username') }}</strong>:
                    <code class="px-2 py-1 bg-white border rounded text-lg select-all">{{ session('clave_temporal.clave') }}</code>
                    <p class="text-sm mt-1">Cópiela ahora: no se volverá a mostrar. El usuario deberá cambiarla al iniciar sesión.</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Filtros --}}
            <form method="GET" action="{{ route('usuarios.index') }}" class="bg-white p-4 shadow-sm sm:rounded-lg flex flex-wrap gap-3 items-end">
                <div>
                    <x-input-label for="buscar" value="Buscar" />
                    <x-text-input id="buscar" name="buscar" type="text" class="mt-1" :value="request('buscar')" placeholder="usuario o correo" />
                </div>
                <div>
                    <x-input-label for="id_rol" value="Rol" />
                    <select id="id_rol" name="id_rol" class="mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->id_rol }}" @selected(request('id_rol') == $rol->id_rol)>{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="estado" value="Estado" />
                    <select id="estado" name="estado" class="mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        <option value="1" @selected(request('estado') === '1')>Activos</option>
                        <option value="0" @selected(request('estado') === '0')>Inactivos</option>
                    </select>
                </div>
                <x-primary-button>Filtrar</x-primary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-2">Usuario</th>
                            <th class="px-4 py-2">Correo</th>
                            <th class="px-4 py-2">Rol</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2">Creado</th>
                            <th class="px-4 py-2 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($usuarios as $u)
                            <tr>
                                <td class="px-4 py-2 font-medium">
                                    {{ $u->username }}
                                    @if ($u->debe_cambiar_clave)
                                        <span class="ml-1 text-xs text-yellow-700">(clave temporal)</span>
                                    @endif
                                    @if ($u->estaBloqueado())
                                        <span class="ml-1 text-xs text-red-700">(bloqueado hasta {{ $u->bloqueado_hasta->format('H:i') }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $u->correo }}</td>
                                <td class="px-4 py-2">{{ $u->rol?->nombre }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $u->estado ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $u->estado ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $u->creado_en?->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('usuarios.edit', $u) }}" class="text-indigo-600 hover:underline">Editar</a>

                                        <form method="POST" action="{{ route('usuarios.estado', $u) }}"
                                              onsubmit="return confirm('¿{{ $u->estado ? 'Desactivar' : 'Activar' }} al usuario {{ $u->username }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="{{ $u->estado ? 'text-red-600' : 'text-green-700' }} hover:underline">
                                                {{ $u->estado ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('usuarios.restablecer', $u) }}"
                                              onsubmit="return confirm('¿Generar una contraseña temporal para {{ $u->username }}?')">
                                            @csrf
                                            <button class="text-gray-700 hover:underline">Restablecer clave</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No se encontraron usuarios.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $usuarios->links() }}
        </div>
    </div>
</x-app-layout>

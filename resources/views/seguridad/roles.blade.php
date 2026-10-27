{{-- CU03 · Listado de roles y alta de un rol nuevo --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">Roles y Permisos</h2>
                <p class="text-xs text-purple-800/70">Perfiles de acceso, asignación de privilegios y control de seguridad</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl shadow-xs flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <!-- Tabla de Roles Existentes -->
            <div class="bg-white shadow-xs border border-purple-100 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-purple-100/80 bg-purple-50/40 flex items-center justify-between">
                    <h3 class="font-bold text-sm text-purple-950 uppercase tracking-wider">Roles Registrados</h3>
                    <span class="text-xs text-purple-800 font-medium">{{ count($roles) }} roles activos</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-purple-50/70 border-b border-purple-100 text-left text-xs font-bold text-purple-950 uppercase tracking-wider">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">Rol</th>
                                <th scope="col" class="px-5 py-3.5">Descripción</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Usuarios</th>
                                <th scope="col" class="px-5 py-3.5 text-center">Permisos</th>
                                <th scope="col" class="px-5 py-3.5">Estado</th>
                                <th scope="col" class="px-5 py-3.5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-100/60 bg-white">
                            @foreach ($roles as $rol)
                                <tr class="hover:bg-purple-50/30 transition-colors">
                                    <td class="px-5 py-3.5 font-bold text-purple-950 whitespace-nowrap">{{ $rol->nombre }}</td>
                                    <td class="px-5 py-3.5 text-gray-600">{{ $rol->descripcion }}</td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-900 border border-purple-200">
                                            {{ $rol->usuarios_count }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-800 border border-purple-200">
                                            {{ $rol->permisos_count }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        @if ($rol->estado)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-900 border border-purple-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-700"></span>
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <a href="{{ route('roles.permisos', $rol) }}" class="inline-flex items-center gap-1 text-xs font-bold text-purple-700 hover:text-purple-950 hover:underline transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            Permisos
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Formulario de Nuevo Rol -->
            <form method="POST" action="{{ route('roles.store') }}" class="bg-white p-6 sm:p-8 shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl space-y-4">
                @csrf
                <div class="border-b border-purple-100/80 pb-3">
                    <h3 class="font-bold text-lg text-purple-950">Crear Nuevo Rol</h3>
                    <p class="text-xs text-gray-500">Agregue un nuevo perfil de usuario para asignar permisos posteriormente</p>
                </div>
                <div class="grid sm:grid-cols-2 gap-4 pt-1">
                    <div>
                        <x-input-label for="nombre" value="Nombre del rol" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full bg-purple-50/20 border-purple-200" required minlength="3" maxlength="30" :value="old('nombre')" placeholder="ej. Recepcionista" />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="descripcion" value="Descripción del rol" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <x-text-input id="descripcion" name="descripcion" type="text" class="mt-1 block w-full bg-purple-50/20 border-purple-200" maxlength="150" :value="old('descripcion')" placeholder="Responsabilidades o alcance" />
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-1.5" />
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <x-primary-button>
                        Registrar rol
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

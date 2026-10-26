{{-- CU03 · Listado de roles y alta de un rol nuevo --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Roles y permisos</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-2">Rol</th>
                            <th class="px-4 py-2">Descripción</th>
                            <th class="px-4 py-2 text-center">Usuarios</th>
                            <th class="px-4 py-2 text-center">Permisos</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($roles as $rol)
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ $rol->nombre }}</td>
                                <td class="px-4 py-2">{{ $rol->descripcion }}</td>
                                <td class="px-4 py-2 text-center">{{ $rol->usuarios_count }}</td>
                                <td class="px-4 py-2 text-center">{{ $rol->permisos_count }}</td>
                                <td class="px-4 py-2">{{ $rol->estado ? 'Activo' : 'Inactivo' }}</td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('roles.permisos', $rol) }}" class="text-indigo-600 hover:underline">Permisos</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('roles.store') }}" class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                @csrf
                <h3 class="font-semibold text-gray-800">Nuevo rol</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" required minlength="3" maxlength="30" :value="old('nombre')" />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="descripcion" value="Descripción" />
                        <x-text-input id="descripcion" name="descripcion" type="text" class="mt-1 block w-full" maxlength="150" :value="old('descripcion')" />
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Registrar rol</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

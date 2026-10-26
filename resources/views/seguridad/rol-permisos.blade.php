{{-- CU03 · Matriz de permisos de un rol, agrupada por paquete --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Permisos del rol: {{ $rol->nombre }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            @error('permisos')
                <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('roles.permisos.update', $rol) }}" class="space-y-4">
                @csrf
                @method('PUT')

                @forelse ($catalogo as $paquete => $permisos)
                    <fieldset class="bg-white p-4 shadow-sm sm:rounded-lg">
                        <legend class="font-semibold text-gray-800 px-1">{{ $paquete }}</legend>
                        <div class="grid sm:grid-cols-2 gap-2 mt-2">
                            @foreach ($permisos as $permiso)
                                @php($protegido = $permiso->codigo === $codigoProtegido)
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" name="permisos[]" value="{{ $permiso->id_permiso }}"
                                           class="mt-0.5 rounded border-gray-300"
                                           @checked(in_array($permiso->id_permiso, old('permisos', $asignados)))
                                           @if ($protegido) onclick="return false;" title="Permiso obligatorio del Administrador" @endif>
                                    <span>
                                        {{ $permiso->descripcion }}
                                        <code class="text-xs text-gray-500">{{ $permiso->codigo }}</code>
                                        @if ($protegido)
                                            <span class="text-xs text-yellow-700">(obligatorio)</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @empty
                    <p class="text-gray-500">No hay permisos registrados. Ejecute <code>php artisan db:seed --class=SeguridadSeeder</code>.</p>
                @endforelse

                <div class="flex justify-end gap-3">
                    <a href="{{ route('roles.index') }}" class="px-4 py-2 text-sm text-gray-700 hover:underline">Volver</a>
                    <x-primary-button>Guardar permisos</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

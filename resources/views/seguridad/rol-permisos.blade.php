{{-- CU03 · Matriz de permisos de un rol, agrupada por paquete --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('roles.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">Permisos del Rol: {{ $rol->nombre }}</h2>
                <p class="text-xs text-purple-800/70">Seleccione los permisos y operaciones autorizadas para este perfil</p>
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

            @error('permisos')
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-xs">
                    {{ $message }}
                </div>
            @enderror

            <form method="POST" action="{{ route('roles.permisos.update', $rol) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @forelse ($catalogo as $paquete => $permisos)
                    <fieldset class="bg-white p-5 sm:p-6 shadow-xs border border-purple-100 rounded-2xl">
                        <legend class="font-bold text-sm text-purple-950 uppercase tracking-wider px-2 bg-purple-50 rounded-lg py-1 border border-purple-200/80">
                            {{ $paquete }}
                        </legend>
                        <div class="grid sm:grid-cols-2 gap-3 mt-3">
                            @foreach ($permisos as $permiso)
                                @php($protegido = $permiso->codigo === $codigoProtegido)
                                <label class="flex items-start gap-3 p-2.5 rounded-xl border border-transparent hover:border-purple-200 hover:bg-purple-50/40 transition-colors cursor-pointer text-sm">
                                    <input type="checkbox" 
                                           name="permisos[]" 
                                           value="{{ $permiso->id_permiso }}"
                                           class="mt-1 rounded-md border-purple-300 text-purple-700 focus:ring-purple-600 w-4 h-4 cursor-pointer"
                                           @checked(in_array($permiso->id_permiso, old('permisos', $asignados)))
                                           @if ($protegido) onclick="return false;" title="Permiso obligatorio del Administrador" @endif>
                                    <div class="leading-snug">
                                        <span class="font-medium text-gray-800">{{ $permiso->descripcion }}</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <code class="text-[11px] font-mono text-purple-700/80 bg-purple-50 px-1.5 py-0.5 rounded">{{ $permiso->codigo }}</code>
                                            @if ($protegido)
                                                <span class="text-[10px] font-bold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">(obligatorio)</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @empty
                    <div class="bg-white p-8 rounded-2xl border border-purple-100 text-center text-gray-500">
                        <p>No hay permisos registrados en el catálogo.</p>
                    </div>
                @endforelse

                <div class="flex items-center justify-end gap-3 pt-3">
                    <a href="{{ route('roles.index') }}" class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">
                        Volver al listado
                    </a>
                    <x-primary-button>
                        Guardar permisos
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

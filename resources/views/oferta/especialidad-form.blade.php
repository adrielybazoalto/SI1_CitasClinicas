{{-- CU07 · Alta y edición de especialidad --}}
@php($esAlta = ! $especialidad->exists)
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('especialidades.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">
                    {{ $esAlta ? 'Nueva especialidad' : 'Editar especialidad' }}
                </h2>
                <p class="text-sm text-gray-500">{{ $esAlta ? 'Registre una especialidad y su duración base de consulta' : 'Modifique los datos de la especialidad' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" class="bg-white p-6 sm:p-8 shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl space-y-5"
                  action="{{ $esAlta ? route('especialidades.store') : route('especialidades.update', $especialidad) }}">
                @csrf
                @unless ($esAlta)
                    @method('PUT')
                @endunless

                <!-- Nombre -->
                <div>
                    <x-input-label for="nombre" value="Nombre de la especialidad" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <input id="nombre" name="nombre" type="text" required minlength="3" maxlength="60"
                           value="{{ old('nombre', $especialidad->nombre) }}" autofocus
                           placeholder="ej. cardiología"
                           class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                    <p class="mt-1.5 text-[11px] text-purple-800/60">Se guarda en minúsculas y con espacios simples para evitar duplicados.</p>
                    <x-input-error :messages="$errors->get('nombre')" class="mt-1.5" />
                </div>

                <!-- Descripción -->
                <div>
                    <x-input-label for="descripcion" value="Descripción (opcional)" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="200"
                              placeholder="Breve descripción de la especialidad"
                              class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">{{ old('descripcion', $especialidad->descripcion) }}</textarea>
                    <x-input-error :messages="$errors->get('descripcion')" class="mt-1.5" />
                </div>

                <!-- Duración -->
                <div>
                    <x-input-label for="duracion_min" value="Duración de la consulta (minutos)" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <input id="duracion_min" name="duracion_min" type="number" required min="10" max="120" step="1"
                           value="{{ old('duracion_min', $especialidad->duracion_min ?? 30) }}"
                           class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                    <p class="mt-1.5 text-[11px] text-purple-800/60">Entre 10 y 120 minutos. Define cuántas fichas caben en cada turno.</p>
                    <x-input-error :messages="$errors->get('duracion_min')" class="mt-1.5" />
                </div>

                <!-- Estado -->
                <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer">
                        <input type="hidden" name="estado" value="0">
                        <input type="checkbox" name="estado" value="1"
                               class="rounded-lg border-purple-300 text-purple-700 focus:ring-purple-600 w-4 h-4"
                               @checked(old('estado', $especialidad->estado ?? true) == '1' || old('estado', $especialidad->estado ?? true) === true)>
                        <span class="text-xs sm:text-sm font-medium text-purple-950">Especialidad activa para la oferta médica</span>
                    </label>
                </div>

                <!-- Acciones -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-purple-100/80">
                    <a href="{{ route('especialidades.index') }}"
                       class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-150 cursor-pointer">
                        <span>{{ $esAlta ? 'Registrar especialidad' : 'Guardar cambios' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

{{-- CU08 · Alta y edición de médico con especialidades y cuenta opcional --}}
@php
    $esAlta = ! $medico->exists;
    $seleccionadas = collect(old('especialidades', $medico->exists ? $medico->especialidades->pluck('id_especialidad')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $estadoActual = old('estado', $medico->estado ?? true);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('medicos.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">
                    {{ $esAlta ? 'Nuevo médico' : 'Editar médico' }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $esAlta ? 'Registre al profesional y asigne sus especialidades' : 'Actualice los datos, especialidades y cuenta de acceso' }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" class="bg-white p-6 sm:p-8 shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl space-y-6"
                  action="{{ $esAlta ? route('medicos.store') : route('medicos.update', $medico) }}">
                @csrf
                @unless ($esAlta)
                    @method('PUT')
                @endunless

                @unless ($esAlta)
                    <div class="flex items-center gap-2 text-xs text-purple-800/70">
                        <span class="font-mono font-semibold text-purple-950">{{ $medico->codigo }}</span>
                        <span class="text-purple-200">·</span>
                        <span>El código correlativo no se modifica.</span>
                    </div>
                @endunless

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Matrícula -->
                    <div>
                        <x-input-label for="matricula" value="Matrícula profesional" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="matricula" name="matricula" type="text" required minlength="3" maxlength="30"
                               value="{{ old('matricula', $medico->matricula) }}" autofocus
                               placeholder="ej. MP-12345"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        <x-input-error :messages="$errors->get('matricula')" class="mt-1.5" />
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <x-input-label for="telefono" value="Teléfono (opcional)" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="telefono" name="telefono" type="text" maxlength="20"
                               value="{{ old('telefono', $medico->telefono) }}"
                               placeholder="ej. 70012345"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        <x-input-error :messages="$errors->get('telefono')" class="mt-1.5" />
                    </div>

                    <!-- Nombres -->
                    <div>
                        <x-input-label for="nombres" value="Nombres" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="nombres" name="nombres" type="text" required minlength="2" maxlength="60"
                               value="{{ old('nombres', $medico->nombres) }}"
                               placeholder="ej. Juan Carlos"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        <x-input-error :messages="$errors->get('nombres')" class="mt-1.5" />
                    </div>

                    <!-- Apellidos -->
                    <div>
                        <x-input-label for="apellidos" value="Apellidos" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="apellidos" name="apellidos" type="text" required minlength="2" maxlength="60"
                               value="{{ old('apellidos', $medico->apellidos) }}"
                               placeholder="ej. Pérez Gómez"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        <x-input-error :messages="$errors->get('apellidos')" class="mt-1.5" />
                    </div>
                </div>

                <!-- Correo -->
                <div>
                    <x-input-label for="correo" value="Correo electrónico (opcional)" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <input id="correo" name="correo" type="email" maxlength="100"
                           value="{{ old('correo', $medico->correo) }}"
                           placeholder="ej. medico@clinica.com"
                           class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                    <x-input-error :messages="$errors->get('correo')" class="mt-1.5" />
                </div>

                <!-- Cuenta de acceso -->
                <div>
                    <x-input-label for="id_usuario" value="Cuenta de acceso (opcional)" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <select id="id_usuario" name="id_usuario"
                            class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                        <option value="">Sin cuenta vinculada</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id_usuario }}" @selected((int) old('id_usuario', $medico->id_usuario) === (int) $usuario->id_usuario)>
                                {{ $usuario->username }} · {{ $usuario->correo }}{{ $usuario->estado ? '' : ' (inactiva)' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-purple-800/60">
                        Solo se muestran cuentas activas con rol Médico que aún no están vinculadas a otro profesional.
                        @if (Auth::user()->tienePermiso('usuario.gestionar'))
                            <a href="{{ route('usuarios.create') }}" class="font-semibold text-purple-800 hover:text-purple-950 underline decoration-purple-300 underline-offset-2">Crear una cuenta</a>.
                        @endif
                    </p>
                    <x-input-error :messages="$errors->get('id_usuario')" class="mt-1.5" />
                </div>

                <!-- Especialidades -->
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <x-input-label value="Especialidades" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <span class="text-[11px] text-purple-800/60">Al menos una activa</span>
                    </div>

                    @if ($especialidades->isEmpty())
                        <div class="mt-2 p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs">
                            No hay especialidades registradas.
                            @if (Auth::user()->tienePermiso('especialidad.gestionar'))
                                <a href="{{ route('especialidades.create') }}" class="font-semibold underline underline-offset-2">Cree la primera especialidad</a> antes de registrar médicos.
                            @endif
                        </div>
                    @else
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($especialidades as $especialidad)
                                <label class="flex items-start gap-2.5 p-3 rounded-xl border transition-colors cursor-pointer {{ in_array((int) $especialidad->id_especialidad, $seleccionadas, true) ? 'bg-purple-50/70 border-purple-300' : 'bg-white border-purple-100 hover:bg-purple-50/40' }}">
                                    <input type="checkbox" name="especialidades[]" value="{{ $especialidad->id_especialidad }}"
                                           class="mt-0.5 rounded-lg border-purple-300 text-purple-700 focus:ring-purple-600 w-4 h-4"
                                           @checked(in_array((int) $especialidad->id_especialidad, $seleccionadas, true))>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-purple-950">{{ $especialidad->nombre }}</span>
                                        <span class="block text-[11px] text-gray-500">{{ $especialidad->duracion_min }} min · {{ $especialidad->estado ? 'activa' : 'inactiva' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <x-input-error :messages="$errors->get('especialidades')" class="mt-1.5" />
                    <x-input-error :messages="$errors->get('especialidades.*')" class="mt-1.5" />
                </div>

                <!-- Estado -->
                <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer">
                        <input type="hidden" name="estado" value="0">
                        <input type="checkbox" name="estado" value="1"
                               class="rounded-lg border-purple-300 text-purple-700 focus:ring-purple-600 w-4 h-4"
                               @checked($estadoActual == '1' || $estadoActual === true)>
                        <span class="text-xs sm:text-sm font-medium text-purple-950">Médico activo para la oferta médica</span>
                    </label>
                    <p class="mt-1.5 text-[11px] text-purple-800/60">La baja es lógica: conserva horarios, citas e historial clínico.</p>
                </div>

                <!-- Acciones -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-purple-100/80">
                    <a href="{{ route('medicos.index') }}"
                       class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-150 cursor-pointer">
                        <span>{{ $esAlta ? 'Registrar médico' : 'Guardar cambios' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

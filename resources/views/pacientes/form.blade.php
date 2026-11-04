{{-- CU05 · Alta y edición de paciente; incluye antecedentes clínicos (CU06) --}}
@php($esAlta = ! $paciente->exists)
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('pacientes.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">
                    {{ $esAlta ? 'Nuevo paciente' : 'Editar paciente: '.$paciente->codigo }}
                </h2>
                <p class="text-xs text-purple-800/70">
                    {{ $esAlta ? 'Registre los datos personales y se creará su cuenta de acceso' : 'Actualice los datos personales y clínicos del paciente' }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" class="bg-white p-6 sm:p-8 shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl space-y-8"
                  action="{{ $esAlta ? route('pacientes.store') : route('pacientes.update', $paciente) }}">
                @csrf
                @unless ($esAlta)
                    @method('PUT')
                @endunless

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

                {{-- 1 · Datos personales --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-2 border-b border-purple-100 pb-3">
                        <span class="w-8 h-8 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-sm">1</span>
                        <h3 class="font-bold text-lg text-purple-950">Datos personales</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="ci" value="CI *" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="ci" name="ci" type="text" required maxlength="15" autofocus
                                   value="{{ old('ci', $paciente->ci) }}" placeholder="Ej. 12345678"
                                   class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <x-input-error :messages="$errors->get('ci')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="telefono" value="Teléfono *" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="telefono" name="telefono" type="text" required maxlength="20"
                                   value="{{ old('telefono', $paciente->telefono) }}" placeholder="Ej. 70012345"
                                   class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <x-input-error :messages="$errors->get('telefono')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="nombres" value="Nombres *" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="nombres" name="nombres" type="text" required maxlength="60"
                                   value="{{ old('nombres', $paciente->nombres) }}" placeholder="Nombres completos"
                                   class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <x-input-error :messages="$errors->get('nombres')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="apellidos" value="Apellidos *" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="apellidos" name="apellidos" type="text" required maxlength="60"
                                   value="{{ old('apellidos', $paciente->apellidos) }}" placeholder="Apellidos completos"
                                   class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <x-input-error :messages="$errors->get('apellidos')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="fecha_nacimiento" value="Fecha de nacimiento *" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" required max="{{ now()->toDateString() }}"
                                   value="{{ old('fecha_nacimiento', $paciente->fecha_nacimiento?->format('Y-m-d')) }}"
                                   class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <x-input-error :messages="$errors->get('fecha_nacimiento')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="sexo" value="Sexo *" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <select id="sexo" name="sexo" required
                                    class="mt-1 block w-full py-2.5 px-3 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                                <option value="">Seleccione…</option>
                                <option value="M" @selected(old('sexo', $paciente->sexo) === 'M')>Masculino</option>
                                <option value="F" @selected(old('sexo', $paciente->sexo) === 'F')>Femenino</option>
                                <option value="O" @selected(old('sexo', $paciente->sexo) === 'O')>Otro</option>
                            </select>
                            <x-input-error :messages="$errors->get('sexo')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="correo" value="Correo electrónico" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="correo" name="correo" type="email" maxlength="100"
                                   value="{{ old('correo', $paciente->correo) }}" placeholder="paciente@correo.com"
                                   class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <x-input-error :messages="$errors->get('correo')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="direccion" value="Dirección" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="direccion" name="direccion" type="text" maxlength="150"
                                   value="{{ old('direccion', $paciente->direccion) }}" placeholder="Dirección domiciliaria"
                                   class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <x-input-error :messages="$errors->get('direccion')" class="mt-1.5" />
                        </div>
                    </div>

                    <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                            <input type="hidden" name="recibe_recordatorios" value="0">
                            <input type="checkbox" name="recibe_recordatorios" value="1"
                                   class="rounded-lg border-purple-300 text-purple-700 focus:ring-purple-600 w-4 h-4"
                                   @checked(old('recibe_recordatorios', $paciente->recibe_recordatorios ?? true) == '1')>
                            <span class="text-xs sm:text-sm font-medium text-purple-950">Recibe recordatorios por correo</span>
                        </label>
                    </div>
                </section>

                {{-- 2 · Cuenta de acceso --}}
                <section class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-purple-100 pb-3">
                        <span class="w-8 h-8 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-sm">2</span>
                        <h3 class="font-bold text-lg text-purple-950">Cuenta de acceso</h3>
                    </div>

                    @if ($esAlta)
                        <div class="p-4 bg-purple-50/60 border border-purple-200/80 rounded-xl text-sm text-purple-950">
                            <p class="font-semibold">Se creará automáticamente una cuenta de paciente.</p>
                            <ul class="mt-2 space-y-1 text-xs text-purple-900/80 list-disc list-inside">
                                <li>Usuario de acceso: el CI ingresado.</li>
                                <li>Contraseña temporal: se mostrará una sola vez al guardar.</li>
                                <li>El paciente deberá cambiarla en su primer inicio de sesión.</li>
                            </ul>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <x-input-label value="Usuario de acceso" class="text-xs font-semibold uppercase text-purple-950/80" />
                                <p class="mt-1 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono text-gray-700">
                                    {{ $paciente->usuario?->username ?? 'Sin cuenta asociada' }}
                                </p>
                            </div>
                            <div>
                                <x-input-label value="Estado de la cuenta" class="text-xs font-semibold uppercase text-purple-950/80" />
                                <p class="mt-1 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700">
                                    {{ $paciente->usuario?->estado ? 'Activa' : 'Inactiva' }}
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-purple-800/70">Si cambia el CI, también se actualiza el usuario de acceso.</p>
                    @endif
                </section>

                {{-- 3 · Antecedentes clínicos --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-2 border-b border-purple-100 pb-3">
                        <span class="w-8 h-8 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-sm">3</span>
                        <h3 class="font-bold text-lg text-purple-950">
                            Antecedentes clínicos
                            <span class="text-xs font-medium text-purple-700">(CU06)</span>
                        </h3>
                    </div>
                    <p class="text-xs text-purple-800/70 -mt-2">
                        Opcional. Puede completarlos ahora o después desde la ficha del paciente.
                    </p>
                    @include('pacientes._antecedente-fields')
                </section>

                {{-- Acciones --}}
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-purple-100/80">
                    <a href="{{ route('pacientes.index') }}"
                       class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-150 cursor-pointer">
                        <span>{{ $esAlta ? 'Registrar Paciente' : 'Guardar Cambios' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

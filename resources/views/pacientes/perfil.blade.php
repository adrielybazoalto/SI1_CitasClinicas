{{-- CU04 · Gestionar perfil del paciente: contacto, recordatorios, contraseña y próximas citas --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-white border border-purple-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20';
    $etiqueta = 'text-xs font-semibold text-gray-600';
    $ant = $paciente->antecedente;
    $usuario = Auth::user();
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-encabezado-modulo />
        <h2 class="text-3xl font-bold text-gray-900 leading-tight">Mi perfil</h2>
        <p class="text-sm text-gray-500 mt-0.5">{{ $paciente->codigo }} · actualice sus datos de contacto y preferencias</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')
            @if (session('status') === 'password-updated')
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl text-sm">Contraseña actualizada.</div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 space-y-5">
                    {{-- Datos de solo lectura --}}
                    <section class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100">
                        <h3 class="font-bold text-gray-900">Datos personales</h3>
                        <dl class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            <div><dt class="text-xs text-gray-500">Nombre</dt><dd class="font-semibold">{{ $paciente->nombres }} {{ $paciente->apellidos }}</dd></div>
                            <div><dt class="text-xs text-gray-500">CI</dt><dd class="font-semibold">{{ $paciente->ci }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Fecha de nacimiento</dt><dd class="font-semibold">{{ $paciente->fecha_nacimiento?->format('d/m/Y') }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Tipo de sangre</dt><dd class="font-semibold">{{ $ant?->tipo_sangre ?? '—' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Alergias</dt><dd class="font-semibold">{{ $ant?->alergias ?? '—' }}</dd></div>
                        </dl>
                        <p class="mt-3 text-xs text-gray-500">Para corregir su CI, nombres o antecedentes clínicos, acuda a recepción.</p>
                    </section>

                    {{-- Datos editables --}}
                    <form method="POST" action="{{ route('perfil.update') }}" id="form-perfil" novalidate class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 space-y-4">
                        @csrf
                        @method('PUT')
                        <h3 class="font-bold text-gray-900">Contacto</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="telefono" class="{{ $etiqueta }}">Teléfono *</label>
                                <input id="telefono" name="telefono" type="tel" required inputmode="numeric" maxlength="15" value="{{ old('telefono', $paciente->telefono) }}" class="{{ $claseCampo }}">
                                <x-input-error :messages="$errors->get('telefono')" class="mt-1" />
                            </div>
                            <div>
                                <label for="correo" class="{{ $etiqueta }}">Correo</label>
                                <input id="correo" name="correo" type="email" maxlength="100" value="{{ old('correo', $paciente->correo) }}" class="{{ $claseCampo }}">
                                <x-input-error :messages="$errors->get('correo')" class="mt-1" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="direccion" class="{{ $etiqueta }}">Dirección</label>
                                <input id="direccion" name="direccion" type="text" maxlength="150" value="{{ old('direccion', $paciente->direccion) }}" class="{{ $claseCampo }}">
                            </div>
                        </div>

                        <h3 class="font-bold text-gray-900 pt-2">Contacto de emergencia</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="contacto_nombre" class="{{ $etiqueta }}">Nombre</label>
                                <input id="contacto_nombre" name="contacto_nombre" type="text" maxlength="100" value="{{ old('contacto_nombre', $ant?->contacto_nombre) }}" class="{{ $claseCampo }}">
                            </div>
                            <div>
                                <label for="contacto_parentesco" class="{{ $etiqueta }}">Parentesco</label>
                                <input id="contacto_parentesco" name="contacto_parentesco" type="text" maxlength="30" value="{{ old('contacto_parentesco', $ant?->contacto_parentesco) }}" class="{{ $claseCampo }}">
                            </div>
                            <div>
                                <label for="contacto_telefono" class="{{ $etiqueta }}">Teléfono</label>
                                <input id="contacto_telefono" name="contacto_telefono" type="tel" inputmode="numeric" maxlength="15" value="{{ old('contacto_telefono', $ant?->contacto_telefono) }}" class="{{ $claseCampo }}">
                                <x-input-error :messages="$errors->get('contacto_telefono')" class="mt-1" />
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="recibe_recordatorios" value="1" @checked(old('recibe_recordatorios', $paciente->recibe_recordatorios)) class="rounded border-purple-300 text-purple-800 focus:ring-purple-500">
                            Recibir recordatorios de mis citas por correo (24 h antes)
                        </label>

                        <p id="error-perfil" class="hidden text-sm text-rose-600"></p>
                        <button type="submit" class="px-5 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold cursor-pointer">Guardar cambios</button>
                    </form>

                    {{-- Contraseña: verifica la actual y pide la nueva dos veces --}}
                    <section class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100">
                        @include('profile.partials.update-password-form')
                    </section>
                </div>

                {{-- Mis próximas citas --}}
                <aside class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 h-fit">
                    <h3 class="font-bold text-gray-900">Mis próximas citas</h3>
                    <ul class="mt-3 space-y-3">
                        @forelse ($proximas as $cita)
                            <li class="p-3 rounded-xl border border-purple-100">
                                <p class="text-sm font-semibold text-gray-900">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->hora_inicio_corta }}</p>
                                <p class="text-xs text-gray-600">{{ ucfirst($cita->especialidad?->nombre) }} · {{ $cita->medico?->nombre_completo }}</p>
                                <p class="text-xs text-gray-500">{{ $cita->codigo }} · {{ $cita->estado }} · pago {{ mb_strtolower($cita->estado_pago) }}</p>
                                @if ($cita->estado === 'Programada')
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($usuario->tienePermiso('cita.reprogramar'))
                                            <a href="{{ route('citas.reprogramar', $cita) }}" class="px-2.5 py-1 text-xs font-semibold bg-purple-800 hover:bg-purple-900 text-white rounded-lg">Reprogramar</a>
                                        @endif
                                        @if ($usuario->tienePermiso('cita.cancelar'))
                                            <a href="{{ route('citas.show', $cita) }}#cancelar" class="px-2.5 py-1 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-lg">Cancelar</a>
                                        @endif
                                        @if ($cita->estado_pago === 'Pendiente' && Route::has('pagos.show') && $usuario->tienePermiso('pago.en_linea'))
                                            <a href="{{ route('pagos.show', $cita) }}" class="px-2.5 py-1 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">Pagar</a>
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">No tiene citas próximas.</li>
                        @endforelse
                    </ul>
                    @if ($usuario->tienePermiso('cita.programar'))
                        <a href="{{ route('fichas.index') }}" class="mt-4 block text-center px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold">Programar una cita</a>
                    @endif
                </aside>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('form-perfil').addEventListener('submit', function (e) {
            const tel = document.getElementById('telefono').value.trim();
            const cTel = document.getElementById('contacto_telefono').value.trim();
            const correo = document.getElementById('correo').value.trim();
            let mensaje = '';
            if (!/^[0-9]{7,15}$/.test(tel)) mensaje = 'El teléfono debe tener entre 7 y 15 dígitos.';
            else if (cTel && !/^[0-9]{7,15}$/.test(cTel)) mensaje = 'El teléfono del contacto debe tener entre 7 y 15 dígitos.';
            else if (correo && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(correo)) mensaje = 'El correo no es válido.';
            if (mensaje) {
                e.preventDefault();
                const error = document.getElementById('error-perfil');
                error.textContent = mensaje;
                error.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>

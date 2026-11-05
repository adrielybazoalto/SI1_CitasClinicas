{{-- CU21 · Registrar atención médica (panel clínico + formulario) --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150';
    $etiqueta = 'text-xs font-semibold uppercase text-purple-950/80';
    $alergias = trim((string) $antecedente?->alergias);
    $tieneAlergias = $alergias !== '' && ! preg_match('/^(ninguna|no|niega)/i', $alergias);
    $volver = route('agenda.index', ['fecha' => $cita->fecha->toDateString(), 'accion' => 'atender']);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ $volver }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors" title="Volver a la agenda">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Registrar atención</h2>
                <p class="text-sm text-gray-500">
                    Cita {{ $cita->codigo }} · {{ $cita->fecha->format('d/m/Y') }} {{ $cita->hora_inicio_corta }} · {{ ucfirst($cita->especialidad?->nombre) }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Panel lateral: datos clínicos del paciente -->
                <aside class="space-y-4 lg:order-2">
                    <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs">
                        <p class="text-[11px] font-bold uppercase text-purple-900/70">Paciente</p>
                        <p class="text-lg font-bold text-purple-950 mt-1">{{ $paciente->apellidos }}, {{ $paciente->nombres }}</p>
                        <p class="text-xs text-gray-500">{{ $paciente->codigo }} · CI {{ $paciente->ci }}</p>
                        <dl class="grid grid-cols-2 gap-2 mt-3 text-sm">
                            <div><dt class="text-[11px] text-gray-400">Edad</dt><dd class="font-semibold text-gray-800">{{ $paciente->fecha_nacimiento?->age }} años</dd></div>
                            <div><dt class="text-[11px] text-gray-400">Sexo</dt><dd class="font-semibold text-gray-800">{{ ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'][$paciente->sexo] ?? '—' }}</dd></div>
                            <div><dt class="text-[11px] text-gray-400">Tipo de sangre</dt><dd class="font-semibold text-gray-800">{{ $antecedente?->tipo_sangre ?? '—' }}</dd></div>
                            <div><dt class="text-[11px] text-gray-400">Motivo</dt><dd class="font-semibold text-gray-800">{{ $cita->motivo ?? '—' }}</dd></div>
                        </dl>
                    </div>

                    @if ($tieneAlergias)
                        <div class="alert-danger p-4 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-900" role="alert">
                            <p class="text-xs font-bold uppercase tracking-wider">⚠ Alergias</p>
                            <p class="text-base font-bold mt-1">{{ $alergias }}</p>
                        </div>
                    @endif

                    <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-bold uppercase text-purple-900/70">Antecedentes</p>
                            @if (Auth::user()->tienePermiso('antecedente.gestionar'))
                                <a href="{{ route('pacientes.antecedentes', ['paciente' => $paciente, 'volver_cita' => $cita->id_cita]) }}"
                                   class="text-xs font-semibold text-purple-800 hover:text-purple-950 hover:underline">Actualizar antecedentes</a>
                            @endif
                        </div>
                        @if ($antecedente)
                            <dl class="mt-3 space-y-2 text-sm">
                                @if (! $tieneAlergias)
                                    <div><dt class="text-[11px] text-gray-400">Alergias</dt><dd class="text-gray-800">{{ $alergias ?: 'Ninguna registrada' }}</dd></div>
                                @endif
                                <div><dt class="text-[11px] text-gray-400">Enfermedades crónicas</dt><dd class="text-gray-800">{{ $antecedente->enfermedades_cronicas ?? '—' }}</dd></div>
                                <div><dt class="text-[11px] text-gray-400">Medicamentos</dt><dd class="text-gray-800">{{ $antecedente->medicamentos ?? '—' }}</dd></div>
                                <div><dt class="text-[11px] text-gray-400">Otros antecedentes</dt><dd class="text-gray-800">{{ $antecedente->antecedentes ?? '—' }}</dd></div>
                                <div><dt class="text-[11px] text-gray-400">Contacto de emergencia</dt><dd class="text-gray-800">{{ $antecedente->contacto_nombre ?? '—' }}{{ $antecedente->contacto_telefono ? ' · '.$antecedente->contacto_telefono : '' }}</dd></div>
                            </dl>
                        @else
                            <p class="mt-3 text-sm text-gray-500">El paciente aún no tiene antecedentes registrados.</p>
                        @endif
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs">
                        <p class="text-[11px] font-bold uppercase text-purple-900/70">Últimas atenciones</p>
                        @forelse ($ultimas as $previa)
                            <div class="mt-3 pt-3 border-t border-purple-50 first:border-0 first:pt-0">
                                <p class="text-xs text-gray-500">{{ $previa->fecha->format('d/m/Y') }} · {{ ucfirst($previa->especialidad?->nombre) }} · {{ $previa->medico?->nombre_completo }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ $previa->atencion?->diagnostico ?? 'Sin registro' }}</p>
                                @if ($previa->atencion?->tratamiento)
                                    <p class="text-xs text-gray-600">{{ $previa->atencion->tratamiento }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="mt-3 text-sm text-gray-500">Primera atención del paciente en la clínica.</p>
                        @endforelse
                    </div>
                </aside>

                <!-- Formulario de la atención -->
                <form method="POST" action="{{ route('atencion.store', $cita) }}" id="form-atencion" novalidate
                      class="lg:col-span-2 lg:order-1 bg-white p-6 sm:p-8 shadow-xs border border-purple-100 rounded-2xl space-y-5">
                    @csrf

                    <x-input-error :messages="$errors->get('cita')" />

                    <div>
                        <x-input-label for="diagnostico" value="Diagnóstico *" class="{{ $etiqueta }}" />
                        <textarea id="diagnostico" name="diagnostico" rows="3" required minlength="3" maxlength="2000" class="{{ $claseCampo }}">{{ old('diagnostico') }}</textarea>
                        <x-input-error :messages="$errors->get('diagnostico')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="tratamiento" value="Tratamiento" class="{{ $etiqueta }}" />
                        <textarea id="tratamiento" name="tratamiento" rows="3" maxlength="2000" class="{{ $claseCampo }}">{{ old('tratamiento') }}</textarea>
                        <x-input-error :messages="$errors->get('tratamiento')" class="mt-1.5" />
                    </div>

                    <fieldset class="border-t border-purple-100 pt-4">
                        <legend class="text-sm font-bold text-purple-950 pr-2">Signos vitales <span class="font-normal text-gray-400">(opcionales)</span></legend>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-2">
                            <div>
                                <x-input-label for="peso_kg" value="Peso (kg)" class="{{ $etiqueta }}" />
                                <input id="peso_kg" name="peso_kg" type="number" step="0.1" min="0.5" max="400" value="{{ old('peso_kg') }}" class="{{ $claseCampo }}">
                                <x-input-error :messages="$errors->get('peso_kg')" class="mt-1.5" />
                            </div>
                            <div>
                                <x-input-label for="talla_cm" value="Talla (cm)" class="{{ $etiqueta }}" />
                                <input id="talla_cm" name="talla_cm" type="number" step="0.1" min="20" max="250" value="{{ old('talla_cm') }}" class="{{ $claseCampo }}">
                                <x-input-error :messages="$errors->get('talla_cm')" class="mt-1.5" />
                            </div>
                            <div>
                                <x-input-label for="presion_arterial" value="Presión" class="{{ $etiqueta }}" />
                                <input id="presion_arterial" name="presion_arterial" type="text" placeholder="120/80" pattern="\d{2,3}/\d{2,3}" maxlength="7" value="{{ old('presion_arterial') }}" class="{{ $claseCampo }}">
                                <x-input-error :messages="$errors->get('presion_arterial')" class="mt-1.5" />
                            </div>
                            <div>
                                <x-input-label for="temperatura_c" value="Temp. (°C)" class="{{ $etiqueta }}" />
                                <input id="temperatura_c" name="temperatura_c" type="number" step="0.1" min="30" max="45" value="{{ old('temperatura_c') }}" class="{{ $claseCampo }}">
                                <x-input-error :messages="$errors->get('temperatura_c')" class="mt-1.5" />
                            </div>
                        </div>
                    </fieldset>

                    <div>
                        <x-input-label for="observaciones" value="Observaciones" class="{{ $etiqueta }}" />
                        <textarea id="observaciones" name="observaciones" rows="2" maxlength="1000" class="{{ $claseCampo }}">{{ old('observaciones') }}</textarea>
                        <x-input-error :messages="$errors->get('observaciones')" class="mt-1.5" />
                    </div>

                    <p id="error-atencion" class="hidden text-sm text-rose-700 font-medium"></p>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-purple-100/80">
                        <a href="{{ $volver }}" class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">Cancelar</a>
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm transition-colors cursor-pointer">
                            Guardar y marcar como Atendida
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Validación en el cliente (la del servidor es la definitiva).
        document.getElementById('form-atencion').addEventListener('submit', function (e) {
            const v = (id) => document.getElementById(id).value.trim().replace(',', '.');
            const rango = (id, min, max, texto) => {
                const valor = v(id);
                return valor !== '' && (isNaN(valor) || valor < min || valor > max) ? texto : '';
            };
            const errores = [
                v('diagnostico').length < 3 ? 'El diagnóstico es obligatorio (mínimo 3 caracteres).' : '',
                rango('peso_kg', 0.5, 400, 'El peso debe estar entre 0,5 y 400 kg.'),
                rango('talla_cm', 20, 250, 'La talla debe estar entre 20 y 250 cm.'),
                rango('temperatura_c', 30, 45, 'La temperatura debe estar entre 30 y 45 °C.'),
                v('presion_arterial') !== '' && !/^\d{2,3}\/\d{2,3}$/.test(v('presion_arterial')) ? 'La presión debe tener el formato 120/80.' : '',
            ].filter(Boolean);

            const caja = document.getElementById('error-atencion');
            if (errores.length) {
                e.preventDefault();
                caja.textContent = errores.join(' ');
                caja.classList.remove('hidden');
                caja.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</x-app-layout>

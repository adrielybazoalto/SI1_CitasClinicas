{{-- CU09 · Alta y edición de un turno semanal (médico + especialidad) --}}
@php
    $esAlta = ! $horario->exists;
    $medicoSeleccionado = old('id_medico', $horario->id_medico);
    $especialidadSeleccionada = old('id_especialidad', $horario->id_especialidad);
    $diaSeleccionado = old('dia_semana', $horario->dia_semana ?? 1);
    $estadoActual = old('estado', $horario->estado ?? true);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('horarios.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">
                    {{ $esAlta ? 'Nuevo turno' : 'Editar turno' }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $esAlta ? 'Defina el día, la franja horaria y la duración de la consulta' : 'Ajuste la franja o la duración del turno' }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" class="bg-white p-6 sm:p-8 shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl space-y-6"
                  action="{{ $esAlta ? route('horarios.store') : route('horarios.update', $horario) }}">
                @csrf
                @unless ($esAlta)
                    @method('PUT')
                @endunless

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Médico -->
                    <div>
                        <x-input-label for="id_medico" value="Médico" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="id_medico" name="id_medico" required
                                class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                            <option value="">Seleccione un médico</option>
                            @foreach ($medicos as $medico)
                                <option value="{{ $medico->id_medico }}" @selected((int) $medicoSeleccionado === (int) $medico->id_medico)>
                                    {{ $medico->nombre_completo }}{{ $medico->estado ? '' : ' (inactivo)' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_medico')" class="mt-1.5" />
                    </div>

                    <!-- Especialidad -->
                    <div>
                        <x-input-label for="id_especialidad" value="Especialidad" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="id_especialidad" name="id_especialidad" required
                                class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                            <option value="">Seleccione una especialidad</option>
                            @foreach ($especialidades as $especialidad)
                                <option value="{{ $especialidad->id_especialidad }}"
                                        data-medicos="{{ implode(',', $mapa[$especialidad->id_especialidad] ?? []) }}"
                                        @selected((int) $especialidadSeleccionada === (int) $especialidad->id_especialidad)>
                                    {{ $especialidad->nombre }}{{ $especialidad->estado ? '' : ' (inactiva)' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-[11px] text-purple-800/60">Solo se habilitan las especialidades que ofrece el médico seleccionado.</p>
                        <x-input-error :messages="$errors->get('id_especialidad')" class="mt-1.5" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <!-- Día -->
                    <div>
                        <x-input-label for="dia_semana" value="Día" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="dia_semana" name="dia_semana" required
                                class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                            @foreach (\App\Models\Horario::DIAS as $numero => $nombre)
                                <option value="{{ $numero }}" @selected((int) $diaSeleccionado === (int) $numero)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('dia_semana')" class="mt-1.5" />
                    </div>

                    <!-- Hora inicio -->
                    <div>
                        <x-input-label for="hora_inicio" value="Hora de inicio" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="hora_inicio" name="hora_inicio" type="time" required
                               value="{{ old('hora_inicio', $horario->hora_inicio_corta) }}"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        <x-input-error :messages="$errors->get('hora_inicio')" class="mt-1.5" />
                    </div>

                    <!-- Hora fin -->
                    <div>
                        <x-input-label for="hora_fin" value="Hora de fin" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="hora_fin" name="hora_fin" type="time" required
                               value="{{ old('hora_fin', $horario->hora_fin_corta) }}"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        <x-input-error :messages="$errors->get('hora_fin')" class="mt-1.5" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Duración -->
                    <div>
                        <x-input-label for="duracion_min" value="Duración de la consulta (minutos)" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="duracion_min" name="duracion_min" type="number" required min="10" max="120" step="1"
                               value="{{ old('duracion_min', $horario->duracion_min ?? 30) }}"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        <p class="mt-1.5 text-[11px] text-purple-800/60">Debe dividir exactamente el turno (sin sobrantes).</p>
                        <x-input-error :messages="$errors->get('duracion_min')" class="mt-1.5" />
                    </div>

                    <!-- Consultorio -->
                    <div>
                        <x-input-label for="consultorio" value="Consultorio (opcional)" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="consultorio" name="consultorio" type="text" maxlength="20"
                               value="{{ old('consultorio', $horario->consultorio) }}"
                               placeholder="ej. 2B"
                               class="mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                        <x-input-error :messages="$errors->get('consultorio')" class="mt-1.5" />
                    </div>
                </div>

                <!-- Vista previa de capacidad -->
                <div class="p-4 bg-gradient-to-r from-purple-50 to-purple-50 border border-purple-100 rounded-xl">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                        </svg>
                        <p class="text-xs font-semibold text-purple-950">Capacidad estimada del turno</p>
                    </div>
                    <p id="capacidad-preview" class="mt-1.5 text-sm font-semibold text-purple-900">Complete las horas y la duración.</p>
                    <p class="mt-1 text-[11px] text-purple-800/60">El reparto se calcula con los porcentajes configurados en el sistema ({{ (int) config('clinica.porcentaje_linea', 70) }}/{{ (int) config('clinica.porcentaje_mostrador', 30) }}).</p>
                </div>

                <!-- Estado -->
                <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer">
                        <input type="hidden" name="estado" value="0">
                        <input type="checkbox" name="estado" value="1"
                               class="rounded-lg border-purple-300 text-purple-700 focus:ring-purple-600 w-4 h-4"
                               @checked($estadoActual == '1' || $estadoActual === true)>
                        <span class="text-xs sm:text-sm font-medium text-purple-950">Turno activo para la reserva de citas</span>
                    </label>
                    <p class="mt-1.5 text-[11px] text-purple-800/60">La baja es lógica: el turno conserva su historial de citas.</p>
                </div>

                <!-- Acciones -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-purple-100/80">
                    <a href="{{ route('horarios.index') }}"
                       class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-150 cursor-pointer">
                        <span>{{ $esAlta ? 'Registrar turno' : 'Guardar cambios' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const mapa = @json($mapa);
                const selectMedico = document.getElementById('id_medico');
                const selectEspecialidad = document.getElementById('id_especialidad');

                const filtrarEspecialidades = () => {
                    const idMedico = selectMedico.value;

                    Array.from(selectEspecialidad.options).forEach((opcion) => {
                        if (!opcion.value) {
                            return;
                        }

                        const medicos = (mapa[opcion.value] || []).map(String);
                        const disponible = idMedico === '' || medicos.includes(String(idMedico));

                        opcion.hidden = !disponible;
                        opcion.disabled = !disponible;
                        opcion.style.display = disponible ? '' : 'none';
                    });

                    const seleccionada = selectEspecialidad.options[selectEspecialidad.selectedIndex];
                    if (seleccionada && seleccionada.value && seleccionada.hidden) {
                        selectEspecialidad.value = '';
                    }
                };

                const aMinutos = (hora) => {
                    if (!hora || !hora.includes(':')) {
                        return null;
                    }

                    const [h, m] = hora.split(':').map(Number);
                    return (h * 60) + m;
                };

                const actualizarCapacidad = () => {
                    const preview = document.getElementById('capacidad-preview');
                    const inicio = aMinutos(document.getElementById('hora_inicio').value);
                    const fin = aMinutos(document.getElementById('hora_fin').value);
                    const duracion = Number(document.getElementById('duracion_min').value);

                    if (inicio === null || fin === null || !duracion || duracion < 10) {
                        preview.textContent = 'Complete las horas y la duración.';
                        preview.className = 'mt-1.5 text-sm font-semibold text-purple-900';
                        return;
                    }

                    const minutos = fin - inicio;

                    if (minutos <= 0) {
                        preview.textContent = 'La hora de fin debe ser mayor que la hora de inicio.';
                        preview.className = 'mt-1.5 text-sm font-semibold text-rose-700';
                        return;
                    }

                    if (minutos % duracion !== 0) {
                        preview.textContent = 'La duración no divide exactamente el turno: quedarían ' + (minutos % duracion) + ' min sin asignar.';
                        preview.className = 'mt-1.5 text-sm font-semibold text-amber-700';
                        return;
                    }

                    const capacidad = Math.floor(minutos / duracion);
                    const linea = Math.round(capacidad * {{ (int) config('clinica.porcentaje_linea', 70) }} / 100);
                    const mostrador = capacidad - linea;

                    preview.textContent = capacidad + ' fichas · ' + linea + ' en línea / ' + mostrador + ' en mostrador';
                    preview.className = 'mt-1.5 text-sm font-semibold text-purple-900';
                };

                selectMedico.addEventListener('change', filtrarEspecialidades);
                ['hora_inicio', 'hora_fin', 'duracion_min'].forEach((id) => {
                    document.getElementById(id).addEventListener('input', actualizarCapacidad);
                });

                filtrarEspecialidades();
                actualizarCapacidad();
            });
        </script>
    @endpush
</x-app-layout>

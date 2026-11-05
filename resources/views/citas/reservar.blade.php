{{-- CU12 · Consultar fichas · CU13 · Programar cita · CU15 · Reprogramar cita --}}
@php
    $reprogramando = $cita !== null;
    $esPaciente = $canal === \App\Services\DisponibilidadService::CANAL_LINEA;
    $especialidadInicial = old('id_especialidad', $cita?->id_especialidad);
    $medicoInicial = old('id_medico', $cita?->id_medico);
    $fechaInicial = old('fecha', '');
    $pacienteSeleccionado = old('id_paciente', $pacienteInicial?->id_paciente);
    $nombrePaciente = $pacienteInicial
        ? "{$pacienteInicial->apellidos}, {$pacienteInicial->nombres} · CI {$pacienteInicial->ci}"
        : old('paciente_texto', '');
    $diasVentana = (int) config('clinica.reserva_abre_dias_antes', 7);
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ $reprogramando ? route('citas.show', $cita) : route('citas.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">
                    {{ $reprogramando ? "Reprogramar cita {$cita->codigo}" : ($puedeReservar ? 'Reservar ficha' : 'Fichas disponibles') }}
                </h2>
                <p class="text-sm text-gray-500">
                    @if ($reprogramando)
                        Elija la nueva ficha de {{ $cita->especialidad?->nombre }} · se conserva el código de la cita
                    @else
                        Especialidad → médico → fecha → ficha libre
                        · canal {{ $esPaciente ? 'en línea' : 'mostrador' }}
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            @if ($reprogramando)
                <div class="p-4 bg-purple-50 border border-purple-200 text-purple-900 rounded-xl text-sm">
                    Cita actual: <strong>{{ $cita->fecha->format('d/m/Y') }} a las {{ $cita->hora_inicio_corta }}</strong>
                    · ficha {{ $cita->nro_cita }} · {{ $cita->medico?->nombre_completo }}
                    · reprogramada {{ $cita->veces_reprogramada }} vez/veces
                    (máximo {{ (int) config('clinica.reprogramacion_max_veces', 1) }}, con {{ (int) config('clinica.reprogramacion_horas_antes', 24) }} h de anticipación).
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                <!-- Paso 1: criterios de búsqueda -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs space-y-4">
                        <p class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">1 · Buscar fichas</p>

                        <div>
                            <x-input-label for="sel_especialidad" value="Especialidad" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <select id="sel_especialidad" class="{{ $claseCampo }}" @disabled($reprogramando)>
                                <option value="">Seleccione una especialidad</option>
                                @foreach ($especialidades as $especialidad)
                                    <option value="{{ $especialidad->id_especialidad }}" @selected((int) $especialidadInicial === (int) $especialidad->id_especialidad)>
                                        {{ ucfirst($especialidad->nombre) }} · {{ $especialidad->duracion_min }} min
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="sel_medico" value="Médico" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <select id="sel_medico" class="{{ $claseCampo }}">
                                <option value="">Seleccione un médico</option>
                                @foreach ($medicos as $medico)
                                    <option value="{{ $medico->id_medico }}" @selected((int) $medicoInicial === (int) $medico->id_medico)>
                                        {{ $medico->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                            <p id="dias-atencion" class="mt-1.5 text-[11px] text-purple-800/60"></p>
                        </div>

                        <div>
                            <x-input-label for="sel_fecha" value="Fecha" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <input id="sel_fecha" type="date" class="{{ $claseCampo }}"
                                   value="{{ $fechaInicial }}"
                                   min="{{ now()->toDateString() }}"
                                   @if ($esPaciente) max="{{ now()->addDays($diasVentana)->toDateString() }}" @endif />
                            @if ($esPaciente)
                                <p class="mt-1.5 text-[11px] text-purple-800/60">La reserva en línea abre {{ $diasVentana }} días antes y cierra {{ (int) config('clinica.reserva_cierra_horas_antes', 1) }} h antes del turno.</p>
                            @endif
                        </div>

                        <button type="button" id="btn-buscar"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Ver fichas
                        </button>
                        <p id="error-busqueda" class="hidden text-sm text-red-600"></p>
                    </div>

                    <!-- Leyenda -->
                    <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs">
                        <p class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider mb-3">Leyenda</p>
                        <ul class="space-y-2 text-xs text-gray-600">
                            <li class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></span> Libre</li>
                            <li class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-purple-700"></span> Seleccionada</li>
                            <li class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-rose-100 border border-rose-200"></span> Ocupada</li>
                            <li class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-gray-100 border border-gray-200"></span> Pasada, bloqueada o fuera de la ventana</li>
                            @if ($esPaciente)
                                <li class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-purple-50 border border-purple-200"></span> Reservada para mostrador</li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Paso 2: grilla de fichas y paso 3: confirmar -->
                <div class="lg:col-span-8 space-y-5">
                    <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">2 · Elegir ficha</p>
                            <p id="resumen-fichas" class="text-xs font-semibold text-purple-900"></p>
                        </div>
                        <div id="contenedor-fichas" class="text-sm text-gray-500">
                            Seleccione especialidad, médico y fecha para ver las fichas.
                        </div>
                    </div>

                    @if ($puedeReservar)
                        <form id="form-reserva" method="POST"
                              action="{{ $reprogramando ? route('citas.reprogramar.update', $cita) : route('citas.store') }}"
                              class="bg-white p-5 sm:p-6 rounded-2xl border border-purple-100 shadow-xs space-y-5">
                            @csrf
                            @if ($reprogramando)
                                @method('PUT')
                            @endif

                            <p class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">3 · {{ $reprogramando ? 'Confirmar reprogramación' : 'Confirmar reserva' }}</p>

                            <input type="hidden" name="id_especialidad" id="id_especialidad" value="{{ $especialidadInicial }}">
                            <input type="hidden" name="id_medico" id="id_medico" value="{{ $medicoInicial }}">
                            <input type="hidden" name="fecha" id="fecha" value="{{ old('fecha') }}">
                            <input type="hidden" name="id_horario" id="id_horario" value="{{ old('id_horario') }}">
                            <input type="hidden" name="nro_cita" id="nro_cita" value="{{ old('nro_cita') }}">
                            @unless ($reprogramando || $esPaciente)
                                <input type="hidden" name="sobrecupo" id="sobrecupo" value="{{ old('sobrecupo') ? '1' : '0' }}">
                            @endunless

                            <div id="ficha-elegida" class="p-4 rounded-xl border border-dashed border-purple-200 bg-purple-50/30 text-sm text-purple-900">
                                Aún no eligió una ficha.
                            </div>
                            <x-input-error :messages="array_merge($errors->get('nro_cita'), $errors->get('id_horario'), $errors->get('fecha'), $errors->get('id_medico'), $errors->get('sobrecupo'))" />

                            @if (! $reprogramando && ! $esPaciente)
                                <!-- Paciente (autocompletado por CI, nombre o código) -->
                                <div class="relative">
                                    <x-input-label for="paciente_texto" value="Paciente" class="text-xs font-semibold uppercase text-purple-950/80" />
                                    <input type="hidden" name="id_paciente" id="id_paciente" value="{{ $pacienteSeleccionado }}">
                                    <input id="paciente_texto" name="paciente_texto" type="text" autocomplete="off"
                                           value="{{ $nombrePaciente }}" placeholder="Buscar por CI, apellido, nombre o código"
                                           class="{{ $claseCampo }}" />
                                    <ul id="sugerencias" class="hidden absolute z-20 mt-1 w-full bg-white border border-purple-100 rounded-xl shadow-lg max-h-60 overflow-y-auto"></ul>
                                    <p class="mt-1.5 text-[11px] text-purple-800/60">
                                        ¿No está registrado? <a href="{{ route('pacientes.create') }}" class="font-semibold underline">Registrar paciente</a>
                                    </p>
                                    <x-input-error :messages="$errors->get('id_paciente')" class="mt-1.5" />
                                </div>
                            @elseif ($esPaciente && ! $reprogramando)
                                <p class="text-sm text-gray-600">La cita se reservará a nombre de <strong>{{ $pacienteActual?->nombres }} {{ $pacienteActual?->apellidos }}</strong>.</p>
                            @endif

                            @unless ($reprogramando)
                                <div>
                                    <x-input-label for="motivo" value="Motivo de la consulta (opcional)" class="text-xs font-semibold uppercase text-purple-950/80" />
                                    <textarea id="motivo" name="motivo" rows="2" maxlength="200" class="{{ $claseCampo }}">{{ old('motivo') }}</textarea>
                                    <x-input-error :messages="$errors->get('motivo')" class="mt-1.5" />
                                </div>
                            @endunless

                            @if ($reprogramando && ! $esPaciente)
                                <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50 space-y-3">
                                    <label class="flex items-center gap-2 text-sm text-amber-900 font-semibold">
                                        <input type="checkbox" name="forzar" id="forzar" value="1" @checked(old('forzar')) class="rounded border-amber-300 text-purple-700 focus:ring-purple-500">
                                        Forzar (sin respetar las 24 h o el límite de una reprogramación)
                                    </label>
                                    <div>
                                        <x-input-label for="motivo_forzado" value="Motivo para forzar" class="text-xs font-semibold uppercase text-amber-900/80" />
                                        <input id="motivo_forzado" name="motivo_forzado" type="text" maxlength="150" value="{{ old('motivo_forzado') }}" class="{{ $claseCampo }}" />
                                        <x-input-error :messages="$errors->get('motivo_forzado')" class="mt-1.5" />
                                    </div>
                                </div>
                            @endif

                            <p id="error-reserva" class="hidden text-sm text-red-600"></p>

                            <div class="flex justify-end">
                                <button type="submit" id="btn-reservar"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ $reprogramando ? 'Reprogramar' : 'Reservar ficha' }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            /*
             * fichas.js · CU12: pide al servidor las fichas del día (fetch, JSON)
             * y arma la grilla. El servidor vuelve a verificar la ficha al
             * guardar; esta validación en el cliente es solo de ayuda.
             */
            document.addEventListener('DOMContentLoaded', () => {
                const mapa = @json($mapa);
                const dias = @json($dias);
                const nombresDias = @json(\App\Models\Horario::DIAS);
                const urlFichas = @json(route('fichas.disponibles'));
                const urlPacientes = @json($puedeReservar && ! $esPaciente ? route('fichas.pacientes') : null);
                const idCita = @json($cita?->id_cita);
                const esPersonal = @json(! $esPaciente);
                const permiteSobrecupo = esPersonal && !idCita && @json($puedeReservar);

                const $ = (id) => document.getElementById(id);
                const selEspecialidad = $('sel_especialidad');
                const selMedico = $('sel_medico');
                const selFecha = $('sel_fecha');
                const contenedor = $('contenedor-fichas');
                const form = $('form-reserva');

                // Valores previos (después de un error del servidor).
                const previo = {
                    fecha: @json(old('fecha')),
                    horario: @json(old('id_horario')),
                    nro: @json(old('nro_cita')),
                    sobrecupo: @json((bool) old('sobrecupo')),
                };
                if (previo.fecha) selFecha.value = previo.fecha;

                const BASE = 'flex flex-col items-center justify-center p-2 rounded-xl border text-xs font-semibold transition-colors';
                const estilos = {
                    libre: 'bg-emerald-50 border-emerald-300 text-emerald-900 hover:bg-emerald-100 cursor-pointer',
                    ocupada: 'bg-rose-50 border-rose-200 text-rose-400 cursor-not-allowed',
                    otro_canal: 'bg-purple-50 border-purple-200 text-purple-400 cursor-not-allowed',
                    seleccionada: 'bg-purple-700 border-purple-800 text-white',
                };
                const textos = {
                    libre: 'Libre', ocupada: 'Ocupada', bloqueada: 'Bloqueada',
                    pasada: 'Pasada', fuera_ventana: 'Fuera de ventana', otro_canal: 'Mostrador',
                };

                // Especialidad → médicos que la atienden.
                const filtrarMedicos = () => {
                    const permitidos = (mapa[selEspecialidad.value] || []).map(String);

                    Array.from(selMedico.options).forEach((opcion) => {
                        if (!opcion.value) return;
                        const visible = permitidos.includes(opcion.value);
                        opcion.hidden = !visible;
                        opcion.disabled = !visible;
                    });

                    if (selMedico.value && !permitidos.includes(selMedico.value)) selMedico.value = '';
                    mostrarDias();
                };

                const mostrarDias = () => {
                    const lista = dias[selMedico.value + '-' + selEspecialidad.value] || [];
                    $('dias-atencion').textContent = lista.length
                        ? 'Atiende: ' + lista.sort().map((d) => nombresDias[d]).join(', ')
                        : '';
                };

                const limpiarSeleccion = () => {
                    if (!form) return;
                    $('id_horario').value = '';
                    $('nro_cita').value = '';
                    if ($('sobrecupo')) $('sobrecupo').value = '0';
                    $('ficha-elegida').textContent = 'Aún no eligió una ficha.';
                };

                const elegir = (boton, turno, ficha, sobrecupo = false) => {
                    if (!form) return;
                    contenedor.querySelectorAll('[data-seleccionada]').forEach((b) => {
                        b.removeAttribute('data-seleccionada');
                        b.className = BASE + ' ' + b.dataset.estilo;
                    });
                    boton.setAttribute('data-seleccionada', '1');
                    boton.className = BASE + ' ' + estilos.seleccionada;

                    $('id_especialidad').value = selEspecialidad.value;
                    $('id_medico').value = selMedico.value;
                    $('fecha').value = selFecha.value;
                    $('id_horario').value = turno.id_horario;
                    $('nro_cita').value = sobrecupo ? '' : ficha.nro;
                    if ($('sobrecupo')) $('sobrecupo').value = sobrecupo ? '1' : '0';

                    const medico = selMedico.options[selMedico.selectedIndex].text.trim();
                    const fecha = selFecha.value.split('-').reverse().join('/');
                    $('ficha-elegida').innerHTML = sobrecupo
                        ? `<strong>Sobrecupo</strong> en el turno ${turno.hora_inicio}–${turno.hora_fin} · ${fecha} · ${medico}`
                        : `<strong>Ficha ${ficha.nro}</strong> · ${fecha} · ${ficha.hora_inicio}–${ficha.hora_fin} · ${medico}`
                          + (turno.consultorio ? ` · consultorio ${turno.consultorio}` : '');
                };

                const crearBotonFicha = (turno, ficha) => {
                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.dataset.estilo = estilos[ficha.estado] || 'bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed';
                    boton.className = BASE + ' ' + boton.dataset.estilo;
                    boton.disabled = !ficha.disponible || !form;
                    boton.title = textos[ficha.estado] || '';
                    boton.innerHTML = `<span class="text-sm font-bold">${ficha.nro}</span><span>${ficha.hora_inicio}</span><span class="text-[10px] font-medium opacity-80">${textos[ficha.estado] || ''}</span>`;
                    boton.addEventListener('click', () => elegir(boton, turno, ficha));

                    if (previo.nro && String(previo.horario) === String(turno.id_horario) && String(previo.nro) === String(ficha.nro) && ficha.disponible) {
                        setTimeout(() => elegir(boton, turno, ficha), 0);
                    }
                    return boton;
                };

                const pintar = (datos) => {
                    contenedor.innerHTML = '';
                    $('resumen-fichas').textContent = datos.turnos.length ? `${datos.libres} ficha(s) libre(s)` : '';

                    if (datos.mensaje) {
                        const aviso = document.createElement('p');
                        aviso.className = 'mb-3 text-sm font-medium text-amber-700';
                        aviso.textContent = datos.mensaje;
                        contenedor.appendChild(aviso);
                    }

                    datos.turnos.forEach((turno) => {
                        const bloque = document.createElement('div');
                        bloque.className = 'mb-5 last:mb-0';
                        bloque.innerHTML = `<div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <p class="text-sm font-semibold text-purple-950">Turno ${turno.hora_inicio}–${turno.hora_fin}
                                    <span class="text-xs font-normal text-gray-500">· ${turno.duracion_min} min · ${turno.capacidad} fichas
                                    (${turno.cupos_linea} en línea / ${turno.cupos_mostrador} mostrador)${turno.consultorio ? ' · consultorio ' + turno.consultorio : ''}</span></p>
                            </div>`;

                        const grilla = document.createElement('div');
                        grilla.className = 'grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2';
                        turno.fichas.forEach((ficha) => grilla.appendChild(crearBotonFicha(turno, ficha)));

                        // RN29: sobrecupo de urgencia, solo recepción.
                        if (permiteSobrecupo && form) {
                            const extra = document.createElement('button');
                            extra.type = 'button';
                            extra.dataset.estilo = 'border-dashed bg-white border-amber-300 text-amber-700 hover:bg-amber-50 cursor-pointer';
                            extra.className = BASE + ' ' + extra.dataset.estilo;
                            extra.innerHTML = '<span class="text-sm font-bold">+</span><span>Sobrecupo</span>';
                            extra.addEventListener('click', () => elegir(extra, turno, null, true));
                            if (previo.sobrecupo && String(previo.horario) === String(turno.id_horario)) {
                                setTimeout(() => elegir(extra, turno, null, true), 0);
                            }
                            grilla.appendChild(extra);
                        }

                        bloque.appendChild(grilla);
                        contenedor.appendChild(bloque);
                    });

                    previo.nro = null;
                    previo.sobrecupo = false;
                };

                const buscar = async () => {
                    const error = $('error-busqueda');
                    error.classList.add('hidden');
                    limpiarSeleccion();

                    if (!selEspecialidad.value || !selMedico.value || !selFecha.value) {
                        error.textContent = 'Seleccione especialidad, médico y fecha.';
                        error.classList.remove('hidden');
                        return;
                    }

                    const parametros = new URLSearchParams({
                        id_especialidad: selEspecialidad.value,
                        id_medico: selMedico.value,
                        fecha: selFecha.value,
                    });
                    if (idCita) parametros.append('cita', idCita);

                    contenedor.textContent = 'Consultando fichas…';

                    try {
                        const respuesta = await fetch(`${urlFichas}?${parametros}`, { headers: { Accept: 'application/json' } });
                        const datos = await respuesta.json();

                        if (!respuesta.ok) {
                            const mensajes = datos.errors ? Object.values(datos.errors).flat() : [datos.message];
                            contenedor.textContent = '';
                            error.textContent = mensajes.join(' ');
                            error.classList.remove('hidden');
                            return;
                        }
                        pintar(datos);
                    } catch (e) {
                        contenedor.textContent = 'No se pudieron consultar las fichas. Intente nuevamente.';
                    }
                };

                selEspecialidad.addEventListener('change', () => { filtrarMedicos(); contenedor.textContent = 'Seleccione médico y fecha.'; limpiarSeleccion(); });
                selMedico.addEventListener('change', () => { mostrarDias(); if (selFecha.value) buscar(); });
                selFecha.addEventListener('change', () => { if (selMedico.value) buscar(); });
                $('btn-buscar').addEventListener('click', buscar);

                // Autocompletado de pacientes (recepción).
                if (urlPacientes) {
                    const texto = $('paciente_texto');
                    const lista = $('sugerencias');
                    let temporizador = null;

                    texto.addEventListener('input', () => {
                        $('id_paciente').value = '';
                        clearTimeout(temporizador);
                        if (texto.value.trim().length < 2) { lista.classList.add('hidden'); return; }

                        temporizador = setTimeout(async () => {
                            const respuesta = await fetch(`${urlPacientes}?q=${encodeURIComponent(texto.value.trim())}`, { headers: { Accept: 'application/json' } });
                            const pacientes = respuesta.ok ? await respuesta.json() : [];
                            lista.innerHTML = '';

                            if (!pacientes.length) {
                                lista.innerHTML = '<li class="px-4 py-2.5 text-sm text-gray-500">Sin resultados</li>';
                            }
                            pacientes.forEach((p) => {
                                const item = document.createElement('li');
                                item.className = 'px-4 py-2.5 text-sm hover:bg-purple-50 cursor-pointer';
                                item.innerHTML = `<span class="font-semibold text-purple-950"></span> <span class="text-xs text-gray-500"></span>`;
                                item.children[0].textContent = p.nombre;
                                item.children[1].textContent = `CI ${p.ci} · ${p.codigo}`;
                                item.addEventListener('click', () => {
                                    $('id_paciente').value = p.id;
                                    texto.value = `${p.nombre} · CI ${p.ci}`;
                                    lista.classList.add('hidden');
                                });
                                lista.appendChild(item);
                            });
                            lista.classList.remove('hidden');
                        }, 250);
                    });

                    document.addEventListener('click', (e) => {
                        if (!lista.contains(e.target) && e.target !== texto) lista.classList.add('hidden');
                    });
                }

                // Validación en el cliente antes de enviar (el servidor valida de nuevo).
                if (form) {
                    form.addEventListener('submit', (e) => {
                        const errores = [];
                        const esSobrecupo = $('sobrecupo') && $('sobrecupo').value === '1';

                        if (!$('id_horario').value || (!esSobrecupo && !$('nro_cita').value)) errores.push('Seleccione una ficha libre.');
                        if ($('id_paciente') && !$('id_paciente').value) errores.push('Seleccione el paciente de la lista.');
                        if ($('forzar') && $('forzar').checked && $('motivo_forzado').value.trim().length < 5) errores.push('Indique el motivo para forzar (mínimo 5 caracteres).');

                        if (errores.length) {
                            e.preventDefault();
                            $('error-reserva').textContent = errores.join(' ');
                            $('error-reserva').classList.remove('hidden');
                            return;
                        }
                        $('btn-reservar').disabled = true; // evita doble envío
                    });
                }

                filtrarMedicos();
                if (selEspecialidad.value && selMedico.value && selFecha.value) buscar();
            });
        </script>
    @endpush
</x-app-layout>

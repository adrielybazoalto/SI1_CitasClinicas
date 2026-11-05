{{-- CU20 · Consultar agenda médica (vista diaria y semanal) --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150';
    $etiquetasFiltro = [
        'pendientes' => 'Pendientes',
        'confirmadas' => 'Confirmadas',
        'atendidas' => 'Atendidas',
        'canceladas' => 'Canceladas',
        'inasistencias' => 'Inasistencias',
    ];
    $base = array_filter(['medico' => $medico?->id_medico, 'estado' => $estado, 'accion' => $accion]);
    $urlDia = fn ($dia) => route('agenda.index', array_merge($base, ['fecha' => $dia->toDateString(), 'vista' => $vista]));
    $paso = $vista === 'semana' ? 7 : 1;
    $rutaAtender = \Illuminate\Support\Facades\Route::has('atencion.create');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $esMedico ? 'Mi agenda' : 'Agenda médica' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if ($medico)
                        {{ $medico->nombre_completo }} ·
                    @endif
                    {{ $vista === 'semana'
                        ? 'Semana del '.$fecha->startOfWeek()->format('d/m').' al '.$fecha->startOfWeek()->addDays(6)->format('d/m/Y')
                        : ucfirst($fecha->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}
                </p>
            </div>
            <div class="inline-flex rounded-xl border border-purple-200 bg-white p-1 text-xs font-semibold">
                @foreach (['dia' => 'Día', 'semana' => 'Semana'] as $clave => $texto)
                    <a href="{{ route('agenda.index', array_merge($base, ['fecha' => $fecha->toDateString(), 'vista' => $clave])) }}"
                       class="px-4 py-1.5 rounded-lg transition-colors {{ $vista === $clave ? 'bg-purple-800 text-white' : 'text-purple-800 hover:bg-purple-50' }}">{{ $texto }}</a>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-aviso-modulo />

            {{-- RNF20 · CU20: dictado de médico, fecha y estado (no envía el filtro solo) --}}
            <x-voz :campos="array_filter(['medico' => $esMedico ? null : 'medico', 'fecha' => 'fecha', 'estado' => 'estado'])" />
            @include('citas._alertas')

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif

            <!-- Filtros -->
            <form method="GET" action="{{ route('agenda.index') }}" id="form-agenda" class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                <input type="hidden" name="vista" value="{{ $vista }}">
                @if ($accion)
                    <input type="hidden" name="accion" value="{{ $accion }}">
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    @unless ($esMedico)
                        <div class="sm:col-span-4">
                            <x-input-label for="medico" value="Médico" class="text-xs font-semibold uppercase text-purple-950/80" />
                            <select id="medico" name="medico" required class="{{ $claseCampo }}">
                                <option value="">Seleccione un médico…</option>
                                @foreach ($medicos as $opcion)
                                    <option value="{{ $opcion->id_medico }}" @selected($medico?->id_medico === $opcion->id_medico)>
                                        {{ $opcion->nombre_completo }}{{ $opcion->estado ? '' : ' (inactivo)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endunless
                    <div class="sm:col-span-3">
                        <x-input-label for="fecha" value="Fecha" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <input id="fecha" name="fecha" type="date" required value="{{ $fecha->toDateString() }}" class="{{ $claseCampo }}" />
                    </div>
                    <div class="{{ $esMedico ? 'sm:col-span-6' : 'sm:col-span-3' }}">
                        <x-input-label for="estado" value="Estado" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <select id="estado" name="estado" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($filtrosEstado as $clave)
                                <option value="{{ $clave }}" @selected($estado === $clave)>{{ $etiquetasFiltro[$clave] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 {{ $esMedico ? 'sm:col-span-3' : '' }} flex items-end">
                        <button type="submit" class="inline-flex w-full items-center justify-center px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors cursor-pointer">
                            Ver agenda
                        </button>
                    </div>
                </div>
                <p id="error-agenda" class="hidden mt-2 text-xs text-red-600"></p>
            </form>

            @if (! $medico)
                <div class="bg-white rounded-2xl border border-purple-100 px-6 py-12 text-center">
                    <p class="text-sm font-semibold text-purple-950">
                        {{ $esMedico ? 'Su cuenta no está vinculada a un médico.' : 'Seleccione un médico para ver su agenda.' }}
                    </p>
                    @if ($esMedico)
                        <p class="text-xs text-gray-500 mt-1">Comuníquese con el administrador.</p>
                    @endif
                </div>
            @else
                <!-- Navegación de fechas -->
                <div class="flex items-center justify-between">
                    <a href="{{ $urlDia($fecha->subDays($paso)) }}" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold text-purple-800 hover:bg-purple-50 rounded-lg">← {{ $vista === 'semana' ? 'Semana anterior' : 'Día anterior' }}</a>
                    @unless ($fecha->isToday())
                        <a href="{{ $urlDia(\Carbon\CarbonImmutable::today()) }}" class="px-3 py-2 text-xs font-semibold text-purple-800 hover:bg-purple-50 rounded-lg">Hoy</a>
                    @endunless
                    <a href="{{ $urlDia($fecha->addDays($paso)) }}" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold text-purple-800 hover:bg-purple-50 rounded-lg">{{ $vista === 'semana' ? 'Semana siguiente' : 'Día siguiente' }} →</a>
                </div>

                @if ($vista === 'semana')
                    <!-- Vista semanal: cantidad de citas por estado -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3">
                        @foreach ($semana as $dia)
                            <a href="{{ route('agenda.index', array_merge($base, ['fecha' => $dia['fecha']->toDateString(), 'vista' => 'dia'])) }}"
                               class="block bg-white rounded-2xl border p-4 hover:shadow-md transition-shadow {{ $dia['fecha']->isToday() ? 'border-purple-500 ring-2 ring-purple-500/20' : 'border-purple-100' }}">
                                <p class="text-[11px] font-bold uppercase text-purple-900/70">{{ $dia['fecha']->locale('es')->isoFormat('ddd') }}</p>
                                <p class="text-lg font-bold text-purple-950">{{ $dia['fecha']->format('d/m') }}</p>
                                <p class="text-xs text-gray-500 mb-2">{{ $dia['total'] }} {{ $dia['total'] === 1 ? 'cita' : 'citas' }}</p>
                                <div class="space-y-1">
                                    @foreach ($dia['estados'] as $nombre => $cantidad)
                                        @if ($cantidad > 0)
                                            <div class="flex items-center justify-between gap-2">
                                                @include('citas._estado', ['estado' => $nombre])
                                                <span class="text-sm font-semibold text-gray-800">{{ $cantidad }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <!-- Vista diaria -->
                    <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-purple-100">
                                <thead class="bg-purple-50/60">
                                    <tr>
                                        <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Hora</th>
                                        <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Código</th>
                                        <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Paciente</th>
                                        <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Especialidad</th>
                                        <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Estado</th>
                                        @if ($verMotivo)
                                            <th class="px-4 sm:px-6 py-3.5 text-left text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Motivo</th>
                                        @endif
                                        <th class="px-4 sm:px-6 py-3.5 text-right text-[10px] font-bold text-purple-900/70 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-purple-50">
                                    @forelse ($citas as $cita)
                                        <tr class="hover:bg-purple-50/30 transition-colors">
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                <p class="text-sm font-semibold text-purple-950">{{ $cita->hora_inicio_corta }} – {{ $cita->hora_fin_corta }}</p>
                                                <p class="text-[11px] text-gray-400 mt-0.5">Cita n.º {{ $cita->nro_cita }}{{ $cita->sobrecupo ? ' · sobrecupo' : '' }}</p>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap font-mono text-sm font-semibold text-purple-950">{{ $cita->codigo }}</td>
                                            <td class="px-4 sm:px-6 py-4">
                                                <p class="text-sm font-semibold text-purple-950">{{ $cita->paciente?->apellidos }}, {{ $cita->paciente?->nombres }}</p>
                                                <p class="text-[11px] text-gray-400 mt-0.5">CI {{ $cita->paciente?->ci }}</p>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-700">{{ ucfirst($cita->especialidad?->nombre) }}</td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">@include('citas._estado', ['estado' => $cita->estado])</td>
                                            @if ($verMotivo)
                                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="{{ $cita->motivo }}">{{ \Illuminate\Support\Str::limit($cita->motivo ?? '—', 40) }}</td>
                                            @endif
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right">
                                                @if ($cita->estado === 'Confirmada' && $puedeAtender && $rutaAtender)
                                                    <a href="{{ route('atencion.create', $cita) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">Atender</a>
                                                @elseif ($cita->estado === 'Atendida')
                                                    <span class="text-xs text-emerald-700 font-semibold">Atención registrada</span>
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $verMotivo ? 7 : 6 }}" class="px-6 py-12 text-center">
                                                <p class="text-sm font-semibold text-purple-950">No hay citas para esta fecha{{ $estado ? ' con ese estado' : '' }}</p>
                                                <p class="text-xs text-gray-500 mt-1">Use las flechas o la vista semanal para buscar otros días.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <script>
        // Validación en el cliente: médico y fecha obligatorios antes de consultar.
        document.getElementById('form-agenda').addEventListener('submit', function (e) {
            const medico = document.getElementById('medico');
            const fecha = document.getElementById('fecha');
            const error = document.getElementById('error-agenda');
            let mensaje = '';
            if (medico && !medico.value) mensaje = 'Seleccione un médico.';
            else if (!fecha.value) mensaje = 'Seleccione una fecha.';
            if (mensaje) {
                e.preventDefault();
                error.textContent = mensaje;
                error.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>

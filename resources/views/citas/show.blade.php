{{-- Detalle de la cita: datos, acciones (CU15, CU16, CU17) e historial de estados --}}
@php
    $usuario = Auth::user();
    $programada = $cita->estado === 'Programada';
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('citas.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight flex items-center gap-3">
                    Cita <span class="font-mono">{{ $cita->codigo }}</span>
                    @include('citas._estado', ['estado' => $cita->estado])
                </h2>
                <p class="text-sm text-gray-500">Registrada el {{ $cita->creado_en?->format('d/m/Y H:i') }} por {{ $cita->creador?->username ?? '—' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Datos de la cita -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Paciente</dt>
                            <dd class="mt-1 font-semibold text-purple-950">{{ $cita->paciente?->apellidos }}, {{ $cita->paciente?->nombres }}</dd>
                            <dd class="text-xs text-gray-500">{{ $cita->paciente?->codigo }} · CI {{ $cita->paciente?->ci }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Médico</dt>
                            <dd class="mt-1 font-semibold text-purple-950">{{ $cita->medico?->nombre_completo }}</dd>
                            <dd class="text-xs text-gray-500">{{ ucfirst($cita->especialidad?->nombre) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Fecha y hora</dt>
                            <dd class="mt-1 font-semibold text-purple-950">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->hora_inicio_corta }} – {{ $cita->hora_fin_corta }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Ficha</dt>
                            <dd class="mt-1 font-semibold text-purple-950">N.º {{ $cita->nro_cita }}{{ $cita->sobrecupo ? ' (sobrecupo)' : '' }}</dd>
                            <dd class="text-xs text-gray-500">Canal: {{ $cita->canal === 'LINEA' ? 'en línea' : 'mostrador' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Motivo de consulta</dt>
                            <dd class="mt-1 text-gray-700">{{ $cita->motivo ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Reprogramaciones</dt>
                            <dd class="mt-1 text-gray-700">{{ $cita->veces_reprogramada }}</dd>
                        </div>
                        @if ($cita->hora_llegada)
                            <div>
                                <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Llegada</dt>
                                <dd class="mt-1 text-gray-700">{{ $cita->hora_llegada->format('d/m/Y H:i') }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Precio y pago</dt>
                            <dd class="mt-1 font-semibold text-purple-950">{{ $cita->monto !== null ? 'Bs '.number_format((float) $cita->monto, 2, ',', '.') : 'Sin tarifa' }}</dd>
                            <dd class="text-xs {{ $cita->estado_pago === 'Pagado' ? 'text-emerald-700' : ($cita->estado_pago === 'Reembolsado' ? 'text-sky-700' : 'text-amber-700') }}">{{ $cita->estado_pago }}</dd>
                        </div>
                        @if ($cita->motivo_cancelacion)
                            <div>
                                <dt class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider">Motivo de cancelación</dt>
                                <dd class="mt-1 text-gray-700">{{ $cita->motivo_cancelacion }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Acciones disponibles según estado y permisos -->
                <div class="space-y-4">
                    @if (app(\App\Services\PagoService::class)->cobrable($cita) && $usuario->tienePermiso('caja.cobrar'))
                        {{-- CU19 extiende a CU13: cobrar en mostrador al reservar --}}
                        <a href="{{ route('cobros.create', $cita) }}"
                           class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors">
                            Cobrar ahora · Bs {{ number_format((float) $cita->monto, 2, ',', '.') }}
                        </a>
                    @endif

                    @if ($programada && $usuario->tienePermiso('cita.reprogramar'))
                        <a href="{{ route('citas.reprogramar', $cita) }}"
                           class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors">
                            Reprogramar
                        </a>
                    @endif

                    @if ($programada && $cita->fecha->isToday() && $usuario->tienePermiso('cita.asistencia'))
                        <form method="POST" action="{{ route('citas.llegada', $cita) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full px-4 py-2.5 bg-sky-600 hover:bg-sky-700 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors cursor-pointer">
                                Registrar llegada
                            </button>
                        </form>
                    @endif

                    @if (in_array($cita->estado, ['Programada', 'Confirmada'], true) && $usuario->tienePermiso('cita.asistencia'))
                        <form method="POST" action="{{ route('citas.inasistencia', $cita) }}" class="bg-white p-4 rounded-2xl border border-amber-200 space-y-2"
                              onsubmit="return confirm('¿Registrar inasistencia para esta cita?')">
                            @csrf
                            @method('PATCH')
                            <x-input-label for="detalle" value="Inasistencia (observación opcional)" class="text-xs font-semibold uppercase text-amber-900/80" />
                            <input id="detalle" name="detalle" type="text" maxlength="200" class="{{ $claseCampo }}" />
                            <button type="submit" class="w-full px-4 py-2 bg-amber-500 hover:bg-amber-600 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors cursor-pointer">
                                Registrar inasistencia
                            </button>
                        </form>
                    @endif

                    @if ($programada && $usuario->tienePermiso('cita.cancelar'))
                        <form id="cancelar" method="POST" action="{{ route('citas.cancelar', $cita) }}" class="bg-white p-4 rounded-2xl border border-rose-200 space-y-2"
                              onsubmit="const m = this.motivo_cancelacion.value.trim(); if (m.length < 5) { alert('Indique el motivo (mínimo 5 caracteres).'); return false; } return confirm('¿Cancelar la cita {{ $cita->codigo }}?')">
                            @csrf
                            @method('PATCH')
                            <x-input-label for="motivo_cancelacion" value="Motivo de cancelación" class="text-xs font-semibold uppercase text-rose-900/80" />
                            <textarea id="motivo_cancelacion" name="motivo_cancelacion" rows="2" maxlength="200" required minlength="5" class="{{ $claseCampo }}">{{ old('motivo_cancelacion') }}</textarea>
                            <button type="submit" class="w-full px-4 py-2 bg-rose-600 hover:bg-rose-700 rounded-xl font-semibold text-xs text-white uppercase tracking-wider transition-colors cursor-pointer">
                                Cancelar cita
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Historial de estados (RF10) -->
            <div class="bg-white p-6 rounded-2xl border border-purple-100 shadow-xs">
                <p class="text-[10px] font-bold text-purple-900/60 uppercase tracking-wider mb-4">Historial de estados</p>
                <ol class="relative border-l border-purple-100 ml-2 space-y-5">
                    @foreach ($cita->historialEstados->sortBy('id_historial') as $paso)
                        <li class="ml-5">
                            <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-purple-600 border-2 border-white"></span>
                            <p class="text-sm text-purple-950">
                                <span class="font-semibold">{{ $paso->estado_anterior ?? '(nueva)' }} → {{ $paso->estado_nuevo }}</span>
                                <span class="text-xs text-gray-500">· {{ $paso->fecha_hora?->format('d/m/Y H:i') }} · {{ $paso->usuario?->username ?? 'Temporizador' }}</span>
                            </p>
                            @if ($paso->detalle)
                                <p class="text-xs text-gray-600 mt-0.5">{{ $paso->detalle }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</x-app-layout>

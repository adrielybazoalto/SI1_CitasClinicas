{{-- CU10 · Citas afectadas por la indisponibilidad: reubicar o cancelar con reembolso total --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('indisponibilidades.index') }}" class="p-2 text-purple-700 hover:bg-purple-50 rounded-xl" title="Volver">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Citas afectadas</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $periodo->medico?->nombre_completo }} · del {{ $periodo->inicio->format('d/m/Y H:i') }} al {{ $periodo->fin->format('d/m/Y H:i') }} · {{ $periodo->motivo }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @include('citas._alertas')

            @forelse ($afectadas as $cita)
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-gray-900"><span class="font-mono">{{ $cita->codigo }}</span> · {{ $cita->fecha->format('d/m/Y') }} {{ $cita->hora_inicio_corta }}</p>
                            <p class="text-sm text-gray-600">{{ $cita->paciente?->apellidos }}, {{ $cita->paciente?->nombres }} · CI {{ $cita->paciente?->ci }} · {{ ucfirst($cita->especialidad?->nombre) }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Pago: {{ $cita->estado_pago }}{{ $cita->paciente?->correo ? '' : ' · el paciente no tiene correo: avísele por teléfono' }}</p>
                        </div>
                        @include('citas._estado', ['estado' => $cita->estado])
                    </div>

                    @if ($cita->estado === 'Programada')
                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-4 gap-3">
                            @forelse ($opciones[$cita->id_cita] ?? [] as $opcion)
                                <form method="POST" action="{{ route('indisponibilidades.reubicar', [$periodo, $cita]) }}"
                                      class="p-3 rounded-xl border border-purple-200 bg-purple-50/40 flex flex-col gap-2">
                                    @csrf
                                    <input type="hidden" name="id_medico" value="{{ $opcion['id_medico'] }}">
                                    <input type="hidden" name="fecha" value="{{ $opcion['fecha'] }}">
                                    <input type="hidden" name="id_horario" value="{{ $opcion['id_horario'] }}">
                                    <input type="hidden" name="nro_cita" value="{{ $opcion['nro'] }}">
                                    <p class="text-sm font-semibold text-gray-900">{{ \Carbon\Carbon::parse($opcion['fecha'])->locale('es')->isoFormat('ddd D/MM') }} · {{ $opcion['hora'] }}</p>
                                    <p class="text-xs text-gray-600">{{ $opcion['medico'] }}{{ $opcion['id_medico'] === $cita->id_medico ? ' (mismo médico)' : '' }}</p>
                                    <button type="submit" class="mt-auto px-3 py-2 bg-purple-800 hover:bg-purple-900 text-white text-xs font-semibold rounded-lg cursor-pointer">Reubicar aquí</button>
                                </form>
                            @empty
                                <p class="lg:col-span-3 text-sm text-gray-500">No hay horarios libres en las próximas tres semanas. Cancele la cita o reprográmela a mano.</p>
                            @endforelse
                            <form method="POST" action="{{ route('indisponibilidades.cancelar', [$periodo, $cita]) }}"
                                  onsubmit="return confirm('¿Cancelar la cita {{ $cita->codigo }}? Si estaba pagada, se reembolsa el total.');"
                                  class="p-3 rounded-xl border border-rose-200 bg-rose-50/40 flex flex-col gap-2">
                                @csrf
                                <p class="text-sm font-semibold text-rose-800">Cancelar</p>
                                <p class="text-xs text-rose-700">Motivo: indisponibilidad médica · reembolso total si estaba pagada.</p>
                                <button type="submit" class="mt-auto px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg cursor-pointer">Cancelar cita</button>
                            </form>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3">
                            El paciente ya llegó (cita Confirmada). Atiéndala con otro médico de la especialidad o registre la inasistencia desde la cita.
                        </p>
                    @endif
                </div>
            @empty
                <div class="bg-white p-10 rounded-2xl shadow-sm border border-purple-100 text-center">
                    <p class="font-semibold text-gray-900">No hay citas afectadas por este periodo.</p>
                    <a href="{{ route('indisponibilidades.index') }}" class="mt-3 inline-block text-sm font-semibold text-purple-800 hover:underline">Volver a la lista</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

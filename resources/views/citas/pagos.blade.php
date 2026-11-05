{{-- CU14 · Mis pagos: citas pendientes de pago en línea y comprobantes --}}
@php $pagos = app(\App\Services\PagoService::class); @endphp
<x-app-layout>
    <x-slot name="header">
        <x-encabezado-modulo />
        <h2 class="text-3xl font-bold text-gray-900 leading-tight">Pagar cita en línea</h2>
        <p class="text-sm text-gray-500 mt-0.5">Las reservas en línea se retienen {{ config('clinica.retencion_pago_min') }} minutos; si no se pagan, se liberan.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @include('citas._alertas')

            @forelse ($citas as $cita)
                @php
                    $pendiente = $pagos->cobrable($cita) && ! $pagos->vencida($cita);
                    $comprobante = $cita->pagos->where('tipo', 'COBRO')->sortByDesc('fecha_hora')->first()?->comprobante;
                @endphp
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="font-bold text-gray-900">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->hora_inicio_corta }} · <span class="font-mono">{{ $cita->codigo }}</span></p>
                        <p class="text-sm text-gray-600">{{ ucfirst($cita->especialidad?->nombre) }} · {{ $cita->medico?->nombre_completo }}</p>
                        <p class="text-sm font-semibold mt-1 {{ $cita->estado_pago === 'Pagado' ? 'text-emerald-700' : 'text-amber-700' }}">
                            Bs {{ number_format((float) $cita->monto, 2, ',', '.') }} · {{ $cita->estado_pago }}
                            @if ($pendiente && $cita->vence_pago_en)
                                <span class="font-normal text-gray-500">· pague antes de las {{ $cita->vence_pago_en->format('H:i') }}</span>
                            @endif
                        </p>
                    </div>
                    @if ($pendiente)
                        <a href="{{ route('pagos.show', $cita) }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-xl font-semibold">Pagar ahora</a>
                    @elseif ($comprobante)
                        <a href="{{ route('pagos.comprobante', $cita) }}" class="px-4 py-2.5 border border-purple-300 text-purple-800 hover:bg-purple-50 text-sm rounded-xl font-semibold">Comprobante N.º {{ $comprobante->numero }} (PDF)</a>
                    @endif
                </div>
            @empty
                <div class="bg-white p-10 rounded-2xl shadow-sm border border-purple-100 text-center text-gray-500">No tiene citas con pagos pendientes ni pagos recientes.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>

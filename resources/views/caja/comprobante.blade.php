{{-- CU19 · Comprobante emitido: imprimir (PDF) o enviar al correo del paciente --}}
@php
    $cita = $pago->cita;
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-encabezado-modulo />
        <h2 class="text-3xl font-bold text-gray-900 leading-tight">Comprobante N.º {{ $pago->comprobante?->numero }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Cita {{ $cita?->codigo }} · emitido el {{ $pago->comprobante?->fecha_emision?->format('d/m/Y H:i') }}</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-purple-100">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-gray-500">Paciente</dt><dd class="font-semibold">{{ $cita?->paciente?->apellidos }}, {{ $cita?->paciente?->nombres }}</dd></div>
                    <div><dt class="text-xs text-gray-500">CI</dt><dd class="font-semibold">{{ $cita?->paciente?->ci }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Cita</dt><dd class="font-semibold">{{ $cita?->fecha->format('d/m/Y') }} {{ $cita?->hora_inicio_corta }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Médico</dt><dd class="font-semibold">{{ $cita?->medico?->nombre_completo }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Método</dt><dd class="font-semibold">{{ ucfirst(mb_strtolower($pago->metodo)) }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Monto</dt><dd class="text-xl font-bold tabular-nums">Bs {{ number_format((float) $pago->monto, 2, ',', '.') }}</dd></div>
                </dl>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('cobros.comprobante', $pago) }}" class="px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold">Imprimir (PDF)</a>
                @if ($cita?->paciente?->correo)
                    <form method="POST" action="{{ route('cobros.enviar', $pago) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 border border-purple-300 text-purple-800 hover:bg-purple-50 text-sm rounded-xl font-semibold cursor-pointer">Enviar a {{ $cita->paciente->correo }}</button>
                    </form>
                @endif
                <a href="{{ route('cobros.index') }}" class="px-4 py-2.5 text-sm font-semibold text-purple-800 hover:bg-purple-50 rounded-xl">Cobrar otra cita</a>
            </div>
        </div>
    </div>
</x-app-layout>

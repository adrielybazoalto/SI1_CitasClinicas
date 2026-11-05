{{-- CU11 · Historial de precios de una especialidad --}}
@php
    $hoy = now()->startOfDay();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tarifas.index') }}" class="p-2 text-purple-700 hover:bg-purple-50 rounded-xl" title="Volver al tarifario">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Historial de precios</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ ucfirst($especialidad->nombre) }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
                <table class="min-w-full divide-y divide-purple-100 text-sm">
                    <thead class="bg-purple-50/60">
                        <tr>
                            @foreach (['Precio', 'Desde', 'Hasta', 'Situación'] as $columna)
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $columna }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-purple-50">
                        @forelse ($tarifas as $tarifa)
                            @php
                                $situacion = match (true) {
                                    $tarifa->vigente_desde->gt($hoy) => ['Programada', 'bg-sky-50 text-sky-700 border-sky-200'],
                                    $tarifa->vigente_hasta === null || $tarifa->vigente_hasta->gte($hoy) => ['Vigente', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                    default => ['Anterior', 'bg-gray-100 text-gray-600 border-gray-200'],
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-900 tabular-nums">Bs {{ number_format((float) $tarifa->monto, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $tarifa->vigente_desde->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $tarifa->vigente_hasta?->format('d/m/Y') ?? 'Sin fecha de fin' }}</td>
                                <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $situacion[1] }}">{{ $situacion[0] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Esta especialidad todavía no tiene precios registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href="{{ route('tarifas.index', ['especialidad' => $especialidad->id_especialidad]) }}"
               class="inline-flex px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold">Registrar un precio nuevo</a>
        </div>
    </div>
</x-app-layout>

{{-- CU19 · Citas pendientes de cobro en mostrador --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Cobrar en mostrador</h2>
                <p class="text-sm text-gray-500 mt-0.5">Citas programadas pendientes de pago</p>
            </div>
            <a href="{{ route('caja.index') }}" class="text-sm font-semibold text-purple-800 hover:underline">Ver mi caja →</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            <form method="GET" action="{{ route('cobros.index') }}" class="bg-white p-4 rounded-2xl shadow-sm border border-purple-100 flex flex-col sm:flex-row gap-3 sm:items-end">
                <div class="flex-1">
                    <label for="buscar" class="text-xs font-semibold text-gray-600">Buscar</label>
                    <input id="buscar" name="buscar" type="search" maxlength="60" value="{{ $buscar }}" placeholder="Código de cita, CI o apellido"
                           class="mt-1 block w-full px-3.5 py-2.5 bg-white border border-purple-200 rounded-xl text-sm focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold cursor-pointer">Buscar</button>
            </form>

            <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100 text-sm">
                        <thead class="bg-purple-50/60">
                            <tr>
                                @foreach (['Cita', 'Fecha y hora', 'Paciente', 'Médico', 'Monto', ''] as $columna)
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $columna }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($citas as $cita)
                                <tr class="hover:bg-purple-50/30">
                                    <td class="px-4 py-3 font-mono font-semibold">{{ $cita->codigo }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $cita->fecha->format('d/m/Y') }} · {{ $cita->hora_inicio_corta }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-900">{{ $cita->paciente?->apellidos }}, {{ $cita->paciente?->nombres }}</p>
                                        <p class="text-xs text-gray-500">CI {{ $cita->paciente?->ci }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p>{{ $cita->medico?->nombre_completo }}</p>
                                        <p class="text-xs text-gray-500">{{ ucfirst($cita->especialidad?->nombre) }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-bold tabular-nums whitespace-nowrap">Bs {{ number_format((float) $cita->monto, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('cobros.create', $cita) }}" class="inline-flex px-3 py-1.5 bg-purple-800 hover:bg-purple-900 text-white text-xs font-semibold rounded-lg">Cobrar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay citas pendientes de cobro{{ $buscar ? ' con esa búsqueda' : '' }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($citas->hasPages())
                    <div class="px-4 py-3 border-t border-purple-100">{{ $citas->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

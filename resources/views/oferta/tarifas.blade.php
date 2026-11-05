{{-- CU11 · Gestionar tarifario: precio vigente por especialidad y registro de un precio nuevo --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-white border border-purple-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20';
    $etiqueta = 'text-xs font-semibold text-gray-600';
    $bs = fn ($monto) => 'Bs '.number_format((float) $monto, 2, ',', '.');
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-encabezado-modulo />
        <h2 class="text-3xl font-bold text-gray-900 leading-tight">Tarifario</h2>
        <p class="text-sm text-gray-500 mt-0.5">Precio de la consulta por especialidad. Las citas ya reservadas conservan su precio.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('oferta._tabs')
            @include('citas._alertas')

            {{-- Nuevo precio --}}
            <form method="POST" action="{{ route('tarifas.store') }}" id="form-tarifa" novalidate
                  class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-purple-100">
                @csrf
                <h3 class="font-bold text-gray-900">Nuevo precio</h3>
                <p class="text-xs text-gray-500 mb-3">El precio anterior se cierra automáticamente el día antes de que empiece el nuevo.</p>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-5">
                        <label for="id_especialidad" class="{{ $etiqueta }}">Especialidad</label>
                        <select id="id_especialidad" name="id_especialidad" required class="{{ $claseCampo }}">
                            <option value="">Seleccione…</option>
                            @foreach ($filas as $fila)
                                <option value="{{ $fila['especialidad']->id_especialidad }}" @selected((int) old('id_especialidad', request('especialidad')) === $fila['especialidad']->id_especialidad)>
                                    {{ ucfirst($fila['especialidad']->nombre) }}{{ $fila['especialidad']->estado ? '' : ' (inactiva)' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_especialidad')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="monto" class="{{ $etiqueta }}">Precio (Bs)</label>
                        <input id="monto" name="monto" type="text" inputmode="decimal" required value="{{ old('monto') }}" placeholder="120,00" class="{{ $claseCampo }} text-right tabular-nums">
                        <x-input-error :messages="$errors->get('monto')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-3">
                        <label for="vigente_desde" class="{{ $etiqueta }}">Vigente desde</label>
                        <input id="vigente_desde" name="vigente_desde" type="date" required min="{{ now()->toDateString() }}"
                               value="{{ old('vigente_desde', now()->toDateString()) }}" class="{{ $claseCampo }}">
                        <x-input-error :messages="$errors->get('vigente_desde')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold cursor-pointer">Guardar precio</button>
                    </div>
                </div>
                <p id="error-tarifa" class="hidden mt-2 text-sm text-rose-600"></p>
            </form>

            {{-- Precios vigentes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100 text-sm">
                        <thead class="bg-purple-50/60">
                            <tr>
                                @foreach (['Especialidad', 'Precio vigente', 'Desde', 'Próximo cambio', ''] as $columna)
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $columna }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @foreach ($filas as $fila)
                                <tr class="hover:bg-purple-50/30">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-900">{{ ucfirst($fila['especialidad']->nombre) }}</p>
                                        @unless ($fila['especialidad']->estado)
                                            <p class="text-xs text-gray-400">Inactiva</p>
                                        @endunless
                                    </td>
                                    <td class="px-4 py-3 font-bold tabular-nums {{ $fila['vigente'] ? 'text-gray-900' : 'text-rose-600' }}">
                                        {{ $fila['vigente'] ? $bs($fila['vigente']->monto) : 'Sin precio' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $fila['vigente']?->vigente_desde->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        @if ($fila['proxima'])
                                            <span class="font-semibold text-purple-900">{{ $bs($fila['proxima']->monto) }}</span> desde el {{ $fila['proxima']->vigente_desde->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('tarifas.show', $fila['especialidad']) }}" class="text-xs font-semibold text-purple-800 hover:underline">Historial de precios</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validación en el cliente (la del servidor es la definitiva).
        document.getElementById('form-tarifa').addEventListener('submit', function (e) {
            const monto = document.getElementById('monto').value.trim().replace(',', '.');
            const desde = document.getElementById('vigente_desde').value;
            const hoy = document.getElementById('vigente_desde').min;
            let mensaje = '';
            if (!document.getElementById('id_especialidad').value) mensaje = 'Seleccione la especialidad.';
            else if (!/^\d+(\.\d{1,2})?$/.test(monto) || Number(monto) <= 0) mensaje = 'El precio debe ser mayor a cero y tener como máximo dos decimales.';
            else if (!desde || desde < hoy) mensaje = 'El nuevo precio no puede empezar en una fecha pasada.';
            if (mensaje) {
                e.preventDefault();
                const error = document.getElementById('error-tarifa');
                error.textContent = mensaje;
                error.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>

{{-- CU18 · Gestionar caja: apertura, resumen del día, cierre con conteo por método y arqueo --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-white border border-purple-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20';
    $etiqueta = 'text-xs font-semibold text-gray-600';
    $bs = fn ($m) => 'Bs '.number_format((float) $m, 2, ',', '.');
    $nombres = ['EFECTIVO' => 'Efectivo', 'TARJETA' => 'Tarjeta', 'TRANSFERENCIA' => 'Transferencia'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Caja</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $caja ? 'Abierta desde el '.$caja->apertura->format('d/m/Y H:i') : 'No tiene una caja abierta' }}
                </p>
            </div>
            @if ($caja && Auth::user()->tienePermiso('caja.cobrar'))
                <a href="{{ route('cobros.index') }}" class="px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold">Cobrar citas →</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            @if (session('arqueo'))
                <a href="{{ route('caja.arqueo', session('arqueo')) }}" class="inline-flex px-4 py-2.5 bg-rose-700 hover:bg-rose-800 text-white text-sm rounded-xl font-semibold">Descargar arqueo (PDF)</a>
            @endif

            @unless ($caja)
                {{-- Apertura --}}
                <form method="POST" action="{{ route('caja.abrir') }}" id="form-abrir" class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 max-w-md">
                    @csrf
                    <h3 class="font-bold text-gray-900">Abrir caja</h3>
                    <p class="text-xs text-gray-500 mb-3">Indique el efectivo con el que empieza el día (fondo de cambio).</p>
                    <label for="monto_inicial" class="{{ $etiqueta }}">Monto inicial (Bs)</label>
                    <input id="monto_inicial" name="monto_inicial" type="number" step="0.01" min="0" required value="{{ old('monto_inicial', '200.00') }}" class="{{ $claseCampo }} text-right tabular-nums">
                    <x-input-error :messages="$errors->get('monto_inicial')" class="mt-1" />
                    <button type="submit" class="mt-4 w-full px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold cursor-pointer">Abrir caja</button>
                </form>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    {{-- Resumen por método --}}
                    <div class="lg:col-span-2 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @foreach ($resumen['metodos'] as $metodo => $datos)
                                <div class="bg-white p-4 rounded-2xl shadow-sm border border-purple-100">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $nombres[$metodo] }}</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">{{ $bs($datos['esperado']) }}</p>
                                    <p class="text-xs text-gray-500">
                                        Cobros {{ $bs($datos['cobros']) }}
                                        @if ($datos['reembolsos'] > 0) · reembolsos {{ $bs($datos['reembolsos']) }} @endif
                                        @if ($metodo === 'EFECTIVO') · inicial {{ $bs($caja->monto_inicial) }} @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
                            <div class="px-5 py-3 border-b border-purple-100 font-bold text-gray-900">Movimientos de la caja</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-purple-50 text-sm">
                                    <thead class="bg-purple-50/60">
                                        <tr>
                                            @foreach (['Hora', 'Cita', 'Paciente', 'Tipo', 'Método', 'Monto', 'Comprobante'] as $columna)
                                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $columna }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-purple-50">
                                        @forelse ($resumen['movimientos'] as $mov)
                                            <tr>
                                                <td class="px-4 py-2.5 tabular-nums">{{ $mov->fecha_hora->format('H:i') }}</td>
                                                <td class="px-4 py-2.5 font-mono">{{ $mov->cita?->codigo }}</td>
                                                <td class="px-4 py-2.5">{{ $mov->cita?->paciente?->apellidos }}, {{ $mov->cita?->paciente?->nombres }}</td>
                                                <td class="px-4 py-2.5 {{ $mov->tipo === 'REEMBOLSO' ? 'text-rose-600 font-semibold' : '' }}">{{ $mov->tipo === 'COBRO' ? 'Cobro' : 'Reembolso' }}</td>
                                                <td class="px-4 py-2.5">{{ $nombres[$mov->metodo] ?? $mov->metodo }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $mov->tipo === 'REEMBOLSO' ? '−' : '' }}{{ $bs($mov->monto) }}</td>
                                                <td class="px-4 py-2.5">
                                                    @if ($mov->comprobante)
                                                        <a href="{{ route('cobros.comprobante.ver', $mov) }}" class="text-purple-800 font-semibold hover:underline">N.º {{ $mov->comprobante->numero }}</a>
                                                    @else — @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Todavía no hay cobros en esta caja.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Cierre --}}
                    <form method="POST" action="{{ route('caja.cerrar') }}" id="form-cerrar" class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 space-y-3 h-fit"
                          onsubmit="return confirm('¿Cerrar la caja? Una caja cerrada ya no se puede modificar.');">
                        @csrf
                        <h3 class="font-bold text-gray-900">Cerrar caja (arqueo)</h3>
                        <p class="text-xs text-gray-500">Cuente el dinero de cada método. Si no cuadra con lo esperado, explique la diferencia.</p>
                        @foreach ($resumen['metodos'] as $metodo => $datos)
                            <div>
                                <label for="contado_{{ $metodo }}" class="{{ $etiqueta }}">{{ $nombres[$metodo] }} contado <span class="font-normal text-gray-400">(esperado {{ $bs($datos['esperado']) }})</span></label>
                                <input id="contado_{{ $metodo }}" name="contado[{{ $metodo }}]" type="number" step="0.01" min="0" required
                                       data-esperado="{{ $datos['esperado'] }}" value="{{ old("contado.$metodo") }}" class="campo-contado {{ $claseCampo }} text-right tabular-nums">
                            </div>
                        @endforeach
                        <x-input-error :messages="$errors->get('contado.*')" />
                        <p id="diferencia" class="text-sm font-semibold text-gray-700">Diferencia: Bs 0,00</p>
                        <div>
                            <label for="observacion" class="{{ $etiqueta }}">Observación <span id="obs-obligatoria" class="hidden text-rose-600">(obligatoria: hay diferencia)</span></label>
                            <textarea id="observacion" name="observacion" rows="2" maxlength="300" class="{{ $claseCampo }}">{{ old('observacion') }}</textarea>
                            <x-input-error :messages="$errors->get('observacion')" class="mt-1" />
                        </div>
                        <p id="error-cierre" class="hidden text-sm text-rose-600"></p>
                        <button type="submit" class="w-full px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold cursor-pointer">Cerrar caja</button>
                    </form>
                </div>
            @endunless

            {{-- Cajas cerradas --}}
            @if ($historial->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-purple-100 font-bold text-gray-900">Cajas cerradas</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-purple-50 text-sm">
                            <thead class="bg-purple-50/60">
                                <tr>
                                    @foreach (['Usuario', 'Apertura', 'Cierre', 'Esperado', 'Contado', 'Diferencia', 'Observación', ''] as $columna)
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $columna }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-purple-50">
                                @foreach ($historial as $cerrada)
                                    <tr>
                                        <td class="px-4 py-2.5">{{ $cerrada->usuario?->username }}</td>
                                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $cerrada->apertura->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $cerrada->cierre?->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums">{{ $bs($cerrada->monto_esperado) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums">{{ $bs($cerrada->monto_contado) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums font-semibold {{ (float) $cerrada->diferencia == 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $bs($cerrada->diferencia) }}</td>
                                        <td class="px-4 py-2.5 text-gray-600 min-w-48">{{ $cerrada->observacion ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-right"><a href="{{ route('caja.arqueo', $cerrada) }}" class="text-purple-800 font-semibold hover:underline whitespace-nowrap">Arqueo PDF</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($historial->hasPages())
                        <div class="px-4 py-3 border-t border-purple-100">{{ $historial->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($caja)
        <script>
            // Cálculo en vivo de la diferencia y validación en el cliente.
            const campos = [...document.querySelectorAll('.campo-contado')];
            const formato = (n) => 'Bs ' + n.toFixed(2).replace('.', ',');
            function diferencia() {
                const dif = campos.reduce((t, c) => t + (parseFloat(c.value || 0) - parseFloat(c.dataset.esperado)), 0);
                const texto = document.getElementById('diferencia');
                texto.textContent = 'Diferencia: ' + (dif > 0 ? 'sobrante ' : dif < 0 ? 'faltante ' : '') + formato(Math.abs(dif));
                texto.className = 'text-sm font-semibold ' + (Math.abs(dif) < 0.005 ? 'text-emerald-700' : 'text-rose-600');
                document.getElementById('obs-obligatoria').classList.toggle('hidden', campos.every(c => Math.abs(parseFloat(c.value || 0) - parseFloat(c.dataset.esperado)) < 0.005));
                return dif;
            }
            campos.forEach(c => c.addEventListener('input', diferencia));
            document.getElementById('form-cerrar').addEventListener('submit', function (e) {
                const error = document.getElementById('error-cierre');
                let mensaje = '';
                if (campos.some(c => c.value === '' || parseFloat(c.value) < 0)) mensaje = 'Ingrese el monto contado de cada método (0 si no hubo).';
                else if (!document.getElementById('obs-obligatoria').classList.contains('hidden') && !document.getElementById('observacion').value.trim()) mensaje = 'Explique el sobrante o faltante en la observación.';
                if (mensaje) { e.preventDefault(); e.stopImmediatePropagation(); error.textContent = mensaje; error.classList.remove('hidden'); }
            }, true);
            diferencia();
        </script>
    @endif
</x-app-layout>

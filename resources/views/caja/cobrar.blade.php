{{-- CU19 · Cobrar una cita: método, monto recibido y cambio --}}
@php
    $monto = (float) $cita->monto;
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-white border border-purple-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('cobros.index') }}" class="p-2 text-purple-700 hover:bg-purple-50 rounded-xl" title="Volver">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Cobrar cita {{ $cita->codigo }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $cita->paciente?->apellidos }}, {{ $cita->paciente?->nombres }} · {{ $cita->fecha->format('d/m/Y') }} {{ $cita->hora_inicio_corta }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            <form method="POST" action="{{ route('cobros.store', $cita) }}" id="form-cobro" class="bg-white p-6 rounded-2xl shadow-sm border border-purple-100 space-y-4">
                @csrf
                <div class="flex items-baseline justify-between border-b border-purple-100 pb-3">
                    <span class="text-sm text-gray-600">{{ ucfirst($cita->especialidad?->nombre) }} · {{ $cita->medico?->nombre_completo }}</span>
                    <span class="text-2xl font-bold text-gray-900 tabular-nums">Bs {{ number_format($monto, 2, ',', '.') }}</span>
                </div>

                <fieldset>
                    <legend class="text-xs font-semibold text-gray-600">Método de pago</legend>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach (['EFECTIVO' => 'Efectivo', 'TARJETA' => 'Tarjeta', 'TRANSFERENCIA' => 'Transferencia'] as $valor => $texto)
                            <label class="flex items-center justify-center gap-2 px-3 py-2.5 border border-purple-200 rounded-xl text-sm cursor-pointer has-[:checked]:bg-purple-800 has-[:checked]:text-white has-[:checked]:border-purple-800">
                                <input type="radio" name="metodo" value="{{ $valor }}" class="sr-only" @checked(old('metodo', 'EFECTIVO') === $valor)>
                                {{ $texto }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('metodo')" class="mt-1" />
                </fieldset>

                <div id="bloque-efectivo">
                    <label for="recibido" class="text-xs font-semibold text-gray-600">Monto recibido (Bs)</label>
                    <input id="recibido" name="recibido" type="number" step="0.01" min="{{ $monto }}" value="{{ old('recibido') }}" class="{{ $claseCampo }} text-right tabular-nums">
                    <x-input-error :messages="$errors->get('recibido')" class="mt-1" />
                    <p id="cambio" class="mt-2 text-sm font-semibold text-gray-700">Cambio: Bs 0,00</p>
                </div>

                <x-input-error :messages="$errors->get('cita')" />
                <x-input-error :messages="$errors->get('caja')" />
                <p id="error-cobro" class="hidden text-sm text-rose-600"></p>

                <button type="submit" class="w-full px-4 py-3 bg-purple-800 hover:bg-purple-900 text-white rounded-xl font-semibold cursor-pointer">Registrar cobro y emitir comprobante</button>
            </form>
        </div>
    </div>

    <script>
        const monto = {{ $monto }};
        const recibido = document.getElementById('recibido');
        const metodo = () => document.querySelector('input[name=metodo]:checked')?.value;
        function actualizar() {
            const efectivo = metodo() === 'EFECTIVO';
            document.getElementById('bloque-efectivo').classList.toggle('hidden', !efectivo);
            const cambio = Math.max(0, (parseFloat(recibido.value) || 0) - monto);
            document.getElementById('cambio').textContent = 'Cambio: Bs ' + cambio.toFixed(2).replace('.', ',');
        }
        document.querySelectorAll('input[name=metodo]').forEach(r => r.addEventListener('change', actualizar));
        recibido.addEventListener('input', actualizar);
        document.getElementById('form-cobro').addEventListener('submit', function (e) {
            if (metodo() === 'EFECTIVO' && !((parseFloat(recibido.value) || 0) >= monto)) {
                e.preventDefault();
                const error = document.getElementById('error-cobro');
                error.textContent = 'El monto recibido debe cubrir el precio de la cita.';
                error.classList.remove('hidden');
            }
        });
        actualizar();
    </script>
</x-app-layout>

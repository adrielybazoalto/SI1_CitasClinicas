{{-- CU19 · Cobrar una cita: método (Efectivo, Tarjeta o QR), monto recibido y cambio, o QR con confirmación --}}
@php
    $monto = (float) $cita->monto;
    $montoTexto = number_format($monto, 2, ',', '.');
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

    {{-- Aparición suave de la sección del método elegido (CSS propio: no depende de compilar Tailwind) --}}
    <style>
        .bloque-metodo { animation: aparecer-metodo .2s ease-out; }
        @keyframes aparecer-metodo { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
    </style>

    <div class="py-6 sm:py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            <form method="POST" action="{{ route('cobros.store', $cita) }}" id="form-cobro" class="bg-white p-6 rounded-2xl shadow-sm border border-purple-100 space-y-4">
                @csrf
                <div class="flex items-baseline justify-between border-b border-purple-100 pb-3">
                    <span class="text-sm text-gray-600">{{ ucfirst($cita->especialidad?->nombre) }} · {{ $cita->medico?->nombre_completo }}</span>
                    <span class="text-2xl font-bold text-gray-900 tabular-nums">Bs {{ $montoTexto }}</span>
                </div>

                <fieldset>
                    <legend class="text-xs font-semibold text-gray-600">Método de pago</legend>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach (\App\Services\PagoService::METODOS_MOSTRADOR as $valor)
                            <label class="flex items-center justify-center gap-2 px-3 py-2.5 border border-purple-200 rounded-xl text-sm cursor-pointer has-[:checked]:bg-purple-800 has-[:checked]:text-white has-[:checked]:border-purple-800">
                                <input type="radio" name="metodo" value="{{ $valor }}" class="sr-only" @checked(old('metodo', 'EFECTIVO') === $valor)>
                                {{ \App\Services\PagoService::nombreMetodo($valor) }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('metodo')" class="mt-1" />
                </fieldset>

                {{-- Efectivo: monto recibido y cambio --}}
                <div id="bloque-efectivo" data-metodo="EFECTIVO" class="bloque-metodo">
                    <label for="recibido" class="text-xs font-semibold text-gray-600">Monto recibido (Bs)</label>
                    <input id="recibido" name="recibido" type="number" step="0.01" min="{{ $monto }}" value="{{ old('recibido') }}" class="{{ $claseCampo }} text-right tabular-nums">
                    <x-input-error :messages="$errors->get('recibido')" class="mt-1" />
                    <p id="cambio" class="mt-2 text-sm font-semibold text-gray-700">Cambio: Bs 0,00</p>
                </div>

                {{-- Tarjeta: se cobra en el POS y se registra con el voucher aprobado --}}
                <div id="bloque-tarjeta" data-metodo="TARJETA" class="bloque-metodo hidden p-3.5 bg-purple-50/60 border border-purple-100 rounded-xl text-sm text-gray-700">
                    Cobre <strong class="tabular-nums">Bs {{ $montoTexto }}</strong> en el POS y registre el cobro cuando el voucher salga aprobado.
                </div>

                {{-- QR: misma estructura que en CU14 (código, monto exacto, pasos y confirmación) --}}
                <div id="bloque-qr" data-metodo="QR" class="bloque-metodo hidden p-4 bg-white border border-purple-100 rounded-2xl">
                    <div class="flex flex-col sm:flex-row gap-4 items-center sm:items-start">
                        <div class="flex flex-col items-center text-center shrink-0">
                            <canvas data-qr="{{ $qr }}" class="rounded-lg border border-purple-100" width="220" height="220" aria-label="Código QR de pago de la cita {{ $cita->codigo }}"></canvas>
                            <p class="mt-2 text-xs text-gray-500">Monto exacto</p>
                            <p class="text-2xl font-bold text-gray-900 tabular-nums">Bs {{ $montoTexto }}</p>
                        </div>
                        <div class="text-sm text-gray-700 w-full">
                            <h3 class="font-bold text-gray-900">Cobro con QR</h3>
                            <ol class="mt-2 space-y-1.5 list-decimal pl-5">
                                <li>Muestre el código al paciente para que lo escanee con la app de su banco.</li>
                                <li>Verifique que pague exactamente <strong class="tabular-nums">Bs {{ $montoTexto }}</strong>.</li>
                                <li>Espere la confirmación del banco en la pantalla del paciente.</li>
                                <li>Marque la casilla y registre el cobro.</li>
                            </ol>
                        </div>
                    </div>
                    <p class="mt-3 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1">
                        Simulación de sandbox: el QR no está conectado a un banco real; la confirmación la hace la recepcionista.
                    </p>
                    <label class="mt-3 flex items-start gap-2.5 p-3 bg-purple-50/60 border border-purple-100 rounded-xl cursor-pointer">
                        <input type="checkbox" id="confirmo_qr" name="confirmo_qr" value="1" @checked(old('confirmo_qr')) class="mt-0.5 rounded border-purple-300 text-purple-800 focus:ring-purple-500">
                        <span class="text-sm font-semibold text-gray-800">Confirmo que recibí el pago por QR de Bs {{ $montoTexto }}.</span>
                    </label>
                    <x-input-error :messages="$errors->get('confirmo_qr')" class="mt-1" />
                </div>

                <x-input-error :messages="$errors->get('cita')" />
                <x-input-error :messages="$errors->get('caja')" />
                <p id="error-cobro" class="hidden text-sm text-rose-600"></p>

                <button type="submit" id="btn-cobrar" class="w-full px-4 py-3 bg-purple-800 hover:bg-purple-900 text-white rounded-xl font-semibold cursor-pointer">Registrar cobro y emitir comprobante</button>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const monto = {{ $monto }};
            const form = document.getElementById('form-cobro');
            const recibido = document.getElementById('recibido');
            const confirmoQr = document.getElementById('confirmo_qr');
            const boton = document.getElementById('btn-cobrar');
            const error = document.getElementById('error-cobro');
            const bloques = document.querySelectorAll('[data-metodo]');
            const metodo = () => document.querySelector('input[name=metodo]:checked')?.value;

            function actualizar() {
                const actual = metodo();
                // Solo se ve la sección del método elegido; los campos ocultos se desactivan
                // para que no se envíen ni bloqueen la validación del navegador.
                bloques.forEach(bloque => {
                    const visible = bloque.dataset.metodo === actual;
                    bloque.classList.toggle('hidden', !visible);
                    bloque.querySelectorAll('input').forEach(campo => { campo.disabled = !visible; });
                });

                const cambio = Math.max(0, (parseFloat(recibido.value) || 0) - monto);
                document.getElementById('cambio').textContent = 'Cambio: Bs ' + cambio.toFixed(2).replace('.', ',');

                // Con QR, el botón se habilita recién al confirmar que el pago llegó.
                const bloqueado = actual === 'QR' && !confirmoQr.checked;
                boton.disabled = bloqueado;
                boton.classList.toggle('opacity-50', bloqueado);
                boton.classList.toggle('cursor-not-allowed', bloqueado);
                error.classList.add('hidden');
            }

            document.querySelectorAll('input[name=metodo]').forEach(r => r.addEventListener('change', actualizar));
            recibido.addEventListener('input', actualizar);
            confirmoQr.addEventListener('change', actualizar);
            form.addEventListener('submit', function (e) {
                if (metodo() === 'EFECTIVO' && !((parseFloat(recibido.value) || 0) >= monto)) {
                    e.preventDefault();
                    error.textContent = 'El monto recibido debe cubrir el precio de la cita.';
                    error.classList.remove('hidden');
                } else if (metodo() === 'QR' && !confirmoQr.checked) {
                    e.preventDefault();
                    error.textContent = 'Confirme que recibió el pago por QR antes de registrar el cobro.';
                    error.classList.remove('hidden');
                }
            });
            actualizar();
        })();
    </script>
</x-app-layout>

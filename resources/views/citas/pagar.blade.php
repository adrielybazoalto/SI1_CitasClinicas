{{-- CU14 · Pagar una cita en línea: contador de la retención (RN20), tarjeta con Stripe Elements y QR simulado --}}
<x-app-layout>
    <x-slot name="header">
        <x-encabezado-modulo />
        <h2 class="text-3xl font-bold text-gray-900 leading-tight">Pagar cita {{ $cita->codigo }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">{{ ucfirst($cita->especialidad?->nombre) }} · {{ $cita->medico?->nombre_completo }} · {{ $cita->fecha->format('d/m/Y') }} {{ $cita->hora_inicio_corta }}</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('citas._alertas')

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total a pagar</p>
                    <p class="text-3xl font-bold text-gray-900 tabular-nums">Bs {{ number_format((float) $cita->monto, 2, ',', '.') }}</p>
                </div>
                @if ($cita->vence_pago_en)
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tiempo para pagar</p>
                        <p id="contador" data-vence="{{ $cita->vence_pago_en->toIso8601String() }}" class="text-3xl font-bold text-amber-600 tabular-nums">--:--</p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Tarjeta con Stripe Elements: los datos de la tarjeta van directo a Stripe, nunca a este servidor --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 flex flex-col">
                    <h3 class="font-bold text-gray-900">Tarjeta de crédito o débito</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Se cobrará <strong>{{ $stripeMoneda }} {{ number_format($stripeMonto, 2, ',', '.') }}</strong>@if ($stripeMoneda === 'USD') (tipo de cambio {{ number_format((float) config('clinica.tipo_cambio_usd'), 2, ',', '.') }})@endif.
                        Pago procesado por Stripe.
                    </p>
                    @if ($stripeActivo)
                        <form id="form-stripe" method="POST" action="{{ route('pagos.stripe.confirmar', $cita) }}" class="mt-4 space-y-3 bloquear-al-vencer" novalidate>
                            @csrf
                            <input type="hidden" name="payment_intent" id="payment_intent">
                            <div>
                                <label for="titular" class="text-xs font-semibold uppercase text-purple-950/80">Nombre del titular</label>
                                <input id="titular" type="text" autocomplete="cc-name" maxlength="80" required
                                       value="{{ trim(($cita->paciente?->nombres ?? '').' '.($cita->paciente?->apellidos ?? '')) }}"
                                       class="mt-1 block w-full px-3.5 py-2.5 bg-white border border-purple-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20">
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase text-purple-950/80">Número de tarjeta</label>
                                <div id="stripe-numero" class="mt-1 px-3.5 py-3 bg-white border border-purple-200 rounded-xl"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-semibold uppercase text-purple-950/80">Vencimiento</label>
                                    <div id="stripe-vencimiento" class="mt-1 px-3.5 py-3 bg-white border border-purple-200 rounded-xl"></div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold uppercase text-purple-950/80">CVC</label>
                                    <div id="stripe-cvc" class="mt-1 px-3.5 py-3 bg-white border border-purple-200 rounded-xl"></div>
                                </div>
                            </div>
                            <p id="stripe-error" class="hidden text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-2.5" role="alert"></p>
                            <button type="submit" id="submit-button" class="btn-pagar-stripe w-full px-4 py-3 bg-purple-800 hover:bg-purple-900 text-white rounded-xl font-bold cursor-pointer">
                                Pagar {{ $stripeMoneda }} {{ number_format($stripeMonto, 2, ',', '.') }}
                            </button>
                        </form>
                        @if ($stripePrueba)
                            <p class="mt-3 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1.5">
                                Modo de pruebas de Stripe: no se debita dinero real. Use la tarjeta <span class="font-mono">4242 4242 4242 4242</span>, cualquier fecha futura y cualquier CVC.
                            </p>
                        @endif
                    @else
                        <p class="mt-auto pt-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3">El pago con tarjeta no está configurado en este servidor. Use el pago con QR.</p>
                    @endif
                </div>

                {{-- QR --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 flex flex-col items-center text-center">
                    <h3 class="font-bold text-gray-900 self-start">QR</h3>
                    <p class="text-sm text-gray-500 mt-1 self-start">Escanee el código con la app de su banco.</p>
                    <canvas data-qr="{{ $qr }}" class="mt-3 rounded-lg border border-purple-100" width="220" height="220" aria-label="Código QR de pago de la cita {{ $cita->codigo }}"></canvas>
                    <p class="mt-2 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1">
                        Simulación de sandbox: no se debita dinero real. «Verificar pago» hace de confirmación del banco.
                    </p>
                    <form method="POST" action="{{ route('pagos.qr', $cita) }}" class="mt-4 w-full bloquear-al-vencer">
                        @csrf
                        <button type="submit" class="w-full px-4 py-3 bg-purple-800 hover:bg-purple-900 text-white rounded-xl font-semibold cursor-pointer">Verificar pago</button>
                    </form>
                </div>
            </div>

            <p id="vencido" class="hidden p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm">
                El tiempo para pagar venció y la reserva se liberará. Vuelva a programar la cita.
            </p>
        </div>
    </div>

    <script>
        // Contador regresivo de la retención (RN20).
        const contador = document.getElementById('contador');
        if (contador) {
            const vence = new Date(contador.dataset.vence).getTime();
            const tic = () => {
                const resta = Math.max(0, Math.floor((vence - Date.now()) / 1000));
                contador.textContent = String(Math.floor(resta / 60)).padStart(2, '0') + ':' + String(resta % 60).padStart(2, '0');
                if (resta <= 60) contador.classList.replace('text-amber-600', 'text-rose-600');
                if (resta === 0) {
                    document.querySelectorAll('.bloquear-al-vencer button').forEach(b => { b.disabled = true; b.classList.add('opacity-50', 'cursor-not-allowed'); });
                    document.getElementById('vencido').classList.remove('hidden');
                    clearInterval(intervalo);
                }
            };
            const intervalo = setInterval(tic, 1000);
            tic();
        }
    </script>
    @if ($stripeActivo)
        {{-- Stripe.js debe cargarse siempre desde js.stripe.com (requisito de Stripe) --}}
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            (() => {
                const form = document.getElementById('form-stripe');
                const boton = document.getElementById('submit-button');
                const error = document.getElementById('stripe-error');
                const textoBoton = boton.textContent;
                const mostrarError = (mensaje) => { error.textContent = mensaje; error.classList.toggle('hidden', !mensaje); };

                if (typeof Stripe === 'undefined') {
                    mostrarError('No se pudo cargar Stripe. Revise su conexión o use el pago con QR.');
                    boton.disabled = true;
                    return;
                }

                const stripe = Stripe(@js($stripeKey), { locale: 'es' });
                const elements = stripe.elements({ locale: 'es' });
                const estilo = { base: { fontSize: '14px', color: '#1f2937', '::placeholder': { color: '#9ca3af' } }, invalid: { color: '#be123c' } };
                const numero = elements.create('cardNumber', { style: estilo, showIcon: true });
                const vencimiento = elements.create('cardExpiry', { style: estilo });
                const cvc = elements.create('cardCvc', { style: estilo });
                numero.mount('#stripe-numero');
                vencimiento.mount('#stripe-vencimiento');
                cvc.mount('#stripe-cvc');
                // Stripe valida en el cliente número, vencimiento y CVC mientras se escribe.
                [numero, vencimiento, cvc].forEach(campo => campo.on('change', e => mostrarError(e.error ? e.error.message : '')));

                form.addEventListener('submit', async (evento) => {
                    evento.preventDefault();
                    const titular = document.getElementById('titular').value.trim();
                    if (!titular) { mostrarError('Escriba el nombre del titular de la tarjeta.'); return; }

                    mostrarError('');
                    boton.disabled = true;
                    boton.classList.add('opacity-50');
                    boton.textContent = 'Procesando pago…';
                    const restablecer = (mensaje) => { mostrarError(mensaje); boton.disabled = false; boton.classList.remove('opacity-50'); boton.textContent = textoBoton; };

                    try {
                        // 1) El servidor crea el PaymentIntent de esta cita.
                        const respuesta = await fetch(@js(route('pagos.stripe.intencion', $cita)), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value, 'Accept': 'application/json' },
                        });
                        const datos = await respuesta.json().catch(() => ({}));
                        if (!respuesta.ok || !datos.client_secret) {
                            restablecer(datos.mensaje || datos.message || 'No se pudo iniciar el pago. Intente de nuevo.');
                            return;
                        }

                        // 2) Stripe autoriza la tarjeta (y pide 3-D Secure si el banco lo exige).
                        const { error: fallo, paymentIntent } = await stripe.confirmCardPayment(datos.client_secret, {
                            payment_method: { card: numero, billing_details: { name: titular } },
                        });
                        if (fallo) { restablecer(fallo.message); return; }

                        // 3) El servidor captura el cobro, marca la cita como Pagada y emite el comprobante.
                        document.getElementById('payment_intent').value = paymentIntent.id;
                        form.submit();
                    } catch (e) {
                        restablecer('Ocurrió un problema de conexión. No se realizó ningún cobro; intente de nuevo.');
                    }
                });
            })();
        </script>
    @endif
</x-app-layout>

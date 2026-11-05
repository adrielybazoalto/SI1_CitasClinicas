{{-- CU14 · Pagar una cita en línea: contador de la retención (RN20), PayPal Sandbox y QR simulado --}}
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
                {{-- PayPal --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-purple-100 flex flex-col">
                    <h3 class="font-bold text-gray-900">PayPal</h3>
                    <p class="text-sm text-gray-500 mt-1">Se cobrará el equivalente de <strong>USD {{ $montoUsd }}</strong> (tipo de cambio {{ number_format((float) config('clinica.paypal_tipo_cambio'), 2, ',', '.') }}).</p>
                    @if ($paypalActivo)
                        <form method="POST" action="{{ route('pagos.paypal', $cita) }}" class="mt-auto pt-4 bloquear-al-vencer">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3 bg-[#FFC439] hover:bg-[#F2BA36] text-[#003087] rounded-xl font-bold cursor-pointer">Pagar con PayPal</button>
                        </form>
                        <p class="mt-2 text-xs text-gray-400">Entorno de pruebas (PayPal Sandbox).</p>
                    @else
                        <p class="mt-auto pt-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3">PayPal no está configurado en este servidor. Use el pago con QR.</p>
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
</x-app-layout>

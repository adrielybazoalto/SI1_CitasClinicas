{{-- CU18 · Reporte de arqueo de caja (dompdf) --}}
@php
    $bs = fn ($m) => 'Bs '.number_format((float) $m, 2, ',', '.');
    $nombres = ['EFECTIVO' => 'Efectivo', 'TARJETA' => 'Tarjeta', 'TRANSFERENCIA' => 'Transferencia'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Arqueo de caja {{ $caja->id_caja }}</title>
    <style>
        @page { margin: 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; }
        .cab { border-bottom: 2px solid #5a215e; padding-bottom: 8px; margin-bottom: 12px; }
        .logo { display: inline-block; width: 30px; height: 30px; background: #5a215e; border-radius: 7px; position: relative; vertical-align: middle; }
        .logo .v { position: absolute; left: 12px; top: 5px; width: 6px; height: 20px; background: #fff; }
        .logo .h { position: absolute; left: 5px; top: 12px; width: 20px; height: 6px; background: #fff; }
        .marca { display: inline-block; vertical-align: middle; margin-left: 6px; }
        .marca b { font-size: 14px; color: #2e0b31; }
        .emision { float: right; text-align: right; color: #6b7280; font-size: 8px; }
        h1 { font-size: 13px; color: #2e0b31; margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #f4e9f4; color: #4a1a4e; text-align: left; font-size: 8px; text-transform: uppercase; padding: 5px; }
        td { padding: 4px 5px; border-bottom: 1px solid #f3f4f6; }
        .num { text-align: right; }
        .total td { font-weight: bold; background: #e8d2e8; color: #2e0b31; }
    </style>
</head>
<body>
    <div class="cab">
        <div class="emision">Emitido: {{ $emitido->format('d/m/Y H:i') }}</div>
        <div class="logo"><div class="v"></div><div class="h"></div></div>
        <div class="marca"><b>Clínica Vida Salud</b><br>Arqueo de caja</div>
    </div>

    <h1>Caja N.º {{ $caja->id_caja }} · {{ $caja->usuario->username }}</h1>
    <p>Apertura: {{ $caja->apertura->format('d/m/Y H:i') }} · Cierre: {{ $caja->cierre?->format('d/m/Y H:i') ?? 'abierta' }} · Monto inicial: {{ $bs($caja->monto_inicial) }}</p>

    <table>
        <thead><tr><th>Método</th><th class="num">Cobros</th><th class="num">Reembolsos</th><th class="num">Esperado</th></tr></thead>
        <tbody>
            @foreach ($resumen['metodos'] as $metodo => $d)
                <tr><td>{{ $nombres[$metodo] }}</td><td class="num">{{ $bs($d['cobros']) }}</td><td class="num">{{ $bs($d['reembolsos']) }}</td><td class="num">{{ $bs($d['esperado']) }}</td></tr>
            @endforeach
            <tr class="total"><td>Total</td><td class="num">{{ $bs($resumen['total_cobrado']) }}</td><td class="num">{{ $bs(array_sum(array_column($resumen['metodos'], 'reembolsos'))) }}</td><td class="num">{{ $bs($caja->monto_esperado ?? array_sum(array_column($resumen['metodos'], 'esperado'))) }}</td></tr>
        </tbody>
    </table>

    @if ($caja->estado === 'CERRADA')
        <table>
            <tr><td>Monto contado</td><td class="num">{{ $bs($caja->monto_contado) }}</td></tr>
            <tr class="total"><td>Diferencia</td><td class="num">{{ $bs($caja->diferencia) }}</td></tr>
            <tr><td>Observación</td><td>{{ $caja->observacion ?? '—' }}</td></tr>
        </table>
    @endif

    <table>
        <thead><tr><th>Hora</th><th>Cita</th><th>Paciente</th><th>Tipo</th><th>Método</th><th>Comprobante</th><th class="num">Monto</th></tr></thead>
        <tbody>
            @forelse ($resumen['movimientos'] as $mov)
                <tr>
                    <td>{{ $mov->fecha_hora->format('d/m H:i') }}</td>
                    <td>{{ $mov->cita?->codigo }}</td>
                    <td>{{ $mov->cita?->paciente?->apellidos }}, {{ $mov->cita?->paciente?->nombres }}</td>
                    <td>{{ $mov->tipo === 'COBRO' ? 'Cobro' : 'Reembolso' }}</td>
                    <td>{{ $nombres[$mov->metodo] ?? $mov->metodo }}</td>
                    <td>{{ $mov->comprobante?->numero ?? '—' }}</td>
                    <td class="num">{{ $mov->tipo === 'REEMBOLSO' ? '−' : '' }}{{ $bs($mov->monto) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Sin movimientos.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

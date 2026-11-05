{{-- CU19 · Comprobante interno de pago (A5, dompdf) --}}
@php $cita = $pago->cita; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $pago->comprobante->numero }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .cab { border-bottom: 2px solid #5a215e; padding-bottom: 8px; margin-bottom: 12px; }
        .logo { display: inline-block; width: 30px; height: 30px; background: #5a215e; border-radius: 7px; position: relative; vertical-align: middle; }
        .logo .v { position: absolute; left: 12px; top: 5px; width: 6px; height: 20px; background: #fff; }
        .logo .h { position: absolute; left: 5px; top: 12px; width: 20px; height: 6px; background: #fff; }
        .marca { display: inline-block; vertical-align: middle; margin-left: 6px; }
        .marca b { font-size: 14px; color: #2e0b31; }
        .numero { float: right; text-align: right; }
        .numero b { font-size: 14px; color: #5a215e; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
        td.e { color: #6b7280; width: 38%; }
        .total td { font-size: 14px; font-weight: bold; color: #2e0b31; border-top: 2px solid #5a215e; border-bottom: none; padding-top: 8px; }
        .pie { margin-top: 18px; font-size: 8px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="cab">
        <div class="numero">COMPROBANTE<br><b>N.º {{ $pago->comprobante->numero }}</b><br>{{ $pago->comprobante->fecha_emision->format('d/m/Y H:i') }}</div>
        <div class="logo"><div class="v"></div><div class="h"></div></div>
        <div class="marca"><b>Clínica Vida Salud</b><br>Comprobante interno de pago</div>
    </div>

    <table>
        <tr><td class="e">Paciente</td><td>{{ $cita->paciente->apellidos }}, {{ $cita->paciente->nombres }} · CI {{ $cita->paciente->ci }}</td></tr>
        <tr><td class="e">Cita</td><td>{{ $cita->codigo }} · {{ $cita->fecha->format('d/m/Y') }} {{ $cita->hora_inicio_corta }}</td></tr>
        <tr><td class="e">Especialidad</td><td>{{ ucfirst($cita->especialidad->nombre) }}</td></tr>
        <tr><td class="e">Médico</td><td>{{ $cita->medico->nombre_completo }}</td></tr>
        <tr><td class="e">Método de pago</td><td>{{ ucfirst(mb_strtolower($pago->metodo)) }}{{ $pago->id_transaccion ? ' · transacción '.$pago->id_transaccion : '' }}</td></tr>
        <tr><td class="e">Cobrado por</td><td>{{ $pago->registrador?->username ?? 'Pago en línea' }}</td></tr>
        <tr class="total"><td>Total pagado</td><td>Bs {{ number_format((float) $pago->monto, 2, ',', '.') }}</td></tr>
    </table>

    <p class="pie">Documento interno de la Clínica Vida Salud. No válido como factura.</p>
</body>
</html>

{{-- CU21 · Receta médica de una atención registrada (A5, dompdf) --}}
@php $paciente = $cita->paciente; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta {{ $cita->codigo }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        /* Cabecera en tabla (no float), igual que el comprobante. */
        .cab { margin-bottom: 12px; }
        .cab td { border-bottom: 2px solid #5a215e; padding: 0 0 8px; vertical-align: middle; }
        .logo { display: inline-block; width: 30px; height: 30px; background: #5a215e; border-radius: 7px; position: relative; vertical-align: middle; }
        .logo .v { position: absolute; left: 12px; top: 5px; width: 6px; height: 20px; background: #fff; }
        .logo .h { position: absolute; left: 5px; top: 12px; width: 20px; height: 6px; background: #fff; }
        .marca { display: inline-block; vertical-align: middle; margin-left: 6px; }
        .marca b { font-size: 14px; color: #2e0b31; }
        .numero { text-align: right; line-height: 1.45; }
        .numero b { font-size: 14px; color: #5a215e; }
        .numero .fecha { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px 0; border-bottom: 1px solid #f3f4f6; }
        td.e { color: #6b7280; width: 38%; }
        h3 { font-size: 11px; color: #5a215e; text-transform: uppercase; margin: 14px 0 4px; }
        .texto { line-height: 1.5; white-space: pre-line; }
        .firma { margin-top: 50px; text-align: center; }
        .firma .linea { width: 55%; margin: 0 auto 4px; border-top: 1px solid #1f2937; }
        .pie { margin-top: 18px; font-size: 8px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <table class="cab">
        <tr>
            <td>
                <div class="logo"><div class="v"></div><div class="h"></div></div>
                <div class="marca"><b>Clínica Vida Salud</b><br>Receta médica</div>
            </td>
            <td class="numero">
                CITA<br>
                <b>{{ $cita->codigo }}</b><br>
                <span class="fecha">{{ $emitido->format('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <table>
        <tr><td class="e">Paciente</td><td>{{ $paciente->apellidos }}, {{ $paciente->nombres }}</td></tr>
        <tr><td class="e">CI · Edad</td><td>{{ $paciente->ci }} · {{ $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->age.' años' : '—' }}</td></tr>
        <tr><td class="e">Fecha de atención</td><td>{{ $cita->fecha->format('d/m/Y') }} {{ $cita->hora_inicio_corta }}</td></tr>
        <tr><td class="e">Médico</td><td>{{ $cita->medico->nombre_completo }} · Mat. {{ $cita->medico->matricula }}</td></tr>
        <tr><td class="e">Especialidad</td><td>{{ ucfirst($cita->especialidad->nombre) }}</td></tr>
    </table>

    <h3>Diagnóstico</h3>
    <p class="texto">{{ $atencion->diagnostico }}</p>

    <h3>Tratamiento (indicaciones)</h3>
    <p class="texto">{{ $atencion->tratamiento ?: '—' }}</p>

    <div class="firma">
        <div class="linea"></div>
        {{ $cita->medico->nombre_completo }}<br>
        <span style="color:#6b7280">Firma y sello del médico</span>
    </div>

    <p class="pie">Documento emitido por la Clínica Vida Salud.</p>
</body>
</html>

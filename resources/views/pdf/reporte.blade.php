{{-- CU23 · Plantilla PDF de reportes (dompdf: solo CSS en línea, sin Tailwind ni imágenes rasterizadas) --}}
@php
    $formato = fn ($valor, $clave) => $reporte['moneda'] && in_array($clave, $reporte['numericas'], true)
        ? number_format((float) $valor, 2, ',', '.')
        : $valor;
    $conGrupo = collect($reporte['grupos'])->contains(fn ($g) => $g['nombre'] !== null);
    $num = fn ($clave) => in_array($clave, $reporte['numericas'], true) ? 'num' : '';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $reporte['titulo'] }}</title>
    <style>
        @page { margin: 90px 36px 50px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; }
        header { position: fixed; top: -70px; left: 0; right: 0; height: 60px; border-bottom: 2px solid #5a215e; }
        footer { position: fixed; bottom: -32px; left: 0; right: 0; font-size: 8px; color: #6b7280; text-align: center; }
        .logo { display: inline-block; width: 34px; height: 34px; background: #5a215e; border-radius: 8px; position: relative; vertical-align: middle; }
        .logo .v { position: absolute; left: 14px; top: 6px; width: 6px; height: 22px; background: #fff; }
        .logo .h { position: absolute; left: 6px; top: 14px; width: 22px; height: 6px; background: #fff; }
        .marca { display: inline-block; vertical-align: middle; margin-left: 8px; }
        .marca b { font-size: 15px; color: #2e0b31; }
        .marca span { display: block; font-size: 8px; color: #6b7280; }
        .emision { position: absolute; right: 0; top: 8px; text-align: right; font-size: 8px; color: #6b7280; }
        h1 { font-size: 14px; color: #2e0b31; margin: 0 0 2px; }
        .sub { color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f4e9f4; color: #4a1a4e; text-align: left; font-size: 8px; text-transform: uppercase; padding: 5px; border-bottom: 1px solid #e8d2e8; }
        td { padding: 4px 5px; border-bottom: 1px solid #f3f4f6; }
        .num { text-align: right; }
        .grupo td { background: #fbf6fb; font-weight: bold; color: #2e0b31; }
        .subtotal td { background: #f9fafb; font-weight: bold; }
        .total td { background: #e8d2e8; font-weight: bold; color: #2e0b31; border-top: 2px solid #5a215e; }
        .vacio { padding: 30px; text-align: center; color: #6b7280; }
    </style>
</head>
<body>
    <header>
        <div class="logo"><div class="v"></div><div class="h"></div></div>
        <div class="marca"><b>Clínica Vida Salud</b><span>Sistema de gestión de citas médicas</span></div>
        <div class="emision">
            Emitido: {{ $emitido->format('d/m/Y H:i') }}<br>
            Por: {{ $usuario->username }}
        </div>
    </header>
    <footer>Clínica Vida Salud · {{ $reporte['titulo'] }} · Documento generado por el sistema</footer>

    <h1>{{ $reporte['titulo'] }}</h1>
    <div class="sub">{{ $reporte['subtitulo'] }}</div>

    @if ($reporte['vacio'])
        <div class="vacio">No hay resultados para los filtros seleccionados.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($reporte['columnas'] as $clave => $columna)
                        <th class="{{ $num($clave) }}">{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($reporte['grupos'] as $grupo)
                    @if ($conGrupo)
                        <tr class="grupo"><td colspan="{{ count($reporte['columnas']) }}">{{ $grupo['nombre'] }}</td></tr>
                    @endif
                    @foreach ($grupo['filas'] as $fila)
                        <tr>
                            @foreach ($reporte['columnas'] as $clave => $columna)
                                <td class="{{ $num($clave) }}">{{ $formato($fila[$clave] ?? '', $clave) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @if ($conGrupo && $grupo['subtotal'])
                        <tr class="subtotal">
                            @foreach ($reporte['columnas'] as $clave => $columna)
                                <td class="{{ $num($clave) }}">{{ $loop->first ? 'Subtotal: ' : '' }}{{ $formato($grupo['subtotal'][$clave] ?? '', $clave) }}</td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
                <tr class="total">
                    @foreach ($reporte['columnas'] as $clave => $columna)
                        <td class="{{ $num($clave) }}">{{ $loop->first ? 'Total general: ' : '' }}{{ $formato($reporte['total'][$clave] ?? '', $clave) }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>

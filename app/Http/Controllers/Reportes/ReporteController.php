<?php

namespace App\Http\Controllers\Reportes;

use App\Models\Bitacora;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\GenerarReporteRequest;
use App\Services\BitacoraService;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU23 · Generar reportes (PDF / CSV).
 *
 * Controlador liviano: recibe la petición, delega en app/Services
 * y devuelve la vista o el JSON. Sin reglas del negocio aquí.
 */
class ReporteController extends Controller
{
    public function __construct(
        private ReporteService $servicio,
        private BitacoraService $bitacora,
    ) {
    }

    /** Pantalla con selector de reporte, filtros y la tabla resultante. */
    public function index(GenerarReporteRequest $request): View
    {
        $filtros = $request->validated();

        return view('reportes.index', array_merge($this->servicio->catalogos(), [
            'tipos' => $this->servicio->tiposPara($request->user()),
            'filtros' => $filtros,
            'usaRango' => $this->servicio->usaRango($filtros['tipo']),
            'reporte' => $this->servicio->generar($filtros),
        ]));
    }

    /** Exporta el reporte con los mismos filtros (formato = pdf | csv). */
    public function exportar(GenerarReporteRequest $request, string $formato): Response|StreamedResponse
    {
        abort_unless(in_array($formato, ['pdf', 'csv'], true), 404);

        $filtros = $request->validated();
        $reporte = $this->servicio->generar($filtros);
        $archivo = 'reporte-'.Str::slug($filtros['tipo']).'-'.now()->format('Ymd-His');

        $this->bitacora->registrar(Bitacora::EXPORTAR_REPORTE, null, null, strtoupper($formato).' · '.$reporte['titulo'].' · '.$reporte['subtitulo']);

        if ($formato === 'pdf') {
            return Pdf::loadView('pdf.reporte', ['reporte' => $reporte, 'emitido' => now(), 'usuario' => $request->user()])
                ->setPaper('letter', count($reporte['columnas']) > 5 ? 'landscape' : 'portrait')
                ->download("{$archivo}.pdf");
        }

        return response()->streamDownload(function () use ($reporte) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel abre el UTF-8 con tildes.
            fputcsv($salida, [$reporte['titulo']], ';');
            fputcsv($salida, [$reporte['subtitulo']], ';');
            fputcsv($salida, [], ';');

            $conGrupo = collect($reporte['grupos'])->contains(fn ($g) => $g['nombre'] !== null);
            fputcsv($salida, array_merge($conGrupo ? ['Grupo'] : [], array_values($reporte['columnas'])), ';');

            foreach ($reporte['grupos'] as $grupo) {
                foreach ($grupo['filas'] as $fila) {
                    fputcsv($salida, array_merge($conGrupo ? [$grupo['nombre']] : [], $this->celdas($fila, $reporte)), ';');
                }
                if ($grupo['subtotal']) {
                    fputcsv($salida, array_merge($conGrupo ? ['Subtotal '.$grupo['nombre']] : [], $this->celdas($grupo['subtotal'], $reporte)), ';');
                }
            }
            fputcsv($salida, array_merge($conGrupo ? ['TOTAL GENERAL'] : [], $this->celdas($reporte['total'], $reporte)), ';');
            fclose($salida);
        }, "{$archivo}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Valores de una fila en el orden de las columnas (vacío si falta).
     * Los textos que empiezan con = + - @ se anteponen con ' para que Excel
     * no los ejecute como fórmula (inyección CSV).
     */
    private function celdas(array $fila, array $reporte): array
    {
        return array_map(function ($clave) use ($fila) {
            $valor = $fila[$clave] ?? '';

            return is_string($valor) && preg_match('/^[=+\-@\t\r]/', $valor) ? "'".$valor : $valor;
        }, array_keys($reporte['columnas']));
    }
}

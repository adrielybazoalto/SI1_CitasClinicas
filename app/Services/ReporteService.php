<?php

namespace App\Services;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Paquete 6 · CU23 Generar reportes.
 *
 * Cada reporte devuelve una estructura común que la pantalla, el PDF y el
 * CSV dibujan igual:
 *   titulo, subtitulo, columnas [clave => etiqueta], numericas [claves],
 *   grupos [ [nombre, filas, subtotal] ], total.
 * Todas las consultas se agrupan en la base de datos (groupBy).
 */
class ReporteService
{
    /** clave => [nombre, ¿solo administrador?, usa rango de fechas (true) o una sola fecha (false)] */
    public const TIPOS = [
        'citas_fecha' => ['Citas programadas para una fecha', false, false],
        'por_medico' => ['Citas por médico', false, true],
        'por_especialidad' => ['Citas por especialidad', false, true],
        'por_estado' => ['Citas por estado', false, true],
        'pacientes' => ['Pacientes registrados', false, true],
        'totales_periodo' => ['Atendidas, canceladas e inasistencias por periodo', false, true],
        'agenda_diaria' => ['Agenda diaria por médico', false, false],
        'bitacora' => ['Consulta de bitácora', true, true],
        'ingresos' => ['Ingresos por periodo', true, true],
    ];

    /** Permiso que habilita los reportes exclusivos del administrador. */
    public const PERMISO_ADMIN = 'bitacora.ver';

    /** Tipos disponibles para el usuario. @return array<string, string> */
    public function tiposPara(User $usuario): array
    {
        $tipos = [];
        foreach (self::TIPOS as $clave => [$nombre, $soloAdmin]) {
            if (! $soloAdmin || $usuario->tienePermiso(self::PERMISO_ADMIN)) {
                $tipos[$clave] = $nombre;
            }
        }

        return $tipos;
    }

    public function usaRango(string $tipo): bool
    {
        return self::TIPOS[$tipo][2] ?? true;
    }

    /** Datos para los selectores de filtros. @return array<string, mixed> */
    public function catalogos(): array
    {
        return [
            'medicos' => Medico::query()->orderBy('apellidos')->get(['id_medico', 'nombres', 'apellidos']),
            'especialidades' => Especialidad::query()->orderBy('nombre')->get(['id_especialidad', 'nombre']),
            'estados' => array_keys(CitaEstadoService::TRANSICIONES),
        ];
    }

    /**
     * Genera el reporte con los filtros ya validados.
     *
     * @param array{tipo: string, desde?: ?string, hasta?: ?string, fecha?: ?string, medico?: ?int, especialidad?: ?int, estado?: ?string} $f
     * @return array<string, mixed>
     */
    public function generar(array $f): array
    {
        $reporte = match ($f['tipo']) {
            'citas_fecha' => $this->citasDeUnaFecha($f, false),
            'agenda_diaria' => $this->citasDeUnaFecha($f, true),
            'por_medico' => $this->conteoPor($f, 'medico'),
            'por_especialidad' => $this->conteoPor($f, 'especialidad'),
            'por_estado' => $this->porEstado($f),
            'pacientes' => $this->pacientes($f),
            'totales_periodo' => $this->totalesPorPeriodo($f),
            'bitacora' => $this->bitacora($f),
            'ingresos' => $this->ingresos($f),
        };

        $reporte['titulo'] = self::TIPOS[$f['tipo']][0];
        $reporte['subtitulo'] = $this->describirFiltros($f);
        $reporte['vacio'] = collect($reporte['grupos'])->sum(fn ($g) => count($g['filas'])) === 0;

        return $reporte;
    }

    /* ------------------------------------------------------------------
     | Reportes
     * ------------------------------------------------------------------ */

    /** Citas de una fecha (todas o solo activas) agrupadas por médico, con subtotales. */
    private function citasDeUnaFecha(array $f, bool $agenda): array
    {
        $filas = $this->citasBase($f)
            ->whereDate('c.fecha', $f['fecha'])
            ->when(! $agenda && empty($f['estado']), fn ($q) => $q->whereIn('c.estado', CitaEstadoService::ACTIVOS))
            ->orderBy('m.apellidos')->orderBy('c.hora_inicio')
            ->get([
                DB::raw($this->concat(['m.apellidos', "', '", 'm.nombres']).' as medico'),
                DB::raw("substr(cast(c.hora_inicio as text), 1, 5) as hora"),
                'c.codigo',
                DB::raw($this->concat(['p.apellidos', "', '", 'p.nombres']).' as paciente'),
                'p.ci',
                'e.nombre as especialidad',
                'c.estado',
                'c.canal',
            ]);

        $columnas = ['hora' => 'Hora', 'codigo' => 'Código', 'paciente' => 'Paciente', 'ci' => 'CI', 'especialidad' => 'Especialidad', 'estado' => 'Estado', 'canal' => 'Canal'];

        return $this->agrupar($filas->map(fn ($r) => (array) $r)->all(), 'medico', $columnas, [], 'citas');
    }

    /** Conteo de citas por médico o especialidad, desglosado por estado. */
    private function conteoPor(array $f, string $por): array
    {
        $etiqueta = $por === 'medico'
            ? DB::raw($this->concat(['m.apellidos', "', '", 'm.nombres']).' as nombre')
            : DB::raw('e.nombre as nombre');
        $grupo = $por === 'medico' ? ['m.id_medico', 'm.apellidos', 'm.nombres'] : ['e.id_especialidad', 'e.nombre'];

        $filas = $this->citasRango($f)
            ->groupBy($grupo)
            ->orderBy($por === 'medico' ? 'm.apellidos' : 'e.nombre')
            ->get(array_merge([$etiqueta], $this->columnasPorEstado()));

        $columnas = ['nombre' => $por === 'medico' ? 'Médico' : 'Especialidad'] + $this->etiquetasPorEstado();

        return $this->sinGrupos($filas->map(fn ($r) => $this->enteros((array) $r))->all(), $columnas);
    }

    private function porEstado(array $f): array
    {
        $filas = $this->citasRango($f)
            ->groupBy('c.estado')
            ->orderBy('c.estado')
            ->get(['c.estado', DB::raw('count(*) as total')]);

        return $this->sinGrupos($filas->map(fn ($r) => $this->enteros((array) $r))->all(), ['estado' => 'Estado', 'total' => 'Citas']);
    }

    /** Pacientes registrados en el periodo. */
    private function pacientes(array $f): array
    {
        $filas = DB::table('paciente')
            ->whereDate('creado_en', '>=', $f['desde'])
            ->whereDate('creado_en', '<=', $f['hasta'])
            ->orderBy('creado_en')
            ->get([
                'codigo', 'ci',
                DB::raw($this->concat(['apellidos', "', '", 'nombres']).' as nombre'),
                'telefono',
                DB::raw('date(creado_en) as registrado'),
                DB::raw("case when estado then 'Activo' else 'Inactivo' end as situacion"),
            ]);

        $columnas = ['codigo' => 'Código', 'ci' => 'CI', 'nombre' => 'Paciente', 'telefono' => 'Teléfono', 'registrado' => 'Registrado', 'situacion' => 'Estado'];

        return $this->sinGrupos($filas->map(fn ($r) => (array) $r)->all(), $columnas, conteo: 'pacientes');
    }

    /** Totales por mes de atendidas, canceladas e inasistencias. */
    private function totalesPorPeriodo(array $f): array
    {
        $mes = $this->mes('c.fecha');

        $filas = $this->citasRango($f)
            ->whereIn('c.estado', ['Atendida', 'Cancelada', 'Inasistencia'])
            ->groupBy(DB::raw($mes))
            ->orderBy(DB::raw($mes))
            ->get(array_merge([DB::raw("{$mes} as periodo")], $this->columnasPorEstado(['Atendida', 'Cancelada', 'Inasistencia'])));

        $columnas = ['periodo' => 'Mes'] + $this->etiquetasPorEstado(['Atendida', 'Cancelada', 'Inasistencia']);

        return $this->sinGrupos($filas->map(fn ($r) => $this->enteros((array) $r))->all(), $columnas);
    }

    /** Solo administrador · registros de la bitácora del periodo. */
    private function bitacora(array $f): array
    {
        $filas = DB::table('bitacora as b')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'b.id_usuario')
            ->where('b.fecha_hora', '>=', $f['desde'].' 00:00:00')
            ->where('b.fecha_hora', '<=', $f['hasta'].' 23:59:59')
            ->orderByDesc('b.fecha_hora')
            ->limit(2000)
            ->get([
                DB::raw("substr(cast(b.fecha_hora as text), 1, 16) as fecha_hora"),
                DB::raw("coalesce(u.username, 'Sistema') as usuario"),
                'b.accion', 'b.tabla', 'b.ip', 'b.detalle',
            ]);

        $columnas = ['fecha_hora' => 'Fecha y hora', 'usuario' => 'Usuario', 'accion' => 'Acción', 'tabla' => 'Tabla', 'ip' => 'IP', 'detalle' => 'Detalle'];

        return $this->sinGrupos($filas->map(fn ($r) => (array) $r)->all(), $columnas, conteo: 'registros');
    }

    /** Solo administrador · cobros − reembolsos aprobados, por día y método. */
    private function ingresos(array $f): array
    {
        $dia = 'date(pg.fecha_hora)';
        $cobros = "sum(case when pg.tipo = 'COBRO' then pg.monto else 0 end)";
        $reembolsos = "sum(case when pg.tipo = 'REEMBOLSO' then pg.monto else 0 end)";

        $filas = DB::table('pago as pg')
            ->where('pg.estado', 'APROBADO')
            ->where('pg.fecha_hora', '>=', $f['desde'].' 00:00:00')
            ->where('pg.fecha_hora', '<=', $f['hasta'].' 23:59:59')
            ->groupBy(DB::raw($dia), 'pg.metodo')
            ->orderBy(DB::raw($dia))->orderBy('pg.metodo')
            ->get([
                DB::raw("{$dia} as dia"),
                'pg.metodo',
                DB::raw("{$cobros} as cobros"),
                DB::raw("{$reembolsos} as reembolsos"),
                DB::raw("{$cobros} - {$reembolsos} as neto"),
            ]);

        $filas = $filas->map(fn ($r) => [
            'dia' => (string) $r->dia,
            'metodo' => $r->metodo,
            'cobros' => round((float) $r->cobros, 2),
            'reembolsos' => round((float) $r->reembolsos, 2),
            'neto' => round((float) $r->neto, 2),
        ])->all();

        $columnas = ['metodo' => 'Método', 'cobros' => 'Cobros (Bs)', 'reembolsos' => 'Reembolsos (Bs)', 'neto' => 'Neto (Bs)'];

        return $this->agrupar($filas, 'dia', $columnas, ['cobros', 'reembolsos', 'neto'], 'movimientos', moneda: true);
    }

    /* ------------------------------------------------------------------
     | Consultas base y utilidades
     * ------------------------------------------------------------------ */

    private function citasBase(array $f): Builder
    {
        return DB::table('cita as c')
            ->join('medico as m', 'm.id_medico', '=', 'c.id_medico')
            ->join('especialidad as e', 'e.id_especialidad', '=', 'c.id_especialidad')
            ->join('paciente as p', 'p.id_paciente', '=', 'c.id_paciente')
            ->when(! empty($f['medico']), fn ($q) => $q->where('c.id_medico', $f['medico']))
            ->when(! empty($f['especialidad']), fn ($q) => $q->where('c.id_especialidad', $f['especialidad']))
            ->when(! empty($f['estado']), fn ($q) => $q->where('c.estado', $f['estado']));
    }

    private function citasRango(array $f): Builder
    {
        return $this->citasBase($f)
            ->whereDate('c.fecha', '>=', $f['desde'])
            ->whereDate('c.fecha', '<=', $f['hasta']);
    }

    /** count(*) por estado como columnas: programada, confirmada, … y total. */
    private function columnasPorEstado(?array $estados = null): array
    {
        $estados ??= array_keys(CitaEstadoService::TRANSICIONES);
        $columnas = [];
        foreach ($estados as $estado) {
            $columnas[] = DB::raw("sum(case when c.estado = '{$estado}' then 1 else 0 end) as ".mb_strtolower($estado));
        }
        $columnas[] = DB::raw('count(*) as total');

        return $columnas;
    }

    private function etiquetasPorEstado(?array $estados = null): array
    {
        $estados ??= array_keys(CitaEstadoService::TRANSICIONES);
        $etiquetas = [];
        foreach ($estados as $estado) {
            $etiquetas[mb_strtolower($estado)] = $estado.(str_ends_with($estado, 'a') ? 's' : '');
        }
        $etiquetas['total'] = 'Total';

        return $etiquetas;
    }

    /** Reporte de un solo bloque: total general = suma de columnas numéricas o cantidad de filas. */
    private function sinGrupos(array $filas, array $columnas, ?string $conteo = null): array
    {
        $numericas = array_values(array_filter(array_keys($columnas), fn ($c) => $filas !== [] && is_int($filas[0][$c] ?? null)));
        $total = $this->sumar($filas, $numericas);

        return [
            'columnas' => $columnas,
            'numericas' => $numericas,
            'moneda' => false,
            'grupos' => [['nombre' => null, 'filas' => $filas, 'subtotal' => null]],
            'total' => $conteo ? [array_key_first($columnas) => $this->cantidad(count($filas), $conteo)] + $total : $total,
        ];
    }

    /** Agrupa filas por una columna con subtotal por grupo y total general. */
    private function agrupar(array $filas, string $por, array $columnas, array $numericas, string $conteo, bool $moneda = false): array
    {
        $grupos = [];
        foreach (collect($filas)->groupBy($por) as $nombre => $grupo) {
            $grupo = $grupo->map(fn ($r) => array_diff_key($r, [$por => true]))->values()->all();
            $grupos[] = [
                'nombre' => $nombre,
                'filas' => $grupo,
                'subtotal' => [array_key_first($columnas) => $this->cantidad(count($grupo), $conteo)] + $this->sumar($grupo, $numericas),
            ];
        }

        return [
            'columnas' => $columnas,
            'numericas' => $numericas,
            'moneda' => $moneda,
            'grupos' => $grupos,
            'total' => [array_key_first($columnas) => $this->cantidad(count($filas), $conteo)] + $this->sumar($filas, $numericas),
        ];
    }

    /** "1 cita", "3 citas". */
    private function cantidad(int $n, string $plural): string
    {
        return $n.' '.($n === 1 ? rtrim($plural, 's') : $plural);
    }

    /** Suma columnas; si todos los valores son enteros, el total también lo es. */
    private function sumar(array $filas, array $columnas): array
    {
        $total = [];
        foreach ($columnas as $c) {
            $valores = array_column($filas, $c);
            $enteros = $valores !== [] && collect($valores)->every(fn ($v) => is_int($v));
            $total[$c] = $enteros ? array_sum($valores) : round(array_sum($valores), 2);
        }

        return $total;
    }

    /** Los conteos llegan como texto desde PostgreSQL: se convierten a entero. */
    private function enteros(array $fila): array
    {
        return array_map(fn ($v) => is_numeric($v) && (string) (int) $v === (string) $v ? (int) $v : $v, $fila);
    }

    private function describirFiltros(array $f): string
    {
        $partes = [];
        if ($this->usaRango($f['tipo'])) {
            $partes[] = 'Del '.CarbonImmutable::parse($f['desde'])->format('d/m/Y').' al '.CarbonImmutable::parse($f['hasta'])->format('d/m/Y');
        } else {
            $partes[] = 'Fecha: '.CarbonImmutable::parse($f['fecha'])->format('d/m/Y');
        }
        if (! empty($f['medico'])) {
            $partes[] = 'Médico: '.Medico::query()->find($f['medico'])?->nombre_completo;
        }
        if (! empty($f['especialidad'])) {
            $partes[] = 'Especialidad: '.Especialidad::query()->find($f['especialidad'])?->nombre;
        }
        if (! empty($f['estado'])) {
            $partes[] = 'Estado: '.$f['estado'];
        }

        return implode(' · ', $partes);
    }

    /** Concatenación compatible con PostgreSQL y SQLite (tests). */
    private function concat(array $partes): string
    {
        return implode(' || ', $partes);
    }

    private function mes(string $columna): string
    {
        return DB::getDriverName() === 'pgsql'
            ? "to_char({$columna}, 'YYYY-MM')"
            : "strftime('%Y-%m', {$columna})";
    }
}

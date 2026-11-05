<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * P6 · CU18 Gestionar caja y CU19 Cobrar cita en mostrador.
 *
 * Una sola caja ABIERTA por usuario (índice ux_caja_abierta). Al cerrar,
 * la recepcionista cuenta el dinero por método de pago; si algo no cuadra,
 * la observación es obligatoria. Una caja cerrada ya no se modifica.
 */
class CajaService
{
    public const ABIERTA = 'ABIERTA';
    public const CERRADA = 'CERRADA';

    public function __construct(
        private PagoService $pagos,
        private BitacoraService $bitacora,
    ) {
    }

    public function abierta(User $usuario): ?Caja
    {
        return Caja::query()->where('id_usuario', $usuario->id_usuario)->where('estado', self::ABIERTA)->first();
    }

    /** Cajas anteriores del usuario (el administrador ve todas). */
    public function historial(User $usuario): LengthAwarePaginator
    {
        return Caja::query()
            ->with('usuario:id_usuario,username')
            ->where('estado', self::CERRADA)
            ->when(! $usuario->tienePermiso('bitacora.ver'), fn ($q) => $q->where('id_usuario', $usuario->id_usuario))
            ->orderByDesc('apertura')
            ->paginate(10);
    }

    /** Apertura con el monto inicial. Si ya tiene una abierta, la devuelve. */
    public function abrir(User $usuario, float $montoInicial): Caja
    {
        return DB::transaction(function () use ($usuario, $montoInicial) {
            if ($caja = $this->abierta($usuario)) {
                return $caja;
            }

            $caja = Caja::create(['id_usuario' => $usuario->id_usuario, 'monto_inicial' => $montoInicial, 'estado' => self::ABIERTA]);

            $this->bitacora->registrar(Bitacora::ABRIR_CAJA, 'caja', $caja->id_caja,
                'Apertura de caja con Bs '.number_format($montoInicial, 2, ',', '.'), $usuario->id_usuario);

            return $caja;
        });
    }

    /**
     * Movimientos de la caja: cobros con id_caja y reembolsos que registró
     * su dueño mientras estuvo abierta (se le asignan al cerrar).
     */
    private function movimientos(Caja $caja): Builder
    {
        return Pago::query()
            ->where('estado', PagoService::APROBADO)
            ->where(fn ($q) => $q->where('id_caja', $caja->id_caja)->orWhere(fn ($r) => $r
                ->whereNull('id_caja')
                ->where('tipo', PagoService::REEMBOLSO)
                ->whereIn('metodo', PagoService::METODOS_MOSTRADOR)
                ->where('registrado_por', $caja->id_usuario)
                ->where('fecha_hora', '>=', $caja->apertura)
                ->when($caja->cierre, fn ($s) => $s->where('fecha_hora', '<=', $caja->cierre))));
    }

    /**
     * Resumen para el arqueo: por método, cobros − reembolsos; en efectivo
     * se suma el monto inicial. Esperado en efectivo = inicial + cobros − reembolsos.
     *
     * @return array{metodos: array<string, array{cobros: float, reembolsos: float, esperado: float}>, movimientos: Collection<int, Pago>, total_cobrado: float}
     */
    public function resumen(Caja $caja): array
    {
        $movimientos = $this->movimientos($caja)->with(['cita:id_cita,codigo,id_paciente', 'cita.paciente:id_paciente,nombres,apellidos', 'comprobante'])
            ->orderBy('fecha_hora')->get();

        $metodos = [];
        foreach (PagoService::METODOS_MOSTRADOR as $metodo) {
            $cobros = (float) $movimientos->where('metodo', $metodo)->where('tipo', PagoService::COBRO)->sum('monto');
            $reembolsos = (float) $movimientos->where('metodo', $metodo)->where('tipo', PagoService::REEMBOLSO)->sum('monto');
            $esperado = $cobros - $reembolsos + ($metodo === 'EFECTIVO' ? (float) $caja->monto_inicial : 0);
            $metodos[$metodo] = ['cobros' => round($cobros, 2), 'reembolsos' => round($reembolsos, 2), 'esperado' => round($esperado, 2)];
        }

        return [
            'metodos' => $metodos,
            'movimientos' => $movimientos,
            'total_cobrado' => round(array_sum(array_column($metodos, 'cobros')), 2),
        ];
    }

    /**
     * Cierra la caja con el conteo por método. La observación es
     * obligatoria si hay sobrante o faltante.
     *
     * @param array{EFECTIVO: float, TARJETA: float, TRANSFERENCIA: float} $contado
     * @throws ValidationException
     */
    public function cerrar(Caja $caja, array $contado, ?string $observacion, User $actor): Caja
    {
        return DB::transaction(function () use ($caja, $contado, $observacion, $actor) {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id_caja);

            if ($caja->estado !== self::ABIERTA || (int) $caja->id_usuario !== (int) $actor->id_usuario) {
                throw ValidationException::withMessages(['caja' => 'Solo puede cerrar su propia caja abierta.']);
            }

            $resumen = $this->resumen($caja);
            $esperado = 0.0;
            $total = 0.0;
            $detalle = [];
            foreach ($resumen['metodos'] as $metodo => $datos) {
                $cuenta = round((float) ($contado[$metodo] ?? 0), 2);
                $dif = round($cuenta - $datos['esperado'], 2);
                $esperado += $datos['esperado'];
                $total += $cuenta;
                if ($dif != 0.0) {
                    $detalle[] = sprintf('%s: %s Bs %s', ucfirst(mb_strtolower($metodo)), $dif > 0 ? 'sobrante' : 'faltante', number_format(abs($dif), 2, ',', '.'));
                }
            }
            $diferencia = round($total - $esperado, 2);
            $observacion = trim((string) $observacion);

            if ($detalle !== [] && $observacion === '') {
                throw ValidationException::withMessages([
                    'observacion' => 'Hay diferencias ('.implode('; ', $detalle).'). Explique el sobrante o faltante.',
                ]);
            }

            // Los reembolsos en efectivo de esta usuaria pasan a pertenecer a esta caja.
            $this->movimientos($caja)->whereNull('id_caja')->update(['id_caja' => $caja->id_caja]);

            $caja->forceFill([
                'cierre' => now(),
                'monto_esperado' => round($esperado, 2),
                'monto_contado' => round($total, 2),
                'diferencia' => $diferencia,
                'observacion' => trim(implode('. ', array_filter([implode('; ', $detalle), $observacion]))) ?: null,
                'estado' => self::CERRADA,
            ])->save();

            $this->bitacora->registrar(Bitacora::CERRAR_CAJA, 'caja', $caja->id_caja, sprintf(
                'Cierre de caja · esperado Bs %s · contado Bs %s · diferencia Bs %s',
                number_format($esperado, 2, ',', '.'), number_format($total, 2, ',', '.'), number_format($diferencia, 2, ',', '.'),
            ), $actor->id_usuario);

            return $caja;
        });
    }

    /* ------------------------------------------------------------------
     | CU19 · Cobrar cita en mostrador
     * ------------------------------------------------------------------ */

    /** Citas pendientes de cobro, con búsqueda por código, CI o apellido. */
    public function pendientesDeCobro(?string $buscar): LengthAwarePaginator
    {
        return Cita::query()
            ->with(['paciente:id_paciente,codigo,ci,nombres,apellidos', 'medico:id_medico,nombres,apellidos', 'especialidad:id_especialidad,nombre'])
            ->where('estado', CitaEstadoService::PROGRAMADA)
            ->where('estado_pago', 'Pendiente')
            ->where('monto', '>', 0)
            ->when(filled($buscar), function ($q) use ($buscar) {
                $texto = '%'.mb_strtolower(trim($buscar)).'%';
                $q->where(fn ($w) => $w->whereRaw('lower(codigo) like ?', [$texto])
                    ->orWhereHas('paciente', fn ($p) => $p->whereRaw('lower(ci) like ?', [$texto])
                        ->orWhereRaw('lower(apellidos) like ?', [$texto])
                        ->orWhereRaw('lower(nombres) like ?', [$texto])));
            })
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->paginate(15)->withQueryString();
    }

    /**
     * Cobra la cita en la caja abierta del usuario. En efectivo el monto
     * recibido debe cubrir el precio y se devuelve el cambio.
     *
     * @return array{pago: Pago, cambio: float}
     * @throws ValidationException
     */
    public function cobrar(Cita $cita, string $metodo, ?float $recibido, User $actor): array
    {
        return DB::transaction(function () use ($cita, $metodo, $recibido, $actor) {
            $caja = $this->abierta($actor);
            if (! $caja) {
                throw ValidationException::withMessages(['caja' => 'Abra su caja antes de cobrar.']);
            }

            $monto = (float) $cita->monto;
            if ($metodo === 'EFECTIVO' && ($recibido === null || $recibido < $monto)) {
                throw ValidationException::withMessages([
                    'recibido' => 'El monto recibido debe ser al menos Bs '.number_format($monto, 2, ',', '.').'.',
                ]);
            }

            $pago = $this->pagos->cobrar($cita, $metodo, $caja->id_caja, null, $actor);

            return ['pago' => $pago, 'cambio' => $metodo === 'EFECTIVO' ? round($recibido - $monto, 2) : 0.0];
        });
    }
}

<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Comprobante;
use App\Models\Pago;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * P4 · Pagos de citas (entidades Pago y Comprobante).
 *
 * Lo usan el cobro en mostrador (CU19, desde la caja), el pago en línea
 * (CU14) y el reembolso al cancelar (subflujo de CU16, RN26). Toda
 * operación corre dentro de la transacción del servicio que la invoca.
 */
class PagoService
{
    public const COBRO = 'COBRO';
    public const REEMBOLSO = 'REEMBOLSO';
    public const APROBADO = 'APROBADO';

    public const METODOS_MOSTRADOR = ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA'];
    public const METODOS_LINEA = ['PAYPAL', 'QR'];

    /** Candado de transacción para el número correlativo del comprobante. */
    private const CANDADO_COMPROBANTE = 4202619;

    /** Primer número de comprobante si todavía no hay ninguno. */
    private const PRIMER_COMPROBANTE = 1001;

    public function __construct(private BitacoraService $bitacora)
    {
    }

    /** ¿Se puede cobrar esta cita? Programada, pendiente de pago y con precio. */
    public function cobrable(Cita $cita): bool
    {
        return $cita->estado === CitaEstadoService::PROGRAMADA
            && $cita->estado_pago === 'Pendiente'
            && (float) $cita->monto > 0;
    }

    /**
     * Registra el cobro, marca la cita como Pagada y emite el comprobante
     * con número correlativo. Debe llamarse dentro de una transacción.
     *
     * @throws ValidationException
     */
    public function cobrar(Cita $cita, string $metodo, ?int $idCaja, ?string $idTransaccion, ?User $actor, string $accion = Bitacora::COBRAR_CITA): Pago
    {
        $cita = Cita::query()->lockForUpdate()->findOrFail($cita->id_cita);

        if (! $this->cobrable($cita)) {
            throw ValidationException::withMessages([
                'cita' => match (true) {
                    $cita->estado_pago !== 'Pendiente' => "La cita {$cita->codigo} ya está {$cita->estado_pago}.",
                    $cita->estado !== CitaEstadoService::PROGRAMADA => "La cita {$cita->codigo} está {$cita->estado}: solo se cobran citas Programadas.",
                    default => "La cita {$cita->codigo} no tiene precio: registre la tarifa de la especialidad (CU11).",
                },
            ]);
        }

        $pago = Pago::create([
            'id_cita' => $cita->id_cita,
            'id_caja' => $idCaja,
            'registrado_por' => $actor?->id_usuario,
            'tipo' => self::COBRO,
            'metodo' => $metodo,
            'monto' => $cita->monto,
            'id_transaccion' => $idTransaccion,
            'estado' => self::APROBADO,
        ]);

        $cita->forceFill(['estado_pago' => 'Pagado'])->save();

        $comprobante = Comprobante::create(['id_pago' => $pago->id_pago, 'numero' => $this->siguienteNumero()]);

        $this->bitacora->registrar(
            $accion,
            'pago',
            $pago->id_pago,
            sprintf('Cita %s · %s · Bs %s · comprobante N.º %d', $cita->codigo, $metodo, number_format((float) $cita->monto, 2, ',', '.'), $comprobante->numero),
            $actor?->id_usuario,
        );

        return $pago->setRelation('comprobante', $comprobante)->setRelation('cita', $cita);
    }

    /* ------------------------------------------------------------------
     | CU14 · Pagar cita en línea
     * ------------------------------------------------------------------ */

    /** ¿Venció la retención de 15 minutos para pagar en línea (RN20)? */
    public function vencida(Cita $cita): bool
    {
        return $cita->vence_pago_en !== null && $cita->vence_pago_en->isPast();
    }

    /**
     * Confirma un pago en línea (PayPal capturado o QR verificado):
     * pago APROBADO con id de transacción, cita Pagada y comprobante.
     *
     * @throws ValidationException
     */
    public function pagarEnLinea(Cita $cita, string $metodo, string $idTransaccion, User $actor): Pago
    {
        return DB::transaction(function () use ($cita, $metodo, $idTransaccion, $actor) {
            if ($this->vencida($cita->fresh())) {
                throw ValidationException::withMessages(['cita' => 'El tiempo para pagar esta cita en línea ya venció.']);
            }

            return $this->cobrar($cita, $metodo, null, $idTransaccion, $actor, Bitacora::PAGO_EN_LINEA);
        });
    }

    /** Envía el comprobante en PDF al correo del paciente. Devuelve false si no se pudo. */
    public function enviarComprobante(Pago $pago): bool
    {
        $pago->load(['comprobante', 'cita.paciente', 'cita.medico', 'cita.especialidad', 'registrador']);
        $correo = $pago->cita?->paciente?->correo;

        if (! $correo || ! $pago->comprobante) {
            return false;
        }

        try {
            $pdf = Pdf::loadView('pdf.comprobante', ['pago' => $pago])->setPaper('a5', 'portrait')->output();
            Mail::raw(
                "Adjuntamos el comprobante N.º {$pago->comprobante->numero} de su cita {$pago->cita->codigo}.\n\nClínica Vida Salud",
                fn ($m) => $m->to($correo)->subject('Vida Salud · Comprobante de pago')
                    ->attachData($pdf, "comprobante-{$pago->comprobante->numero}.pdf", ['mime' => 'application/pdf']),
            );
        } catch (Throwable $e) {
            Log::error('No se pudo enviar el comprobante', ['pago' => $pago->id_pago, 'error' => $e->getMessage()]);

            return false;
        }

        return true;
    }

    /**
     * Reembolso total de una cita pagada (RN26 y RN28): pago tipo
     * REEMBOLSO por el mismo método del cobro y estado_pago = Reembolsado.
     * Debe llamarse dentro de la transacción de la cancelación.
     */
    public function reembolsar(Cita $cita, ?User $actor, string $motivo): ?Pago
    {
        if ($cita->estado_pago !== 'Pagado') {
            return null;
        }

        $cobro = Pago::query()
            ->where('id_cita', $cita->id_cita)
            ->where('tipo', self::COBRO)
            ->where('estado', self::APROBADO)
            ->latest('fecha_hora')
            ->first();

        $reembolso = Pago::create([
            'id_cita' => $cita->id_cita,
            // El reembolso en efectivo lo adopta la caja de quien lo registra al cerrarla (CU18).
            'id_caja' => null,
            'registrado_por' => $actor?->id_usuario,
            'tipo' => self::REEMBOLSO,
            'metodo' => $cobro?->metodo ?? 'EFECTIVO',
            'monto' => $cobro?->monto ?? $cita->monto,
            'id_transaccion' => $cobro?->id_transaccion ? 'RF-'.$cobro->id_transaccion : null,
            'estado' => self::APROBADO,
        ]);

        $cita->forceFill(['estado_pago' => 'Reembolsado'])->save();

        $this->bitacora->registrar(
            Bitacora::REEMBOLSAR_CITA,
            'pago',
            $reembolso->id_pago,
            sprintf('Cita %s · reembolso %s de Bs %s · %s', $cita->codigo, $reembolso->metodo, number_format((float) $reembolso->monto, 2, ',', '.'), $motivo),
            $actor?->id_usuario,
        );

        return $reembolso;
    }

    /** Siguiente número de comprobante sin repetidos aunque cobren dos cajas a la vez. */
    private function siguienteNumero(): int
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select('SELECT pg_advisory_xact_lock(?)', [self::CANDADO_COMPROBANTE]);
        }

        return max(self::PRIMER_COMPROBANTE - 1, (int) Comprobante::query()->max('numero')) + 1;
    }
}

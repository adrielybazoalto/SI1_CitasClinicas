<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Notificacion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Programa y envía recordatorios por correo (RN23).
 *
 * Los avisos se encolan en la tabla notificacion y el temporizador los envía
 * (routes/console.php). Un recordatorio de una cita que ya no está activa
 * (cancelada, atendida…) no se envía nunca.
 */
class NotificacionService
{
    public const RECORDATORIO = 'RECORDATORIO';
    public const REPROGRAMACION = 'REPROGRAMACION';
    public const CANCELACION = 'CANCELACION';

    public const PENDIENTE = 'PENDIENTE';
    public const ENVIADA = 'ENVIADA';
    public const FALLIDA = 'FALLIDA';

    /**
     * Programa (o reprograma) el recordatorio a −24 h de la cita. Si la cita
     * es en menos de 24 h, el recordatorio sale en el siguiente minuto.
     */
    public function programarRecordatorio(Cita $cita): ?Notificacion
    {
        $cita->loadMissing('paciente');

        if (! $cita->paciente?->recibe_recordatorios) {
            return null;
        }

        $horaCita = $this->inicioCita($cita);
        $programada = $horaCita->subHours((int) config('clinica.recordatorio_horas_antes', 24));
        $programada = $programada->lt(CarbonImmutable::now()) ? CarbonImmutable::now() : $programada;

        $notificacion = Notificacion::query()
            ->where('id_cita', $cita->id_cita)
            ->where('tipo', self::RECORDATORIO)
            ->where('estado', self::PENDIENTE)
            ->first() ?? new Notificacion([
                'id_paciente' => $cita->id_paciente,
                'id_cita' => $cita->id_cita,
                'tipo' => self::RECORDATORIO,
                'estado' => self::PENDIENTE,
            ]);

        $notificacion->programada_para = $programada;
        $notificacion->save();

        return $notificacion;
    }

    /** Aviso inmediato de reprogramación o cancelación. */
    public function avisar(Cita $cita, string $tipo): ?Notificacion
    {
        $cita->loadMissing('paciente');

        if (! $cita->paciente?->recibe_recordatorios) {
            return null;
        }

        return Notificacion::create([
            'id_paciente' => $cita->id_paciente,
            'id_cita' => $cita->id_cita,
            'tipo' => $tipo,
            'programada_para' => now(),
            'estado' => self::PENDIENTE,
        ]);
    }

    /**
     * Temporizador · envía los avisos pendientes cuya hora ya llegó.
     *
     * @return int cantidad de correos enviados
     */
    public function enviarPendientes(): int
    {
        $enviadas = 0;

        $pendientes = Notificacion::query()
            ->with(['paciente', 'cita.medico', 'cita.especialidad'])
            ->where('estado', self::PENDIENTE)
            ->where('programada_para', '<=', now())
            ->limit(100)
            ->get();

        foreach ($pendientes as $notificacion) {
            $cita = $notificacion->cita;

            // Un recordatorio de una cita que ya no está activa se descarta.
            if ($notificacion->tipo === self::RECORDATORIO
                && (! $cita || ! in_array($cita->estado, CitaEstadoService::ACTIVOS, true))) {
                $notificacion->forceFill(['estado' => self::FALLIDA])->save();
                continue;
            }

            $correo = $notificacion->paciente?->correo;

            if (! $correo) {
                $notificacion->forceFill(['estado' => self::FALLIDA])->save();
                continue;
            }

            try {
                Mail::raw($this->mensaje($notificacion), function ($mensaje) use ($correo, $notificacion) {
                    $mensaje->to($correo)->subject('Clínica Vida Salud · '.ucfirst(mb_strtolower($notificacion->tipo)));
                });

                $notificacion->forceFill(['estado' => self::ENVIADA, 'enviada_en' => now()])->save();
                $enviadas++;
            } catch (Throwable $e) {
                Log::error('No se pudo enviar la notificación', ['id' => $notificacion->id_notificacion, 'error' => $e->getMessage()]);
                $notificacion->forceFill(['estado' => self::FALLIDA])->save();
            }
        }

        return $enviadas;
    }

    private function mensaje(Notificacion $notificacion): string
    {
        $cita = $notificacion->cita;
        $detalle = $cita
            ? sprintf(
                'Cita %s · %s · Dr(a). %s · %s a las %s.',
                $cita->codigo,
                $cita->especialidad?->nombre,
                $cita->medico?->nombre_completo,
                $cita->fecha->format('d/m/Y'),
                substr((string) $cita->hora_inicio, 0, 5),
            )
            : '';

        return match ($notificacion->tipo) {
            self::RECORDATORIO => "Le recordamos su cita en la Clínica Vida Salud. {$detalle} Por favor llegue 15 minutos antes.",
            self::REPROGRAMACION => "Su cita fue reprogramada. Nuevos datos: {$detalle}",
            self::CANCELACION => "Su cita fue cancelada. {$detalle}",
            default => $detalle,
        };
    }

    private function inicioCita(Cita $cita): CarbonImmutable
    {
        return CarbonImmutable::parse($cita->fecha->toDateString().' '.substr((string) $cita->hora_inicio, 0, 8));
    }
}

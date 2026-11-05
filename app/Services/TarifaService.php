<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Especialidad;
use App\Models\Tarifa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * P3 · CU11 Gestionar tarifario.
 *
 * Cada especialidad tiene una sola tarifa abierta (vigente_hasta = null).
 * Un precio nuevo empieza en una fecha ≥ hoy y, en la misma transacción,
 * cierra la tarifa anterior el día previo. Los precios no se borran: el
 * historial queda completo. RN22: la cita guarda el precio vigente al
 * reservar (CitaService usa vigenteEn()).
 */
class TarifaService
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    /**
     * Especialidades con su precio vigente hoy y el próximo programado.
     *
     * @return Collection<int, array{especialidad: Especialidad, vigente: ?Tarifa, proxima: ?Tarifa}>
     */
    public function listar(): Collection
    {
        $hoy = CarbonImmutable::today();

        return Especialidad::query()->orderBy('nombre')->get()->map(fn (Especialidad $e) => [
            'especialidad' => $e,
            'vigente' => $this->vigenteEn($e->id_especialidad, $hoy),
            'proxima' => Tarifa::query()
                ->where('id_especialidad', $e->id_especialidad)
                ->whereDate('vigente_desde', '>', $hoy->toDateString())
                ->orderBy('vigente_desde')
                ->first(),
        ]);
    }

    /** Tarifa vigente de una especialidad en una fecha (null si no tiene precio). */
    public function vigenteEn(int $idEspecialidad, CarbonImmutable $fecha): ?Tarifa
    {
        $dia = $fecha->toDateString();

        return Tarifa::query()
            ->where('id_especialidad', $idEspecialidad)
            ->whereDate('vigente_desde', '<=', $dia)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $dia))
            ->orderByDesc('vigente_desde')
            ->first();
    }

    /** Historial de precios, del más reciente al más antiguo. */
    public function historial(Especialidad $especialidad): Collection
    {
        return Tarifa::query()
            ->where('id_especialidad', $especialidad->id_especialidad)
            ->orderByDesc('vigente_desde')
            ->get();
    }

    /**
     * Registra un precio nuevo y cierra el anterior.
     * $datos ya viene validado por TarifaRequest (monto > 0, vigente_desde ≥ hoy).
     *
     * @throws ValidationException
     */
    public function registrar(array $datos, User $actor): Tarifa
    {
        $idEspecialidad = (int) $datos['id_especialidad'];
        $desde = CarbonImmutable::parse($datos['vigente_desde'])->startOfDay();

        return DB::transaction(function () use ($idEspecialidad, $desde, $datos, $actor) {
            // Bloquea las tarifas de la especialidad: dos cambios simultáneos no se pisan.
            $abierta = Tarifa::query()
                ->where('id_especialidad', $idEspecialidad)
                ->whereNull('vigente_hasta')
                ->lockForUpdate()
                ->first();

            if ($abierta && $abierta->vigente_desde->gte($desde)) {
                throw ValidationException::withMessages([
                    'vigente_desde' => 'Ya hay un precio que rige desde el '.$abierta->vigente_desde->format('d/m/Y')
                        .'. El nuevo precio debe empezar después de esa fecha.',
                ]);
            }

            $anterior = $abierta ? (float) $abierta->monto : null;
            $abierta?->forceFill(['vigente_hasta' => $desde->subDay()->toDateString()])->save();

            $tarifa = Tarifa::create([
                'id_especialidad' => $idEspecialidad,
                'monto' => $datos['monto'],
                'vigente_desde' => $desde->toDateString(),
                'vigente_hasta' => null,
            ]);

            $nombre = Especialidad::query()->whereKey($idEspecialidad)->value('nombre');
            $this->bitacora->registrar(
                Bitacora::ACTUALIZAR_TARIFA,
                'tarifa',
                $tarifa->id_tarifa,
                sprintf('%s: %sBs %s desde el %s', $nombre,
                    $anterior !== null ? 'de Bs '.number_format($anterior, 2, ',', '.').' a ' : '',
                    number_format((float) $datos['monto'], 2, ',', '.'), $desde->format('d/m/Y')),
                $actor->id_usuario,
            );

            return $tarifa;
        });
    }
}

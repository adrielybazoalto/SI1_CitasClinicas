<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Lee config/menu.php (un ítem por caso de uso) y lo filtra por los
 * permisos del rol (CU03). Lo usan el menú lateral, el encabezado de cada
 * página (<x-encabezado-modulo>), el aviso de acción (<x-aviso-modulo>) y
 * el panel de inicio, para que todos muestren siempre el mismo CU.
 *
 * Cuál es el CU actual se decide en este orden:
 *  1. 'tambien': rutas a las que se llega desde otra pantalla y que siempre
 *     son de ese CU (por ejemplo, el formulario de antecedentes = CU06);
 *  2. 'activo' + mismo ?accion: si el ítem tiene 'accion', debe coincidir;
 *     si no la tiene, la URL no debe llevar ?accion;
 *  3. solo 'activo': se conoce el paquete pero no el CU exacto (por ejemplo,
 *     el detalle de una cita sirve a CU15, CU16 y CU17).
 */
class MenuModulos
{
    /**
     * Paquetes con al menos un ítem visible para el usuario. Cada ítem trae
     * 'disponible' (la ruta existe), 'url', 'actual' y 'etiqueta'.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function paraUsuario(User $usuario): Collection
    {
        $actual = self::actual();

        return collect(config('menu'))->map(function (array $paquete) use ($usuario, $actual) {
            $items = collect($paquete['items'])
                ->filter(fn (array $i) => collect((array) $i['permiso'])->contains(fn ($codigo) => $usuario->tienePermiso($codigo)))
                ->map(function (array $i) use ($actual) {
                    $disponible = Route::has($i['ruta']);

                    return $i + [
                        'disponible' => $disponible,
                        'url' => $disponible ? route($i['ruta'], $i['parametros'] ?? []) : null,
                        'actual' => ($actual['item']['cu'] ?? null) === $i['cu'],
                        'etiqueta' => self::etiqueta($i),
                    ];
                })
                ->values();

            return $paquete + [
                'visibles' => $items,
                'abierto' => ($actual['paquete']['paquete'] ?? null) === $paquete['paquete'],
            ];
        })->filter(fn (array $p) => $p['visibles']->isNotEmpty())->values();
    }

    /**
     * Paquete e ítem (CU) de la pantalla actual; 'item' es null cuando solo
     * se sabe el paquete.
     *
     * @return array{paquete: array<string, mixed>, item: ?array<string, mixed>}|null
     */
    public static function actual(): ?array
    {
        $accion = request()->query('accion') ?: null;
        $porPaquete = null;

        foreach ([1, 2] as $paso) {
            foreach (config('menu') as $paquete) {
                foreach ($paquete['items'] as $item) {
                    if ($paso === 1 && ! empty($item['tambien']) && request()->routeIs(...$item['tambien'])) {
                        return ['paquete' => $paquete, 'item' => $item];
                    }
                    if ($paso === 2 && request()->routeIs(...(array) $item['activo'])) {
                        if (($item['parametros']['accion'] ?? null) === $accion) {
                            return ['paquete' => $paquete, 'item' => $item];
                        }
                        $porPaquete ??= ['paquete' => $paquete, 'item' => null];
                    }
                }
            }
        }

        return $porPaquete;
    }

    /** "Consultar bitácora (CU24)". */
    public static function etiqueta(array $item): string
    {
        return "{$item['titulo']} ({$item['cu']})";
    }
}

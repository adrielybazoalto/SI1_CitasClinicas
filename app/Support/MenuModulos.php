<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Lee config/menu.php y lo filtra por los permisos del rol (CU03).
 * Lo usan el menú lateral, el encabezado de cada página
 * (<x-encabezado-modulo>) y el panel de inicio, para que los tres
 * muestren siempre lo mismo.
 */
class MenuModulos
{
    /**
     * Paquetes con al menos un ítem visible para el usuario.
     * Cada ítem trae además 'disponible' (la ruta existe) y 'actual'.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function paraUsuario(User $usuario): Collection
    {
        return collect(config('menu'))->map(function (array $paquete) use ($usuario) {
            $items = collect($paquete['items'])
                ->filter(fn (array $i) => collect((array) $i['permiso'])->contains(fn ($codigo) => $usuario->tienePermiso($codigo)))
                ->map(fn (array $i) => $i + [
                    'disponible' => Route::has($i['ruta']),
                    'actual' => request()->routeIs(...(array) $i['activo']),
                    'etiqueta' => self::etiqueta($i),
                ])
                ->values();

            return $paquete + ['visibles' => $items, 'abierto' => $items->contains('actual', true)];
        })->filter(fn (array $p) => $p['visibles']->isNotEmpty())->values();
    }

    /**
     * Paquete e ítem de la ruta actual, sin filtrar por permisos.
     *
     * @return array{paquete: array<string, mixed>, item: array<string, mixed>}|null
     */
    public static function actual(): ?array
    {
        foreach (config('menu') as $paquete) {
            foreach ($paquete['items'] as $item) {
                if (request()->routeIs(...(array) $item['activo'])) {
                    return ['paquete' => $paquete, 'item' => $item];
                }
            }
        }

        return null;
    }

    /** "Usuarios (CU02)" · "Citas (CU15·CU16·CU17)". */
    public static function etiqueta(array $item): string
    {
        return $item['titulo'].' ('.str_replace(' · ', '·', $item['cu']).')';
    }
}

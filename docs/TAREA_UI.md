# TAREA UI — Nueva paleta, menú lateral por paquetes y arreglo de superposiciones

> Tarea para Claude Code. Guárdala en `docs/TAREA_UI.md` y pide: **"Lee docs/TAREA_UI.md y aplícala paso a paso"**.
> Respeta las reglas de `CLAUDE.md`. Esta tarea **no toca la base de datos**.

## Objetivo

1. **Mantener el estilo actual** (Tailwind, tarjetas blancas, bordes suaves, tipografía Figtree), pero **cambiar el morado por un ciruela** como el de la referencia: botón principal `#5A215E` y fondos lavanda muy claros.
2. Reemplazar la barra de navegación superior por un **menú lateral organizado por los 6 paquetes**. Cada paquete se despliega y muestra sus casos de uso, y el paquete de la pantalla actual aparece abierto.
3. Corregir los elementos que se **superponen**: íconos encima del texto de los campos, el menú superior que se amontona y el nombre de usuario encima de "Roles y permisos".

Trabaja en una rama nueva: `git checkout -b ui-sidebar-paquetes`.

---

## Paso 1 — Diagnosticar las superposiciones (antes de cambiar nada)

Síntomas en las capturas: los inputs se ven cuadrados con borde azul, los íconos quedan encima del texto (como si `pl-11` no existiera) y los elementos del menú superior se montan entre sí. Eso indica que **las clases de Tailwind no están llegando al navegador**: solo se aplican los estilos base de `@tailwindcss/forms`.

Revisa en este orden y dile a la usuaria qué encontraste:

1. ¿Existe `public/hot` sin que `npm run dev` esté corriendo? ¿`public/build` es anterior al rediseño? En ese caso:
   ```bash
   rm -f public/hot
   rm -rf public/build
   npm run build
   ```
   Para desarrollar se necesitan **dos terminales**: `npm run dev` y `php artisan serve`, o una sola con `composer run dev`. Después, recarga con Ctrl+F5.
2. Busca CSS de Bootstrap cargado: `grep -rn "bootstrap" resources/ vite.config.js` y enlaces CDN en `resources/views`. Si el CSS de Bootstrap se carga junto a Tailwind, sus estilos pisan los de Tailwind. Las vistas están hechas con Tailwind, así que **quita el CSS de Bootstrap** de los layouts y de `app.js`/`app.css`. El paquete puede quedar en `package.json`.
3. Confirma que el CSS compilado contiene las clases: `grep -o "\.pl-11" public/build/assets/*.css`.
4. `postcss.config.js` usa Tailwind 3 (el correcto). El paquete `@tailwindcss/vite` (v4) **no se usa** en `vite.config.js`; no lo agregues a la configuración.

---

## Paso 2 — Paleta ciruela (recolorea todo sin tocar las vistas)

Todas las vistas usan las clases `purple-*`. Si se redefine `purple` en Tailwind, **todo el sistema cambia de color** y se conserva el diseño.

En `tailwind.config.js`, dentro de `theme.extend.colors`, agrega `purple` y apunta `brand` y `health` a los mismos tonos:

```js
const ciruela = {
    50:  '#FBF6FB',
    100: '#F4E9F4',
    200: '#E8D2E8',
    300: '#D5AFD6',
    400: '#BA82BC',
    500: '#9B5A9F',
    600: '#7F3F84',
    700: '#6A2E6F',
    800: '#5A215E',   // botón principal (como "Sign In" de la referencia)
    900: '#4A1A4E',
    950: '#2E0B31',
};

// dentro de export default { theme: { extend: { colors: { ... } } } }
purple: ciruela,
brand: ciruela,
health: {
    dark: '#4A1A4E', primary: '#5A215E', hover: '#4A1A4E',
    light: '#F4E9F4', surface: '#FBF6FB', accent: '#9B5A9F',
},
```

Reemplaza también los colores **escritos a mano** (hexadecimales) por los nuevos. Búscalos con:
`grep -rnE "#(7E22CE|6B21A8|4C1D95|581C87|3B0764|A855F7|9333EA|8B5CF6|C084FC|D8B4FE|E9D5FF|F3E8FF|FAF5FF|FAF9FD)" resources/`

| Antes | Después |
|---|---|
| `#7E22CE`, `#9333EA`, `#8B5CF6` | `#7F3F84` |
| `#6B21A8` | `#5A215E` |
| `#4C1D95`, `#581C87` | `#4A1A4E` |
| `#3B0764` | `#2E0B31` |
| `#A855F7`, `#C084FC` | `#9B5A9F` |
| `#D8B4FE`, `#E9D5FF` | `#E8D2E8` |
| `#F3E8FF` | `#F4E9F4` |
| `#FAF5FF`, `#FAF9FD` | `#FBF6FB` |

Afecta como mínimo a `resources/css/app.css` (scrollbar y selección), `components/application-logo.blade.php` (degradado del logo) y `layouts/app.blade.php`.
Revisa también si hay clases `violet-*`, `indigo-*` o `fuchsia-*` y cámbialas por `purple-*`.

---

## Paso 3 — Menú lateral por paquetes

### 3.1 `config/menu.php` (nuevo)

Un solo lugar define qué casos de uso tiene cada paquete. **Ajusta los nombres de ruta** a los reales de `php artisan route:list`. Si una ruta todavía no existe, el ítem aparece en gris con la etiqueta "Pronto" (no da error).

```php
<?php

/*
| Menú lateral organizado por los 6 paquetes del documento PUDS (sección 4.5.1).
| permiso: código de la tabla permiso (string, o array = basta con uno).
| activo:  patrones de nombre de ruta que marcan el ítem como actual.
*/
return [
    [
        'paquete' => 'P1', 'titulo' => 'Seguridad y Accesos',
        'items' => [
            ['cu' => 'CU02', 'titulo' => 'Usuarios', 'ruta' => 'usuarios.index', 'activo' => ['usuarios.*'], 'permiso' => 'usuario.gestionar'],
            ['cu' => 'CU03', 'titulo' => 'Roles y permisos', 'ruta' => 'roles.index', 'activo' => ['roles.*'], 'permiso' => 'rol.gestionar'],
            ['cu' => 'RF04', 'titulo' => 'Bitácora', 'ruta' => 'bitacora.index', 'activo' => ['bitacora.*'], 'permiso' => 'bitacora.ver'],
        ],
    ],
    [
        'paquete' => 'P2', 'titulo' => 'Gestión de Pacientes',
        'items' => [
            ['cu' => 'CU05 · CU06', 'titulo' => 'Pacientes y antecedentes', 'ruta' => 'pacientes.index', 'activo' => ['pacientes.*'], 'permiso' => ['paciente.gestionar', 'antecedente.gestionar']],
            ['cu' => 'CU04', 'titulo' => 'Mi perfil', 'ruta' => 'perfil.edit', 'activo' => ['perfil.*'], 'permiso' => 'perfil.gestionar'],
        ],
    ],
    [
        'paquete' => 'P3', 'titulo' => 'Oferta Médica',
        'items' => [
            ['cu' => 'CU07', 'titulo' => 'Especialidades', 'ruta' => 'especialidades.index', 'activo' => ['especialidades.*'], 'permiso' => 'especialidad.gestionar'],
            ['cu' => 'CU08', 'titulo' => 'Médicos', 'ruta' => 'medicos.index', 'activo' => ['medicos.*'], 'permiso' => 'medico.gestionar'],
            ['cu' => 'CU09', 'titulo' => 'Horarios de atención', 'ruta' => 'horarios.index', 'activo' => ['horarios.*'], 'permiso' => 'horario.gestionar'],
            ['cu' => 'CU10', 'titulo' => 'Indisponibilidad médica', 'ruta' => 'indisponibilidades.index', 'activo' => ['indisponibilidades.*'], 'permiso' => 'indisponibilidad.registrar'],
            ['cu' => 'CU11', 'titulo' => 'Tarifario', 'ruta' => 'tarifas.index', 'activo' => ['tarifas.*'], 'permiso' => 'tarifa.gestionar'],
        ],
    ],
    [
        'paquete' => 'P4', 'titulo' => 'Gestión de Citas',
        'items' => [
            ['cu' => 'CU12 · CU13', 'titulo' => 'Citas disponibles y reservar', 'ruta' => 'fichas.index', 'activo' => ['fichas.*'], 'permiso' => 'cita.consultar'],
            ['cu' => 'CU15 · CU16 · CU17', 'titulo' => 'Citas: reprogramar, cancelar, asistencia', 'ruta' => 'citas.index', 'activo' => ['citas.*'], 'permiso' => ['cita.consultar', 'cita.asistencia']],
            ['cu' => 'CU14', 'titulo' => 'Pagar cita en línea', 'ruta' => 'pagos.index', 'activo' => ['pagos.*'], 'permiso' => 'pago.en_linea'],
        ],
    ],
    [
        'paquete' => 'P5', 'titulo' => 'Atención Médica',
        'items' => [
            ['cu' => 'CU20 · CU21', 'titulo' => 'Agenda y atención', 'ruta' => 'agenda.index', 'activo' => ['agenda.*', 'atencion.*'], 'permiso' => ['agenda.consultar', 'atencion.registrar']],
            ['cu' => 'CU22', 'titulo' => 'Historial del paciente', 'ruta' => 'historial.index', 'activo' => ['historial.*'], 'permiso' => 'historial.consultar'],
        ],
    ],
    [
        'paquete' => 'P6', 'titulo' => 'Gestión Financiera y Reportes',
        'items' => [
            ['cu' => 'CU18', 'titulo' => 'Caja', 'ruta' => 'caja.index', 'activo' => ['caja.*'], 'permiso' => 'caja.gestionar'],
            ['cu' => 'CU19', 'titulo' => 'Cobros en mostrador', 'ruta' => 'cobros.index', 'activo' => ['cobros.*'], 'permiso' => 'caja.cobrar'],
            ['cu' => 'CU23', 'titulo' => 'Reportes', 'ruta' => 'reportes.index', 'activo' => ['reportes.*'], 'permiso' => 'reporte.generar'],
        ],
    ],
];
```

### 3.2 `resources/views/layouts/navigation.blade.php` (reemplazar todo el contenido)

Ahora es la barra lateral. Solo muestra los paquetes y los ítems para los que el rol tiene permiso, y el paquete de la pantalla actual se abre solo.

```blade
@php
    $usuario = Auth::user();

    // Arma el menú filtrado por permisos del rol (CU03).
    $paquetes = collect(config('menu'))->map(function ($paquete) use ($usuario) {
        $items = collect($paquete['items'])
            ->filter(fn ($i) => collect((array) $i['permiso'])->contains(fn ($c) => $usuario->tienePermiso($c)))
            ->map(function ($i) {
                $i['disponible'] = Route::has($i['ruta']);
                $i['actual'] = request()->routeIs(...(array) $i['activo']);
                return $i;
            })
            ->values();

        return $paquete + ['visibles' => $items, 'abierto' => $items->contains('actual', true)];
    })->filter(fn ($p) => $p['visibles']->isNotEmpty())->values();
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-72 flex flex-col bg-white border-r border-purple-100 shadow-xl shadow-purple-950/10 lg:shadow-none transition-transform duration-200 lg:translate-x-0"
       :class="menu ? 'translate-x-0' : '-translate-x-full'">

    {{-- Logo --}}
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-purple-100">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <x-application-logo size="sm" :showText="true" />
        </a>
        <button type="button" @click="menu = false" class="lg:hidden p-2 rounded-lg text-purple-700 hover:bg-purple-50" aria-label="Cerrar menú">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" aria-label="Menú principal">
        {{-- Inicio --}}
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors
                  {{ request()->routeIs('dashboard') ? 'bg-purple-800 text-white shadow-sm' : 'text-gray-700 hover:bg-purple-50 hover:text-purple-900' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Inicio
        </a>

        <p class="px-3 pt-5 pb-1 text-[11px] font-semibold uppercase tracking-wider text-purple-900/50">Paquetes</p>

        @foreach ($paquetes as $paquete)
            <div x-data="{ open: @js($paquete['abierto']) }">
                {{-- Cabecera del paquete --}}
                <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors
                               {{ $paquete['abierto'] ? 'bg-purple-50 text-purple-950' : 'text-gray-700 hover:bg-purple-50 hover:text-purple-950' }}">
                    <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center text-xs font-bold
                                 {{ $paquete['abierto'] ? 'bg-purple-800 text-white' : 'bg-purple-100 text-purple-800' }}">
                        {{ $paquete['paquete'] }}
                    </span>
                    <span class="flex-1 text-left leading-tight">{{ $paquete['titulo'] }}</span>
                    <svg class="w-4 h-4 shrink-0 text-purple-400 transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                {{-- Casos de uso del paquete --}}
                <ul x-show="open" x-transition.opacity.duration.150ms
                    class="mt-1 mb-2 ml-7 pl-4 border-l-2 border-purple-100 space-y-0.5"
                    @unless ($paquete['abierto']) style="display: none" @endunless>
                    @foreach ($paquete['visibles'] as $item)
                        <li>
                            @if ($item['disponible'])
                                <a href="{{ route($item['ruta']) }}"
                                   @if ($item['actual']) aria-current="page" @endif
                                   class="block px-3 py-2 rounded-lg text-sm transition-colors
                                          {{ $item['actual'] ? 'bg-purple-100 text-purple-950 font-semibold' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-950' }}">
                                    <span class="block leading-snug">{{ $item['titulo'] }}</span>
                                    <span class="block text-[11px] font-medium text-purple-700/60">{{ $item['cu'] }}</span>
                                </a>
                            @else
                                <span class="block px-3 py-2 rounded-lg text-sm text-gray-400 cursor-not-allowed" title="En desarrollo">
                                    <span class="flex items-center justify-between gap-2 leading-snug">
                                        {{ $item['titulo'] }}
                                        <span class="shrink-0 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">Pronto</span>
                                    </span>
                                    <span class="block text-[11px] font-medium">{{ $item['cu'] }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 px-5 py-3 border-t border-purple-100 text-[11px] text-purple-900/50">
        Vida Salud · Sistema clínico
    </div>
</aside>
```

### 3.3 `resources/views/layouts/partials/menu-usuario.blade.php` (nuevo)

Mueve aquí el **dropdown de usuario** que hoy está en `navigation.blade.php` (el `<x-dropdown align="right" width="56">` completo, con "Mi Perfil" y "Cerrar sesión") **sin cambiar su contenido**. Envuélvelo en un contenedor que no se encoja:

```blade
<div class="shrink-0">
    {{-- pegar aquí el <x-dropdown ...> ... </x-dropdown> actual del menú de usuario --}}
</div>
```

En pantallas pequeñas oculta el nombre (deja solo el avatar) para que no se monte sobre el título: agrega `hidden sm:inline` al `<span>` del username dentro del botón.

### 3.4 `resources/views/layouts/app.blade.php` (cambiar el `<body>`)

Mantén el `<head>` igual. Reemplaza el contenido de `<body>` por esto, que incluye el **menú lateral fijo**, la **barra superior** con el título de la página y el usuario, y el **menú deslizable en celular**:

```blade
<body class="font-sans antialiased text-gray-900 bg-[#FBF6FB] min-h-screen selection:bg-purple-200 selection:text-purple-900"
      x-data="{ menu: false }" @keydown.escape.window="menu = false">

    @include('layouts.navigation')

    {{-- Fondo oscuro detrás del menú en celular --}}
    <div x-show="menu" x-transition.opacity @click="menu = false"
         class="fixed inset-0 z-30 bg-purple-950/40 lg:hidden" style="display: none"></div>

    <div class="lg:pl-72 min-h-screen flex flex-col">
        {{-- Barra superior --}}
        <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-md border-b border-purple-100">
            <div class="flex items-center gap-3 min-h-16 px-4 sm:px-6 lg:px-8 py-3">
                <button type="button" @click="menu = true" class="lg:hidden p-2 -ml-2 rounded-lg text-purple-800 hover:bg-purple-50" aria-label="Abrir menú">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="flex-1 min-w-0">
                    @isset($header)
                        {{ $header }}
                    @endisset
                </div>

                @include('layouts.partials.menu-usuario')
            </div>
        </header>

        <main class="flex-1 py-2">
            {{ $slot }}
        </main>

        <footer class="py-5 text-center text-xs text-purple-900/60 border-t border-purple-100/60 bg-white/40">
            &copy; 2026 Vida Salud. Todos los derechos reservados.
        </footer>
    </div>

    @stack('scripts')
</body>
```

Notas:
- El contenido de las páginas ya usa `max-w-7xl mx-auto`, así que se centra solo en el espacio que queda a la derecha del menú.
- Si alguna vista tenía su propio menú de pestañas (por ejemplo, `oferta/_tabs.blade.php`), puede quedarse: ahora complementa al menú lateral.
- Borra el antiguo menú responsive (el `x-data="{ open: false }"` de la barra superior): el menú lateral lo reemplaza.

---

## Paso 4 — Login con la nueva paleta y sin superposiciones

- Mantén `auth/login.blade.php` como está; con el Paso 1 corregido, los íconos ya no se montan, porque el input tiene `pl-11 pr-11`.
- Con el Paso 2, el botón queda en `purple-800` (`#5A215E`), como "Sign In" en la referencia.
- **Opcional** (pregúntale a la usuaria antes): en `layouts/guest.blade.php`, una versión de **dos columnas** como la referencia. A la izquierda (solo en pantallas `lg`), un panel `bg-purple-50` con el logo grande, la frase "Clínica & Citas Médicas" y círculos decorativos en `purple-200/40`. A la derecha, el formulario actual. **No uses fotos de personas ni imágenes copiadas de la referencia.**

---

## Paso 5 — Verificar

- [ ] `npm run build` termina sin errores y `grep -o "\.pl-11" public/build/assets/*.css` encuentra la clase.
- [ ] Con `php artisan serve` + `npm run dev`, revisa con **admin**, **recepcionista**, **médico** y **paciente**:
  - Cada rol ve solo sus paquetes. Por ejemplo, el paciente no ve P1 ni P3.
  - Al entrar a una pantalla, su paquete aparece abierto y el ítem resaltado.
  - Los ítems sin ruta aparecen como "Pronto" y no dan error.
- [ ] Revisa los anchos **1366 px** (laptop) y **390 px** (celular): nada se superpone; en celular el menú se abre con ☰ y se cierra con ✕, con Esc o tocando fuera.
- [ ] El login, la lista de usuarios y la de citas se ven con inputs redondeados y los íconos no tapan el texto.
- [ ] `composer test` sigue en verde.
- [ ] Commit: `feat(ui): menú lateral por paquetes y paleta ciruela`. **Pide confirmación antes del push.**

# Vida Salud — Sistema web de gestión de citas médicas (reserva de fichas)

Grupo 4 · INF 342 Sistemas de Información I · UAGRM · 2-2026

Sistema web para la Clínica Vida Salud: reserva de fichas por turno, agenda médica, atención, historial y reportes.
El contexto funcional completo (casos de uso, reglas del negocio, modelo de datos y arquitectura) está en
[docs/CONTEXTO_PROYECTO.md](docs/CONTEXTO_PROYECTO.md). **Léelo antes de programar.**

---

## Stack

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje | PHP (XAMPP) | 8.2 |
| Framework | Laravel | 12 |
| Base de datos | PostgreSQL (driver `pdo_pgsql`) | 16 o superior (probado en 18) |
| Autenticación | Laravel Breeze (stack Blade) | 2.x |
| Vistas | Blade + Bootstrap 5 | 5.3 |
| PDF | barryvdh/laravel-dompdf | 3.x |
| Front | JavaScript sin frameworks + Vite | Node.js 20 o superior |

> Se usa Laravel 12 y no 11: todas las versiones de Laravel 11 tienen avisos de seguridad sin parche y Composer se niega a instalarlas.

---

## 1. Requisitos (Windows)

Instala estas herramientas:

1. **XAMPP 8.2** (trae PHP 8.2): https://www.apachefriends.org
2. **Composer**: https://getcomposer.org
3. **Node.js LTS**: https://nodejs.org
4. **PostgreSQL 16+ con pgAdmin**: https://www.postgresql.org/download/windows/
5. **Git**: https://git-scm.com

### Habilitar PostgreSQL en PHP

Edita `C:\xampp\php\php.ini` y quita el `;` del inicio de estas dos líneas:

```ini
extension=pdo_pgsql
extension=pgsql
```

Comprueba que quedaron habilitadas:

```bash
php -m | findstr pgsql      # debe mostrar pdo_pgsql y pgsql
```

---

## 2. Base de datos (pgAdmin)

1. Verifica que el servicio de PostgreSQL esté corriendo (`services.msc` → `postgresql-x64-NN` → Iniciar).
2. En pgAdmin crea la base de datos **`SI1-CLINICA`** (Encoding `UTF8`).
3. Abre el **Query Tool** sobre `SI1-CLINICA` y ejecuta:

```sql
CREATE EXTENSION IF NOT EXISTS btree_gist;          -- necesaria para la restricción anti-superposición de citas
ALTER DATABASE "SI1-CLINICA" SET timezone TO 'America/La_Paz';
```

> El nombre lleva guion, por eso va entre comillas dobles en SQL.

---

## 3. Instalación del proyecto

```bash
git clone https://github.com/adrielybazoalto/SI1_CitasClinicas.git
cd SI1_CitasClinicas

composer install          # dependencias PHP (Laravel, Breeze, dompdf)
npm install               # dependencias JS (Vite, Bootstrap, Popper)

copy .env.example .env    # en Git Bash: cp .env.example .env
php artisan key:generate
```

Edita `.env` y coloca **tu** contraseña de PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=SI1-CLINICA
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña
```

Crea las tablas y compila los assets:

```bash
php artisan migrate        # (más adelante: php artisan migrate --seed)
npm run build
```

> `.env` nunca se sube al repositorio. Cada desarrollador tiene el suyo a partir de `.env.example`.

---

## 4. Ejecutar en desarrollo

Usa tres terminales:

```bash
php artisan serve          # http://127.0.0.1:8000
npm run dev                # Vite con recarga automática
php artisan schedule:work  # temporizador (inasistencias, recordatorios)
```

---

## 5. Configuración ya aplicada

| Qué | Dónde | Valor |
|---|---|---|
| Zona horaria | `config/app.php`, `.env` | `America/La_Paz` (sin esto, "no permitir fechas pasadas" falla por 4 h) |
| Idioma | `.env` | `APP_LOCALE=es` |
| Sesión | `.env` | `SESSION_LIFETIME=15` (CU01: expira a los 15 min) |
| Reglas del negocio | `config/clinica.php` | RN16–RN31, bloqueo de login y formatos de códigos. Usar `config('clinica.xxx')`, nunca valores fijos |
| Middleware de permisos | `bootstrap/app.php` | alias `permiso` → `->middleware('permiso:cita.programar')` |

---

## 6. Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/          # Breeze
│   │   ├── Seguridad/     # UsuarioController, RolController, BitacoraController
│   │   ├── Pacientes/     # PacienteController, AntecedenteController, PerfilController
│   │   ├── Oferta/        # Especialidad, Medico, Horario, Indisponibilidad, Tarifa
│   │   ├── Citas/         # FichaController, CitaController, AsistenciaController, PagoController
│   │   ├── Caja/          # CajaController, CobroController (ciclo 2)
│   │   ├── Atencion/      # AgendaController, AtencionController, HistorialController
│   │   └── Reportes/      # ReporteController
│   ├── Middleware/VerificarPermiso.php
│   └── Requests/          # un Form Request por formulario, por paquete
├── Models/                # 17 modelos con $table, $primaryKey y $timestamps ya configurados
├── Services/              # TODA la lógica del negocio (Disponibilidad, Cita, CitaEstado, ...)
└── Exceptions/FichaNoDisponibleException.php
config/clinica.php         # parámetros de las reglas del negocio
database/
├── migrations/            # replicarán el script SQL de la BD
├── seeders/
└── sql/                   # script SQL de referencia
docs/CONTEXTO_PROYECTO.md  # contexto funcional y técnico completo
resources/
├── views/                 # seguridad/ pacientes/ oferta/ citas/ caja/ atencion/ reportes/ pdf/
└── js/                    # validaciones.js, fichas.js, voz.js
routes/
├── web.php                # rutas agrupadas por paquete con middleware permiso:
└── console.php            # Scheduler (temporizador)
```

---

## 7. Reglas para programar

1. Lógica del negocio **solo en `app/Services`**; controladores livianos.
2. Reservar, reprogramar, cancelar y registrar atención van dentro de `DB::transaction`.
3. Todo cambio de estado de una cita pasa por `CitaEstadoService`.
4. Nada se borra físicamente: se desactiva (`estado`) o se cambia el estado de la cita.
5. Validación en cliente (JS) **y** en servidor (Form Request).
6. Tablas y columnas en español, singular y `snake_case` (`cita`, `id_cita`); no traducir ni pluralizar.
7. Código y comentarios en español.

---

## 8. Estado del desarrollo

- [x] Proyecto Laravel 12 + Breeze + dompdf + Bootstrap instalados
- [x] Estructura de carpetas, controladores, modelos y servicios base
- [x] Configuración de PostgreSQL, zona horaria, sesión y reglas del negocio
- [ ] Migraciones de las 20 tablas + seeders
- [ ] Breeze adaptado a la tabla `usuario` + permisos + layout Bootstrap con menú por rol
- [ ] Casos de uso del ciclo 1 (ver orden en `docs/CONTEXTO_PROYECTO.md`, sección 10)

---

## 9. Flujo de trabajo con Git

```bash
git pull                        # antes de empezar
git checkout -b feature/cu13-programar-cita
# ... cambios ...
git add .
git commit -m "CU13: programar cita con transacción"
git push -u origin feature/cu13-programar-cita
# luego abrir un Pull Request hacia main en GitHub
```

Después de un `git pull` que traiga cambios de dependencias o de la BD:

```bash
composer install
npm install
php artisan migrate
```

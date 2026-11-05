# CLAUDE.md — Plan de trabajo · Sistema de Citas Médicas "Vida Salud"

> Archivo para Claude Code. Va en la **raíz del repositorio** (`SI1_CitasClinicas/CLAUDE.md`).
> Proyecto INF 342 Sistemas de Información I · UAGRM · Grupo 4 · 2-2026.

**Fechas que mandan:** Presentación 2 (100 % del ciclo 2) el **domingo 08/11/2026**. Defensa el **martes 10/11/2026**.

---

## 0. Cómo trabajar en este repositorio

### Fuentes de verdad (en este orden)

1. **Documentación PUDS** `docs/SI1-CLINICA_14.pdf`: casos de uso, paquetes, reglas y diseño de datos. *(Copia el PDF a `docs/` si aún no está.)*
2. **`docs/CONTEXTO_PROYECTO.md`**: detalle de cada CU (§3), estados de la cita (§4), reglas del negocio RN01–RN31 (§5), tablas (§6), arquitectura (§7) y pruebas mínimas (§11).
3. **Este archivo**: el plan, el orden y el estado de avance.

Si dos fuentes se contradicen, gana el PDF. Avísale a la usuaria en vez de decidir en silencio.

### Reglas de oro (no negociables)

- **Arquitectura:** Ruta → Middleware `permiso:codigo` → Controller (liviano) → Form Request → **Service** (reglas + `DB::transaction`) → Model → PostgreSQL.
- La lógica del negocio va **solo en `app/Services`**. Toda transición de estado de una cita pasa por `CitaEstadoService`.
- **Nada se borra físicamente**: se usa `estado = false` o el estado de la cita.
- Tablas y columnas en **español, snake_case y singular**, tal como están en las migraciones. No las traduzcas ni las pluralices.
- Valores de las reglas del negocio en `config/clinica.php`, **nunca fijos en el código**.
- Toda operación crítica se registra en la bitácora (`BitacoraService`).
- Validación en cliente (JS) **y** en servidor (Form Request).
- Código y comentarios en español. En los textos visibles para el usuario usa "cita" (no "ficha"), como la documentación. Los nombres de clases existentes (`FichaController`, etc.) **no se renombran**.
- Antes de cada CU, lee su detalle en `CONTEXTO_PROYECTO.md` §3 y su especificación en el PDF (sección 4.3).

### 🚫 Prohibido (la base está en Supabase y es compartida)

- `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe` o cualquier `DROP`/`TRUNCATE` contra Supabase.
- Ejecutar los tests sin la protección de la Fase 0.2.
- Modificar una migración que ya está aplicada en Supabase. Para cambios, crea una **migración nueva**.
- Subir `.env`, contraseñas o claves al repositorio.
- Hacer `git push`, merge a `main` o cualquier acción destructiva **sin preguntar antes**.

### Menú: un ítem por caso de uso

`config/menu.php` define un ítem por CU (CU24 = Consultar bitácora). Cuando dos CU comparten pantalla, el ítem usa `?accion=...`, que solo filtra y muestra un aviso (`<x-aviso-modulo>`); no se crean pantallas duplicadas. `App\Support\MenuModulos::actual()` decide el CU actual para el menú y para `<x-encabezado-modulo>`.

### Rama de trabajo

Todo se trabaja y se commitea en la rama **`amalia`** (decisión de la usuaria, 04/11). No crear ramas por paquete.

### Al terminar cada CU

1. Prueba manual con los usuarios de demostración.
2. Agrega o actualiza sus tests en `tests/Feature/<Paquete>/` y ejecuta `php artisan test`.
3. Marca su casilla en este archivo (`[x]`).
4. Haz un commit con el formato `feat(P5): CU20 consultar agenda médica`.

---

## 1. Organización por paquetes (6)

| Paquete | Casos de uso | Carpetas del código |
|---|---|---|
| **P1 Seguridad y Accesos** | CU01, CU02, CU03, CU24 | `Controllers/Seguridad`, `Controllers/Auth`, `views/seguridad`, `views/auth` |
| **P2 Gestión de Pacientes** | CU04, CU05, CU06 | `Controllers/Pacientes`, `views/pacientes` |
| **P3 Oferta Médica** | CU07, CU08, CU09, CU10, CU11 | `Controllers/Oferta`, `views/oferta` |
| **P4 Gestión de Citas** | CU12, CU13, CU14, CU15, CU16, CU17 (+ voz RNF20) | `Controllers/Citas`, `views/citas` |
| **P5 Atención Médica** | CU20, CU21, CU22 | `Controllers/Atencion`, `views/atencion` |
| **P6 Gestión Financiera y Reportes** | CU18, CU19, CU23 | `Controllers/Caja`, `Controllers/Reportes`, `views/caja`, `views/reportes`, `views/pdf` |

**Dependencias (sin ciclos):** P2 → P1 · P3 → P1 · P4 → P1, P2, P3 · P5 → P1, P2, P3, P4 · P6 → P1…P5.
Un paquete **nunca** llama a otro que esté por encima de él en esta lista. Las entidades `Pago` y `Comprobante` viven en P4, porque las usan el pago en línea (CU14) y el cobro en mostrador (CU19).

---

## 2. Estado actual (al 04/11/2026)

| Paquete | CU | Ciclo | Estado |
|---|---|:-:|---|
| P1 | CU01, CU02, CU03 | 1 | ✅ Hecho · ✅ consulta de bitácora (`amalia`) |
| P2 | CU05, CU06 | 1 | ✅ Hecho |
| P2 | CU04 | 2 | ✅ Hecho (04/11) |
| P3 | CU07, CU08, CU09 | 1 | ✅ Hecho |
| P3 | CU10, CU11 | 2 | ✅ Hechos (04/11) · CU10: falta decidir Confirmada → Cancelada |
| P4 | CU12, CU13, CU15, CU16, CU17 | 1 | ✅ Hecho en la rama `adriely` (falta llevarlo a `main`) |
| P4 | CU14 + voz | 2 | ✅ Hechos (04/11) |
| P5 | CU20, CU21, CU22 | 1 | ✅ Hecho en `amalia` (04/11) |
| P6 | CU23 | 1 | ✅ Hecho en `amalia` (04/11) |
| P6 | CU18, CU19 | 2 | ✅ Hechos (04/11) |

---

## 3. Orden de trabajo

```
FASE 0  Base de datos y repositorio   ← PRIMERO, nada más se toca antes
FASE 1  Cerrar ciclo 1                 P5 (CU20 → CU21 → CU22) → P6 (CU23) → P1 (bitácora)
FASE 2  Ciclo 2                        P3 CU11 → P6 CU18, CU19 → P3 CU10 → P2 CU04 → P4 CU14 → P4 voz
FASE 3  Pruebas, despliegue y documentación
```

---

## FASE 0 — Arreglar la base de datos y el repositorio

### 0.1 Diagnóstico (solo lectura)

```bash
php artisan config:clear
php artisan db:show --counts
```

- [x] Informa a la usuaria a qué **host** estás conectado: `127.0.0.1` (local) o `*.supabase.com` (Supabase).
- [x] Informa cuántos registros tiene cada tabla.
- [x] Si estás en la base local y existe `.env.supabase.bak`, **pregunta** antes de cambiar el `.env`.

En Supabase (SQL Editor), revisa si los datos se borraron o si las tablas se recrearon:

```sql
select relname tabla, n_live_tup filas, n_tup_ins insertadas, n_tup_del borradas
from pg_stat_user_tables where schemaname = 'public' order by relname;
```

`borradas > 0` significa que hubo DELETE o TRUNCATE. Si `insertadas` es casi 0, las tablas se recrearon (`migrate:fresh` o tests).

### 0.2 Proteger Supabase de los tests (causa probable de la pérdida de datos)

Los tests usan `RefreshDatabase`. `phpunit.xml` fuerza SQLite en memoria, **pero** si la configuración está en caché (`php artisan config:cache`), Laravel la ignora y ejecuta `migrate:fresh` **sobre Supabase**.

- [x] En `tests/TestCase.php`, agrega una guardia que detenga los tests si la conexión no es SQLite:

```php
protected function setUp(): void
{
    parent::setUp();
    if (config('database.default') !== 'sqlite') {
        $this->fail('PELIGRO: los tests deben correr en SQLite. Ejecuta "php artisan config:clear".');
    }
}
```

- [x] Agrega un script en `composer.json`: `"test": ["@php artisan config:clear", "@php artisan test"]`. A partir de ahora los tests se ejecutan con `composer test`.

### 0.3 Secretos expuestos

En el historial de Git quedaron la contraseña de Supabase y el `APP_KEY` (`render.yaml` antes del 04/11 y `.env.supabase.bak`).

- [x] Ejecuta `git rm --cached .env.supabase.bak`.
- [x] Agrega a `.gitignore` las líneas `.env.*.bak` y `*.bak`.
- [x] Revisa `Contrasenas_de_prueba.md`: solo puede tener **usuarios de demostración**, nunca credenciales de Supabase, Render ni PayPal.
- [ ] **Avisa a la usuaria** que haga esto a mano (Claude no puede hacerlo):
  1. En Supabase → Settings → Database → **Reset database password**.
  2. Actualizar la contraseña en su `.env` local y en Render → Environment.
  3. Generar un `APP_KEY` nuevo (`php artisan key:generate --show`) y ponerlo en Render.

### 0.4 Verificar el esquema en Supabase

Ejecuta estas consultas y compara el resultado con el script del PDF (sección 6.2.2.1):

```sql
select conrelid::regclass tabla, conname from pg_constraint
where contype in ('x','c') and connamespace = 'public'::regnamespace order by 1, 2;
select indexname from pg_indexes where schemaname = 'public' and indexname like 'ux_%';
select tgname, tgrelid::regclass from pg_trigger where not tgisinternal;
show timezone;
```

Debe existir lo siguiente. Lo que falte se agrega con **una migración nueva** que solo actúe en `pgsql`:

- [x] `ex_cita_sin_superposicion` (EXCLUDE) y los CHECK de `cita`, `horario`, `atencion`, `pago` y `caja`.
- [x] El índice único `lower(trim(nombre))` de `especialidad` y el índice parcial `ux_caja_abierta`.
- [x] El trigger de **bitácora inmutable** (RNF08) — migración `2026_11_04_000001` aplicada en Supabase el 04/11, que no existe en las migraciones actuales: una función que lance `RAISE EXCEPTION` ante un `UPDATE` o `DELETE` sobre `bitacora`.
- [x] La zona horaria `America/La_Paz`. Si no lo está: `ALTER DATABASE postgres SET timezone TO 'America/La_Paz';`

### 0.5 Población de datos (no existe en el repositorio)

Crea `database/seeders/DatosDemoSeeder.php` y regístralo en `DatabaseSeeder` después de `SeguridadSeeder`.

> **Decisión de la usuaria (04/11):** los datos maestros salen de `02_poblacion.sql` (23 usuarios con clave `<username>@2026`, 13 pacientes, 6 médicos, especialidades con Odontología, 13 horarios, tarifas). Solo las citas se generan relativas a hoy. Prevalece sobre la tabla de abajo.

Requisitos del seeder:

- **Idempotente:** se identifica cada registro por su código, CI o username (`firstOrCreate`). Ejecutarlo dos veces no duplica nada ni borra nada.
- **Fechas relativas a hoy** (`now()`), para que la demo del 08/11 y del 10/11 tenga citas pasadas, de hoy y futuras.
- **Respeta todas las restricciones:** horarios, duración, sin superposición, CI y matrícula únicos, estados válidos y RN19 (1 cita por médico por día, máximo 3 activas por paciente).
- Las citas se ubican **en los turnos reales** del médico (`hora_inicio` del turno + k × duración; `nro_cita = k + 1`). Reutiliza el cálculo de turnos de `DisponibilidadService`, no lo dupliques.

| Tabla | Datos de demostración |
|---|---|
| `usuario` | `admin` (ya existe), 2 recepcionistas, 6 médicos y 12 pacientes; todos con `debe_cambiar_clave = false` y la clave de demostración `Clinica2026` |
| `especialidad` | Medicina General (20 min), Pediatría (20), Cardiología (30), Ginecología (30), Traumatología (30), Dermatología (20) |
| `medico` + `medico_especialidad` | 6 médicos `MED-0001…0006` con matrícula única; 1 o 2 especialidades cada uno |
| `horario` | Lunes a viernes; turno mañana 08:00–12:00 o tarde 14:00–18:00; consultorio `C-101…`; duración = la de la especialidad |
| `tarifa` | 1 tarifa vigente por especialidad (Bs 80 a 200), `vigente_desde = hoy − 60 días` |
| `paciente` + `antecedente` | 12 pacientes `PAC-000001…`; 8 con antecedentes, 3 de ellos **con alergias** (para que se vean resaltadas en CU21) |
| `cita` | Unas 45: **pasadas** (−30 días): 15 Atendida, 5 Cancelada con motivo, 4 Inasistencia. **Hoy**: 3 Programada y 2 Confirmada con `hora_llegada`. **Futuras** (+14 días): 15 Programada. Canales LINEA y MOSTRADOR; `monto` = tarifa vigente |
| `atencion` | 1 por cada cita Atendida, con diagnóstico, tratamiento y signos vitales dentro de rango |
| `historial_estado_cita` | Cada transición de cada cita (Programada → Confirmada → Atendida, etc.) |
| `indisponibilidad` | 1 periodo futuro de un médico (para probar CU10) |

- [x] Ejecuta solo `php artisan db:seed --class=DatosDemoSeeder`. **Nunca** `migrate:fresh --seed`.
- [x] Verifica con `php artisan db:show --counts`.
- [ ] Actualiza `Contrasenas_de_prueba.md` con la tabla de usuarios de demostración (usuario, rol y clave `Clinica2026`).
- [ ] Opcional, para la documentación: exporta los datos a `database/sql/02_poblacion.sql` con `pg_dump --data-only --inserts`.

### 0.6 Unificar el código en `main`

Render despliega desde `main`. El Paquete 4 está en `adriely`, y la usuaria tiene commits locales en `amalia`.

- [x] Ejecuta `git fetch origin` y muestra `git log --oneline origin/main..origin/adriely` y `git log --oneline origin/main..amalia`.
- [ ] Propón el orden del merge (primero `adriely` → `main`, luego `amalia` → `main`), resuelve los conflictos y **pide confirmación antes de cada push**.
- [ ] Después del merge, en `main`: `composer install`, `npm install`, `npm run build`, `composer test`. Todo debe pasar.
- [x] ~~Una rama por paquete~~ → **Decisión de la usuaria (04/11): todo el trabajo va en la rama `amalia`.** No crear ramas nuevas; `amalia` se sube a `main` con un Pull Request cuando ella lo apruebe.

---

## FASE 1 — Cerrar el ciclo 1 (bloquea la defensa)

### P5 · Atención Médica

**Permisos:** `agenda.consultar`, `atencion.registrar`, `historial.consultar`.
**Archivos:** `Controllers/Atencion/{Agenda,Atencion,Historial}Controller.php`, `Services/AtencionService.php` y `views/atencion/`.

#### CU20 Consultar agenda médica
- [x] Ruta `GET /atencion/agenda` con `permiso:agenda.consultar`.
- [x] El **médico** ve directamente su agenda del día (usuario → `medico.id_usuario`) y no puede ver la de otro médico. El administrador y la recepcionista eligen médico y fecha.
- [x] La lista va ordenada por hora, con: hora, código, paciente, especialidad, estado y motivo breve.
- [x] Filtro por estado (pendientes, confirmadas, atendidas, canceladas e inasistencias).
- [x] **Vista semanal:** los 7 días, con la cantidad de citas por estado.
- [x] En las citas **Confirmada**, botón "Atender" (activo desde CU21) que lleva a CU21. Solo aparece si el usuario tiene `atencion.registrar`.
- [x] El motivo se oculta a la recepcionista (excepción de CU20). Lo ven el médico y el administrador.
- [x] Prueba mínima 8: "Buscar citas de un médico por fecha".

#### CU21 Registrar atención médica (incluye CU22; CU06 lo extiende)
- [x] `AtencionService::registrar(Cita $cita, array $datos, User $usuario)` dentro de `DB::transaction`:
  - La cita debe estar **Confirmada** (RN30) y pertenecer al médico del usuario.
  - El **diagnóstico** es obligatorio. Rangos: peso 0,5–400 kg, talla 20–250 cm, temperatura 30–45 °C, presión `^\d{2,3}/\d{2,3}$`.
  - Inserta en `atencion` (`id_cita` es único), luego `CitaEstadoService::transicionar($cita, 'Atendida', …)` y registra en la bitácora.
- [x] La pantalla tiene dos áreas: un **panel lateral** con los datos del paciente, los antecedentes con las **alergias resaltadas** (`alert-danger`) y las últimas atenciones; y el **formulario** de diagnóstico, tratamiento y signos vitales opcionales.
- [x] Enlace "Actualizar antecedentes" que abre CU06 y vuelve a esta pantalla.
- [x] Una cita Atendida ya no cambia de estado (RN10).

#### CU22 Consultar historial del paciente
- [x] Búsqueda de paciente por CI o nombre (reutiliza el buscador de CU05).
- [x] Citas en orden cronológico **descendente**, **incluidas las canceladas y las inasistencias**: fecha y hora, médico, especialidad, estado y fecha de registro.
- [x] Filtros por rango de fechas, médico y estado.
- [x] Detalle de una cita Atendida (diagnóstico, tratamiento y signos): **solo** lo ve quien tiene `atencion.registrar`. La recepcionista ve solo los datos de la cita.
- [x] El **paciente** ve directamente su propio historial, sin el detalle clínico, y no puede ver el de otro paciente.
- [x] Prueba mínima 7: "Consultar historial de un paciente".

### P6 · Reportes (CU23, ciclo 1)

**Permisos:** `reporte.generar`; `bitacora.ver` para los reportes exclusivos del administrador.
**Archivos:** `Controllers/Reportes/ReporteController.php`, `Services/ReporteService.php`, `views/reportes/` y `views/pdf/`.

- [x] Pantalla con un selector de reporte, filtros (rango de fechas, médico, especialidad, estado) y una tabla con **subtotales y total general**.
- [x] Reportes mínimos: citas programadas para una fecha · citas por médico · por especialidad · por estado · pacientes registrados · totales de atendidas, canceladas e inasistencias por periodo · agenda diaria de cada médico.
- [x] Solo para el administrador: **consulta de bitácora** e **ingresos por periodo** (cobros − reembolsos; ya funciona con los pagos de la población de demostración).
- [x] Exportación a **PDF** (dompdf, con logo y fecha de emisión) y a **CSV** UTF-8 con BOM, para que Excel lo abra con tildes. La exportación respeta los filtros aplicados.
- [x] Validaciones: fecha de inicio ≤ fecha de fin; si no hay resultados, mostrar un mensaje.
- [x] Las consultas van en `ReporteService`, agrupadas en la base de datos (`groupBy`), sin cálculos en las vistas.

### P1 · CU24 Consultar bitácora (antes RF04)

- [x] `BitacoraController@index` con `permiso:bitacora.ver`: tabla paginada, solo lectura, con filtros por fecha, usuario, acción y tabla.
- [x] Revisa que `BitacoraService` se llame en: login y logout, CRUD de usuarios, roles, pacientes, médicos y horarios, y al crear, reprogramar, cancelar y atender citas.
- [x] Revisa lo que debe cumplir CU01: bloqueo después de 5 intentos por 15 minutos, cierre de sesión a los 15 minutos de inactividad, recuperación por correo (enlace de 30 minutos — antes 60, ahora en `config/clinica.php` — y un solo uso) y contraseña temporal (`VerificarCambioClave`).

---

## FASE 2 — Ciclo 2

### P3 · CU11 Gestionar tarifario
- [x] `TarifaController` con `permiso:tarifa.gestionar`: lista de especialidades con su precio vigente.
- [x] Nuevo precio: monto > 0 y `vigente_desde ≥ hoy`. En la misma transacción se cierra la tarifa anterior (`vigente_hasta = nuevo.vigente_desde − 1 día`).
- [x] Vista "Historial de precios" de cada especialidad.
- [x] **RN22:** `CitaService::programar` guarda en `cita.monto` la tarifa vigente al momento de reservar. Las citas ya reservadas conservan su precio.

### P6 · CU18 Gestionar caja
- [x] `CajaService::abrir(usuario, monto_inicial)`: una sola caja ABIERTA por usuario (índice `ux_caja_abierta`). Si ya tiene una abierta, se muestra esa.
- [x] Cierre: la usuaria ingresa el conteo **por método de pago**. Efectivo esperado = monto inicial + cobros en efectivo − reembolsos en efectivo. Se calcula la diferencia; si no es cero, la observación es **obligatoria** (sobrante o faltante).
- [x] Una caja cerrada no se modifica. Reporte de arqueo en PDF.

### P6 · CU19 Cobrar cita en mostrador y emitir comprobante (extiende a CU13)
- [x] Requiere una caja abierta; si no hay, redirige a abrirla.
- [x] Cita Programada con `estado_pago = Pendiente`. El monto es `cita.monto`.
- [x] Métodos: EFECTIVO (monto recibido ≥ monto, con cálculo del cambio), TARJETA o TRANSFERENCIA.
- [x] En una sola transacción: `pago` (COBRO, APROBADO, `id_caja`) → `cita.estado_pago = Pagado` → `comprobante` con **número correlativo** (bloquea con `lockForUpdate` para que no se repita).
- [x] Comprobante interno en PDF para imprimir o enviar al correo del paciente.
- [x] Al programar una cita en mostrador (CU13, canal MOSTRADOR), se ofrece cobrarla en ese momento.
- [x] **Reembolsos (RF41, subflujo de CU16):** si se cancela una cita pagada con 24 horas o más de anticipación, se registra un `pago` tipo REEMBOLSO y `estado_pago = Reembolsado`. Con menos de 24 horas, no hay reembolso (RN26).
- [x] **CU17 en el ciclo 2:** para registrar la llegada, el pago debe estar en Pagado; si no lo está, se pide cobrar primero.
- [x] Actualiza la columna `permiso.paquete`: `caja.gestionar`, `caja.cobrar` y `reporte.generar` pasan a **'Finanzas y Reportes'** (en `SeguridadSeeder` y con un `UPDATE` en la migración `2026_11_04_000002`, **pendiente de aplicar en Supabase**). Así, la matriz de CU03 muestra los 6 paquetes.

### P3 · CU10 Registrar indisponibilidad médica y reubicar citas
- [x] Registro de un periodo (inicio < fin y motivo) con `permiso:indisponibilidad.registrar`. A partir de ese momento, `DisponibilidadService` ya no ofrece esos horarios (verifica que lo haga).
- [x] Lista de las citas Programada y Confirmada afectadas.
- [x] Por cada cita:
  - **Reubicar:** propone los primeros horarios libres del mismo médico o de otro médico de la misma especialidad. Usa `CitaService::reprogramar` en modo forzado (sin el límite de RN27) y conserva el código.
  - **Cancelar:** con el motivo "indisponibilidad médica" y **reembolso total** si estaba pagada (RN28).
- [x] Envía un correo a cada paciente afectado (`NotificacionService`).
- [ ] **Decisión pendiente:** la tabla de estados no permite Confirmada → Cancelada. Por ahora las citas Confirmadas afectadas solo se muestran con un aviso; falta que la usuaria decida si se habilita **solo** para CU10.
- [x] CU08: al desactivar un médico que tiene citas futuras, se ofrece ir a CU10.

### P2 · CU04 Gestionar perfil del paciente
- [x] "Mi perfil" con `permiso:perfil.gestionar`: el paciente solo accede a su propio registro.
- [x] Puede editar teléfono, correo, dirección y contacto de emergencia. El CI, los nombres y los antecedentes son **de solo lectura** (con un mensaje que indique acudir a recepción).
- [x] Cambio de contraseña: verifica la actual y pide la nueva dos veces (mínimo 8 caracteres).
- [x] Preferencia `recibe_recordatorios` (activar o desactivar).
- [x] Bloque "Mis próximas citas" con enlaces para reprogramar y cancelar.

### P4 · CU14 Pagar cita en línea (PayPal o QR) — extiende a CU13
- [x] En una reserva por el canal LINEA: `estado_pago = Pendiente` y `vence_pago_en = creado_en + 15 min` (RN20). La pantalla muestra un contador regresivo.
- [x] **PayPal Sandbox:** `PayPalService` con la API REST v2 (crear la orden y **capturarla al volver** en `/pago/retorno`). Las credenciales van en `.env` (`PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`, `PAYPAL_MODE=sandbox`). Webhook opcional con verificación de firma (RNF16) **no implementado** (el pago se confirma al capturar la orden). Monto en USD con `clinica.paypal_tipo_cambio`.
- [x] **QR:** genera un QR con el código de la cita y el monto, y deja un botón "Verificar pago" que simule la confirmación del banco. En la pantalla debe decir claramente que es una simulación de sandbox.
- [x] Cuando se confirma el pago: `pago` (PAYPAL o QR, `id_transaccion`, APROBADO) → `estado_pago = Pagado` → `comprobante` → correo al paciente.
- [x] **Temporizador:** en `routes/console.php`, agrega una tarea cada minuto que cancele las citas con pago vencido, usando `CitaEstadoService` con el motivo "pago vencido".

### P4 · Comandos de voz (RNF20, RF42–RF44)
- [x] `resources/js/voz.js` con Web Speech API (`lang = 'es-ES'`), en las pantallas de CU12, CU13 y CU20.
- [x] Botón de micrófono con indicador de "escuchando" y el texto reconocido en pantalla.
- [x] Navegación ("abrir agenda", "nueva cita", "citas disponibles"): solo hacia las rutas permitidas para el rol. El servidor pasa la lista a la vista.
- [x] Dictado de especialidad, médico, fecha y motivo: llena los campos y **nunca** envía el formulario solo. La usuaria confirma visualmente.
- [x] Si el navegador no tiene soporte o no se entiende el comando, se muestra un mensaje y se sigue con teclado y ratón.

---

## FASE 3 — Pruebas, despliegue y documentación

### Pruebas mínimas de la guía (`CONTEXTO_PROYECTO.md` §11)
- [ ] Las 10 pruebas implementadas como `tests/Feature` y ejecutadas con `composer test`.
- [ ] Una tabla para el PDF (sección 8.1) con: n.º, prueba, resultado esperado y **resultado obtenido**.

### Despliegue en Render
- [ ] Todo integrado en `main`, con los tests en verde.
- [ ] Variables de entorno en Render: DB (con la contraseña **nueva**), `APP_KEY` nuevo, `APP_DEBUG=false`, `SESSION_LIFETIME=15`, SMTP y PayPal sandbox.
- [ ] **Temporizador:** el plan gratuito de Render no tiene cron. Agrega `php artisan schedule:work &` en `docker/entrypoint.sh`, antes de iniciar nginx.
- [ ] Prueba la URL pública con los usuarios de demostración.

### Documentación (avisa a la usuaria qué secciones del PDF cambian)
- [ ] 7.1 y 7.2 del PDF: reemplazar "PHP puro / core/Router.php" por la estructura real de Laravel 12 (Controllers → Services → Models).
- [ ] Agregar al PDF la tabla de reglas del negocio RN01–RN31 (copiar de `CONTEXTO_PROYECTO.md` §5).
- [ ] Actualizar `docs/CONTEXTO_PROYECTO.md` §8 y §10 a los 6 paquetes.

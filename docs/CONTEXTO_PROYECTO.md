# CONTEXTO DEL PROYECTO — Sistema web de gestión de citas médicas (reserva de fichas) · Clínica Vida Salud

> Archivo de contexto para el asistente de IA del entorno de desarrollo (VS Code).
> Grupo 4 · INF 342 Sistemas de Información I · UAGRM · 2-2026.
> Fuente de verdad funcional: `Documentacion_PUDS_Vida_Salud.pdf`. Fuente de verdad de datos: `vida_salud.sql` (probado en PostgreSQL 16).

---

## 1. Qué estamos construyendo

Sistema web para la Clínica Vida Salud (clínica privada, ficticia) que reemplaza cuadernos, llamadas y Excel en la gestión de citas médicas.

**Modelo de negocio: reserva de fichas.** Cada médico atiende en turnos; cada turno tiene un número limitado de **fichas** (cupos numerados). Cada **cita** ocupa exactamente una ficha.

| Concepto | Definición | Ejemplo |
|---|---|---|
| Horario | Días y horas en que un médico atiende una especialidad | Dr. Pérez, Medicina general, lun–vie 08:00–12:00 |
| Turno | Una jornada concreta de ese horario en una fecha | Lunes 16/11/2026, consultorio 2 |
| Ficha | Cupo numerado dentro de un turno, con hora | Ficha 4, 09:00–09:20 |
| Capacidad | Duración del turno / duración de consulta | 240 / 20 = 12 fichas |
| Hora de la ficha | inicio + (n − 1) × duración | Ficha 4 = 08:00 + 3×20 = 09:00 |
| Cita | Ficha asignada a un paciente, con código `CM-000123` | |
| Sobrecupo | Ficha extra para urgencias (máx. 2 por turno) | Ficha 13 |

**Las fichas NO se guardan en una tabla**: se calculan en tiempo real desde `horario`, restando citas no canceladas, bloqueos de `indisponibilidad` y horas pasadas. La cita guarda `nro_ficha`, `hora_inicio` y `hora_fin`.

Requisitos de la guía del docente (obligatorios): PHP, PostgreSQL con PDO `pdo_pgsql`, HTML5/CSS3, JavaScript, sesiones, consultas parametrizadas, validación cliente + servidor, errores sin exponer detalles, BD normalizada, separación interfaz / lógica / datos, código comentado, diseño profesional, código en GitHub.

**Fechas:** Presentación 1 dom 25/10/2026 (≥50% ciclo 1) · Presentación 2 dom 08/11/2026 (100% ciclo 2) · Defensa mar 10/11/2026.

---

## 2. Actores y roles

| Rol | Qué hace |
|---|---|
| Administrador | Usuarios, roles/permisos, especialidades, médicos, horarios, tarifas, reportes, bitácora. |
| Recepcionista | Registra pacientes y antecedentes, consulta fichas, programa / reprograma / cancela citas, registra llegada e inasistencia; ciclo 2: caja y cobros. |
| Médico | Ve su agenda, ve antecedentes, registra la atención y marca la cita como Atendida (si tiene el permiso). |
| Paciente | Ya registrado presencialmente en recepción. Consulta fichas, reserva, reprograma, cancela, ve su historial; ciclo 2: perfil y pago en línea. |
| Pasarela de pago (externo, ciclo 2) | PayPal Sandbox / QR. |
| Temporizador (proceso automático) | Scheduler de Laravel: cancela citas con pago vencido, marca inasistencias, envía recordatorios. |

El paciente **no se auto-registra**: lo registra la recepción en su primera visita (crea su cuenta con contraseña temporal).

---

## 3. Casos de uso (23) y ciclos

**Ciclo 1 (17 CU — cubre todos los RF01–RF13 de la guía). Implementar primero.**
**Ciclo 2 (6 CU + voz).**

| ID | Caso de uso | Actores | Ciclo | Prioridad |
|---|---|---|:-:|---|
| CU01 | Iniciar y cerrar sesión | Todos | 1 | Alta |
| CU02 | Gestionar usuarios | Admin | 1 | Alta |
| CU03 | Gestionar roles y permisos | Admin | 1 | Alta |
| CU04 | Gestionar perfil del paciente | Paciente | 2 | Media |
| CU05 | Gestionar pacientes | Recepcionista | 1 | Alta |
| CU06 | Gestionar antecedentes clínicos | Recepcionista, Médico | 1 | Media |
| CU07 | Gestionar especialidades | Admin | 1 | Alta |
| CU08 | Gestionar médicos | Admin | 1 | Alta |
| CU09 | Gestionar horarios de atención | Admin | 1 | Alta |
| CU10 | Registrar indisponibilidad médica y reubicar citas | Admin, Recepcionista | 2 | Media |
| CU11 | Gestionar tarifario | Admin | 2 | Media |
| CU12 | Consultar fichas disponibles | Paciente, Recepcionista | 1 | Alta |
| CU13 | Programar cita (reservar ficha) | Recepcionista, Paciente | 1 | Alta |
| CU14 | Pagar ficha en línea (PayPal / QR) | Paciente, Pasarela, Temporizador | 2 | Media |
| CU15 | Reprogramar cita | Recepcionista, Paciente | 1 | Media |
| CU16 | Cancelar cita | Recepcionista, Paciente | 1 | Media |
| CU17 | Registrar asistencia e inasistencia | Recepcionista, Temporizador | 1 | Media |
| CU18 | Gestionar caja (apertura, cierre, arqueo) | Recepcionista | 2 | Media |
| CU19 | Cobrar ficha en mostrador y emitir comprobante | Recepcionista | 2 | Media |
| CU20 | Consultar agenda médica | Médico, Recepcionista, Admin | 1 | Media |
| CU21 | Registrar atención médica | Médico | 1 | Media |
| CU22 | Consultar historial del paciente | Médico, Recepcionista, Paciente | 1 | Media |
| CU23 | Generar reportes (PDF / CSV) | Admin, Recepcionista | 1 | Baja |

Comandos de voz (Web Speech API) = RNF20, ciclo 2, en las pantallas de CU12, CU13 y CU20.

### Detalle mínimo por CU (lo que el código debe respetar)

- **CU01**: login con usuario o correo + contraseña; 5 intentos fallidos → bloqueo 15 min; sesión expira a los 15 min de inactividad; páginas internas sin sesión → redirigir a login; registrar LOGIN en bitácora.
- **CU02**: CRUD de cuentas del personal; usuario y correo únicos; contraseña ≥ 8; no se eliminan, se desactivan.
- **CU03**: matriz rol × permiso; el permiso se verifica en el servidor (middleware), no solo en el menú; el rol Administrador no puede perder el permiso de gestionar roles; "marcar cita como atendida" es un permiso configurable.
- **CU05**: CI único; código `PAC-000001` autogenerado; crea cuenta con rol Paciente y contraseña temporal; buscar por CI/nombre/apellido con autocompletado; desactivar sin perder historial.
- **CU06**: tipo de sangre, alergias, enfermedades crónicas, medicamentos, antecedentes, contacto de emergencia; guarda quién y cuándo modificó.
- **CU07**: nombre normalizado único (`lower(trim(nombre))`); duración 10–120 min.
- **CU08**: matrícula única; código `MED-0001`; ≥ 1 especialidad; médico inactivo no recibe citas pero conserva las históricas.
- **CU09**: hora_fin > hora_inicio; sin superposición con otro horario del mismo médico ese día; mostrar cuántas fichas genera.
- **CU12**: especialidad → médico → fecha → fichas libres (número y hora). Quitar ocupadas (estado ≠ Cancelada), bloqueadas y pasadas.
- **CU13**: validar RN04–RN07, RN17, RN19; **transacción** + volver a verificar la ficha; código `CM-000123`; estado Programada; programar recordatorio (−24 h). Si choca (restricción de exclusión) → rollback y mensaje "La ficha ya no está disponible".
- **CU15**: solo citas Programada, ≥ 24 h antes, una sola vez (la recepcionista puede forzar con motivo); conserva el código; guarda el cambio en historial.
- **CU16**: solo Programada; motivo obligatorio; libera la ficha; nunca borrar. Ciclo 2: reembolso total si ≥ 24 h.
- **CU17**: Programada → Confirmada al llegar (guarda hora_llegada); Inasistencia manual o automática a los 15 min de la hora sin llegada.
- **CU20**: agenda por médico y fecha (hora, código, paciente, especialidad, estado, motivo); filtro por estado; el médico ve solo la suya.
- **CU21**: solo citas Confirmada del propio médico; diagnóstico obligatorio; peso/talla/presión/temperatura opcionales con rangos; alergias resaltadas; cita → Atendida.
- **CU22**: historial cronológico incluyendo canceladas e inasistencias; recepcionista no ve el detalle clínico.
- **CU23**: reportes mínimos: citas por fecha, por médico, por especialidad, por estado, pacientes registrados, totales atendidas/canceladas/inasistencias por periodo, agenda diaria por médico; filtros por rango de fechas; exportar PDF y CSV (UTF-8).

---

## 4. Estados de la cita (RF10 de la guía, obligatorio)

`Programada`, `Confirmada`, `Atendida`, `Cancelada`, `Inasistencia`.

| Desde | Hacia | Cuándo |
|---|---|---|
| (nueva) | Programada | Se reserva la ficha |
| Programada | Programada | Reprogramar (mismo código) |
| Programada | Confirmada | El paciente llega a recepción |
| Programada | Cancelada | Cancela, o no paga en 15 min (ciclo 2) |
| Programada | Inasistencia | No llega 15 min después de su hora |
| Confirmada | Atendida | El médico registra la atención |
| Confirmada | Inasistencia | Se retira sin ser atendido |
| Atendida / Cancelada / Inasistencia | — | Estados finales |

El **pago** va en un campo aparte `estado_pago`: `Pendiente`, `Pagado`, `Reembolsado` (ciclo 2). Toda transición se registra en `historial_estado_cita`.

---

## 5. Reglas del negocio

Guía (RN01–RN14): identificadores únicos de paciente y médico; cita asociada a paciente y médico válidos; médico activo; cita dentro del horario; no fechas pasadas; sin superposición; duración según configuración; canceladas no ocupan; atendidas se conservan; no borrar pacientes/médicos con historial (desactivar); solo usuarios autorizados; verificar disponibilidad en el servidor; persistencia en PostgreSQL.

Fichas (RN15–RN31, valores a confirmar con la clínica; ponerlos en `config/clinica.php`, no fijos en el código):

| Regla | Valor |
|---|---|
| RN15 Fichas calculadas desde el horario | — |
| RN16 Reparto de fichas por canal | 70% en línea / 30% mostrador |
| RN17 Ventana de reserva en línea | abre 7 días antes, cierra 1 h antes del turno |
| RN18 Solo pacientes registrados presencialmente | — |
| RN19 Límite por paciente | 1 cita por médico por día; 3 citas activas |
| RN20 Retención para pago en línea (ciclo 2) | 15 minutos |
| RN21 Mostrador se cobra en el acto (ciclo 2) | — |
| RN22 Precio = tarifa vigente al reservar (ciclo 2) | — |
| RN23 Recordatorio por correo | 24 h antes |
| RN24 Llegada | hasta 15 min antes → Confirmada |
| RN25 Inasistencia automática | 15 min después de la hora |
| RN26 Cancelación | ≥ 24 h reembolso total; < 24 h sin reembolso |
| RN27 Reprogramación | 1 vez, ≥ 24 h, misma especialidad |
| RN28 Indisponibilidad médica | bloquear, reubicar con prioridad o reembolso total, avisar por correo |
| RN29 Sobrecupos | máx. 2 por turno |
| RN30 Solo se atienden citas Confirmada | — |
| RN31 Penalización | 3 inasistencias en 90 días → solo reserva en mostrador |

---

## 6. Modelo de datos (20 tablas, PostgreSQL 16)

Script completo y probado: `vida_salud.sql`. Las migraciones de Laravel deben replicarlo **exactamente** (mismos nombres de tablas y columnas en español, snake_case, singular).

| Paquete | Tabla | Columnas clave |
|---|---|---|
| Seguridad | `rol` | id_rol, nombre (único), descripcion, estado |
| | `permiso` | id_permiso, codigo (único, ej. `cita.programar`), descripcion, paquete |
| | `rol_permiso` | id_rol, id_permiso (PK compuesta) |
| | `usuario` | id_usuario, username (único), correo (único), password, id_rol, intentos_fallidos, bloqueado_hasta, estado, remember_token, creado_en |
| | `bitacora` | id_bitacora, id_usuario, fecha_hora, ip, accion, tabla, id_registro, detalle — **inmutable** (trigger) |
| Pacientes | `paciente` | id_paciente, codigo, ci (único), nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion, recibe_recordatorios, id_usuario, estado, creado_en |
| | `antecedente` | id_paciente (PK/FK), tipo_sangre, alergias, enfermedades_cronicas, medicamentos, antecedentes, contacto_nombre, contacto_parentesco, contacto_telefono, actualizado_por, actualizado_en |
| | `notificacion` | id_notificacion, id_paciente, id_cita, tipo, programada_para, enviada_en, estado |
| Oferta médica | `especialidad` | id_especialidad, nombre (único normalizado), descripcion, duracion_min, estado |
| | `medico` | id_medico, codigo, matricula (única), nombres, apellidos, telefono, correo, id_usuario, estado |
| | `medico_especialidad` | id_medico, id_especialidad (PK compuesta) |
| | `horario` | id_horario, id_medico, id_especialidad, dia_semana (1=lunes), hora_inicio, hora_fin, duracion_min, consultorio, estado |
| | `indisponibilidad` | id_indisponibilidad, id_medico, inicio, fin, motivo, registrado_por |
| | `tarifa` (ciclo 2) | id_tarifa, id_especialidad, monto, vigente_desde, vigente_hasta |
| Citas | `cita` | id_cita, codigo, id_paciente, id_medico, id_especialidad, fecha, hora_inicio, hora_fin, nro_ficha, sobrecupo, canal (LINEA/MOSTRADOR), motivo, estado, estado_pago, monto, vence_pago_en, veces_reprogramada, motivo_cancelacion, hora_llegada, creado_por, creado_en |
| | `historial_estado_cita` | id_historial, id_cita, estado_anterior, estado_nuevo, detalle, id_usuario (NULL = temporizador), fecha_hora |
| Atención | `atencion` | id_atencion, id_cita (único), diagnostico, tratamiento, peso_kg, talla_cm, presion_arterial, temperatura_c, observaciones, registrado_en |
| Caja (ciclo 2) | `caja` | id_caja, id_usuario, apertura, cierre, monto_inicial, monto_esperado, monto_contado, diferencia, observacion, estado — una sola ABIERTA por usuario |
| | `pago` | id_pago, id_cita, id_caja, tipo (COBRO/REEMBOLSO), metodo, monto, id_transaccion, estado, fecha_hora, registrado_por |
| | `comprobante` | id_comprobante, numero (único correlativo), id_pago, fecha_emision |

**Integridad que vive en la base de datos (no quitar):**
- `EXCLUDE USING gist (id_medico WITH =, tsrange(fecha + hora_inicio, fecha + hora_fin) WITH &&) WHERE (estado <> 'Cancelada' AND sobrecupo = FALSE)` → requiere `CREATE EXTENSION btree_gist`. Impide citas superpuestas aunque dos usuarios reserven a la vez.
- `CHECK` de estados, de horas (`hora_fin > hora_inicio`), de rangos de signos vitales.
- Índice único `lower(trim(nombre))` en especialidad; índice único parcial de caja abierta.
- Trigger `tg_bitacora_inmutable` que rechaza UPDATE/DELETE en bitácora.

> Diferencias permitidas respecto al SQL por exigencia de Laravel: en `usuario` la columna se llama `password` (no `password_hash`) y se agrega `remember_token`. Actualizar `vida_salud.sql` y la documentación para que coincidan.

---

## 7. Arquitectura acordada

### Stack

| Capa | Tecnología |
|---|---|
| Framework | **Laravel 12** (PHP 8.2+) — Laravel 11 fue descartado: todas sus versiones tienen avisos de seguridad y Composer las bloquea |
| Base de datos | **PostgreSQL 16** vía PDO `pdo_pgsql` (driver `pgsql` de Laravel) |
| Vistas | **Blade** + **Bootstrap 5** (responsivo) |
| Cliente | **JavaScript** sin frameworks (validaciones, `fetch` para fichas disponibles) |
| Autenticación | **Laravel Breeze** (stack Blade), adaptado a la tabla `usuario` |
| Autorización | **Middleware propio** sobre las tablas `rol`, `permiso`, `rol_permiso` (no usar spatie, para respetar el modelo de datos documentado) |
| PDF | `barryvdh/laravel-dompdf` |
| Correo | Mail de Laravel (SMTP) |
| Pagos (ciclo 2) | PayPal REST API v2 Sandbox |
| Voz (ciclo 2) | Web Speech API del navegador |
| Servidor local | XAMPP 8.2 (Apache) con VirtualHost a `public/`, o `php artisan serve` |
| Control de versiones | Git + GitHub |

### Capas (MVC + servicios)

```
Ruta (routes/web.php)
  → Middleware (auth, sesión 15 min, permiso:codigo)
    → Controller (liviano: recibe, llama al servicio, devuelve vista/JSON)
      → Form Request (validación del servidor)
      → Service (reglas del negocio + transacciones DB::transaction)
        → Model Eloquent (acceso a datos, relaciones)
          → PostgreSQL (restricciones, triggers)
  ← Vista Blade + Bootstrap + JS
```

Reglas de diseño:
1. **Lógica del negocio solo en `app/Services`**, nunca en controladores ni vistas. El Scheduler (temporizador) reutiliza los mismos servicios.
2. **Transacciones en el servicio** (`DB::transaction`) para reservar, reprogramar, cancelar, registrar atención y pagar. Capturar la violación de la restricción de exclusión (SQLSTATE `23P01`) y traducirla a "La ficha ya no está disponible".
3. **Todo cambio de estado de una cita pasa por un solo método** (`CitaEstadoService::transicionar($cita, $nuevo, $detalle)`) que valida la tabla de transiciones y escribe `historial_estado_cita`.
4. **Bitácora** con un servicio `BitacoraService::registrar(accion, tabla, id, detalle)` llamado desde los servicios.
5. **Nada se borra físicamente**: desactivar (`estado = false`) o cambiar estado.
6. Validar en cliente (JS) **y** en servidor (Form Request).
7. Errores: `APP_DEBUG=false` en la demo; mensajes genéricos al usuario, detalle en `storage/logs`.
8. Código y comentarios en español; nombres de clases en PascalCase, tablas y columnas en snake_case español.

### Configuración de modelos Eloquent con este esquema

- Tablas en singular y en español → declarar `protected $table = 'cita';`
- Claves primarias propias → `protected $primaryKey = 'id_cita';`
- Sin `created_at`/`updated_at` → `public $timestamps = false;` o `const CREATED_AT = 'creado_en'; const UPDATED_AT = null;`
- Modelo de usuario: `App\Models\User` con `$table = 'usuario'`, `$primaryKey = 'id_usuario'`; login por `username` o `correo` (ajustar `LoginRequest` de Breeze).

### Seguridad

- CSRF: `@csrf` en todos los formularios (incluido en Laravel).
- Contraseñas: `Hash::make` (bcrypt, equivalente a `password_hash`).
- Sesión: `SESSION_LIFETIME=15`; `session()->regenerate()` al iniciar sesión.
- Bloqueo tras 5 intentos fallidos (usar `intentos_fallidos` y `bloqueado_hasta`, o el RateLimiter de Breeze).
- Middleware `permiso:cita.programar` en cada ruta protegida.
- `.env` fuera del repositorio; subir `.env.example`.

### Zona horaria (obligatorio)

- `config/app.php` → `'timezone' => 'America/La_Paz'` y `APP_TIMEZONE=America/La_Paz`.
- PostgreSQL: `ALTER DATABASE vida_salud SET timezone TO 'America/La_Paz';`
Sin esto, "no permitir fechas pasadas" falla por 4 horas.

### Temporizador (Scheduler)

En `routes/console.php`, cada minuto:
- cancelar citas en línea con `estado_pago = 'Pendiente'` y `vence_pago_en < now()` (ciclo 2);
- marcar `Inasistencia` las citas `Programada` cuya hora + 15 min ya pasó;
- enviar `notificacion` pendientes (recordatorios a −24 h).

En Windows: Programador de tareas ejecutando `php artisan schedule:run` cada minuto (o `php artisan schedule:work` durante el desarrollo).

### PayPal (ciclo 2)

XAMPP local no recibe webhooks. Confirmar el pago **capturando la orden al volver** de PayPal (`/pago/retorno`); usar ngrok solo si se quiere probar el webhook.

---

## 8. Estructura de carpetas

```
vida-salud/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                      # Breeze
│   │   │   ├── Seguridad/                 # UsuarioController, RolController, BitacoraController
│   │   │   ├── Pacientes/                 # PacienteController, AntecedenteController, PerfilController
│   │   │   ├── Oferta/                    # EspecialidadController, MedicoController, HorarioController,
│   │   │   │                              # IndisponibilidadController, TarifaController
│   │   │   ├── Citas/                     # FichaController, CitaController, AsistenciaController, PagoController
│   │   │   ├── Caja/                      # CajaController, CobroController
│   │   │   ├── Atencion/                  # AgendaController, AtencionController, HistorialController
│   │   │   └── Reportes/                  # ReporteController
│   │   ├── Middleware/
│   │   │   └── VerificarPermiso.php       # alias 'permiso'
│   │   └── Requests/                      # un Form Request por formulario (StorePacienteRequest, ...)
│   ├── Models/                            # User(usuario), Rol, Permiso, Bitacora, Paciente, Antecedente,
│   │                                      # Especialidad, Medico, Horario, Indisponibilidad, Tarifa, Cita,
│   │                                      # HistorialEstadoCita, Atencion, Caja, Pago, Comprobante, Notificacion
│   ├── Services/
│   │   ├── DisponibilidadService.php      # calcula fichas libres (CU12)
│   │   ├── CitaService.php                # programar, reprogramar, cancelar (CU13, CU15, CU16)
│   │   ├── CitaEstadoService.php          # máquina de estados + historial
│   │   ├── AtencionService.php            # CU21
│   │   ├── CodigoService.php              # PAC-000001, MED-0001, CM-000123
│   │   ├── BitacoraService.php
│   │   ├── NotificacionService.php
│   │   ├── ReporteService.php
│   │   ├── CajaService.php                # ciclo 2
│   │   └── PayPalService.php              # ciclo 2
│   └── Exceptions/
│       └── FichaNoDisponibleException.php
├── config/
│   └── clinica.php                        # valores de RN16–RN31 (70/30, 15 min, 24 h, ...)
├── database/
│   ├── migrations/                        # replican vida_salud.sql (orden por dependencias)
│   └── seeders/                           # RolSeeder, PermisoSeeder, UsuarioAdminSeeder,
│                                          # EspecialidadSeeder, DatosDemoSeeder
├── resources/
│   ├── views/
│   │   ├── layouts/app.blade.php          # menú lateral según permisos del rol
│   │   ├── seguridad/ pacientes/ oferta/ citas/ caja/ atencion/ reportes/
│   │   └── pdf/                           # plantillas de reportes y comprobante
│   └── js/                                # validaciones.js, fichas.js (fetch), voz.js (ciclo 2)
├── routes/
│   ├── web.php                            # agrupadas por paquete con middleware permiso:
│   └── console.php                        # Scheduler (temporizador)
├── tests/Feature/                         # las 10 pruebas mínimas de la guía
├── .env.example
└── README.md
```

---

## 9. Instalación (Windows + XAMPP + VS Code)

```bash
# Requisitos: XAMPP 8.2 (PHP 8.2), Composer, Node.js LTS, PostgreSQL 16, Git
# En C:\xampp\php\php.ini descomentar:  extension=pdo_pgsql  y  extension=pgsql

composer create-project laravel/laravel:^12.0 vida-salud
cd vida-salud
composer require laravel/breeze --dev
php artisan breeze:install blade
composer require barryvdh/laravel-dompdf
npm install bootstrap @popperjs/core
npm run build
```

Base de datos (psql o pgAdmin):

```sql
CREATE DATABASE vida_salud ENCODING 'UTF8';
\c vida_salud
CREATE EXTENSION IF NOT EXISTS btree_gist;
ALTER DATABASE vida_salud SET timezone TO 'America/La_Paz';
```

`.env`:

```
APP_NAME="Vida Salud"
APP_TIMEZONE=America/La_Paz
APP_LOCALE=es
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=vida_salud
DB_USERNAME=postgres
DB_PASSWORD=********
SESSION_LIFETIME=15
MAIL_MAILER=log            # cambiar a smtp para enviar correos reales
```

```bash
php artisan migrate --seed
php artisan serve          # http://127.0.0.1:8000
php artisan schedule:work  # temporizador en otra terminal
```

VirtualHost opcional en `C:\xampp\apache\conf\extra\httpd-vhosts.conf` con `DocumentRoot` apuntando a `.../vida-salud/public`.

---

## 10. Orden de implementación sugerido (ciclo 1)

1. Migraciones de las 20 tablas + seeders (roles, permisos, admin, especialidades, datos demo).
2. Breeze adaptado a `usuario` + middleware `permiso` + layout con menú por rol (CU01, CU03).
3. CRUD de usuarios (CU02), especialidades (CU07), médicos (CU08), horarios (CU09).
4. Pacientes y antecedentes (CU05, CU06).
5. `DisponibilidadService` + pantalla de fichas (CU12).
6. `CitaService::programar` con transacción y manejo de `23P01` (CU13).
7. Reprogramar, cancelar, asistencia (CU15, CU16, CU17) con `CitaEstadoService`.
8. Agenda, atención e historial (CU20, CU21, CU22).
9. Reportes con PDF y CSV (CU23) y bitácora.
10. Scheduler (inasistencias, recordatorios).
11. Pruebas mínimas (sección 11).

Ciclo 2: CU04, CU10, CU11, CU14, CU18, CU19 y voz.

---

## 11. Pruebas mínimas de la guía (deben pasar)

| N.º | Prueba | Resultado esperado |
|:-:|---|---|
| 1 | Registrar un paciente nuevo | Queda guardado |
| 2 | Registrar paciente con CI duplicado | Rechazado |
| 3 | Programar cita en horario disponible | Registrada, estado Programada |
| 4 | Programar otra cita superpuesta para el mismo médico | Impedida |
| 5 | Reservar fuera del horario del médico | Rechazada |
| 6 | Cancelar una cita | Pasa a Cancelada y conserva historial |
| 7 | Consultar historial de un paciente | Aparecen sus citas |
| 8 | Buscar citas de un médico por fecha | Resultados correctos |
| 9 | Entrar a una página interna sin sesión | Acceso denegado |
| 10 | Operación con datos inválidos (sin validación JS) | El servidor la rechaza |

Implementarlas también como `tests/Feature` de Laravel cuando sea posible.

---

## 12. Instrucciones para el asistente de IA

- Respeta los nombres de tablas y columnas de la sección 6; no los traduzcas al inglés ni los pluralices.
- No pongas reglas del negocio en controladores ni en vistas: van en `app/Services`.
- No elimines registros; usa `estado` o los estados de la cita.
- Toda operación que reserve, mueva o cancele una cita va dentro de `DB::transaction`.
- Toda transición de estado de una cita pasa por `CitaEstadoService`.
- Comenta el código en español en las funciones clave.
- Ante una duda funcional, sigue el PDF de documentación; ante una duda de datos, sigue `vida_salud.sql`.
- Prioriza el ciclo 1; no implementes pagos, caja ni voz hasta terminarlo.

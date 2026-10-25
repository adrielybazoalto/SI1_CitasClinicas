-- =============================================================
-- Sistema web de gestión de citas médicas - Clínica Vida Salud
-- Script de creación de la base de datos (PostgreSQL 16)
-- 21 tablas, restricciones, índices y datos de catálogo
-- =============================================================

CREATE EXTENSION IF NOT EXISTS btree_gist; -- necesaria para la restricción de exclusión de citas

-- ---------- P1. Seguridad y Accesos ----------
CREATE TABLE rol (
    id_rol       SERIAL PRIMARY KEY,
    nombre       VARCHAR(30)  NOT NULL UNIQUE,
    descripcion  VARCHAR(150),
    estado       BOOLEAN      NOT NULL DEFAULT TRUE
);

CREATE TABLE permiso (
    id_permiso   SERIAL PRIMARY KEY,
    codigo       VARCHAR(50)  NOT NULL UNIQUE,          -- ej. 'cita.programar'
    descripcion  VARCHAR(150) NOT NULL,
    paquete      VARCHAR(30)  NOT NULL
);

CREATE TABLE rol_permiso (
    id_rol      INT NOT NULL REFERENCES rol(id_rol) ON DELETE CASCADE,
    id_permiso  INT NOT NULL REFERENCES permiso(id_permiso) ON DELETE CASCADE,
    PRIMARY KEY (id_rol, id_permiso)
);

CREATE TABLE usuario (
    id_usuario          SERIAL PRIMARY KEY,
    id_rol              INT          NOT NULL REFERENCES rol(id_rol) ON DELETE RESTRICT,
    username            VARCHAR(50)  NOT NULL UNIQUE,
    correo              VARCHAR(100) NOT NULL UNIQUE,
    password_hash       VARCHAR(255) NOT NULL,          -- password_hash() de PHP (BCRYPT)
    intentos_fallidos   SMALLINT     NOT NULL DEFAULT 0 CHECK (intentos_fallidos >= 0),
    bloqueado_hasta     TIMESTAMP,
    debe_cambiar_clave  BOOLEAN      NOT NULL DEFAULT FALSE, -- contraseña temporal
    estado              BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en           TIMESTAMP    NOT NULL DEFAULT now()
);

CREATE TABLE recuperacion_clave (
    id_recuperacion  SERIAL PRIMARY KEY,
    id_usuario       INT          NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    token_hash       VARCHAR(255) NOT NULL UNIQUE,      -- se guarda el hash, nunca el token
    expira_en        TIMESTAMP    NOT NULL,             -- creado_en + 30 minutos
    usado_en         TIMESTAMP,                         -- NULL = aún no usado
    creado_en        TIMESTAMP    NOT NULL DEFAULT now(),
    CHECK (expira_en > creado_en)
);

CREATE TABLE bitacora (
    id_bitacora  BIGSERIAL PRIMARY KEY,
    id_usuario   INT REFERENCES usuario(id_usuario) ON DELETE SET NULL,
    fecha_hora   TIMESTAMP   NOT NULL DEFAULT now(),
    ip           VARCHAR(45),
    accion       VARCHAR(50) NOT NULL,                  -- ej. 'CITA_CANCELADA'
    tabla        VARCHAR(50),
    id_registro  INT,
    detalle      TEXT
);

-- ---------- P2. Pacientes ----------
CREATE TABLE paciente (
    id_paciente           SERIAL PRIMARY KEY,
    id_usuario            INT UNIQUE REFERENCES usuario(id_usuario) ON DELETE SET NULL,
    codigo                VARCHAR(12)  NOT NULL UNIQUE,  -- ej. 'PAC-000001'
    ci                    VARCHAR(15)  NOT NULL UNIQUE,  -- RN01
    nombres               VARCHAR(60)  NOT NULL,
    apellidos             VARCHAR(60)  NOT NULL,
    fecha_nacimiento      DATE         NOT NULL CHECK (fecha_nacimiento <= CURRENT_DATE),
    sexo                  CHAR(1)      CHECK (sexo IN ('M','F','O')),
    telefono              VARCHAR(20)  NOT NULL,
    correo                VARCHAR(100),
    direccion             VARCHAR(150),
    recibe_recordatorios  BOOLEAN      NOT NULL DEFAULT TRUE,
    estado                BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en             TIMESTAMP    NOT NULL DEFAULT now()
);

CREATE TABLE antecedente (
    id_paciente            INT PRIMARY KEY REFERENCES paciente(id_paciente) ON DELETE CASCADE,
    actualizado_por        INT REFERENCES usuario(id_usuario) ON DELETE SET NULL,
    tipo_sangre            VARCHAR(3) CHECK (tipo_sangre IN ('A+','A-','B+','B-','AB+','AB-','O+','O-')),
    alergias               TEXT,
    enfermedades_cronicas  TEXT,
    medicamentos           TEXT,
    antecedentes           TEXT,
    contacto_nombre        VARCHAR(100),
    contacto_parentesco    VARCHAR(30),
    contacto_telefono      VARCHAR(20),
    actualizado_en         TIMESTAMP NOT NULL DEFAULT now()
);

-- ---------- P3. Oferta Médica ----------
CREATE TABLE especialidad (
    id_especialidad  SERIAL PRIMARY KEY,
    nombre           VARCHAR(60)  NOT NULL,
    descripcion      VARCHAR(200),
    duracion_min     SMALLINT     NOT NULL DEFAULT 30 CHECK (duracion_min BETWEEN 10 AND 120),
    estado           BOOLEAN      NOT NULL DEFAULT TRUE
);
-- nombre normalizado único (Guía RF04)
CREATE UNIQUE INDEX ux_especialidad_nombre ON especialidad (lower(trim(nombre)));

CREATE TABLE medico (
    id_medico   SERIAL PRIMARY KEY,
    id_usuario  INT UNIQUE REFERENCES usuario(id_usuario) ON DELETE SET NULL,
    codigo      VARCHAR(12)  NOT NULL UNIQUE,            -- ej. 'MED-0001'
    matricula   VARCHAR(30)  NOT NULL UNIQUE,
    nombres     VARCHAR(60)  NOT NULL,
    apellidos   VARCHAR(60)  NOT NULL,
    telefono    VARCHAR(20),
    correo      VARCHAR(100),
    estado      BOOLEAN      NOT NULL DEFAULT TRUE         -- RN04
);

CREATE TABLE medico_especialidad (
    id_medico        INT NOT NULL REFERENCES medico(id_medico) ON DELETE RESTRICT,
    id_especialidad  INT NOT NULL REFERENCES especialidad(id_especialidad) ON DELETE RESTRICT,
    PRIMARY KEY (id_medico, id_especialidad)
);

CREATE TABLE horario (
    id_horario       SERIAL PRIMARY KEY,
    id_medico        INT      NOT NULL,
    id_especialidad  INT      NOT NULL,
    dia_semana       SMALLINT NOT NULL CHECK (dia_semana BETWEEN 1 AND 7),  -- 1 = lunes
    hora_inicio      TIME     NOT NULL,
    hora_fin         TIME     NOT NULL,
    duracion_min     SMALLINT NOT NULL CHECK (duracion_min BETWEEN 10 AND 120),
    consultorio      VARCHAR(20),
    estado           BOOLEAN  NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_medico, id_especialidad)
        REFERENCES medico_especialidad(id_medico, id_especialidad) ON DELETE RESTRICT,
    CHECK (hora_fin > hora_inicio)                                         -- Guía RF05
);

CREATE TABLE indisponibilidad (
    id_indisponibilidad  SERIAL PRIMARY KEY,
    id_medico            INT          NOT NULL REFERENCES medico(id_medico) ON DELETE RESTRICT,
    registrado_por       INT REFERENCES usuario(id_usuario) ON DELETE SET NULL,
    inicio               TIMESTAMP    NOT NULL,
    fin                  TIMESTAMP    NOT NULL,
    motivo               VARCHAR(150) NOT NULL,
    CHECK (fin > inicio)
);

CREATE TABLE tarifa (                                       -- ciclo 2
    id_tarifa        SERIAL PRIMARY KEY,
    id_especialidad  INT           NOT NULL REFERENCES especialidad(id_especialidad) ON DELETE RESTRICT,
    monto            NUMERIC(10,2) NOT NULL CHECK (monto > 0),
    vigente_desde    DATE          NOT NULL,
    vigente_hasta    DATE,
    CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)
);

-- ---------- P4. Gestión de Citas ----------
CREATE TABLE cita (
    id_cita             SERIAL PRIMARY KEY,
    id_paciente         INT          NOT NULL REFERENCES paciente(id_paciente) ON DELETE RESTRICT,
    id_medico           INT          NOT NULL REFERENCES medico(id_medico) ON DELETE RESTRICT,
    id_especialidad     INT          NOT NULL REFERENCES especialidad(id_especialidad) ON DELETE RESTRICT,
    creado_por          INT          NOT NULL REFERENCES usuario(id_usuario) ON DELETE RESTRICT,
    codigo              VARCHAR(12)  NOT NULL UNIQUE,     -- ej. 'CM-000123'
    fecha               DATE         NOT NULL,
    hora_inicio         TIME         NOT NULL,
    hora_fin            TIME         NOT NULL,
    nro_cita            SMALLINT     NOT NULL CHECK (nro_cita > 0),   -- número de orden en el turno
    sobrecupo           BOOLEAN      NOT NULL DEFAULT FALSE,
    canal               VARCHAR(10)  NOT NULL CHECK (canal IN ('LINEA','MOSTRADOR')),
    motivo              VARCHAR(200),
    estado              VARCHAR(12)  NOT NULL DEFAULT 'Programada'
                        CHECK (estado IN ('Programada','Confirmada','Atendida','Cancelada','Inasistencia')),
    estado_pago         VARCHAR(12)  NOT NULL DEFAULT 'Pendiente'
                        CHECK (estado_pago IN ('Pendiente','Pagado','Reembolsado')),
    monto               NUMERIC(10,2) CHECK (monto IS NULL OR monto >= 0),  -- precio al reservar (RN22)
    vence_pago_en       TIMESTAMP,                        -- RN20: creado_en + 15 minutos
    veces_reprogramada  SMALLINT     NOT NULL DEFAULT 0 CHECK (veces_reprogramada >= 0),
    motivo_cancelacion  VARCHAR(200),
    hora_llegada        TIMESTAMP,
    creado_en           TIMESTAMP    NOT NULL DEFAULT now(),
    CHECK (hora_fin > hora_inicio),
    -- RN07: un médico no puede tener citas superpuestas (las canceladas no ocupan, RN09)
    CONSTRAINT ex_cita_sin_superposicion EXCLUDE USING gist (
        id_medico WITH =,
        tsrange(fecha + hora_inicio, fecha + hora_fin) WITH &&
    ) WHERE (estado <> 'Cancelada' AND sobrecupo = FALSE)
);

CREATE TABLE historial_estado_cita (
    id_historial     BIGSERIAL PRIMARY KEY,
    id_cita          INT         NOT NULL REFERENCES cita(id_cita) ON DELETE CASCADE,
    id_usuario       INT REFERENCES usuario(id_usuario) ON DELETE SET NULL,  -- NULL = temporizador
    estado_anterior  VARCHAR(12),
    estado_nuevo     VARCHAR(12) NOT NULL,
    detalle          VARCHAR(200),
    fecha_hora       TIMESTAMP   NOT NULL DEFAULT now()
);

CREATE TABLE notificacion (
    id_notificacion  SERIAL PRIMARY KEY,
    id_paciente      INT         NOT NULL REFERENCES paciente(id_paciente) ON DELETE CASCADE,
    id_cita          INT REFERENCES cita(id_cita) ON DELETE CASCADE,
    tipo             VARCHAR(20) NOT NULL
                     CHECK (tipo IN ('RECORDATORIO','REPROGRAMACION','CANCELACION','COMPROBANTE')),
    programada_para  TIMESTAMP   NOT NULL,
    enviada_en       TIMESTAMP,
    estado           VARCHAR(10) NOT NULL DEFAULT 'PENDIENTE'
                     CHECK (estado IN ('PENDIENTE','ENVIADA','FALLIDA'))
);

-- ---------- P5. Caja y Pagos (ciclo 2) ----------
CREATE TABLE caja (
    id_caja         SERIAL PRIMARY KEY,
    id_usuario      INT           NOT NULL REFERENCES usuario(id_usuario) ON DELETE RESTRICT,
    apertura        TIMESTAMP     NOT NULL DEFAULT now(),
    cierre          TIMESTAMP,
    monto_inicial   NUMERIC(10,2) NOT NULL CHECK (monto_inicial >= 0),
    monto_esperado  NUMERIC(10,2),
    monto_contado   NUMERIC(10,2),
    diferencia      NUMERIC(10,2),
    observacion     VARCHAR(200),
    estado          VARCHAR(7)    NOT NULL DEFAULT 'ABIERTA' CHECK (estado IN ('ABIERTA','CERRADA')),
    CHECK (cierre IS NULL OR cierre >= apertura)
);
-- una sola caja abierta por recepcionista
CREATE UNIQUE INDEX ux_caja_abierta ON caja (id_usuario) WHERE estado = 'ABIERTA';

CREATE TABLE pago (
    id_pago         SERIAL PRIMARY KEY,
    id_cita         INT           NOT NULL REFERENCES cita(id_cita) ON DELETE RESTRICT,
    id_caja         INT REFERENCES caja(id_caja) ON DELETE RESTRICT,     -- NULL en pagos en línea
    registrado_por  INT REFERENCES usuario(id_usuario) ON DELETE SET NULL,
    tipo            VARCHAR(10)   NOT NULL DEFAULT 'COBRO' CHECK (tipo IN ('COBRO','REEMBOLSO')),
    metodo          VARCHAR(15)   NOT NULL
                    CHECK (metodo IN ('EFECTIVO','TARJETA','TRANSFERENCIA','PAYPAL','QR')),
    monto           NUMERIC(10,2) NOT NULL CHECK (monto > 0),
    id_transaccion  VARCHAR(80),                                       -- id de PayPal o del banco
    estado          VARCHAR(10)   NOT NULL DEFAULT 'APROBADO'
                    CHECK (estado IN ('PENDIENTE','APROBADO','RECHAZADO')),
    fecha_hora      TIMESTAMP     NOT NULL DEFAULT now()
);

CREATE TABLE comprobante (
    id_comprobante  SERIAL PRIMARY KEY,
    id_pago         INT       NOT NULL UNIQUE REFERENCES pago(id_pago) ON DELETE RESTRICT,
    numero          INT       NOT NULL UNIQUE,                         -- correlativo
    fecha_emision   TIMESTAMP NOT NULL DEFAULT now()
);

-- ---------- P6. Atención Médica ----------
CREATE TABLE atencion (
    id_atencion       SERIAL PRIMARY KEY,
    id_cita           INT          NOT NULL UNIQUE REFERENCES cita(id_cita) ON DELETE RESTRICT,
    diagnostico       TEXT         NOT NULL,
    tratamiento       TEXT,
    peso_kg           NUMERIC(5,2) CHECK (peso_kg BETWEEN 0.5 AND 400),
    talla_cm          NUMERIC(5,1) CHECK (talla_cm BETWEEN 20 AND 250),
    presion_arterial  VARCHAR(7),                                       -- ej. '120/80'
    temperatura_c     NUMERIC(4,1) CHECK (temperatura_c BETWEEN 30 AND 45),
    observaciones     TEXT,
    registrado_en     TIMESTAMP    NOT NULL DEFAULT now()
);

-- ---------- Índices de búsqueda frecuente (RNF02) ----------
CREATE INDEX ix_paciente_apellidos   ON paciente (apellidos, nombres);
CREATE INDEX ix_cita_medico_fecha    ON cita (id_medico, fecha);
CREATE INDEX ix_cita_paciente        ON cita (id_paciente);
CREATE INDEX ix_cita_estado          ON cita (estado);
CREATE INDEX ix_bitacora_fecha       ON bitacora (fecha_hora);
CREATE INDEX ix_recuperacion_usuario ON recuperacion_clave (id_usuario);
CREATE INDEX ix_notificacion_pend    ON notificacion (programada_para) WHERE estado = 'PENDIENTE';

-- =============================================================
-- FIN DEL SCRIPT DE CREACIÓN
-- =============================================================

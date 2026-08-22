-- ============================================
-- SCHEMA - Bolsa Laboral UTC
-- Hackathon DITI 2026
-- ============================================
-- Run: psql -U postgres -h localhost -d bolsa_laboral -f config/db.sql
-- ============================================

BEGIN;

-- ============================================
-- 0. CLEANUP (drop in reverse dependency order)
-- ============================================

DROP TABLE IF EXISTS respuestas_detalle CASCADE;
DROP TABLE IF EXISTS banco_preguntas CASCADE;
DROP TABLE IF EXISTS evaluaciones CASCADE;
DROP TABLE IF EXISTS postulaciones CASCADE;
DROP TABLE IF EXISTS vacantes CASCADE;
DROP TABLE IF EXISTS egresados CASCADE;
DROP TABLE IF EXISTS empresas CASCADE;
DROP TABLE IF EXISTS token_blacklist CASCADE;
DROP TABLE IF EXISTS config_pruebas CASCADE;
DROP TABLE IF EXISTS carreras CASCADE;
DROP TABLE IF EXISTS divisiones CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;

-- ============================================
-- 1. LOOKUP TABLES (no FK dependencies)
-- ============================================

CREATE TABLE divisiones (
    id SERIAL PRIMARY KEY,
    nombre TEXT NOT NULL
);

CREATE TABLE carreras (
    id SERIAL PRIMARY KEY,
    division_id INTEGER NOT NULL REFERENCES divisiones(id),
    nombre TEXT NOT NULL,
    competencias_base JSONB
);

CREATE TABLE config_pruebas (
    id SERIAL PRIMARY KEY,
    tipo_prueba TEXT NOT NULL,
    duracion_minutos INTEGER NOT NULL,
    cantidad_preguntas INTEGER NOT NULL
);

-- ============================================
-- 2. USUARIOS (auth root — FK source for all roles)
-- ============================================

CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    matricula TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    rol TEXT NOT NULL,
    primer_ingreso BOOLEAN NOT NULL DEFAULT true,
    fecha_registro TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================
-- 3. ROLE TABLES (1:1 with usuarios)
-- ============================================

CREATE TABLE egresados (
    usuario_id INTEGER PRIMARY KEY REFERENCES usuarios(id),
    nombre TEXT NOT NULL,
    apellido_paterno TEXT NOT NULL,
    apellido_materno TEXT,
    carrera_id INTEGER NOT NULL REFERENCES carreras(id),
    periodo_egreso TEXT,
    foto_drive_id TEXT,
    biografia_ia TEXT,
    contacto JSONB,
    trayectoria JSONB,
    habilidades JSONB,
    vistas_perfil INTEGER DEFAULT 0,
    cv_drive_id TEXT
);

CREATE TABLE empresas (
    usuario_id INTEGER PRIMARY KEY REFERENCES usuarios(id),
    nombre_comercial TEXT NOT NULL,
    rfc TEXT,
    foto_drive_id TEXT,
    estatus_convenio TEXT DEFAULT 'pendiente',
    calificacion_ia INTEGER,
    contacto JSONB
);

-- ============================================
-- 4. JWT BLACKLIST
-- ============================================

CREATE TABLE token_blacklist (
    token_hash TEXT PRIMARY KEY,
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
    expires_at TIMESTAMP NOT NULL
);

-- ============================================
-- 5. VACANTES
-- ============================================

CREATE TABLE vacantes (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES usuarios(id),
    titulo TEXT NOT NULL,
    descripcion TEXT,
    ubicacion TEXT,
    division_destino INTEGER REFERENCES divisiones(id),
    perfil_idoneo JSONB,
    analisis_gemini TEXT,
    es_externa BOOLEAN DEFAULT false,
    url_externa TEXT,
    fecha_publicacion TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================
-- 6. POSTULACIONES
-- ============================================

CREATE TABLE postulaciones (
    id SERIAL PRIMARY KEY,
    egresado_id INTEGER NOT NULL REFERENCES usuarios(id),
    vacante_id INTEGER NOT NULL REFERENCES vacantes(id),
    match_porcentaje INTEGER,
    estatus TEXT NOT NULL DEFAULT 'pendiente',
    fecha TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================
-- 7. EVALUACIONES
-- ============================================

CREATE TABLE evaluaciones (
    id SERIAL PRIMARY KEY,
    egresado_id INTEGER NOT NULL REFERENCES usuarios(id),
    tipo_prueba TEXT NOT NULL,
    puntaje_global INTEGER,
    detalle_resultados JSONB,
    es_base BOOLEAN DEFAULT true,
    fecha_inicio TIMESTAMP NOT NULL DEFAULT NOW(),
    fecha_fin TIMESTAMP
);

-- ============================================
-- 8. BANCO DE PREGUNTAS
-- ============================================

CREATE TABLE banco_preguntas (
    id SERIAL PRIMARY KEY,
    division_id INTEGER REFERENCES divisiones(id),
    carrera_id INTEGER REFERENCES carreras(id),
    tipo_prueba TEXT NOT NULL,
    pregunta TEXT NOT NULL,
    opciones JSONB NOT NULL,
    respuesta_correcta TEXT,
    activo BOOLEAN DEFAULT true
);

-- ============================================
-- 9. RESPUESTAS DETALLE
-- ============================================

CREATE TABLE respuestas_detalle (
    id SERIAL PRIMARY KEY,
    evaluacion_id INTEGER NOT NULL REFERENCES evaluaciones(id),
    pregunta_id INTEGER NOT NULL REFERENCES banco_preguntas(id),
    respuesta_dada TEXT,
    es_correcta BOOLEAN
);

-- ============================================
-- 10. INDEXES
-- ============================================

CREATE INDEX idx_usuarios_matricula ON usuarios(matricula);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_egresados_carrera ON egresados(carrera_id);
CREATE INDEX idx_empresas_estatus ON empresas(estatus_convenio);
CREATE INDEX idx_vacantes_empresa ON vacantes(empresa_id);
CREATE INDEX idx_vacantes_division ON vacantes(division_destino);
CREATE INDEX idx_postulaciones_egresado ON postulaciones(egresado_id);
CREATE INDEX idx_postulaciones_vacante ON postulaciones(vacante_id);
CREATE INDEX idx_evaluaciones_egresado ON evaluaciones(egresado_id);
CREATE INDEX idx_evaluaciones_tipo ON evaluaciones(tipo_prueba);
CREATE INDEX idx_banco_preguntas_tipo ON banco_preguntas(tipo_prueba);
CREATE INDEX idx_banco_preguntas_carrera ON banco_preguntas(carrera_id);
CREATE INDEX idx_respuestas_evaluacion ON respuestas_detalle(evaluacion_id);
CREATE INDEX idx_token_blacklist_hash ON token_blacklist(token_hash);
CREATE INDEX idx_token_blacklist_expires ON token_blacklist(expires_at);

COMMIT;

-- =====================================================================
-- Sistema de gestión de solicitudes de encuesta — Santiago de Cali
-- Modelo de datos de la ENTREGA 1 (requisitos 1 a 4)
-- Motor: MySQL 8.4 LTS · InnoDB · utf8mb4
-- Las tablas de la entrega 2 (hojas de ruta) se agregan en el Sprint 2.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS encuestas_cali
  CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE encuestas_cali;

-- ---------------------------------------------------------------------
-- perfiles: los 5 perfiles exigidos por R1
-- ---------------------------------------------------------------------
CREATE TABLE perfiles (
  id      TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre  VARCHAR(20)      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_perfiles_nombre (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- comunas_corregimientos: división político-administrativa de Cali
-- tipo = 'comuna'        -> zona urbana (22 comunas)
-- tipo = 'corregimiento' -> zona rural  (15 corregimientos)
-- ---------------------------------------------------------------------
CREATE TABLE comunas_corregimientos (
  id      SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo    ENUM('comuna','corregimiento') NOT NULL,
  numero  TINYINT UNSIGNED NULL,          -- solo comunas (1..22)
  nombre  VARCHAR(60)       NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cc_tipo_nombre (tipo, nombre),
  CONSTRAINT ck_cc_numero CHECK (
    (tipo = 'comuna' AND numero BETWEEN 1 AND 22) OR
    (tipo = 'corregimiento' AND numero IS NULL)
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- barrios: barrio, urbanización o sector (en comunas) o vereda
-- (en corregimientos). Cada uno pertenece a UNA comuna o corregimiento,
-- lo que impide registrar un barrio en la comuna equivocada.
-- Se carga desde el dataset oficial "Código único de identificación a
-- nivel de barrio" de datos.cali.gov.co.
-- ---------------------------------------------------------------------
CREATE TABLE barrios (
  id                       SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo                   VARCHAR(10)       NULL,   -- código oficial
  nombre                   VARCHAR(100)      NOT NULL,
  comuna_corregimiento_id  SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_barrios_nombre_cc (nombre, comuna_corregimiento_id),
  KEY ix_barrios_cc (comuna_corregimiento_id),
  CONSTRAINT fk_barrios_cc FOREIGN KEY (comuna_corregimiento_id)
    REFERENCES comunas_corregimientos (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- puntos_atencion: los 8 puntos de R5 (comunas 1, 6, 7, 13, 14, 20, 21
-- y 17 = oficina principal). Se necesitan desde la entrega 1 porque
-- cada ventanilla pertenece a un punto (R4 y R7).
-- Latitud y longitud se completan en el Sprint 2 (R5, mapa estático).
-- ---------------------------------------------------------------------
CREATE TABLE puntos_atencion (
  id                    TINYINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nombre                VARCHAR(80)       NOT NULL,
  comuna_id             SMALLINT UNSIGNED NOT NULL,
  direccion             VARCHAR(150)      NULL,
  latitud               DECIMAL(9,6)      NULL,
  longitud              DECIMAL(9,6)      NULL,
  es_oficina_principal  BOOLEAN           NOT NULL DEFAULT FALSE,
  PRIMARY KEY (id),
  UNIQUE KEY uq_puntos_comuna (comuna_id),
  CONSTRAINT fk_puntos_comuna FOREIGN KEY (comuna_id)
    REFERENCES comunas_corregimientos (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- usuarios: cuentas del sistema (R1)
-- punto_atencion_id es obligatorio para el perfil ventanilla; la regla
-- se valida en la aplicación (un CHECK no puede consultar otra tabla).
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  documento          VARCHAR(15)      NOT NULL,
  nombres            VARCHAR(80)      NOT NULL,
  apellidos          VARCHAR(80)      NOT NULL,
  correo             VARCHAR(120)     NOT NULL,
  password_hash      VARCHAR(255)     NOT NULL,  -- password_hash() de PHP
  perfil_id          TINYINT UNSIGNED NOT NULL,
  punto_atencion_id  TINYINT UNSIGNED NULL,
  activo             BOOLEAN          NOT NULL DEFAULT TRUE,
  creado_en          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_documento (documento),
  UNIQUE KEY uq_usuarios_correo (correo),
  KEY ix_usuarios_perfil (perfil_id),
  CONSTRAINT fk_usuarios_perfil FOREIGN KEY (perfil_id)
    REFERENCES perfiles (id),
  CONSTRAINT fk_usuarios_punto FOREIGN KEY (punto_atencion_id)
    REFERENCES puntos_atencion (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- solicitudes: solicitud de encuesta radicada por ventanilla (R2, R3)
-- * Solo se guarda barrio_id: la comuna o corregimiento se obtiene del
--   barrio, así no pueden quedar inconsistentes (3FN).
-- * fecha_limite es una columna generada (radicación + 15 días
--   calendario): no se puede editar a mano.
-- * latitud/longitud en NULL = "sin georreferenciar".
-- * Rango de coordenadas: caja aproximada del municipio de Cali
--   (zona urbana y rural); la aplicación usa los mismos límites.
-- ---------------------------------------------------------------------
CREATE TABLE solicitudes (
  id                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  nombres            VARCHAR(80)      NOT NULL,
  apellidos          VARCHAR(80)      NOT NULL,
  celular            CHAR(10)         NOT NULL,
  direccion          VARCHAR(150)     NOT NULL,
  barrio_id          SMALLINT UNSIGNED NOT NULL,
  latitud            DECIMAL(9,6)     NULL,
  longitud           DECIMAL(9,6)     NULL,
  punto_atencion_id  TINYINT UNSIGNED NOT NULL,
  usuario_id         INT UNSIGNED     NOT NULL,  -- ventanilla que radicó
  fecha_radicacion   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_limite       DATE AS (DATE(fecha_radicacion + INTERVAL 15 DAY)) STORED,
  PRIMARY KEY (id),
  KEY ix_solicitudes_fecha (fecha_radicacion),
  KEY ix_solicitudes_punto (punto_atencion_id),
  KEY ix_solicitudes_barrio (barrio_id),
  CONSTRAINT fk_solicitudes_barrio  FOREIGN KEY (barrio_id)
    REFERENCES barrios (id),
  CONSTRAINT fk_solicitudes_punto   FOREIGN KEY (punto_atencion_id)
    REFERENCES puntos_atencion (id),
  CONSTRAINT fk_solicitudes_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id),
  CONSTRAINT ck_solicitudes_celular CHECK (celular REGEXP '^3[0-9]{9}$'),
  CONSTRAINT ck_solicitudes_coords_par CHECK ((latitud IS NULL) = (longitud IS NULL)),
  CONSTRAINT ck_solicitudes_lat CHECK (latitud  IS NULL OR latitud  BETWEEN 3.28 AND 3.53),
  CONSTRAINT ck_solicitudes_lon CHECK (longitud IS NULL OR longitud BETWEEN -76.75 AND -76.44)
) ENGINE=InnoDB;

-- =====================================================================
-- DATOS SEMILLA
-- =====================================================================

INSERT INTO perfiles (nombre) VALUES
  ('administrador'), ('ventanilla'), ('encuestador'), ('calidad'), ('enrutador');

-- 22 comunas
INSERT INTO comunas_corregimientos (tipo, numero, nombre)
WITH RECURSIVE n AS (SELECT 1 AS k UNION ALL SELECT k + 1 FROM n WHERE k < 22)
SELECT 'comuna', k, CONCAT('Comuna ', k) FROM n;

-- 15 corregimientos
INSERT INTO comunas_corregimientos (tipo, numero, nombre) VALUES
  ('corregimiento', NULL, 'Navarro'),
  ('corregimiento', NULL, 'El Hormiguero'),
  ('corregimiento', NULL, 'Pance'),
  ('corregimiento', NULL, 'La Buitrera'),
  ('corregimiento', NULL, 'Villacarmelo'),
  ('corregimiento', NULL, 'Los Andes'),
  ('corregimiento', NULL, 'Pichindé'),
  ('corregimiento', NULL, 'La Leonera'),
  ('corregimiento', NULL, 'Felidia'),
  ('corregimiento', NULL, 'El Saladito'),
  ('corregimiento', NULL, 'La Elvira'),
  ('corregimiento', NULL, 'La Castilla'),
  ('corregimiento', NULL, 'La Paz'),
  ('corregimiento', NULL, 'Montebello'),
  ('corregimiento', NULL, 'Golondrinas');

-- 8 puntos de atención de R5
INSERT INTO puntos_atencion (nombre, comuna_id, es_oficina_principal)
SELECT IF(numero = 17, 'Oficina principal (Comuna 17)', CONCAT('Punto de atención Comuna ', numero)),
       id,
       numero = 17
FROM comunas_corregimientos
WHERE tipo = 'comuna' AND numero IN (1, 6, 7, 13, 14, 17, 20, 21)
ORDER BY numero;

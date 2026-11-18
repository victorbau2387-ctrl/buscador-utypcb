-- Buscador de preguntas frecuentes UTyP Coyuca de Benítez
-- MariaDB 10.11 / MySQL 5.7+ · utf8mb4
-- Ejecutar sobre una base vacía:  mysql -u USUARIO -p NOMBRE_BD < database/schema.sql

SET NAMES utf8mb4;

-- Áreas responsables: a quién se canaliza y quién valida las respuestas.
CREATE TABLE IF NOT EXISTS bf_area (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave        VARCHAR(40)  NOT NULL,
    nombre       VARCHAR(120) NOT NULL,
    correo       VARCHAR(120) NULL,
    telefono     VARCHAR(40)  NULL,
    url          VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_area_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preguntas frecuentes. tipo='fija' responde con `respuesta`;
-- tipo='dinamica' arma la respuesta en vivo desde los archivos de datos del sitio (handler).
CREATE TABLE IF NOT EXISTS bf_faq (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug            VARCHAR(80)  NOT NULL,
    categoria       VARCHAR(40)  NOT NULL,
    pregunta        VARCHAR(255) NOT NULL,
    respuesta       TEXT         NOT NULL,
    tipo            ENUM('fija','dinamica') NOT NULL DEFAULT 'fija',
    handler         VARCHAR(60)  NULL,
    palabras_clave  VARCHAR(500) NOT NULL DEFAULT '',
    carrera         VARCHAR(80)  NULL,
    fuente_url      VARCHAR(255) NOT NULL,
    area_id         INT UNSIGNED NULL,
    estado          ENUM('borrador','validada','archivada') NOT NULL DEFAULT 'borrador',
    validado_por    VARCHAR(120) NULL,
    validado_en     DATE         NULL,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_faq_slug (slug),
    KEY ix_faq_estado (estado),
    CONSTRAINT fk_faq_area FOREIGN KEY (area_id) REFERENCES bf_area (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formas distintas de hacer la misma pregunta (coloquial, sin acentos, con faltas).
CREATE TABLE IF NOT EXISTS bf_variante (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    faq_id  INT UNSIGNED NOT NULL,
    texto   VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    KEY ix_variante_faq (faq_id),
    CONSTRAINT fk_variante_faq FOREIGN KEY (faq_id) REFERENCES bf_faq (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de búsquedas (sin IP ni datos personales; ver docs/07-aviso-privacidad-y-seguridad.md).
-- Se purga con private/bin/purgar.php según config `privacidad.dias_retencion`.
CREATE TABLE IF NOT EXISTS bf_consulta (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    consulta    VARCHAR(300) NOT NULL,
    resultado   ENUM('respondida','sugerencias','sin_respuesta','no_ofertada') NOT NULL,
    faq_id      INT UNSIGNED NULL,
    confianza   DECIMAL(4,3) NOT NULL DEFAULT 0,
    ms          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    util        TINYINT(1)   NULL,
    revisada    TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY ix_consulta_resultado (resultado, revisada),
    KEY ix_consulta_fecha (creado_en),
    CONSTRAINT fk_consulta_faq FOREIGN KEY (faq_id) REFERENCES bf_faq (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas del panel. Se crean con private/bin/crear-admin.php (nunca con contraseñas en el repo).
CREATE TABLE IF NOT EXISTS bf_admin (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario        VARCHAR(60)  NOT NULL,
    nombre         VARCHAR(120) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    rol            ENUM('superadmin','editor') NOT NULL DEFAULT 'editor',
    activo         TINYINT(1)   NOT NULL DEFAULT 1,
    ultimo_acceso  DATETIME     NULL,
    creado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Límite de solicitudes (chat y login). La clave es un hash, no la IP en claro.
CREATE TABLE IF NOT EXISTS bf_limite (
    clave    CHAR(64)     NOT NULL,
    ventana  INT UNSIGNED NOT NULL,
    conteo   INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (clave, ventana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

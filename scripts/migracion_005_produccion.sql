START TRANSACTION;

ALTER TABLE personas
    ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1 AFTER id_rol,
    ADD COLUMN IF NOT EXISTS inactivado_en DATETIME NULL AFTER activo,
    ADD COLUMN IF NOT EXISTS inactivado_por INT NULL AFTER inactivado_en,
    ADD KEY IF NOT EXISTS idx_personas_activo (activo),
    ADD KEY IF NOT EXISTS idx_personas_inactivado_por (inactivado_por);

UPDATE personas
SET activo = 1
WHERE activo IS NULL;

CREATE TABLE IF NOT EXISTS auth_login_intentos (
    id_intento BIGINT NOT NULL AUTO_INCREMENT,
    clave_hash CHAR(64) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    usuario_ref VARCHAR(190) NOT NULL,
    intentos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_intento),
    UNIQUE KEY uq_auth_intentos_clave (clave_hash),
    KEY idx_auth_bloqueado_hasta (bloqueado_hasta),
    KEY idx_auth_usuario_ref (usuario_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS boletin_auditoria (
    id_auditoria BIGINT NOT NULL AUTO_INCREMENT,
    id_actor INT NULL,
    tipo_evento VARCHAR(80) NOT NULL,
    entidad VARCHAR(80) NOT NULL,
    id_curso INT NULL,
    id_periodo INT NULL,
    id_materia INT NULL,
    id_alumno INT NULL,
    id_docente INT NULL,
    id_objetivo INT NULL,
    payload_json LONGTEXT NULL,
    ip_origen VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_auditoria),
    KEY idx_ba_actor (id_actor),
    KEY idx_ba_evento (tipo_evento),
    KEY idx_ba_contexto (id_curso, id_periodo, id_materia, id_alumno),
    KEY idx_ba_creado (creado_en),
    CONSTRAINT fk_ba_actor FOREIGN KEY (id_actor) REFERENCES personas (id_persona) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

COMMIT;

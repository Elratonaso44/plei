START TRANSACTION;

ALTER TABLE boletin_periodos
    ADD COLUMN IF NOT EXISTS codigo_pdf ENUM('INF1', 'CUAT1', 'INF2', 'CUAT2') NULL AFTER orden;

SET @existe_uq_codigo_pdf := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'boletin_periodos'
      AND index_name = 'uq_periodo_ciclo_codigo_pdf'
);

SET @sql_uq_codigo_pdf := IF(
    @existe_uq_codigo_pdf = 0,
    'ALTER TABLE boletin_periodos ADD UNIQUE KEY uq_periodo_ciclo_codigo_pdf (id_ciclo, codigo_pdf)',
    'SELECT 1'
);
PREPARE stmt_uq_codigo_pdf FROM @sql_uq_codigo_pdf;
EXECUTE stmt_uq_codigo_pdf;
DEALLOCATE PREPARE stmt_uq_codigo_pdf;

CREATE TABLE IF NOT EXISTS boletin_institucion_config (
    id_config INT NOT NULL AUTO_INCREMENT,
    nombre_escuela VARCHAR(180) NOT NULL,
    direccion VARCHAR(180) NOT NULL,
    ciudad VARCHAR(80) NOT NULL,
    codigo_postal VARCHAR(20) NOT NULL,
    telefono VARCHAR(60) NOT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    actualizado_por INT NULL,
    PRIMARY KEY (id_config),
    KEY idx_bic_actualizado_por (actualizado_por),
    CONSTRAINT fk_bic_actualizado_por FOREIGN KEY (actualizado_por) REFERENCES personas (id_persona) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO boletin_institucion_config (nombre_escuela, direccion, ciudad, codigo_postal, telefono, actualizado_por)
SELECT
    'Escuela de Educacion Secundaria Tecnica Nro 1 "Brig. Gral. Bartolome Mitre"',
    'Av. de Mayo 1425',
    'Pergamino',
    '2700',
    '02477-322031',
    NULL
WHERE NOT EXISTS (SELECT 1 FROM boletin_institucion_config);

CREATE TABLE IF NOT EXISTS boletin_complementos_anuales (
    id_complemento BIGINT NOT NULL AUTO_INCREMENT,
    id_ciclo INT NOT NULL,
    id_curso INT NOT NULL,
    id_alumno INT NOT NULL,
    id_materia INT NOT NULL,
    inas_1 INT UNSIGNED NULL,
    inas_2 INT UNSIGNED NULL,
    int_dic TINYINT UNSIGNED NULL,
    int_feb_mar TINYINT UNSIGNED NULL,
    nota_final DECIMAL(4,1) NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    actualizado_por INT NULL,
    PRIMARY KEY (id_complemento),
    UNIQUE KEY uq_bca_contexto (id_ciclo, id_curso, id_alumno, id_materia),
    KEY idx_bca_ciclo_curso_alumno (id_ciclo, id_curso, id_alumno),
    KEY idx_bca_materia (id_materia),
    CONSTRAINT fk_bca_ciclo FOREIGN KEY (id_ciclo) REFERENCES ciclos_lectivos (id_ciclo) ON DELETE CASCADE,
    CONSTRAINT fk_bca_curso FOREIGN KEY (id_curso) REFERENCES cursos (id_curso) ON DELETE CASCADE,
    CONSTRAINT fk_bca_alumno FOREIGN KEY (id_alumno) REFERENCES personas (id_persona) ON DELETE CASCADE,
    CONSTRAINT fk_bca_materia FOREIGN KEY (id_materia) REFERENCES materias (id_materia) ON DELETE CASCADE,
    CONSTRAINT fk_bca_actualizado_por FOREIGN KEY (actualizado_por) REFERENCES personas (id_persona) ON DELETE SET NULL,
    CONSTRAINT chk_bca_int_dic CHECK (int_dic IS NULL OR (int_dic BETWEEN 1 AND 10)),
    CONSTRAINT chk_bca_int_feb_mar CHECK (int_feb_mar IS NULL OR (int_feb_mar BETWEEN 1 AND 10)),
    CONSTRAINT chk_bca_nota_final CHECK (nota_final IS NULL OR (nota_final BETWEEN 1.0 AND 10.0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

COMMIT;

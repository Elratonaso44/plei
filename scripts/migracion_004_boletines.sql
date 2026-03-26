START TRANSACTION;

ALTER TABLE alumnos_x_materia
    ADD COLUMN IF NOT EXISTS id_grupo INT NULL AFTER id_materia,
    ADD KEY IF NOT EXISTS idx_axm_materia_grupo (id_materia, id_grupo);

CREATE TABLE IF NOT EXISTS ciclos_lectivos (
    id_ciclo INT NOT NULL AUTO_INCREMENT,
    anio INT NOT NULL,
    nombre VARCHAR(90) NOT NULL,
    estado ENUM('activo', 'cerrado') NOT NULL DEFAULT 'activo',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NULL,
    cerrado_en DATETIME NULL,
    cerrado_por INT NULL,
    PRIMARY KEY (id_ciclo),
    UNIQUE KEY uq_ciclos_anio (anio),
    KEY idx_ciclos_estado (estado),
    CONSTRAINT fk_ciclos_creado_por FOREIGN KEY (creado_por) REFERENCES personas (id_persona) ON DELETE SET NULL,
    CONSTRAINT fk_ciclos_cerrado_por FOREIGN KEY (cerrado_por) REFERENCES personas (id_persona) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS boletin_periodos (
    id_periodo INT NOT NULL AUTO_INCREMENT,
    id_ciclo INT NOT NULL,
    nombre VARCHAR(90) NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_periodo),
    UNIQUE KEY uq_periodo_ciclo_nombre (id_ciclo, nombre),
    UNIQUE KEY uq_periodo_ciclo_orden (id_ciclo, orden),
    KEY idx_periodo_ciclo_activo (id_ciclo, activo),
    CONSTRAINT fk_periodo_ciclo FOREIGN KEY (id_ciclo) REFERENCES ciclos_lectivos (id_ciclo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS boletin_curso_periodo (
    id_boletin_curso_periodo INT NOT NULL AUTO_INCREMENT,
    id_curso INT NOT NULL,
    id_periodo INT NOT NULL,
    estado ENUM('cerrado', 'carga_docente', 'publicado') NOT NULL DEFAULT 'cerrado',
    abierto_por INT NULL,
    abierto_en DATETIME NULL,
    publicado_por INT NULL,
    publicado_en DATETIME NULL,
    reabierto_por INT NULL,
    reabierto_en DATETIME NULL,
    version_publicada INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_boletin_curso_periodo),
    UNIQUE KEY uq_curso_periodo (id_curso, id_periodo),
    KEY idx_bcp_estado (estado),
    CONSTRAINT fk_bcp_curso FOREIGN KEY (id_curso) REFERENCES cursos (id_curso) ON DELETE CASCADE,
    CONSTRAINT fk_bcp_periodo FOREIGN KEY (id_periodo) REFERENCES boletin_periodos (id_periodo) ON DELETE CASCADE,
    CONSTRAINT fk_bcp_abierto_por FOREIGN KEY (abierto_por) REFERENCES personas (id_persona) ON DELETE SET NULL,
    CONSTRAINT fk_bcp_publicado_por FOREIGN KEY (publicado_por) REFERENCES personas (id_persona) ON DELETE SET NULL,
    CONSTRAINT fk_bcp_reabierto_por FOREIGN KEY (reabierto_por) REFERENCES personas (id_persona) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS boletin_notas (
    id_boletin_nota INT NOT NULL AUTO_INCREMENT,
    id_periodo INT NOT NULL,
    id_curso INT NOT NULL,
    id_materia INT NOT NULL,
    id_alumno INT NOT NULL,
    id_docente INT NOT NULL,
    nota_num TINYINT UNSIGNED NOT NULL,
    sigla CHAR(3) NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_boletin_nota),
    UNIQUE KEY uq_nota_periodo_materia_alumno (id_periodo, id_materia, id_alumno),
    KEY idx_nota_curso_periodo (id_curso, id_periodo),
    KEY idx_nota_docente (id_docente),
    CONSTRAINT fk_bn_periodo FOREIGN KEY (id_periodo) REFERENCES boletin_periodos (id_periodo) ON DELETE CASCADE,
    CONSTRAINT fk_bn_curso FOREIGN KEY (id_curso) REFERENCES cursos (id_curso) ON DELETE CASCADE,
    CONSTRAINT fk_bn_materia FOREIGN KEY (id_materia) REFERENCES materias (id_materia) ON DELETE CASCADE,
    CONSTRAINT fk_bn_alumno FOREIGN KEY (id_alumno) REFERENCES personas (id_persona) ON DELETE CASCADE,
    CONSTRAINT fk_bn_docente FOREIGN KEY (id_docente) REFERENCES personas (id_persona) ON DELETE CASCADE,
    CONSTRAINT chk_bn_nota CHECK (nota_num BETWEEN 1 AND 10),
    CONSTRAINT chk_bn_sigla CHECK (sigla IN ('TED', 'TEP', 'TEA'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS boletin_pdf_historial (
    id_boletin_pdf INT NOT NULL AUTO_INCREMENT,
    id_periodo INT NOT NULL,
    id_curso INT NOT NULL,
    id_alumno INT NOT NULL,
    version INT NOT NULL,
    ruta_pdf VARCHAR(255) NOT NULL,
    hash_sha256 CHAR(64) NOT NULL,
    generado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    generado_por INT NULL,
    PRIMARY KEY (id_boletin_pdf),
    UNIQUE KEY uq_pdf_periodo_curso_alumno_version (id_periodo, id_curso, id_alumno, version),
    KEY idx_pdf_busqueda (id_periodo, id_curso, id_alumno),
    CONSTRAINT fk_bpdf_periodo FOREIGN KEY (id_periodo) REFERENCES boletin_periodos (id_periodo) ON DELETE CASCADE,
    CONSTRAINT fk_bpdf_curso FOREIGN KEY (id_curso) REFERENCES cursos (id_curso) ON DELETE CASCADE,
    CONSTRAINT fk_bpdf_alumno FOREIGN KEY (id_alumno) REFERENCES personas (id_persona) ON DELETE CASCADE,
    CONSTRAINT fk_bpdf_generado_por FOREIGN KEY (generado_por) REFERENCES personas (id_persona) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

COMMIT;

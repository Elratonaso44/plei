-- PLEI structure export
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `alumnos_x_curso`;
CREATE TABLE `alumnos_x_curso` (
  `id_persona_x_curso` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  PRIMARY KEY (`id_persona_x_curso`),
  UNIQUE KEY `uq_axc_persona_curso` (`id_persona`,`id_curso`),
  KEY `id_persona` (`id_persona`),
  KEY `id_curso` (`id_curso`),
  KEY `idx_axc_curso_persona` (`id_curso`,`id_persona`),
  CONSTRAINT `alumnos_x_curso_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE,
  CONSTRAINT `alumnos_x_curso_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `alumnos_x_materia`;
CREATE TABLE `alumnos_x_materia` (
  `id_alumno_x_materia` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_alumno_x_materia`),
  UNIQUE KEY `uq_axm_persona_materia` (`id_persona`,`id_materia`),
  KEY `id_materia` (`id_materia`),
  KEY `id_persona` (`id_persona`),
  KEY `idx_axm_materia_grupo` (`id_materia`,`id_grupo`),
  CONSTRAINT `alumnos_x_materia_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `alumnos_x_materia_ibfk_2` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `auth_login_intentos`;
CREATE TABLE `auth_login_intentos` (
  `id_intento` bigint(20) NOT NULL AUTO_INCREMENT,
  `clave_hash` char(64) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `usuario_ref` varchar(190) NOT NULL,
  `intentos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_intento`),
  UNIQUE KEY `uq_auth_intentos_clave` (`clave_hash`),
  KEY `idx_auth_bloqueado_hasta` (`bloqueado_hasta`),
  KEY `idx_auth_usuario_ref` (`usuario_ref`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `boletin_auditoria`;
CREATE TABLE `boletin_auditoria` (
  `id_auditoria` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_actor` int(11) DEFAULT NULL,
  `tipo_evento` varchar(80) NOT NULL,
  `entidad` varchar(80) NOT NULL,
  `id_curso` int(11) DEFAULT NULL,
  `id_periodo` int(11) DEFAULT NULL,
  `id_materia` int(11) DEFAULT NULL,
  `id_alumno` int(11) DEFAULT NULL,
  `id_docente` int(11) DEFAULT NULL,
  `id_objetivo` int(11) DEFAULT NULL,
  `payload_json` longtext DEFAULT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_ba_actor` (`id_actor`),
  KEY `idx_ba_evento` (`tipo_evento`),
  KEY `idx_ba_contexto` (`id_curso`,`id_periodo`,`id_materia`,`id_alumno`),
  KEY `idx_ba_creado` (`creado_en`),
  CONSTRAINT `fk_ba_actor` FOREIGN KEY (`id_actor`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `boletin_complementos_anuales`;
CREATE TABLE `boletin_complementos_anuales` (
  `id_complemento` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_ciclo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `inas_1` int(10) unsigned DEFAULT NULL,
  `inas_2` int(10) unsigned DEFAULT NULL,
  `int_dic` tinyint(3) unsigned DEFAULT NULL,
  `int_feb_mar` tinyint(3) unsigned DEFAULT NULL,
  `nota_final` decimal(4,1) DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_complemento`),
  UNIQUE KEY `uq_bca_contexto` (`id_ciclo`,`id_curso`,`id_alumno`,`id_materia`),
  KEY `idx_bca_ciclo_curso_alumno` (`id_ciclo`,`id_curso`,`id_alumno`),
  KEY `idx_bca_materia` (`id_materia`),
  KEY `fk_bca_curso` (`id_curso`),
  KEY `fk_bca_alumno` (`id_alumno`),
  KEY `fk_bca_actualizado_por` (`actualizado_por`),
  CONSTRAINT `fk_bca_actualizado_por` FOREIGN KEY (`actualizado_por`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL,
  CONSTRAINT `fk_bca_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE,
  CONSTRAINT `fk_bca_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos_lectivos` (`id_ciclo`) ON DELETE CASCADE,
  CONSTRAINT `fk_bca_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  CONSTRAINT `fk_bca_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `chk_bca_int_dic` CHECK (`int_dic` is null or `int_dic` between 1 and 10),
  CONSTRAINT `chk_bca_int_feb_mar` CHECK (`int_feb_mar` is null or `int_feb_mar` between 1 and 10),
  CONSTRAINT `chk_bca_nota_final` CHECK (`nota_final` is null or `nota_final` between 1.0 and 10.0)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `boletin_curso_periodo`;
CREATE TABLE `boletin_curso_periodo` (
  `id_boletin_curso_periodo` int(11) NOT NULL AUTO_INCREMENT,
  `id_curso` int(11) NOT NULL,
  `id_periodo` int(11) NOT NULL,
  `estado` enum('cerrado','carga_docente','publicado') NOT NULL DEFAULT 'cerrado',
  `abierto_por` int(11) DEFAULT NULL,
  `abierto_en` datetime DEFAULT NULL,
  `publicado_por` int(11) DEFAULT NULL,
  `publicado_en` datetime DEFAULT NULL,
  `reabierto_por` int(11) DEFAULT NULL,
  `reabierto_en` datetime DEFAULT NULL,
  `version_publicada` int(11) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_boletin_curso_periodo`),
  UNIQUE KEY `uq_curso_periodo` (`id_curso`,`id_periodo`),
  KEY `idx_bcp_estado` (`estado`),
  KEY `fk_bcp_periodo` (`id_periodo`),
  KEY `fk_bcp_abierto_por` (`abierto_por`),
  KEY `fk_bcp_publicado_por` (`publicado_por`),
  KEY `fk_bcp_reabierto_por` (`reabierto_por`),
  CONSTRAINT `fk_bcp_abierto_por` FOREIGN KEY (`abierto_por`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL,
  CONSTRAINT `fk_bcp_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  CONSTRAINT `fk_bcp_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `boletin_periodos` (`id_periodo`) ON DELETE CASCADE,
  CONSTRAINT `fk_bcp_publicado_por` FOREIGN KEY (`publicado_por`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL,
  CONSTRAINT `fk_bcp_reabierto_por` FOREIGN KEY (`reabierto_por`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `boletin_institucion_config`;
CREATE TABLE `boletin_institucion_config` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_escuela` varchar(180) NOT NULL,
  `direccion` varchar(180) NOT NULL,
  `ciudad` varchar(80) NOT NULL,
  `codigo_postal` varchar(20) NOT NULL,
  `telefono` varchar(60) NOT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_config`),
  KEY `idx_bic_actualizado_por` (`actualizado_por`),
  CONSTRAINT `fk_bic_actualizado_por` FOREIGN KEY (`actualizado_por`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `boletin_notas`;
CREATE TABLE `boletin_notas` (
  `id_boletin_nota` int(11) NOT NULL AUTO_INCREMENT,
  `id_periodo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `nota_num` tinyint(3) unsigned NOT NULL,
  `sigla` char(3) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_boletin_nota`),
  UNIQUE KEY `uq_nota_periodo_materia_alumno` (`id_periodo`,`id_materia`,`id_alumno`),
  KEY `idx_nota_curso_periodo` (`id_curso`,`id_periodo`),
  KEY `idx_nota_docente` (`id_docente`),
  KEY `fk_bn_materia` (`id_materia`),
  KEY `fk_bn_alumno` (`id_alumno`),
  CONSTRAINT `fk_bn_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE,
  CONSTRAINT `fk_bn_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  CONSTRAINT `fk_bn_docente` FOREIGN KEY (`id_docente`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE,
  CONSTRAINT `fk_bn_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `fk_bn_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `boletin_periodos` (`id_periodo`) ON DELETE CASCADE,
  CONSTRAINT `chk_bn_nota` CHECK (`nota_num` between 1 and 10),
  CONSTRAINT `chk_bn_sigla` CHECK (`sigla` in ('TED','TEP','TEA'))
) ENGINE=InnoDB AUTO_INCREMENT=513 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `boletin_pdf_historial`;
CREATE TABLE `boletin_pdf_historial` (
  `id_boletin_pdf` int(11) NOT NULL AUTO_INCREMENT,
  `id_periodo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `ruta_pdf` varchar(255) NOT NULL,
  `hash_sha256` char(64) NOT NULL,
  `generado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `generado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_boletin_pdf`),
  UNIQUE KEY `uq_pdf_periodo_curso_alumno_version` (`id_periodo`,`id_curso`,`id_alumno`,`version`),
  KEY `idx_pdf_busqueda` (`id_periodo`,`id_curso`,`id_alumno`),
  KEY `fk_bpdf_curso` (`id_curso`),
  KEY `fk_bpdf_alumno` (`id_alumno`),
  KEY `fk_bpdf_generado_por` (`generado_por`),
  CONSTRAINT `fk_bpdf_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpdf_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpdf_generado_por` FOREIGN KEY (`generado_por`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL,
  CONSTRAINT `fk_bpdf_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `boletin_periodos` (`id_periodo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `boletin_periodos`;
CREATE TABLE `boletin_periodos` (
  `id_periodo` int(11) NOT NULL AUTO_INCREMENT,
  `id_ciclo` int(11) NOT NULL,
  `nombre` varchar(90) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `codigo_pdf` enum('INF1','CUAT1','INF2','CUAT2') DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_periodo`),
  UNIQUE KEY `uq_periodo_ciclo_nombre` (`id_ciclo`,`nombre`),
  UNIQUE KEY `uq_periodo_ciclo_orden` (`id_ciclo`,`orden`),
  UNIQUE KEY `uq_periodo_ciclo_codigo_pdf` (`id_ciclo`,`codigo_pdf`),
  KEY `idx_periodo_ciclo_activo` (`id_ciclo`,`activo`),
  CONSTRAINT `fk_periodo_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos_lectivos` (`id_ciclo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `ciclos_lectivos`;
CREATE TABLE `ciclos_lectivos` (
  `id_ciclo` int(11) NOT NULL AUTO_INCREMENT,
  `anio` int(11) NOT NULL,
  `nombre` varchar(90) NOT NULL,
  `estado` enum('activo','cerrado') NOT NULL DEFAULT 'activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `creado_por` int(11) DEFAULT NULL,
  `cerrado_en` datetime DEFAULT NULL,
  `cerrado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_ciclo`),
  UNIQUE KEY `uq_ciclos_anio` (`anio`),
  KEY `idx_ciclos_estado` (`estado`),
  KEY `fk_ciclos_creado_por` (`creado_por`),
  KEY `fk_ciclos_cerrado_por` (`cerrado_por`),
  CONSTRAINT `fk_ciclos_cerrado_por` FOREIGN KEY (`cerrado_por`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL,
  CONSTRAINT `fk_ciclos_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `personas` (`id_persona`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `cursos`;
CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL AUTO_INCREMENT,
  `grado` int(11) NOT NULL,
  `id_modalidad` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  PRIMARY KEY (`id_curso`),
  KEY `id_modalidad` (`id_modalidad`),
  KEY `id_seccion` (`id_seccion`),
  KEY `idx_cursos_grado_modalidad_seccion` (`grado`,`id_modalidad`,`id_seccion`),
  CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`id_modalidad`) REFERENCES `modalidad` (`id_modalidad`),
  CONSTRAINT `cursos_ibfk_2` FOREIGN KEY (`id_seccion`) REFERENCES `secciones` (`id_seccion`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `docentes_x_materia`;
CREATE TABLE `docentes_x_materia` (
  `id_docente_x_materia` int(11) NOT NULL AUTO_INCREMENT,
  `id_materia` int(11) NOT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  `id_persona` int(11) NOT NULL,
  PRIMARY KEY (`id_docente_x_materia`),
  UNIQUE KEY `uq_dxm_persona_materia` (`id_persona`,`id_materia`),
  UNIQUE KEY `uq_dxm_materia_grupo` (`id_materia`,`id_grupo`),
  KEY `id_materia` (`id_materia`),
  KEY `id_persona` (`id_persona`),
  KEY `idx_dxm_materia_persona` (`id_materia`,`id_persona`),
  KEY `idx_dxm_grupo` (`id_grupo`),
  CONSTRAINT `docentes_x_materia_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE,
  CONSTRAINT `docentes_x_materia_ibfk_2` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE,
  CONSTRAINT `fk_dxm_materia_grupo` FOREIGN KEY (`id_materia`, `id_grupo`) REFERENCES `materias_x_grupo` (`id_materia`, `id_grupo`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `materiales`;
CREATE TABLE `materiales` (
  `id_material` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_material` varchar(40) NOT NULL,
  `unidad` varchar(40) NOT NULL,
  `url` varchar(200) NOT NULL,
  `id_materia` int(11) NOT NULL,
  PRIMARY KEY (`id_material`),
  KEY `id_materia` (`id_materia`),
  KEY `idx_materiales_materia_material` (`id_materia`,`id_material`),
  CONSTRAINT `materiales_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `materias`;
CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_materia` varchar(50) NOT NULL,
  `turno` varchar(30) NOT NULL,
  `grupo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  PRIMARY KEY (`id_materia`),
  KEY `id_curso` (`id_curso`),
  KEY `idx_materias_curso_nombre` (`id_curso`,`nombre_materia`),
  KEY `idx_materias_nombre_turno_grupo` (`nombre_materia`,`turno`,`grupo`),
  CONSTRAINT `materias_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `materias_x_grupo`;
CREATE TABLE `materias_x_grupo` (
  `id_materia` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_materia`,`id_grupo`),
  KEY `idx_mxg_grupo` (`id_grupo`),
  CONSTRAINT `fk_mxg_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `modalidad`;
CREATE TABLE `modalidad` (
  `id_modalidad` int(11) NOT NULL AUTO_INCREMENT,
  `moda` varchar(40) NOT NULL,
  PRIMARY KEY (`id_modalidad`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `personas`;
CREATE TABLE `personas` (
  `id_persona` int(11) NOT NULL AUTO_INCREMENT,
  `dni` int(11) NOT NULL,
  `apellido` varchar(40) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `mail` varchar(40) NOT NULL,
  `usuario` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `inactivado_en` datetime DEFAULT NULL,
  `inactivado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_persona`),
  UNIQUE KEY `dni` (`dni`),
  UNIQUE KEY `mail` (`mail`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `id_rol` (`id_rol`),
  KEY `idx_personas_apellido_nombre_dni` (`apellido`,`nombre`,`dni`),
  KEY `idx_personas_activo` (`activo`),
  KEY `idx_personas_inactivado_por` (`inactivado_por`),
  CONSTRAINT `personas_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `preceptor_x_curso`;
CREATE TABLE `preceptor_x_curso` (
  `id_preceptor_x_curso` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  PRIMARY KEY (`id_preceptor_x_curso`),
  UNIQUE KEY `uq_pxc_persona_curso` (`id_persona`,`id_curso`),
  KEY `id_curso` (`id_curso`),
  KEY `id_persona` (`id_persona`),
  KEY `idx_pxc_curso_persona` (`id_curso`,`id_persona`),
  CONSTRAINT `preceptor_x_curso_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  CONSTRAINT `preceptor_x_curso_ibfk_2` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `rol` varchar(30) NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `secciones`;
CREATE TABLE `secciones` (
  `id_seccion` int(11) NOT NULL AUTO_INCREMENT,
  `seccion` varchar(30) NOT NULL,
  PRIMARY KEY (`id_seccion`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `tipo_persona_x_persona`;
CREATE TABLE `tipo_persona_x_persona` (
  `id_tipo_persona_x_persona` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `id_tipo_persona` int(11) NOT NULL,
  PRIMARY KEY (`id_tipo_persona_x_persona`),
  UNIQUE KEY `uq_tpp_persona_tipo` (`id_persona`,`id_tipo_persona`),
  KEY `id_persona` (`id_persona`),
  KEY `id_tipo_persona` (`id_tipo_persona`),
  CONSTRAINT `tipo_persona_x_persona_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`) ON DELETE CASCADE,
  CONSTRAINT `tipo_persona_x_persona_ibfk_2` FOREIGN KEY (`id_tipo_persona`) REFERENCES `tipos_personas` (`id_tipo_persona`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DROP TABLE IF EXISTS `tipos_personas`;
CREATE TABLE `tipos_personas` (
  `id_tipo_persona` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) NOT NULL,
  PRIMARY KEY (`id_tipo_persona`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

SET FOREIGN_KEY_CHECKS=1;
-- ==========================================================
-- PLEI - Datos de prueba para entorno demo/exportacion
-- Password simple para todos los usuarios de prueba: 1234
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE boletin_auditoria;
TRUNCATE TABLE boletin_pdf_historial;
TRUNCATE TABLE boletin_complementos_anuales;
TRUNCATE TABLE boletin_notas;
TRUNCATE TABLE boletin_curso_periodo;
TRUNCATE TABLE boletin_periodos;
TRUNCATE TABLE ciclos_lectivos;
TRUNCATE TABLE boletin_institucion_config;
TRUNCATE TABLE auth_login_intentos;
TRUNCATE TABLE materiales;
TRUNCATE TABLE alumnos_x_materia;
TRUNCATE TABLE alumnos_x_curso;
TRUNCATE TABLE docentes_x_materia;
TRUNCATE TABLE materias_x_grupo;
TRUNCATE TABLE materias;
TRUNCATE TABLE preceptor_x_curso;
TRUNCATE TABLE cursos;
TRUNCATE TABLE secciones;
TRUNCATE TABLE modalidad;
TRUNCATE TABLE tipo_persona_x_persona;
TRUNCATE TABLE tipos_personas;
TRUNCATE TABLE personas;
TRUNCATE TABLE roles;

SET FOREIGN_KEY_CHECKS=1;

-- Roles y tipos
INSERT INTO roles (id_rol, rol) VALUES
  (1, 'Administrador'),
  (2, 'docente'),
  (3, 'usuario');

INSERT INTO tipos_personas (id_tipo_persona, tipo) VALUES
  (1, 'Administrador'),
  (2, 'docente'),
  (3, 'alumno'),
  (4, 'preceptor');

-- Usuarios de prueba (password simple: 1234)
INSERT INTO personas (id_persona, dni, apellido, nombre, mail, usuario, password, id_rol, activo) VALUES
  (1, 30000001, 'Torres', 'Ana', 'ana.torres@plei.test', 'ana_admin', '1234', 1, 1),
  (2, 30000002, 'Vega', 'Carlos', 'carlos.vega@plei.test', 'carlos_preceptor', '1234', 3, 1),
  (3, 30000003, 'Mendez', 'Laura', 'laura.mendez@plei.test', 'laura_preceptor', '1234', 3, 1),
  (4, 30000004, 'Ruiz', 'Diego', 'diego.ruiz@plei.test', 'diego_preceptor', '1234', 3, 1),
  (5, 30000005, 'Lopez', 'Maria', 'maria.lopez@plei.test', 'maria_docente', '1234', 2, 1),
  (6, 30000006, 'Perez', 'Juan', 'juan.perez@plei.test', 'juan_docente', '1234', 2, 1),
  (7, 30000007, 'Martinez', 'Sofia', 'sofia.martinez@plei.test', 'sofia_docente', '1234', 2, 1),
  (8, 30000008, 'Gomez', 'Pedro', 'pedro.gomez@plei.test', 'pedro_docente', '1234', 2, 1),
  (9, 30000009, 'Fernandez', 'Lucia', 'lucia.fernandez@plei.test', 'lucia_docente', '1234', 2, 1),
  (10, 30000010, 'Sosa', 'Martin', 'martin.sosa@plei.test', 'martin_docente', '1234', 2, 1),
  (11, 30000011, 'Rojas', 'Valeria', 'valeria.rojas@plei.test', 'valeria_docente', '1234', 2, 1),
  (12, 30000012, 'Castro', 'Nicolas', 'nicolas.castro@plei.test', 'nicolas_docente', '1234', 2, 1),
  (13, 30000013, 'Diaz', 'Camila', 'camila.diaz@plei.test', 'camila_docente', '1234', 2, 1),
  (14, 31000001, 'Alonso', 'Tomas', 'tomas.alonso@plei.test', 'alumno01', '1234', 3, 1),
  (15, 31000002, 'Silva', 'Martina', 'martina.silva@plei.test', 'alumno02', '1234', 3, 1),
  (16, 31000003, 'Herrera', 'Bruno', 'bruno.herrera@plei.test', 'alumno03', '1234', 3, 1),
  (17, 31000004, 'Acosta', 'Pilar', 'pilar.acosta@plei.test', 'alumno04', '1234', 3, 1),
  (18, 31000005, 'Molina', 'Facundo', 'facundo.molina@plei.test', 'alumno05', '1234', 3, 1),
  (19, 31000006, 'Navarro', 'Julieta', 'julieta.navarro@plei.test', 'alumno06', '1234', 3, 1),
  (20, 31000007, 'Benitez', 'Ignacio', 'ignacio.benitez@plei.test', 'alumno07', '1234', 3, 1),
  (21, 31000008, 'Romero', 'Agustina', 'agustina.romero@plei.test', 'alumno08', '1234', 3, 1),
  (22, 31000009, 'Peralta', 'Lucas', 'lucas.peralta@plei.test', 'alumno09', '1234', 3, 1),
  (23, 31000010, 'Suarez', 'Emma', 'emma.suarez@plei.test', 'alumno10', '1234', 3, 1),
  (24, 31000011, 'Diaz', 'Juan Cruz', 'juancruz.diaz@plei.test', 'alumno11', '1234', 3, 1),
  (25, 31000012, 'Ortega', 'Lara', 'lara.ortega@plei.test', 'alumno12', '1234', 3, 1);

INSERT INTO tipo_persona_x_persona (id_persona, id_tipo_persona) VALUES
  (1, 1),
  (2, 4), (3, 4), (4, 4),
  (5, 2), (6, 2), (7, 2), (8, 2), (9, 2), (10, 2), (11, 2), (12, 2), (13, 2),
  (14, 3), (15, 3), (16, 3), (17, 3), (18, 3), (19, 3),
  (20, 3), (21, 3), (22, 3), (23, 3), (24, 3), (25, 3);

-- Estructura escolar
INSERT INTO modalidad (id_modalidad, moda) VALUES
  (1, 'Basica'),
  (2, 'Programacion'),
  (3, 'Electromecanica'),
  (4, 'Electronica'),
  (5, 'Maestro Mayor de Obras');

INSERT INTO secciones (id_seccion, seccion) VALUES
  (1, 'A'),
  (2, 'B'),
  (3, 'C'),
  (4, 'D'),
  (5, 'E'),
  (6, 'F');

-- Cursos: 1°-3° Basica en divisiones A..F
INSERT INTO cursos (grado, id_modalidad, id_seccion)
SELECT g.grado, m.id_modalidad, s.id_seccion
FROM (
  SELECT 1 AS grado UNION ALL SELECT 2 UNION ALL SELECT 3
) AS g
INNER JOIN modalidad AS m ON m.moda = 'Basica'
INNER JOIN secciones AS s ON s.seccion IN ('A', 'B', 'C', 'D', 'E', 'F');

-- Cursos: 4°-7° para Programacion, Electromecanica, Electronica, MMO (A..F)
INSERT INTO cursos (grado, id_modalidad, id_seccion)
SELECT g.grado, m.id_modalidad, s.id_seccion
FROM (
  SELECT 4 AS grado UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
) AS g
INNER JOIN modalidad AS m ON m.moda IN ('Programacion', 'Electromecanica', 'Electronica', 'Maestro Mayor de Obras')
INNER JOIN secciones AS s ON s.seccion IN ('A', 'B', 'C', 'D', 'E', 'F');

-- Materias por modalidad
INSERT INTO materias (nombre_materia, turno, grupo, id_curso)
SELECT x.nombre_materia, 'Manana', 1, c.id_curso
FROM cursos AS c
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
INNER JOIN (
  SELECT 'Matematica' AS nombre_materia
  UNION ALL SELECT 'Lengua y Literatura'
  UNION ALL SELECT 'Historia'
  UNION ALL SELECT 'Geografia'
  UNION ALL SELECT 'Biologia'
  UNION ALL SELECT 'Ingles'
  UNION ALL SELECT 'Educacion Fisica'
  UNION ALL SELECT 'Tecnologia'
) AS x
WHERE mo.moda = 'Basica' AND c.grado BETWEEN 1 AND 3;

INSERT INTO materias (nombre_materia, turno, grupo, id_curso)
SELECT x.nombre_materia, 'Manana', 1, c.id_curso
FROM cursos AS c
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
INNER JOIN (
  SELECT 'Matematica Aplicada' AS nombre_materia
  UNION ALL SELECT 'Programacion'
  UNION ALL SELECT 'Laboratorio de Programacion'
  UNION ALL SELECT 'Base de Datos'
  UNION ALL SELECT 'Sistemas Digitales'
  UNION ALL SELECT 'Redes'
  UNION ALL SELECT 'Proyecto de Software'
  UNION ALL SELECT 'Ingles Tecnico'
  UNION ALL SELECT 'Educacion Fisica'
) AS x
WHERE mo.moda = 'Programacion' AND c.grado BETWEEN 4 AND 7;

INSERT INTO materias (nombre_materia, turno, grupo, id_curso)
SELECT x.nombre_materia, 'Manana', 1, c.id_curso
FROM cursos AS c
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
INNER JOIN (
  SELECT 'Matematica Aplicada' AS nombre_materia
  UNION ALL SELECT 'Electrotecnia'
  UNION ALL SELECT 'Maquinas Electricas'
  UNION ALL SELECT 'Automatizacion Industrial'
  UNION ALL SELECT 'Taller de Mecanizado'
  UNION ALL SELECT 'Termodinamica'
  UNION ALL SELECT 'Dibujo Tecnico'
  UNION ALL SELECT 'Educacion Fisica'
) AS x
WHERE mo.moda = 'Electromecanica' AND c.grado BETWEEN 4 AND 7;

INSERT INTO materias (nombre_materia, turno, grupo, id_curso)
SELECT x.nombre_materia, 'Manana', 1, c.id_curso
FROM cursos AS c
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
INNER JOIN (
  SELECT 'Matematica Aplicada' AS nombre_materia
  UNION ALL SELECT 'Electronica Analogica'
  UNION ALL SELECT 'Electronica Digital'
  UNION ALL SELECT 'Microcontroladores'
  UNION ALL SELECT 'Instrumentacion'
  UNION ALL SELECT 'Laboratorio de Electronica'
  UNION ALL SELECT 'Ingles Tecnico'
  UNION ALL SELECT 'Educacion Fisica'
) AS x
WHERE mo.moda = 'Electronica' AND c.grado BETWEEN 4 AND 7;

INSERT INTO materias (nombre_materia, turno, grupo, id_curso)
SELECT x.nombre_materia, 'Manana', 1, c.id_curso
FROM cursos AS c
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
INNER JOIN (
  SELECT 'Matematica Aplicada' AS nombre_materia
  UNION ALL SELECT 'Dibujo Tecnico'
  UNION ALL SELECT 'Construcciones'
  UNION ALL SELECT 'Estructuras'
  UNION ALL SELECT 'Instalaciones'
  UNION ALL SELECT 'Materiales y Ensayos'
  UNION ALL SELECT 'Proyecto de Obra'
  UNION ALL SELECT 'Ingles Tecnico'
  UNION ALL SELECT 'Educacion Fisica'
) AS x
WHERE mo.moda = 'Maestro Mayor de Obras' AND c.grado BETWEEN 4 AND 7;

-- Asignacion de docentes a materias (con criterio por modalidad)
INSERT INTO docentes_x_materia (id_materia, id_grupo, id_persona)
SELECT m.id_materia, NULL,
  CASE
    WHEN mo.moda = 'Basica' THEN 13
    WHEN mo.moda = 'Programacion' AND m.nombre_materia IN ('Programacion', 'Laboratorio de Programacion', 'Base de Datos', 'Proyecto de Software') THEN 5
    WHEN mo.moda = 'Programacion' THEN 6
    WHEN mo.moda = 'Electromecanica' AND m.nombre_materia IN ('Electrotecnia', 'Maquinas Electricas', 'Automatizacion Industrial') THEN 7
    WHEN mo.moda = 'Electromecanica' THEN 8
    WHEN mo.moda = 'Electronica' AND m.nombre_materia IN ('Electronica Analogica', 'Electronica Digital', 'Microcontroladores') THEN 9
    WHEN mo.moda = 'Electronica' THEN 10
    WHEN mo.moda = 'Maestro Mayor de Obras' AND m.nombre_materia IN ('Construcciones', 'Proyecto de Obra') THEN 11
    ELSE 12
  END AS id_persona_docente
FROM materias AS m
INNER JOIN cursos AS c ON c.id_curso = m.id_curso
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad;

-- Asignacion de preceptores por cursos
INSERT INTO preceptor_x_curso (id_persona, id_curso)
SELECT 2, c.id_curso
FROM cursos AS c
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
WHERE mo.moda = 'Basica' AND c.grado BETWEEN 1 AND 3;

INSERT INTO preceptor_x_curso (id_persona, id_curso)
SELECT 3, c.id_curso
FROM cursos AS c
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
WHERE mo.moda IN ('Programacion', 'Electronica') AND c.grado BETWEEN 4 AND 7;

INSERT INTO preceptor_x_curso (id_persona, id_curso)
SELECT 4, c.id_curso
FROM cursos AS c
INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
WHERE mo.moda IN ('Electromecanica', 'Maestro Mayor de Obras') AND c.grado BETWEEN 4 AND 7;

-- Alumnos en cursos de ejemplo
INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 14, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 6 AND mo.moda = 'Programacion' AND s.seccion = 'A' LIMIT 1;
INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 15, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 6 AND mo.moda = 'Programacion' AND s.seccion = 'A' LIMIT 1;

INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 16, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 6 AND mo.moda = 'Electromecanica' AND s.seccion = 'A' LIMIT 1;
INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 17, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 6 AND mo.moda = 'Electromecanica' AND s.seccion = 'A' LIMIT 1;

INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 18, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 5 AND mo.moda = 'Electronica' AND s.seccion = 'B' LIMIT 1;
INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 19, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 5 AND mo.moda = 'Electronica' AND s.seccion = 'B' LIMIT 1;

INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 20, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 4 AND mo.moda = 'Maestro Mayor de Obras' AND s.seccion = 'C' LIMIT 1;
INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 21, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 4 AND mo.moda = 'Maestro Mayor de Obras' AND s.seccion = 'C' LIMIT 1;

INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 22, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 2 AND mo.moda = 'Basica' AND s.seccion = 'A' LIMIT 1;
INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 23, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 2 AND mo.moda = 'Basica' AND s.seccion = 'A' LIMIT 1;

INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 24, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 3 AND mo.moda = 'Basica' AND s.seccion = 'B' LIMIT 1;
INSERT INTO alumnos_x_curso (id_persona, id_curso)
SELECT 25, c.id_curso FROM cursos c INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad INNER JOIN secciones s ON s.id_seccion = c.id_seccion
WHERE c.grado = 3 AND mo.moda = 'Basica' AND s.seccion = 'B' LIMIT 1;

-- Materiales de ejemplo (planificaciones subidas por algunos docentes)
-- Nota: se dejan muchas materias sin registro para simular "docente no subio planificacion".
SET @curso_prog6a := (
  SELECT c.id_curso
  FROM cursos c
  INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad
  INNER JOIN secciones s ON s.id_seccion = c.id_seccion
  WHERE c.grado = 6 AND mo.moda = 'Programacion' AND s.seccion = 'A'
  LIMIT 1
);

SET @curso_electro6a := (
  SELECT c.id_curso
  FROM cursos c
  INNER JOIN modalidad mo ON mo.id_modalidad = c.id_modalidad
  INNER JOIN secciones s ON s.id_seccion = c.id_seccion
  WHERE c.grado = 6 AND mo.moda = 'Electromecanica' AND s.seccion = 'A'
  LIMIT 1
);

INSERT INTO materiales (tipo_material, unidad, url, id_materia)
SELECT 'Planificacion Anual', 'Unidad 1', 'planificaciones/programacion_6a_programacion_u1.pdf', m.id_materia
FROM materias m
WHERE m.id_curso = @curso_prog6a AND m.nombre_materia = 'Programacion';

INSERT INTO materiales (tipo_material, unidad, url, id_materia)
SELECT 'Planificacion Anual', 'Unidad 1', 'planificaciones/programacion_6a_bd_u1.pdf', m.id_materia
FROM materias m
WHERE m.id_curso = @curso_prog6a AND m.nombre_materia = 'Base de Datos';

INSERT INTO materiales (tipo_material, unidad, url, id_materia)
SELECT 'Planificacion Trimestral', 'Unidad 2', 'planificaciones/programacion_6a_matematica_u2.pdf', m.id_materia
FROM materias m
WHERE m.id_curso = @curso_prog6a AND m.nombre_materia = 'Matematica Aplicada';

INSERT INTO materiales (tipo_material, unidad, url, id_materia)
SELECT 'Planificacion Anual', 'Unidad 1', 'planificaciones/electromecanica_6a_electrotecnia_u1.pdf', m.id_materia
FROM materias m
WHERE m.id_curso = @curso_electro6a AND m.nombre_materia = 'Electrotecnia';

-- Configuracion institucional y ciclo de boletin
INSERT INTO boletin_institucion_config (
  id_config, nombre_escuela, direccion, ciudad, codigo_postal, telefono, actualizado_por
) VALUES (
  1,
  'Escuela Tecnica de Prueba Nro 1',
  'Av. Tecnica 1234',
  'Pergamino',
  '2700',
  '02477-322031',
  1
);

INSERT INTO ciclos_lectivos (id_ciclo, anio, nombre, estado, creado_por) VALUES
  (1, 2026, 'Ciclo Lectivo 2026', 'activo', 1);

INSERT INTO boletin_periodos (id_periodo, id_ciclo, nombre, orden, codigo_pdf, activo) VALUES
  (1, 1, 'Primer Informe', 1, 'INF1', 1),
  (2, 1, 'Primer Cuatrimestre', 2, 'CUAT1', 1),
  (3, 1, 'Segundo Informe', 3, 'INF2', 1),
  (4, 1, 'Segundo Cuatrimestre', 4, 'CUAT2', 1);

SET @periodo_inf1 := 1;
SET @periodo_cuat1 := 2;

-- Estados de boletin por curso (ejemplos)
INSERT INTO boletin_curso_periodo (id_curso, id_periodo, estado)
SELECT @curso_prog6a, bp.id_periodo, 'cerrado'
FROM boletin_periodos bp
WHERE @curso_prog6a IS NOT NULL;

INSERT INTO boletin_curso_periodo (id_curso, id_periodo, estado)
SELECT @curso_electro6a, bp.id_periodo, 'cerrado'
FROM boletin_periodos bp
WHERE @curso_electro6a IS NOT NULL;

UPDATE boletin_curso_periodo
SET estado = 'publicado', abierto_por = 3, abierto_en = NOW(), publicado_por = 3, publicado_en = NOW(), version_publicada = 1
WHERE id_curso = @curso_prog6a AND id_periodo = @periodo_inf1;

UPDATE boletin_curso_periodo
SET estado = 'carga_docente', abierto_por = 3, abierto_en = NOW()
WHERE id_curso = @curso_prog6a AND id_periodo = @periodo_cuat1;

UPDATE boletin_curso_periodo
SET estado = 'publicado', abierto_por = 4, abierto_en = NOW(), publicado_por = 4, publicado_en = NOW(), version_publicada = 1
WHERE id_curso = @curso_electro6a AND id_periodo = @periodo_inf1;

-- Notas de boletin de ejemplo (alumnos con boletin cargado)
INSERT INTO boletin_notas (id_periodo, id_curso, id_materia, id_alumno, id_docente, nota_num, sigla)
SELECT
  @periodo_inf1,
  @curso_prog6a,
  m.id_materia,
  a.id_persona,
  dxm.id_persona,
  (((m.id_materia + a.id_persona) % 5) + 6) AS nota_num,
  CASE
    WHEN (((m.id_materia + a.id_persona) % 5) + 6) BETWEEN 1 AND 3 THEN 'TED'
    WHEN (((m.id_materia + a.id_persona) % 5) + 6) BETWEEN 4 AND 6 THEN 'TEP'
    ELSE 'TEA'
  END AS sigla
FROM materias m
INNER JOIN docentes_x_materia dxm ON dxm.id_materia = m.id_materia
INNER JOIN alumnos_x_curso axc ON axc.id_curso = @curso_prog6a
INNER JOIN personas a ON a.id_persona = axc.id_persona
WHERE m.id_curso = @curso_prog6a
  AND a.usuario IN ('alumno01', 'alumno02');

INSERT INTO boletin_notas (id_periodo, id_curso, id_materia, id_alumno, id_docente, nota_num, sigla)
SELECT
  @periodo_inf1,
  @curso_electro6a,
  m.id_materia,
  a.id_persona,
  dxm.id_persona,
  (((m.id_materia + a.id_persona) % 5) + 6) AS nota_num,
  CASE
    WHEN (((m.id_materia + a.id_persona) % 5) + 6) BETWEEN 1 AND 3 THEN 'TED'
    WHEN (((m.id_materia + a.id_persona) % 5) + 6) BETWEEN 4 AND 6 THEN 'TEP'
    ELSE 'TEA'
  END AS sigla
FROM materias m
INNER JOIN docentes_x_materia dxm ON dxm.id_materia = m.id_materia
INNER JOIN alumnos_x_curso axc ON axc.id_curso = @curso_electro6a
INNER JOIN personas a ON a.id_persona = axc.id_persona
WHERE m.id_curso = @curso_electro6a
  AND a.usuario IN ('alumno03', 'alumno04');

-- Carga parcial en CUAT1 para simular avance en proceso (aun no completo)
INSERT INTO boletin_notas (id_periodo, id_curso, id_materia, id_alumno, id_docente, nota_num, sigla)
SELECT
  @periodo_cuat1,
  @curso_prog6a,
  m.id_materia,
  a.id_persona,
  dxm.id_persona,
  8,
  'TEA'
FROM materias m
INNER JOIN (
  SELECT id_materia
  FROM materias
  WHERE id_curso = @curso_prog6a
  ORDER BY nombre_materia
  LIMIT 3
) m3 ON m3.id_materia = m.id_materia
INNER JOIN docentes_x_materia dxm ON dxm.id_materia = m.id_materia
INNER JOIN alumnos_x_curso axc ON axc.id_curso = @curso_prog6a
INNER JOIN personas a ON a.id_persona = axc.id_persona
WHERE a.usuario IN ('alumno01', 'alumno02');

-- Complementos anuales de ejemplo
INSERT INTO boletin_complementos_anuales (
  id_ciclo, id_curso, id_alumno, id_materia, inas_1, inas_2, int_dic, int_feb_mar, nota_final, actualizado_por
)
SELECT 1, @curso_prog6a, a.id_persona, m.id_materia, 2, NULL, NULL, NULL, 8.0, 3
FROM materias m
INNER JOIN alumnos_x_curso axc ON axc.id_curso = @curso_prog6a
INNER JOIN personas a ON a.id_persona = axc.id_persona
WHERE m.id_curso = @curso_prog6a
  AND a.usuario IN ('alumno01', 'alumno02');

INSERT INTO boletin_complementos_anuales (
  id_ciclo, id_curso, id_alumno, id_materia, inas_1, inas_2, int_dic, int_feb_mar, nota_final, actualizado_por
)
SELECT 1, @curso_electro6a, a.id_persona, m.id_materia, 1, NULL, NULL, NULL, 7.5, 4
FROM materias m
INNER JOIN alumnos_x_curso axc ON axc.id_curso = @curso_electro6a
INNER JOIN personas a ON a.id_persona = axc.id_persona
WHERE m.id_curso = @curso_electro6a
  AND a.usuario IN ('alumno03', 'alumno04');

-- Limpieza de variables de sesion SQL
SET @curso_prog6a := NULL;
SET @curso_electro6a := NULL;
SET @periodo_inf1 := NULL;
SET @periodo_cuat1 := NULL;


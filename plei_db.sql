-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 12-09-2025 a las 00:48:07
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `plei_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos_x_curso`
--

CREATE TABLE `alumnos_x_curso` (
  `id_persona_x_curso` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `alumnos_x_curso`
--

INSERT INTO `alumnos_x_curso` (`id_persona_x_curso`, `id_persona`, `id_curso`) VALUES
(1, 6, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `grado` int(11) NOT NULL,
  `id_modalidad` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  `id_preceptor_a_cargo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id_curso`, `grado`, `id_modalidad`, `id_seccion`, `id_preceptor_a_cargo`) VALUES
(4, 4, 1, 1, NULL),
(5, 2, 2, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes_x_materia`
--

CREATE TABLE `docentes_x_materia` (
  `id_docente_x_materia` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `docentes_x_materia`
--

INSERT INTO `docentes_x_materia` (`id_docente_x_materia`, `id_materia`, `id_persona`) VALUES
(1, 1, 4),
(2, 2, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales`
--

CREATE TABLE `materiales` (
  `id_material` int(11) NOT NULL,
  `tipo_material` varchar(40) NOT NULL,
  `unidad` varchar(40) NOT NULL,
  `url` varchar(200) NOT NULL,
  `id_materia` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `materiales`
--

INSERT INTO `materiales` (`id_material`, `tipo_material`, `unidad`, `url`, `id_materia`) VALUES
(1, 'Teorico', '1', 'https://google.com', 1),
(2, 'Teorico', '1', 'https://google.com', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `nombre_materia` varchar(50) NOT NULL,
  `turno` varchar(30) NOT NULL,
  `grupo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id_materia`, `nombre_materia`, `turno`, `grupo`, `id_curso`) VALUES
(1, 'Programacion', 'Tarde', 2, 1),
(2, 'Matematica', 'MaÃ±ana', 1, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modalidad`
--

CREATE TABLE `modalidad` (
  `id_modalidad` int(11) NOT NULL,
  `moda` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `modalidad`
--

INSERT INTO `modalidad` (`id_modalidad`, `moda`) VALUES
(1, 'ProgramaciÃ³n'),
(2, 'Basica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

CREATE TABLE `personas` (
  `id_persona` int(11) NOT NULL,
  `dni` int(11) NOT NULL,
  `apellido` varchar(40) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `mail` varchar(40) NOT NULL,
  `usuario` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `id_rol` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `personas`
--

INSERT INTO `personas` (`id_persona`, `dni`, `apellido`, `nombre`, `mail`, `usuario`, `password`, `id_rol`) VALUES
(1, 48434188, 'Benetti', 'Matias', 'elratonaso88@gmail.com', 'Elratonaso88', '884488', 1),
(3, 23921102, 'rrrrr', 'Ricardo', '1234@abc.com', 'Ricardo', '1234', 1),
(4, 30102255, 'ttttt', 'Tito', '5678@abc.com', 'Tito', '1234', 1),
(5, 123456789, 'Si', 'Si2', 'Si@si.com', 'Sisi', '1234', 1),
(6, 48162488, 'Sassia', 'Pedro', 'pedrosassia10@gmail.com', 'Pepsas', '884488', 1),
(7, 20000000, 'sagsag', 'dsgasgsa', 'asgasg@sagsa.csas', 'sadgasg', '1234', 1),
(8, 235125521, 'sss', 'sss', 'sss@sss.sss', 'sss', '1234', 1),
(11, 235632613, 'ppp', 'ppp', 'ppp@ppp.ppp', 'ppp', '1234', 1),
(13, 32805890, 'casa', 'casas', 'casa@gcasa.casa', 'Casa', '1234', 1),
(15, 999999999, 'Pppprueba', 'ChabonPrueba', 'Prueba@prueba.prueba', 'Prueba', '1234', 1),
(17, 934790437, 'dskjgdksj', 'dlfkbhlds', 'sdgasd@sdlkgn.dsgfas', 'saglkjask', '1234', 1),
(18, 43623632, 'Preceptor2', 'PReceptop', 'preceptor@abc.gob.ar', 'Prece', '1234', 1),
(19, 43285823, 'Precepto2', '', '', '', '', NULL),
(20, 5325211, 'Prece2', 'Prece', 'prece@prece.com', 'Preceptooo', '1234', 1),
(21, 213521532, 'sdlkmgla', 'Ã±kdsgmlkasm', 'lkdsngl@aslkgn.cdsv', '12', '1234', 1),
(22, 3252363, 'sagfas', 'sdasgas', 'sasdgas@gsas.safsa', 'sagas', '1234', 1),
(23, 980957892, 'slkgdnsa', 'dslkglkas', 'dsgas@saf.asf', 'sdgknas', '1234', 1),
(24, 7868768, 'kdjsgkjdsb', 'dsjgnkjasn', 'kjdshgkjasd@gakshbgfas.com', 'sdkjgbas', '1234', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preceptor_x_curso`
--

CREATE TABLE `preceptor_x_curso` (
  `id_preceptor_x_curso` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `preceptor_x_curso`
--

INSERT INTO `preceptor_x_curso` (`id_preceptor_x_curso`, `id_persona`, `id_curso`) VALUES
(8, 3, 4),
(9, 3, 5),
(10, 20, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `rol` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `rol`) VALUES
(1, 'usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones`
--

CREATE TABLE `secciones` (
  `id_seccion` int(11) NOT NULL,
  `seccion` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `secciones`
--

INSERT INTO `secciones` (`id_seccion`, `seccion`) VALUES
(1, 'a');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_personas`
--

CREATE TABLE `tipos_personas` (
  `id_tipo_persona` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `tipos_personas`
--

INSERT INTO `tipos_personas` (`id_tipo_persona`, `tipo`) VALUES
(1, 'alumno'),
(2, 'Preceptor'),
(3, 'Docente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_persona_x_persona`
--

CREATE TABLE `tipo_persona_x_persona` (
  `id_tipo_persona_x_persona` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_tipo_persona` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tipo_persona_x_persona`
--

INSERT INTO `tipo_persona_x_persona` (`id_tipo_persona_x_persona`, `id_persona`, `id_tipo_persona`) VALUES
(2, 1, 1),
(3, 1, 3),
(4, 6, 1),
(5, 3, 2),
(6, 20, 2),
(7, 22, 1),
(8, 24, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos_x_curso`
--
ALTER TABLE `alumnos_x_curso`
  ADD PRIMARY KEY (`id_persona_x_curso`),
  ADD KEY `id_persona` (`id_persona`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`),
  ADD KEY `id_modalidad` (`id_modalidad`),
  ADD KEY `id_seccion` (`id_seccion`);

--
-- Indices de la tabla `docentes_x_materia`
--
ALTER TABLE `docentes_x_materia`
  ADD PRIMARY KEY (`id_docente_x_materia`),
  ADD KEY `id_materia` (`id_materia`),
  ADD KEY `id_persona` (`id_persona`);

--
-- Indices de la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD PRIMARY KEY (`id_material`),
  ADD KEY `id_materia` (`id_materia`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `modalidad`
--
ALTER TABLE `modalidad`
  ADD PRIMARY KEY (`id_modalidad`);

--
-- Indices de la tabla `personas`
--
ALTER TABLE `personas`
  ADD PRIMARY KEY (`id_persona`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `mail` (`mail`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `preceptor_x_curso`
--
ALTER TABLE `preceptor_x_curso`
  ADD PRIMARY KEY (`id_preceptor_x_curso`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_persona` (`id_persona`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `secciones`
--
ALTER TABLE `secciones`
  ADD PRIMARY KEY (`id_seccion`);

--
-- Indices de la tabla `tipos_personas`
--
ALTER TABLE `tipos_personas`
  ADD PRIMARY KEY (`id_tipo_persona`);

--
-- Indices de la tabla `tipo_persona_x_persona`
--
ALTER TABLE `tipo_persona_x_persona`
  ADD PRIMARY KEY (`id_tipo_persona_x_persona`),
  ADD KEY `id_persona` (`id_persona`),
  ADD KEY `id_tipo_persona` (`id_tipo_persona`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos_x_curso`
--
ALTER TABLE `alumnos_x_curso`
  MODIFY `id_persona_x_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `docentes_x_materia`
--
ALTER TABLE `docentes_x_materia`
  MODIFY `id_docente_x_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `id_material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `modalidad`
--
ALTER TABLE `modalidad`
  MODIFY `id_modalidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `personas`
--
ALTER TABLE `personas`
  MODIFY `id_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `preceptor_x_curso`
--
ALTER TABLE `preceptor_x_curso`
  MODIFY `id_preceptor_x_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `secciones`
--
ALTER TABLE `secciones`
  MODIFY `id_seccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tipos_personas`
--
ALTER TABLE `tipos_personas`
  MODIFY `id_tipo_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipo_persona_x_persona`
--
ALTER TABLE `tipo_persona_x_persona`
  MODIFY `id_tipo_persona_x_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumnos_x_curso`
--
ALTER TABLE `alumnos_x_curso`
  ADD CONSTRAINT `alumnos_x_curso_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`),
  ADD CONSTRAINT `alumnos_x_curso_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`);

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`id_modalidad`) REFERENCES `modalidad` (`id_modalidad`),
  ADD CONSTRAINT `cursos_ibfk_2` FOREIGN KEY (`id_seccion`) REFERENCES `secciones` (`id_seccion`),
  ADD CONSTRAINT `cursos_ibfk_3` FOREIGN KEY (`id_preceptor_a_cargo`) REFERENCES `personas` (`id_persona`);

--
-- Filtros para la tabla `docentes_x_materia`
--
ALTER TABLE `docentes_x_materia`
  ADD CONSTRAINT `docentes_x_materia_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`),
  ADD CONSTRAINT `docentes_x_materia_ibfk_2` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`);

--
-- Filtros para la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD CONSTRAINT `materiales_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`);

--
-- Filtros para la tabla `materias`
--
ALTER TABLE `materias`
  ADD CONSTRAINT `materias_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`);

--
-- Filtros para la tabla `personas`
--
ALTER TABLE `personas`
  ADD CONSTRAINT `personas_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

--
-- Filtros para la tabla `preceptor_x_curso`
--
ALTER TABLE `preceptor_x_curso`
  ADD CONSTRAINT `preceptor_x_curso_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `preceptor_x_curso_ibfk_2` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`);

--
-- Filtros para la tabla `tipo_persona_x_persona`
--
ALTER TABLE `tipo_persona_x_persona`
  ADD CONSTRAINT `tipo_persona_x_persona_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`),
  ADD CONSTRAINT `tipo_persona_x_persona_ibfk_2` FOREIGN KEY (`id_tipo_persona`) REFERENCES `tipos_personas` (`id_tipo_persona`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

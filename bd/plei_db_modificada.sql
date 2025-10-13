-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-10-2025 a las 15:54:48
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
(1, 28, 7),
(2, 30, 7),
(3, 124, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos_x_materia`
--

CREATE TABLE `alumnos_x_materia` (
  `id_persona` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `alumnos_x_materia`
--

INSERT INTO `alumnos_x_materia` (`id_persona`, `id_materia`) VALUES
(125, 7),
(126, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `grado` int(11) NOT NULL,
  `id_modalidad` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id_curso`, `grado`, `id_modalidad`, `id_seccion`) VALUES
(7, 3, 4, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes_x_materia`
--

CREATE TABLE `docentes_x_materia` (
  `id_docente_x_materia` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

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
(1, 'rrr', '3', 'q', 7);

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
(7, 'Desarrollo de sitios web dinámicos', 'V', 2, 7),
(8, 'Desarrollo de sitios web dinámicos', 'T', 1, 7);

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
(3, 'programacion'),
(4, 'mmo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

CREATE TABLE `personas` (
  `id_persona` int(11) NOT NULL,
  `dni` int(11) NOT NULL,
  `apellido` varchar(40) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `usuario` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `id_rol` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `personas`
--

INSERT INTO `personas` (`id_persona`, `dni`, `apellido`, `fecha_nacimiento`, `nombre`, `usuario`, `password`, `id_rol`) VALUES
(124, 32554879, 'Matrix', '1988-06-15', 'Jorge', 'jmatrix@gmail.com', '32554879', 2),
(125, 45556687, 'Conti', '1985-11-02', 'Marita', 'mconti@gmail.com', '45556687', 3),
(126, 40877654, 'Pérez', '1990-03-21', 'Lucía', 'lperez@gmail.com', '40877654', 2),
(127, 37999543, 'Gómez', '1989-07-14', 'Carlos', 'cgomez@gmail.com', '37999543', 3),
(128, 36654221, 'Ramírez', '1992-09-30', 'Sofía', 'sramirez@gmail.com', '36654221', 2),
(129, 41233659, 'López', '1986-12-05', 'Martín', 'mlopez@gmail.com', '41233659', 3),
(130, 39877411, 'Fernández', '1991-01-19', 'Ana', 'afernandez@gmail.com', '39877411', 2),
(131, 38444522, 'Torres', '1993-05-23', 'Diego', 'dtorres@gmail.com', '38444522', 3),
(132, 42688993, 'Rossi', '1987-08-11', 'Laura', 'lrossi@gmail.com', '42688993', 2),
(133, 43122877, 'Ruiz', '1990-04-07', 'Nicolás', 'nruiz@gmail.com', '43122877', 3),
(134, 40221985, 'Acosta', '1992-10-16', 'María', 'macosta@gmail.comaaaa', '40221985', 2),
(135, 41566732, 'Santos', '1989-06-22', 'Gabriel', 'gsantos@gmail.com', '41566732', 3),
(136, 38955112, 'Romero', '1988-09-05', 'Valeria', 'vromero@gmail.com', '38955112', 2),
(137, 40577899, 'Castro', '1991-11-28', 'Pablo', 'pcastro@gmail.com', '40577899', 3),
(138, 43366780, 'Herrera', '1993-02-13', 'Florencia', 'fherrera@gmail.com', '43366780', 2),
(139, 42888541, 'Navarro', '1987-12-19', 'Juan', 'jnavarro@gmail.comw', '42888541', 3),
(140, 39766455, 'Díaz', '1985-05-30', 'Elena', 'ediaz@gmail.com', '39766455', 2),
(141, 41011223, 'Vega', '1993-07-14', 'Mariano', 'mvega@gmail.com', '41011223', 3),
(142, 41789456, 'Silva', '1996-02-21', 'Cecilia', 'csilva@gmail.com', '41789456', 2),
(143, 42233698, 'Benítez', '1990-11-30', 'Rodrigo', 'rbenitez@gmail.com', '42233698', 3),
(144, 43001244, 'Sosa', '1999-03-08', 'Milagros', 'msosa@gmail.com', '43001244', 2),
(145, 41877542, 'Molina', '1995-05-17', 'Ezequiel', 'emolina@gmail.com', '41877542', 3),
(146, 39544782, 'Ojeda', '1991-08-12', 'Valentín', 'vojeda@gmail.com', '39544782', 2),
(147, 40633218, 'Cabrera', '1998-12-04', 'Aldana', 'acabrera@gmail.com', '40633218', 3),
(148, 41255471, 'Campos', '1994-04-22', 'Iván', 'icampos@gmail.com', '41255471', 2),
(149, 2147483647, 'Borghi', '0000-00-00', 'bichi', 'bichiborgi@gmai.com', '9999999999', 2),
(150, 123123, 'qweqw', '0000-00-00', 'eqweqw', 'qweqwew@gg', '123123', 2),
(152, 134434245, 'werewrew', '1996-11-23', 'rewr', 'qweqwe@gmgmg', '134434245', 2),
(161, 2342344, '32ewrwer', '2222-02-02', 'ewrwe', '', '234234', 2),
(174, 34324, 'qweeqw', '2226-02-22', 'wqewqe', 'pruebasplei123@gmail.com', '34324', 2),
(175, 48162488, 'Sassia', '2007-10-10', 'Pedro', 'pedrosassia10@gmail.com', '48162488', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preceptor_x_curso`
--

CREATE TABLE `preceptor_x_curso` (
  `id_preceptor_x_curso` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `turno` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
(2, 'usuario'),
(3, 'administrador'),
(4, 'superadministrador');

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
(2, 'A'),
(3, 'B');

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
(5, 'Administrador'),
(6, 'docente'),
(7, 'alumno'),
(8, 'preceptor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_persona_x_persona`
--

CREATE TABLE `tipo_persona_x_persona` (
  `id_persona` int(11) NOT NULL,
  `id_tipo_persona` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tipo_persona_x_persona`
--

INSERT INTO `tipo_persona_x_persona` (`id_persona`, `id_tipo_persona`) VALUES
(28, 7),
(30, 7),
(124, 7),
(125, 6),
(126, 7),
(127, 8),
(128, 7),
(129, 6),
(130, 7),
(131, 5),
(131, 8),
(132, 7),
(133, 6),
(134, 7),
(135, 8),
(136, 7),
(137, 6),
(138, 7),
(139, 8),
(140, 7),
(141, 6),
(142, 7),
(143, 8),
(144, 7),
(145, 6),
(146, 7),
(147, 8),
(148, 7),
(149, 7),
(150, 7),
(152, 7),
(161, 7),
(174, 7);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos_x_curso`
--
ALTER TABLE `alumnos_x_curso`
  ADD PRIMARY KEY (`id_persona_x_curso`),
  ADD UNIQUE KEY `id_persona_2` (`id_persona`,`id_curso`),
  ADD KEY `id_persona` (`id_persona`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `alumnos_x_materia`
--
ALTER TABLE `alumnos_x_materia`
  ADD PRIMARY KEY (`id_persona`,`id_materia`),
  ADD KEY `id_materia` (`id_materia`),
  ADD KEY `id_persona` (`id_persona`);

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
  ADD UNIQUE KEY `id_materia` (`id_materia`,`id_persona`),
  ADD KEY `fk_personas` (`id_persona`);

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
  ADD PRIMARY KEY (`id_persona`,`id_tipo_persona`),
  ADD KEY `id_persona` (`id_persona`),
  ADD KEY `id_tipo_persona` (`id_tipo_persona`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos_x_curso`
--
ALTER TABLE `alumnos_x_curso`
  MODIFY `id_persona_x_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `docentes_x_materia`
--
ALTER TABLE `docentes_x_materia`
  MODIFY `id_docente_x_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `id_material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `modalidad`
--
ALTER TABLE `modalidad`
  MODIFY `id_modalidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `personas`
--
ALTER TABLE `personas`
  MODIFY `id_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=176;

--
-- AUTO_INCREMENT de la tabla `preceptor_x_curso`
--
ALTER TABLE `preceptor_x_curso`
  MODIFY `id_preceptor_x_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `secciones`
--
ALTER TABLE `secciones`
  MODIFY `id_seccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipos_personas`
--
ALTER TABLE `tipos_personas`
  MODIFY `id_tipo_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- Filtros para la tabla `alumnos_x_materia`
--
ALTER TABLE `alumnos_x_materia`
  ADD CONSTRAINT `alumnos_x_materia_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`),
  ADD CONSTRAINT `alumnos_x_materia_ibfk_2` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`);

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`id_modalidad`) REFERENCES `modalidad` (`id_modalidad`),
  ADD CONSTRAINT `cursos_ibfk_2` FOREIGN KEY (`id_seccion`) REFERENCES `secciones` (`id_seccion`);

--
-- Filtros para la tabla `docentes_x_materia`
--
ALTER TABLE `docentes_x_materia`
  ADD CONSTRAINT `docentes_x_materia_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`),
  ADD CONSTRAINT `docentes_x_materia_ibfk_2` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`),
  ADD CONSTRAINT `fk_personas` FOREIGN KEY (`id_persona`) REFERENCES `personas` (`id_persona`);

--
-- Filtros para la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD CONSTRAINT `materiales_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`);

--
-- Filtros para la tabla `materias`
--
ALTER TABLE `materias`
  ADD CONSTRAINT `materias_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

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

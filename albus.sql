-- phpMyAdmin SQL Dump
-- version 4.9.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jun 01, 2020 at 12:31 AM
-- Server version: 5.7.26
-- PHP Version: 7.4.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `albus`
--
CREATE DATABASE IF NOT EXISTS `albus` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `albus`;

-- --------------------------------------------------------

--
-- Table structure for table `alumno`
--

DROP TABLE IF EXISTS `alumno`;
CREATE TABLE `alumno` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `apellido` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `email` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `alumno_x_periodo`
--

DROP TABLE IF EXISTS `alumno_x_periodo`;
CREATE TABLE `alumno_x_periodo` (
  `id` int(11) NOT NULL,
  `periodo_id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `estado` text CHARACTER SET utf8 COLLATE utf8_bin,
  `nota` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `clase`
--

DROP TABLE IF EXISTS `clase`;
CREATE TABLE `clase` (
  `id` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `semestre` int(11) NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `clase_x_alumno`
--

DROP TABLE IF EXISTS `clase_x_alumno`;
CREATE TABLE `clase_x_alumno` (
  `id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `clase_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `examen`
--

DROP TABLE IF EXISTS `examen`;
CREATE TABLE `examen` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `instancia_hash`
--

DROP TABLE IF EXISTS `instancia_hash`;
CREATE TABLE `instancia_hash` (
  `id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `periodo_id` int(11) NOT NULL,
  `hash` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `fechahora_ingreso` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `instancia_preguntas`
--

DROP TABLE IF EXISTS `instancia_preguntas`;
CREATE TABLE `instancia_preguntas` (
  `id` int(11) NOT NULL,
  `pregunta_id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `periodo_id` int(11) NOT NULL,
  `texto_respuesta` text CHARACTER SET utf8 COLLATE utf8_bin,
  `timestamp_instancia` int(11) NOT NULL,
  `timestamp_respuesta` int(11) DEFAULT NULL,
  `orden` int(11) NOT NULL,
  `comentarios_profesor` text CHARACTER SET utf8 COLLATE utf8_bin,
  `nota` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

DROP TABLE IF EXISTS `login`;
CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `token` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `fechahora` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `materia`
--

DROP TABLE IF EXISTS `materia`;
CREATE TABLE `materia` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `materia_x_profesor`
--

DROP TABLE IF EXISTS `materia_x_profesor`;
CREATE TABLE `materia_x_profesor` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `periodo`
--

DROP TABLE IF EXISTS `periodo`;
CREATE TABLE `periodo` (
  `id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `inicio_dttm` int(11) NOT NULL,
  `fin_dttm` int(11) NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pregunta`
--

DROP TABLE IF EXISTS `pregunta`;
CREATE TABLE `pregunta` (
  `id` int(11) NOT NULL,
  `seccion_id` int(11) NOT NULL,
  `texto_pregunta` text CHARACTER SET utf8 COLLATE utf8_bin,
  `imagen_pregunta` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `tipo_pregunta` int(11) NOT NULL,
  `texto_respuesta` text CHARACTER SET utf8 COLLATE utf8_bin,
  `puntaje` int(11) DEFAULT NULL,
  `habilitada` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `profesor`
--

DROP TABLE IF EXISTS `profesor`;
CREATE TABLE `profesor` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `email` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `password` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `pendiente_cambio_pass` tinyint(4) NOT NULL,
  `activo` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `respuesta_cruda`
--

DROP TABLE IF EXISTS `respuesta_cruda`;
CREATE TABLE `respuesta_cruda` (
  `id` int(11) NOT NULL,
  `respuesta` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `fechahora` int(11) NOT NULL,
  `direccion_ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `respuesta_interim`
--

DROP TABLE IF EXISTS `respuesta_interim`;
CREATE TABLE `respuesta_interim` (
  `id` int(11) NOT NULL,
  `instancia_id` int(11) NOT NULL,
  `texto_respuesta` text CHARACTER SET utf8 COLLATE utf8_bin,
  `fechahora` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `seccion`
--

DROP TABLE IF EXISTS `seccion`;
CREATE TABLE `seccion` (
  `id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `cantidad_preguntas` int(11) NOT NULL,
  `titulo` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `descripcion` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `imagen` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `puntaje` int(11) DEFAULT NULL,
  `orden` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alumno`
--
ALTER TABLE `alumno`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `alumno_x_periodo`
--
ALTER TABLE `alumno_x_periodo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `periodo_id` (`periodo_id`),
  ADD KEY `alumno_id` (`alumno_id`);

--
-- Indexes for table `clase`
--
ALTER TABLE `clase`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clase_x_alumno`
--
ALTER TABLE `clase_x_alumno`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alunno_id` (`alumno_id`),
  ADD KEY `clase_id` (`clase_id`);

--
-- Indexes for table `examen`
--
ALTER TABLE `examen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materia_id` (`materia_id`);

--
-- Indexes for table `instancia_hash`
--
ALTER TABLE `instancia_hash`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alumno_id` (`alumno_id`),
  ADD KEY `periodo_id` (`periodo_id`);

--
-- Indexes for table `instancia_preguntas`
--
ALTER TABLE `instancia_preguntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pregunta_id` (`pregunta_id`),
  ADD KEY `alumno_id` (`alumno_id`),
  ADD KEY `periodo_id` (`periodo_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profesor_id` (`profesor_id`);

--
-- Indexes for table `materia`
--
ALTER TABLE `materia`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materia_x_profesor`
--
ALTER TABLE `materia_x_profesor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materia_id` (`materia_id`),
  ADD KEY `profesor_id` (`profesor_id`);

--
-- Indexes for table `periodo`
--
ALTER TABLE `periodo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examen_id` (`examen_id`);

--
-- Indexes for table `pregunta`
--
ALTER TABLE `pregunta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seccion_id` (`seccion_id`);

--
-- Indexes for table `profesor`
--
ALTER TABLE `profesor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `respuesta_cruda`
--
ALTER TABLE `respuesta_cruda`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `respuesta_interim`
--
ALTER TABLE `respuesta_interim`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instancia_id` (`instancia_id`);

--
-- Indexes for table `seccion`
--
ALTER TABLE `seccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `examen_id` (`examen_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alumno`
--
ALTER TABLE `alumno`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alumno_x_periodo`
--
ALTER TABLE `alumno_x_periodo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clase`
--
ALTER TABLE `clase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clase_x_alumno`
--
ALTER TABLE `clase_x_alumno`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examen`
--
ALTER TABLE `examen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instancia_hash`
--
ALTER TABLE `instancia_hash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instancia_preguntas`
--
ALTER TABLE `instancia_preguntas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materia`
--
ALTER TABLE `materia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materia_x_profesor`
--
ALTER TABLE `materia_x_profesor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `periodo`
--
ALTER TABLE `periodo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pregunta`
--
ALTER TABLE `pregunta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profesor`
--
ALTER TABLE `profesor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `respuesta_cruda`
--
ALTER TABLE `respuesta_cruda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `respuesta_interim`
--
ALTER TABLE `respuesta_interim`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seccion`
--
ALTER TABLE `seccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alumno_x_periodo`
--
ALTER TABLE `alumno_x_periodo`
  ADD CONSTRAINT `alumno_x_periodo_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `alumno` (`id`),
  ADD CONSTRAINT `alumno_x_periodo_ibfk_2` FOREIGN KEY (`periodo_id`) REFERENCES `periodo` (`id`);

--
-- Constraints for table `clase_x_alumno`
--
ALTER TABLE `clase_x_alumno`
  ADD CONSTRAINT `clase_x_alumno_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `alumno` (`id`),
  ADD CONSTRAINT `clase_x_alumno_ibfk_2` FOREIGN KEY (`clase_id`) REFERENCES `clase` (`id`);

--
-- Constraints for table `examen`
--
ALTER TABLE `examen`
  ADD CONSTRAINT `examen_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materia` (`id`);

--
-- Constraints for table `instancia_hash`
--
ALTER TABLE `instancia_hash`
  ADD CONSTRAINT `instancia_hash_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `alumno` (`id`),
  ADD CONSTRAINT `instancia_hash_ibfk_2` FOREIGN KEY (`periodo_id`) REFERENCES `periodo` (`id`);

--
-- Constraints for table `instancia_preguntas`
--
ALTER TABLE `instancia_preguntas`
  ADD CONSTRAINT `instancia_preguntas_ibfk_1` FOREIGN KEY (`pregunta_id`) REFERENCES `pregunta` (`id`),
  ADD CONSTRAINT `instancia_preguntas_ibfk_2` FOREIGN KEY (`alumno_id`) REFERENCES `alumno` (`id`),
  ADD CONSTRAINT `instancia_preguntas_ibfk_3` FOREIGN KEY (`periodo_id`) REFERENCES `periodo` (`id`);

--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `login_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `profesor` (`id`);

--
-- Constraints for table `materia_x_profesor`
--
ALTER TABLE `materia_x_profesor`
  ADD CONSTRAINT `materia_x_profesor_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materia` (`id`),
  ADD CONSTRAINT `materia_x_profesor_ibfk_2` FOREIGN KEY (`profesor_id`) REFERENCES `profesor` (`id`);

--
-- Constraints for table `periodo`
--
ALTER TABLE `periodo`
  ADD CONSTRAINT `periodo_ibfk_1` FOREIGN KEY (`examen_id`) REFERENCES `examen` (`id`);

--
-- Constraints for table `pregunta`
--
ALTER TABLE `pregunta`
  ADD CONSTRAINT `pregunta_ibfk_1` FOREIGN KEY (`seccion_id`) REFERENCES `seccion` (`id`);

--
-- Constraints for table `respuesta_interim`
--
ALTER TABLE `respuesta_interim`
  ADD CONSTRAINT `respuesta_interim_ibfk_1` FOREIGN KEY (`instancia_id`) REFERENCES `instancia_preguntas` (`id`);

--
-- Constraints for table `seccion`
--
ALTER TABLE `seccion`
  ADD CONSTRAINT `seccion_ibfk_1` FOREIGN KEY (`examen_id`) REFERENCES `examen` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

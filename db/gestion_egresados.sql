-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-10-2024 a las 03:47:13
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
-- Base de datos: `gestion_egresados`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egresado`
--

CREATE TABLE `egresado` (
  `identificacion` int(11) NOT NULL,
  `nombreCompleto` varchar(255) DEFAULT NULL,
  `dirResidencia` varchar(255) DEFAULT NULL,
  `telResidencia` varchar(20) DEFAULT NULL,
  `telAlternativo` varchar(20) DEFAULT NULL,
  `correoPrincipal` varchar(255) DEFAULT NULL,
  `correoSecundario` varchar(255) DEFAULT NULL,
  `carnet` varchar(255) DEFAULT NULL,
  `sexo` varchar(15) DEFAULT NULL,
  `fallecido` varchar(2) DEFAULT NULL,
  `idGestion` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `expediente_pdf` varchar(255) DEFAULT NULL,
  `anioEgreso` int(11) DEFAULT NULL,
  `numeroCertificado` varchar(50) DEFAULT NULL,
  `fechaEntregaCertificado` date DEFAULT NULL,
  `tituloObtenido` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `egresado`
--

INSERT INTO `egresado` (`identificacion`, `nombreCompleto`, `dirResidencia`, `telResidencia`, `telAlternativo`, `correoPrincipal`, `correoSecundario`, `carnet`, `sexo`, `fallecido`, `idGestion`, `avatar`) VALUES
(1, 'Juan Perez', 'Calle 12', '1111177', '2111111', 'juan.perez@example.com', 'jperez@example.com', 'A12345', 'Masculino', 'No', 1, '66d27d559d460-fondo de pantalla.jpg'),
(2, 'Maria Gomez', 'Calle 2', '2222222', '3222222', 'maria.gomez@example.com', 'mgomez@example.com', 'B12345', 'Femenino', 'No', 1, 'avatar2.png'),
(3, 'Carlos Sanchez', 'Calle 3', '3333333', '4333333', 'carlos.sanchez@example.com', 'csanchez@example.com', 'C12345', 'Masculino', 'No', 1, 'avatar3.png'),
(4, 'Ana Martinez', 'Calle 4', '4444444', '5444444', 'ana.martinez@example.com', 'amartinez@example.com', 'D12345', 'Femenino', 'No', 1, 'avatar4.png'),
(8, 'Claudia Diaz', 'Calle 8', '8888888', '9888888', 'claudia.diaz@example.com', 'cdiaz@example.com', 'H12345', 'Femenino', 'No', 1, 'avatar8.png'),
(9, 'Pedro Ramirez', 'Calle 9', '9999999', '1999999', 'pedro.ramirez@example.com', 'pramirez@example.com', 'I12345', 'Masculino', 'No', 1, 'avatar9.png'),
(10, 'Sofia Torres', 'Calle 10', '10101010', '1101010', 'sofia.torres@example.com', 'storres@example.com', 'J12345', 'Femenino', 'No', 1, 'avatar10.png'),
(11, 'Miguel Castro', 'Calle 11', '11111111', '2111111', 'miguel.castro@example.com', 'mcastro@example.com', 'K12345', 'Masculino', 'No', 1, 'avatar11.png'),
(12, 'Valeria Morales', 'Calle 12', '12121212', '2212121', 'valeria.morales@example.com', 'vmorales@example.com', 'L12345', 'Femenino', 'No', 1, 'avatar12.png'),
(13, 'David Nunez', 'Calle 13', '13131313', '2313131', 'david.nunez@example.com', 'dnunez@example.com', 'M12345', 'Masculino', 'No', 1, 'avatar13.png'),
(14, 'Adriana Flores', 'Calle 14', '14141414', '2414141', 'adriana.flores@example.com', 'aflores@example.com', 'N12345', 'Femenino', 'No', 1, 'avatar14.png'),
(15, 'Jose Ortega', 'Calle 15', '15151515', '2515151', 'jose.ortega@example.com', 'jortega@example.com', 'O12345', 'Masculino', 'No', 1, 'avatar15.png'),
(16, 'Marta Ruiz', 'Calle 16', '16161616', '2616161', 'marta.ruiz@example.com', 'mruiz@example.com', 'P12345', 'Femenino', 'No', 1, 'avatar16.png'),
(17, 'Andres Vasquez', 'Calle 17', '17171717', '2717171', 'andres.vasquez@example.com', 'avasquez@example.com', 'Q12345', 'Masculino', 'No', 1, 'avatar17.png'),
(18, 'Elena Rios', 'Calle 18', '18181818', '2818181', 'elena.rios@example.com', 'erios@example.com', 'R12345', 'Femenino', 'No', 1, 'avatar18.png'),
(19, 'Ricardo Herrera', 'Calle 19', '19191919', '2919191', 'ricardo.herrera@example.com', 'rherrera@example.com', 'S12345', 'Masculino', 'No', 1, 'avatar19.png'),
(20, 'Lucia Vargas', 'Calle 20', '20202020', '2102020', 'lucia.vargas@example.com', 'lvargas@example.com', 'T12345', 'Femenino', 'No', 1, 'avatar20.png'),
(21, 'Roberto Chavez', 'Calle 21', '21212121', '2121212', 'roberto.chavez@example.com', 'rchavez@example.com', 'U12345', 'Masculino', 'No', 1, 'avatar21.png'),
(22, 'Paula Guzman', 'Calle 22', '22222222', '2222222', 'paula.guzman@example.com', 'pguzman@example.com', 'V12345', 'Femenino', 'No', 1, 'avatar22.png'),
(23, 'Fernando Mendoza', 'Calle 23', '23232323', '2323232', 'fernando.mendoza@example.com', 'fmendoza@example.com', 'W12345', 'Masculino', 'No', 1, 'avatar23.png'),
(24, 'Natalia Romero', 'Calle 24', '24242424', '2424242', 'natalia.romero@example.com', 'nromero@example.com', 'X12345', 'Femenino', 'No', 1, 'avatar24.png'),
(25, 'Alejandro Soto', 'Calle 25', '25252525', '2525252', 'alejandro.soto@example.com', 'asoto@example.com', 'Y12345', 'Masculino', 'No', 1, 'avatar25.png'),
(26, 'Gabriela Silva', 'Calle 26', '26262626', '2626262', 'gabriela.silva@example.com', 'gsilva@example.com', 'Z12345', 'Femenino', 'No', 1, 'avatar26.png'),
(27, 'Sergio Ibarra', 'Calle 27', '27272727', '2727272', 'sergio.ibarra@example.com', 'sibarra@example.com', 'AA12345', 'Masculino', 'No', 1, 'avatar27.png'),
(28, 'Rosa Rojas', 'Calle 28', '28282828', '2828282', 'rosa.rojas@example.com', 'rrojas@example.com', 'BB12345', 'Femenino', 'No', 1, 'avatar28.png'),
(29, 'Francisco Ortega', 'Calle 29', '29292929', '2929292', 'francisco.ortega@example.com', 'fortega@example.com', 'CC12345', 'Masculino', 'No', 1, 'avatar29.png'),
(30, 'Veronica Aguilar', 'Calle 30', '30303030', '3020300', 'veronica.aguilar@example.com', 'vaguilar@example.com', 'DD12345', 'Femenino', 'No', 1, 'avatar30.png'),
(31, 'Oscar Paredes', 'Calle 31', '31313131', '3131313', 'oscar.paredes@example.com', 'oparedes@example.com', 'EE12345', 'Masculino', 'No', 1, 'avatar31.png'),
(32, 'Diana Peña', 'Calle 32', '32323232', '3232323', 'diana.pena@example.com', 'dpena@example.com', 'FF12345', 'Femenino', 'No', 1, 'avatar32.png'),
(33, 'Hector Campos', 'Calle 33', '33333333', '3333333', 'hector.campos@example.com', 'hcampos@example.com', 'GG12345', 'Masculino', 'No', 1, 'avatar33.png'),
(34, 'Carmen Navarro', 'Calle 34', '34343434', '3434343', 'carmen.navarro@example.com', 'cnavarro@example.com', 'HH12345', 'Femenino', 'No', 1, 'avatar34.png'),
(35, 'Eduardo Palma', 'Calle 35', '35353535', '3535353', 'eduardo.palma@example.com', 'epalma@example.com', 'II12345', 'Masculino', 'No', 1, 'avatar35.png'),
(36, 'Alicia Tapia', 'Calle 36', '36363636', '3636363', 'alicia.tapia@example.com', 'atapia@example.com', 'JJ12345', 'Femenino', 'No', 1, 'avatar36.png'),
(37, 'Martin Guerra', 'Calle 37', '37373737', '3737373', 'martin.guerra@example.com', 'mguerra@example.com', 'KK12345', 'Masculino', 'No', 1, 'avatar37.png'),
(38, 'Isabel Cruz', 'Calle 38', '38383838', '3838383', 'isabel.cruz@example.com', 'icruz@example.com', 'LL12345', 'Femenino', 'No', 1, 'avatar38.png'),
(39, 'Victor Blanco', 'Calle 39', '39393939', '3939393', 'victor.blanco@example.com', 'vblanco@example.com', 'MM12345', 'Masculino', 'No', 1, 'avatar39.png'),
(40, 'Lilian Molina', 'Calle 40', '40404040', '4040404', 'lilian.molina@example.com', 'lmolina@example.com', 'NN12345', 'Femenino', 'No', 1, 'avatar40.png'),
(41, 'Gustavo Reyes', 'Calle 41', '41414141', '4141414', 'gustavo.reyes@example.com', 'greyes@example.com', 'OO12345', 'Masculino', 'No', 1, 'avatar41.png'),
(42, 'Lorena Vega', 'Calle 42', '42424242', '4242424', 'lorena.vega@example.com', 'lvega@example.com', 'PP12345', 'Femenino', 'No', 1, 'avatar42.png'),
(43, 'Alberto Espinosa', 'Calle 43', '43434343', '4343434', 'alberto.espinosa@example.com', 'aespinosa@example.com', 'QQ12345', 'Masculino', 'No', 1, 'avatar43.png'),
(44, 'Patricia Mendoza', 'Calle 44', '44444444', '4444444', 'patricia.mendoza@example.com', 'pmendoza@example.com', 'RR12345', 'Femenino', 'No', 1, 'avatar44.png'),
(45, 'Sebastian Ruiz', 'Calle 45', '45454545', '4545454', 'sebastian.ruiz@example.com', 'sruiz@example.com', 'SS12345', 'Masculino', 'No', 1, 'avatar45.png'),
(46, 'Mariana Campos', 'Calle 46', '46464646', '4646464', 'mariana.campos@example.com', 'mcampos@example.com', 'TT12345', 'Femenino', 'No', 1, 'avatar46.png'),
(47, 'Enrique Alvarez', 'Calle 47', '47474747', '4747474', 'enrique.alvarez@example.com', 'ealvarez@example.com', 'UU12345', 'Masculino', 'No', 1, 'avatar47.png'),
(48, 'Natalia Mendez', 'Calle 48', '48484848', '4848484', 'natalia.mendez@example.com', 'nmendez@example.com', 'VV12345', 'Femenino', 'No', 1, 'avatar48.png'),
(49, 'Pablo Herrera', 'Calle 49', '49494949', '4949494', 'pablo.herrera@example.com', 'pherrera@example.com', 'WW12345', 'Masculino', 'No', 1, 'avatar49.png'),
(50, 'Carolina Paredes', 'Calle 50', '50505050', '5050505', 'carolina.paredes@example.com', 'cparedes@example.com', 'XX12345', 'Femenino', 'No', 1, 'avatar50.png'),
(1088355429, 'Miguel Ángel Osorio Londoño', 'Vereda el jordán - finca la soledad', '3059116668', '3059116668', 'miguelosorio1904@gmail.com', 'miguelosorio1904@gmail.com', '3123654767', 'Masculino', 'Si', 7, '669742e4c775f-Mark.jpg'),
(1903854023, 'Marcia Milena López Marín', 'Cuba, manzana 35 casa 43.', '3059116669', '3059116669', 'marcia@gmail.com', 'marcia@gmail.com', '3157348099', 'Femenino', 'No', 8, '66a05773494b5-El palomo.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gestion`
--

CREATE TABLE `gestion` (
  `idGestion` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gestion`
--

INSERT INTO `gestion` (`idGestion`, `nombre`) VALUES
(1, 'Realizada la gestión por llamada.'),
(2, 'Realizada la gestión por correo'),
(3, 'Gestión realizada en persona'),
(4, 'Gestión realizada por chat'),
(5, 'Gestión realizada por videollamada'),
(6, 'Gestión realizada por mensajería instantánea'),
(7, 'Gestión realizada por redes sociales'),
(8, 'Gestión realizada con información de las bases de datos de la  u');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `observacion`
--

CREATE TABLE `observacion` (
  `id` int(11) NOT NULL,
  `observacion` text NOT NULL,
  `identificacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `observacion`
--

INSERT INTO `observacion` (`id`, `observacion`, `identificacion`) VALUES
(2, 'Mal estudiante', 1088355429),
(3, 'Excelente ', 49);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `titulo`
--

CREATE TABLE `titulo` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `titulo`
--

INSERT INTO `titulo` (`id`, `nombre`) VALUES
(1, 'Ingeniería de Sistemas'),
(2, 'Microbiología'),
(3, 'Derecho'),
(4, 'Ingeniería comercial'),
(5, 'Ingeniería Financiera'),
(6, 'Ingeniería Robotica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tituloegresado`
--

CREATE TABLE `tituloegresado` (
  `id` int(11) NOT NULL,
  `identificacion` int(11) NOT NULL,
  `fechaGrado` date DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tituloegresado`
--

INSERT INTO `tituloegresado` (`id`, `identificacion`, `fechaGrado`) VALUES
(1, 9, '2022-06-15'),
(2, 4, '1995-10-26'),
(2, 1088355429, '2024-07-16'),
(3, 1, '1984-10-17'),
(3, 2, '1984-10-17'),
(3, 3, '1984-10-26'),
(4, 1, '1984-10-17'),
(4, 8, '2024-07-26'),
(4, 1903854023, '2000-06-14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contraseña` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombre`, `email`, `contraseña`) VALUES
(2, 'Miguel Ángel Osorio Londoño', 'miguelosorio1904@gmail.com', '$2y$10$pYmI6hFJc8Nmpy/5M.zDteSEnbw0NG3N6h/sKrxXzNv8bZvA1KPp.'),
(4, 'Daniela Osorio', 'danioso16@gmail.com', '$2y$10$KImDVaeKvM2qMD840lmQRel74TV013YVanQSCHw0pe41IYObyUGWG'),
(5, 'Marcia Milena Lopez Marín', 'marcia@gmail.com', '$2y$10$3vGbP4FR62SOTqFbl3huFuIIofBzWQZwmZqizzck21ltCTeoCXJBC'),
(6, 'Julian Quevedo', 'julian@gmail.com', '$2y$10$e.eQSBHw/hxKXyYaD7/nQemNVlk5RXMqS0j/j5ATp8Jjok3120XCu'),
(7, 'Julian Quevedo', 'julianquevedo@gmail.com', '$2y$10$Nxc4u9wjO7Xcc1r4YtaZweUiC/MebSykMfIO2t4A6MNYmaezYgKES'),
(8, 'Jason Wills', 'jason@gmail.com', '$2y$10$1jcXFtcgX5.dYKZ18wQz3edBqV0KUqL2OEHL1LaBphfcMHVxgnNAu'),
(9, 'Isabel ', 'isabel@gmail.com', '$2y$10$BQlrrmF3MpFMD5y3eA13H.sNRzk/ZfhUiLzVqI5fy1PNt8xU3E142'),
(15, 'Camila Gonzales', 'camigonza@gmail.com', '$2y$10$MWjDxv3C248StLwjdLHlXuvbVlQx.7NfT3IbRlwbN9L2LciRHCcjq'),
(16, 'Daniel Franco', 'danielito@gmail.com', '$2y$10$bjgOkYDtJZ3XlE6UbxlOIO8.Wx5fd1vX56bcUjiGKdl26TImN2cU2'),
(17, 'pablito', 'diablo@gmail.com', '$2y$10$WNEdjR3K8jlSiMCtWaU61eTyz0fHFS5IqJ/5eubK9N1D941U37Wt6'),
(18, 'pablo yasunari', 'pabloo@gmail.com', '$2y$10$zq6WeUd57S9YpbjJpWFzqub83TALQgMVPlSMvOvT1cLL2aiLmnD2q');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `egresado`
--
ALTER TABLE `egresado`
  ADD PRIMARY KEY (`identificacion`),
  ADD KEY `idGestion` (`idGestion`);

--
-- Indices de la tabla `gestion`
--
ALTER TABLE `gestion`
  ADD PRIMARY KEY (`idGestion`);

--
-- Indices de la tabla `observacion`
--
ALTER TABLE `observacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `identificacion` (`identificacion`);

--
-- Indices de la tabla `titulo`
--
ALTER TABLE `titulo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tituloegresado`
--
ALTER TABLE `tituloegresado`
  ADD PRIMARY KEY (`id`,`identificacion`),
  ADD KEY `fk_tituloegresado_egresado1_idx` (`identificacion`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `gestion`
--
ALTER TABLE `gestion`
  MODIFY `idGestion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `observacion`
--
ALTER TABLE `observacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `titulo`
--
ALTER TABLE `titulo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `egresado`
--
ALTER TABLE `egresado`
  ADD CONSTRAINT `egresado_ibfk_1` FOREIGN KEY (`idGestion`) REFERENCES `gestion` (`idGestion`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `observacion`
--
ALTER TABLE `observacion`
  ADD CONSTRAINT `observacion_ibfk_1` FOREIGN KEY (`identificacion`) REFERENCES `egresado` (`identificacion`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `tituloegresado`
--
ALTER TABLE `tituloegresado`
  ADD CONSTRAINT `fk_tituloegresado_egresado1` FOREIGN KEY (`identificacion`) REFERENCES `egresado` (`identificacion`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

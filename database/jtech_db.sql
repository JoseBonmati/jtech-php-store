-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-01-2026 a las 19:00:31
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
-- Base de datos: `jtech_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito`
--

INSERT INTO `carrito` (`id`, `id_usuario`, `token`, `id_producto`, `cantidad`) VALUES
(1, NULL, 'c91a839d391a74a0bee06c3f23a9a8fe', 1, 4),
(17, NULL, 'b52b33f4980ec5fc6edbb9b4ea92b3c8', 5, 1),
(18, NULL, 'b52b33f4980ec5fc6edbb9b4ea92b3c8', 1, 1),
(28, 2, 'e2c93b656bac47f033b13272e0565c68', 1, 1),
(29, 1, 'f04d3d527b9f455bae35077b1443210a', 11, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `estado`) VALUES
(1, 'Componentes', 'activo'),
(2, 'Periféricos', 'activo'),
(3, 'Ordenadores', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedidos`
--

CREATE TABLE `detalles_pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_pedidos`
--

INSERT INTO `detalles_pedidos` (`id_pedido`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 1354.43),
(2, 5, 1, 88.50),
(2, 9, 1, 349.99),
(2, 11, 1, 759.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('En curso','Enviado','En reparto','Recogido','Cancelado') NOT NULL DEFAULT 'En curso',
  `tipo_pago` varchar(30) NOT NULL,
  `direccion_envio` varchar(100) NOT NULL,
  `localidad_envio` varchar(50) NOT NULL,
  `provincia_envio` varchar(50) NOT NULL,
  `telefono_envio` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `id_usuario`, `fecha`, `total`, `estado`, `tipo_pago`, `direccion_envio`, `localidad_envio`, `provincia_envio`, `telefono_envio`) VALUES
(1, 4, '2026-01-07 19:04:31', 1354.43, 'En reparto', 'Stripe', 'Calle Silvia, 42', 'Elche', 'Alicante', '231434123'),
(2, 1, '2026-01-11 12:46:06', 1197.49, 'En curso', 'Stripe', 'Calle Inventada, 35', 'Santa Pola', 'Alicante', '123456789');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_subcategoria` int(11) DEFAULT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `id_categoria`, `id_subcategoria`, `nombre`, `descripcion`, `precio`, `stock`, `imagen`, `estado`) VALUES
(1, 1, 1, 'Gigabyte GeForce RTX 5080', 'Tarjeta Gráfica Gigabyte GeForce RTX 5080 WINDFORCE OC SFF 16GB GDDR7 Reflex 2 RTX AI DLSS4', 1354.43, 5, '../assets/imagenes/Tarjeta Gráfica Gigabyte GeForce RTX 5080.jpg', 'activo'),
(2, 1, 3, 'SSD M.2 Samsung 990 Pro 2TB', 'Disco Duro Samsung 990 Pro 2TB Disco SSD 7450MB/S NVMe PCIe 4.0 M.2 Gen4', 238.95, 17, '../assets/imagenes/Samsung 990 Pro 2TB Disco SSD.jpg', 'activo'),
(3, 1, 2, 'AMD Ryzen 5 7600X', 'Procesador AMD Ryzen 5 7600X 4.7 GHz Box sin Ventilador', 366.00, 8, '../assets/imagenes/AMD Ryzen 5 7600X.jpg', 'activo'),
(4, 3, 4, 'Portátil HP 15-fd0279ns', 'Portátil HP 15-fd0279ns 15.6\" Intel Core i5-1335U 16GB 1TB SSD Windows 11', 649.00, 2, '../assets/imagenes/Portátil HP 15-fd0279ns.jpg', 'activo'),
(5, 2, 6, 'HyperX Pulsefire Haste 2', 'HyperX Pulsefire Haste 2 Wireless Ratón Gaming Inalámbrico RGB 26000 DPI Negro', 88.50, 22, '../assets/imagenes/HyperX Pulsefire Haste.jpg', 'activo'),
(6, 2, 7, 'Tempest K20 Beast', 'Tempest K20 Beast Teclado Mecánico Gaming RGB TKL Negro', 48.99, 30, '../assets/imagenes/Tempest K20 Beast.jpg', 'activo'),
(7, 1, 3, 'HDD WD Red Plus NAS 4TB', 'Disco Duro WD Red Plus NAS 4TB Disco interno HDD 3.5\" 256MB SATA 3', 170.99, 17, '../assets/imagenes/HDD WD Red Plus NAS 4TB.jpg', 'activo'),
(8, 1, 3, 'SSD SATA Samsung 870 EVO 2TB', 'Disco Duro Samsung 870 EVO 2TB Disco SSD SATA3 560MB', 219.95, 23, '../assets/imagenes/SSD SATA Samsung 870 EVO 2TB.jpg', 'activo'),
(9, 1, 2, 'Intel Core i7-12700', 'Procesador Intel Core i7-12700 2.1 GHz', 349.99, 6, '../assets/imagenes/Intel Core i7-12700.jpg', 'activo'),
(10, 1, 2, 'AMD Ryzen 5 9600X', 'Procesador AMD Ryzen 5 9600X 3.9/5.4GHz', 194.96, 11, '../assets/imagenes/AMD Ryzen 5 9600X.jpg', 'activo'),
(11, 1, 1, 'Gigabyte AMD Radeon RX 9070 XT', 'Tarjeta Gráfica Gigabyte AMD Radeon RX 9070 XT GAMING OC 16GB GDDR6 FSR 4', 759.00, 4, '../assets/imagenes/Gigabyte AMD Radeon RX 9070 XT.jpg', 'activo'),
(12, 1, 1, 'MSI GeForce RTX 3050', 'Tarjeta Gráfica MSI GeForce RTX 3050 VENTUS 2X XS OC 8GB GDDR6', 263.31, 32, '../assets/imagenes/MSI GeForce RTX 3050.jpg', 'activo'),
(13, 3, 4, 'Portátil MSI Cyborg 15', 'Portátil MSI Cyborg 15 B13WFKG-687XES 15.6\" Intel Core i7-13620H 16GB 1TB RTX 5060 8GB FreeDOS', 1299.00, 4, '../assets/imagenes/Portátil MSI Cyborg 15.jpg', 'activo'),
(14, 3, 5, 'PC HP OmniDesk', 'PC HP OmniDesk M02-0029ns AMD Ryzen 5 8500G/16GB/512GB SSD/AMD Radeon 740M/FreeDOS', 649.00, 5, '../assets/imagenes/PC HP OmniDesk.jpg', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subcategorias`
--

CREATE TABLE `subcategorias` (
  `id` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `subcategorias`
--

INSERT INTO `subcategorias` (`id`, `id_categoria`, `nombre`, `estado`) VALUES
(1, 1, 'Tarjetas gráficas', 'activo'),
(2, 1, 'Procesadores', 'activo'),
(3, 1, 'Discos duros', 'activo'),
(4, 3, 'Portátiles', 'activo'),
(5, 3, 'Torres', 'activo'),
(6, 2, 'Ratones', 'activo'),
(7, 2, 'Teclados', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `contrasenya` varchar(255) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `localidad` varchar(50) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `rol` enum('usuario','empleado','administrador') NOT NULL DEFAULT 'usuario',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `contrasenya`, `telefono`, `direccion`, `localidad`, `provincia`, `rol`, `estado`) VALUES
(1, 'Jose', 'jose@jtech.com', '$2y$10$I1O8e75yzeTyha1Jkn5icezA.Vg8vF52ieCwetxmsHiYh5spf0tKW', '123456789', 'Calle Inventada, 35', 'Santa Pola', 'Alicante', 'administrador', 'activo'),
(2, 'Alicia', 'alicia@jtech.com', '$2y$10$IO7e/wQbdgcdAyu2pXi2BukjxiQOfN/kwp6fZ1qLkOUGo3BQQcPhy', '987654322', '', '', '', 'empleado', 'activo'),
(3, 'Juan', 'juan@jtech.com', '$2y$10$VHpd0.aUZNYuL1Z2/PSOXehlXnwunn28kW.4NYA7Udg8BhTvWagwG', '876345987', '', '', '', 'usuario', 'inactivo'),
(4, 'Silvia', 'silvia@jtech.com', '$2y$10$L0CrUTSFKXZAU5xPq5gAjuV69vQEI6/tifZzWeYlbVOpR..ng/6VW', '231434123', 'Calle Silvia, 42', 'Elche', 'Alicante', 'usuario', 'activo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carrito_ibfk_1` (`id_usuario`),
  ADD KEY `carrito_ibfk_2` (`id_producto`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `detalles_pedidos`
--
ALTER TABLE `detalles_pedidos`
  ADD PRIMARY KEY (`id_pedido`,`id_producto`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_subcategoria` (`id_subcategoria`);

--
-- Indices de la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_pedidos`
--
ALTER TABLE `detalles_pedidos`
  ADD CONSTRAINT `detalles_pedidos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalles_pedidos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_subcategoria`) REFERENCES `subcategorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD CONSTRAINT `subcategorias_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

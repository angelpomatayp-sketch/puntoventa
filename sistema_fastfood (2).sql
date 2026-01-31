-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-01-2026 a las 01:44:34
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
-- Base de datos: `sistema_fastfood`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajas`
--

CREATE TABLE `cajas` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_apertura` timestamp NOT NULL DEFAULT current_timestamp(),
  `saldo_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `efectivo_esperado` decimal(10,2) DEFAULT 0.00,
  `efectivo_contado` decimal(10,2) DEFAULT NULL,
  `yape_total_registrado` decimal(10,2) DEFAULT 0.00,
  `diferencia_efectivo` decimal(10,2) DEFAULT 0.00,
  `estado` enum('ABIERTA','CERRADA','VALIDADA') NOT NULL DEFAULT 'ABIERTA',
  `observacion` text DEFAULT NULL,
  `validado_por` int(11) DEFAULT NULL,
  `fecha_validacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cajas`
--

INSERT INTO `cajas` (`id`, `tienda_id`, `usuario_id`, `fecha_apertura`, `saldo_inicial`, `fecha_cierre`, `efectivo_esperado`, `efectivo_contado`, `yape_total_registrado`, `diferencia_efectivo`, `estado`, `observacion`, `validado_por`, `fecha_validacion`) VALUES
(1, 2, 6, '2026-01-22 16:39:28', 20.00, NULL, 21.00, NULL, 9.40, 0.00, 'ABIERTA', NULL, NULL, NULL),
(2, 1, 3, '2026-01-22 18:24:44', 15.00, NULL, 45.00, NULL, 25.00, 0.00, 'ABIERTA', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) DEFAULT NULL COMMENT 'NULL = Global (SUPER_ADMIN), valor = Personalizada',
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_negocio` enum('ALIMENTOS','FERRETERIA','FARMACIA','ROPA','TECNOLOGIA','LIMPIEZA','LIBRERIA','MASCOTAS','VARIADO') DEFAULT 'VARIADO',
  `es_global` tinyint(1) DEFAULT 1 COMMENT '1=Global, 0=Personalizada',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `tienda_id`, `nombre`, `descripcion`, `tipo_negocio`, `es_global`, `activo`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Abarrotes', 'Arroz, fideos, aceite, azúcar', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(2, NULL, 'Bebidas', 'Bebidas frías y calientes, jugos, gaseosas', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(3, NULL, 'Carnes y Embutidos', 'Pollo, res, jamón, salchichas', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(4, NULL, 'Conservas', 'Atún, sardinas, menestras enlatadas', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(5, NULL, 'Fast Food', 'Hamburguesas, pizzas, pollo frito', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(6, NULL, 'Frutas y Verduras', 'Frutas y verduras frescas', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(7, NULL, 'Galletas', 'Galletas dulces y saladas', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(8, NULL, 'Golosinas', 'Dulces, chocolates, caramelos, snacks', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(9, NULL, 'Lácteos', 'Leche, yogurt, quesos, mantequilla', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(10, NULL, 'Panadería', 'Pan, pasteles, bocaditos', 'ALIMENTOS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(11, NULL, 'Cerrajería', 'Candados, chapas, cerraduras', 'FERRETERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(12, NULL, 'Electricidad', 'Cables, enchufes, interruptores, focos', 'FERRETERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(13, NULL, 'Herramientas Eléctricas', 'Taladros, sierras, lijadoras', 'FERRETERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(14, NULL, 'Herramientas Manuales', 'Martillos, destornilladores, llaves', 'FERRETERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(15, NULL, 'Materiales de Construcción', 'Cemento, ladrillos, arena, yeso', 'FERRETERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(16, NULL, 'Pinturas', 'Pinturas, brochas, rodillos, thinner', 'FERRETERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(17, NULL, 'Plomería', 'Tubos, llaves, accesorios de baño', 'FERRETERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(18, NULL, 'Tornillería', 'Tornillos, clavos, tuercas, pernos', 'FERRETERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(19, NULL, 'Bebé y Maternidad', 'Pañales, biberones, toallitas, leche de fórmula', 'FARMACIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(20, NULL, 'Cuidado Bucal', 'Pasta dental, cepillos, enjuague bucal', 'FARMACIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(21, NULL, 'Cuidado Personal', 'Jabones, shampoo, cremas, desodorantes', 'FARMACIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(22, NULL, 'Dermatología', 'Cremas, lociones, protector solar', 'FARMACIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(23, NULL, 'Higiene Femenina', 'Toallas higiénicas, tampones, protectores', 'FARMACIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(24, NULL, 'Medicamentos', 'Medicamentos con y sin receta médica', 'FARMACIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(25, NULL, 'Primeros Auxilios', 'Vendas, alcohol, gasas, curitas', 'FARMACIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(26, NULL, 'Vitaminas y Suplementos', 'Vitaminas, minerales, suplementos', 'FARMACIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(27, NULL, 'Accesorios', 'Gorros, cinturones, carteras, billeteras', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(28, NULL, 'Calzado Hombre', 'Zapatos, zapatillas, sandalias masculinas', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(29, NULL, 'Calzado Mujer', 'Zapatos, zapatillas, sandalias femeninas', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(30, NULL, 'Calzado Niños', 'Calzado infantil', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(31, NULL, 'Ropa Deportiva', 'Ropa y calzado deportivo', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(32, NULL, 'Ropa Hombre', 'Camisas, pantalones, polos, casacas', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(33, NULL, 'Ropa Interior', 'Ropa interior masculina y femenina', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(34, NULL, 'Ropa Mujer', 'Blusas, vestidos, faldas, pantalones', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(35, NULL, 'Ropa Niños', 'Ropa para niños y bebés', 'ROPA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(36, NULL, 'Accesorios Tech', 'Cables, cargadores, fundas, protectores', 'TECNOLOGIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(37, NULL, 'Audio', 'Audífonos, parlantes, micrófonos', 'TECNOLOGIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(38, NULL, 'Celulares y Smartphones', 'Teléfonos celulares', 'TECNOLOGIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(39, NULL, 'Componentes PC', 'Memorias RAM, discos duros, tarjetas de video', 'TECNOLOGIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(40, NULL, 'Computadoras', 'Laptops, PCs de escritorio, tablets', 'TECNOLOGIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(41, NULL, 'Gaming', 'Consolas, juegos, controles, accesorios', 'TECNOLOGIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(42, NULL, 'Impresoras', 'Impresoras, cartuchos, papel', 'TECNOLOGIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(43, NULL, 'Smart Home', 'Dispositivos inteligentes para el hogar', 'TECNOLOGIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(44, NULL, 'Desinfectantes', 'Lejía, desinfectantes, limpiadores', 'LIMPIEZA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(45, NULL, 'Detergentes', 'Detergentes líquidos y en polvo', 'LIMPIEZA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(46, NULL, 'Papel Higiénico y Toallas', 'Papel higiénico, papel toalla, servilletas', 'LIMPIEZA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(47, NULL, 'Utensilios de Limpieza', 'Escobas, trapeadores, baldes', 'LIMPIEZA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(48, NULL, 'Cuadernos y Blocks', 'Cuadernos escolares, universitarios', 'LIBRERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(49, NULL, 'Libros', 'Libros educativos, literatura, revistas', 'LIBRERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(50, NULL, 'Material de Oficina', 'Archivadores, folders, clips, engrapadoras', 'LIBRERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(51, NULL, 'Útiles Escolares', 'Lapiceros, lápices, borradores, reglas', 'LIBRERIA', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(52, NULL, 'Accesorios Mascotas', 'Collares, correas, juguetes, camas', 'MASCOTAS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(53, NULL, 'Alimento para Gatos', 'Comida seca y húmeda para gatos', 'MASCOTAS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(54, NULL, 'Alimento para Perros', 'Comida seca y húmeda para perros', 'MASCOTAS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(55, NULL, 'Higiene Mascotas', 'Shampoo, arena para gatos, desinfectantes', 'MASCOTAS', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(56, NULL, 'Electrodomésticos', 'Pequeños electrodomésticos', 'VARIADO', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(57, NULL, 'Hogar y Decoración', 'Artículos decorativos, mantelería', 'VARIADO', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(58, NULL, 'Juguetería', 'Juguetes y entretenimiento infantil', 'VARIADO', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(59, NULL, 'Otros', 'Productos varios sin categoría específica', 'VARIADO', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(60, NULL, 'Variado', 'Productos varios sin categoría específica', 'VARIADO', 1, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(61, NULL, 'Licorería', '', 'ALIMENTOS', 1, 1, '2026-01-22 13:00:53', '2026-01-22 13:00:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `dni_ruc` varchar(11) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `tienda_id`, `nombre`, `dni_ruc`, `telefono`, `direccion`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'ANÓNIMO', '00000000', '-', NULL, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(2, 2, 'ANÓNIMO', '00000000', '-', NULL, 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(3, 2, 'SERVICIOS Y PROYECTOS DYM SAC', '10987654321', '951753123', NULL, 1, '2026-01-22 16:51:09', '2026-01-22 16:51:09'),
(4, 1, 'Juan Perez', '70354375', '951753123', NULL, 1, '2026-01-22 18:25:15', '2026-01-22 18:25:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id`, `tienda_id`, `venta_id`, `producto_id`, `cantidad`, `precio_unit`, `subtotal`) VALUES
(1, 2, 1, 3, 2, 3.50, 7.00),
(2, 2, 1, 4, 2, 1.20, 2.40),
(3, 2, 2, 3, 2, 3.50, 7.00),
(4, 2, 2, 4, 2, 1.20, 2.40),
(5, 2, 3, 3, 1, 3.50, 3.50),
(6, 2, 3, 4, 1, 1.20, 1.20),
(7, 1, 4, 2, 1, 25.00, 25.00),
(8, 1, 5, 1, 1, 20.00, 20.00),
(9, 1, 6, 2, 1, 25.00, 25.00),
(10, 2, 7, 5, 1, 2.20, 2.20),
(11, 2, 7, 3, 1, 3.50, 3.50),
(12, 2, 7, 4, 1, 1.20, 1.20),
(13, 2, 8, 3, 1, 3.50, 3.50),
(14, 2, 8, 4, 2, 1.20, 2.40),
(15, 2, 8, 5, 2, 2.20, 4.40);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('ENTRADA','SALIDA','AJUSTE') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_nuevo` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `costo_unitario` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `tienda_id`, `producto_id`, `usuario_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`, `motivo`, `costo_unitario`, `created_at`) VALUES
(1, 1, 2, 3, 'ENTRADA', 5, 0, 5, NULL, NULL, '2026-01-22 16:27:12'),
(2, 1, 1, 3, 'ENTRADA', 4, 0, 4, NULL, NULL, '2026-01-22 16:27:27'),
(3, 2, 3, 6, 'ENTRADA', 15, 0, 15, NULL, NULL, '2026-01-22 16:37:04'),
(4, 2, 4, 6, 'ENTRADA', 12, 0, 12, NULL, NULL, '2026-01-22 16:37:13'),
(5, 2, 5, 6, 'ENTRADA', 10, 0, 10, NULL, NULL, '2026-01-23 02:37:55'),
(6, 2, 11, 5, 'ENTRADA', 12, 0, 12, NULL, NULL, '2026-01-23 19:20:22'),
(7, 2, 9, 5, 'ENTRADA', 10, 0, 10, NULL, NULL, '2026-01-23 19:20:30'),
(8, 2, 8, 5, 'ENTRADA', 10, 0, 10, NULL, NULL, '2026-01-23 19:20:39'),
(9, 2, 6, 5, 'ENTRADA', 15, 0, 15, NULL, NULL, '2026-01-23 19:20:46'),
(10, 2, 10, 5, 'ENTRADA', 12, 0, 12, NULL, NULL, '2026-01-23 19:20:57'),
(11, 2, 12, 5, 'ENTRADA', 10, 0, 10, NULL, NULL, '2026-01-23 19:21:07'),
(12, 2, 13, 5, 'ENTRADA', 15, 0, 15, NULL, NULL, '2026-01-23 19:21:15'),
(13, 2, 7, 5, 'ENTRADA', 10, 0, 10, NULL, NULL, '2026-01-23 19:21:21'),
(14, 2, 14, 5, 'ENTRADA', 10, 0, 10, NULL, NULL, '2026-01-26 19:35:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `medio` enum('EFECTIVO','YAPE','PLIN','TARJETA','TRANSFERENCIA') NOT NULL DEFAULT 'EFECTIVO',
  `monto` decimal(10,2) NOT NULL,
  `monto_recibido` decimal(10,2) DEFAULT NULL,
  `vuelto` decimal(10,2) DEFAULT NULL,
  `ref_operacion` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `tienda_id`, `venta_id`, `medio`, `monto`, `monto_recibido`, `vuelto`, `ref_operacion`, `created_at`) VALUES
(1, 2, 1, 'EFECTIVO', 9.40, 10.00, 0.60, NULL, '2026-01-22 16:50:21'),
(2, 2, 2, 'YAPE', 9.40, NULL, 0.00, '159123', '2026-01-22 16:51:30'),
(3, 2, 3, 'EFECTIVO', 4.70, 5.00, 0.30, NULL, '2026-01-22 16:55:12'),
(4, 1, 4, 'EFECTIVO', 25.00, 25.00, 0.00, NULL, '2026-01-22 18:25:31'),
(5, 1, 5, 'EFECTIVO', 20.00, 20.00, 0.00, NULL, '2026-01-22 18:34:34'),
(6, 1, 6, 'YAPE', 25.00, NULL, 0.00, '159123', '2026-01-22 18:49:17'),
(7, 2, 7, 'EFECTIVO', 6.90, 10.00, 3.10, NULL, '2026-01-23 02:38:26'),
(8, 2, 8, 'EFECTIVO', 10.30, 11.00, 0.70, NULL, '2026-01-23 17:53:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_suscripcion`
--

CREATE TABLE `pagos_suscripcion` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA','YAPE','PLIN','TARJETA') NOT NULL DEFAULT 'EFECTIVO',
  `referencia` varchar(100) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `periodo_desde` date NOT NULL,
  `periodo_hasta` date NOT NULL,
  `registrado_por` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `unidad_medida_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `codigo_barra` varchar(50) DEFAULT NULL,
  `precio_compra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `aplica_igv` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `tienda_id`, `categoria_id`, `unidad_medida_id`, `nombre`, `descripcion`, `codigo_barra`, `precio_compra`, `precio_venta`, `stock_minimo`, `stock_actual`, `activo`, `aplica_igv`, `created_at`, `updated_at`) VALUES
(1, 1, 14, NULL, 'Martillo carpintero', NULL, NULL, 0.00, 20.00, 3, 3, 1, 1, '2026-01-22 16:23:17', '2026-01-22 18:34:34'),
(2, 1, 14, NULL, 'Alicate truper', NULL, NULL, 0.00, 25.00, 2, 3, 1, 1, '2026-01-22 16:24:23', '2026-01-22 18:49:17'),
(3, 2, 2, 23, 'Coca cola 500 ml', NULL, NULL, 2.70, 3.50, 3, 9, 1, 1, '2026-01-22 16:34:50', '2026-01-26 19:35:53'),
(4, 2, 7, 1, 'Glasitas Clasicas', NULL, NULL, 0.90, 1.20, 3, 6, 1, 1, '2026-01-22 16:35:15', '2026-01-26 19:36:38'),
(5, 2, 8, 1, 'Papa lays grande', NULL, NULL, 0.00, 2.20, 3, 9, 1, 1, '2026-01-23 02:37:30', '2026-01-26 19:36:52'),
(6, 2, 2, 1, 'Inka kola 500 ml', NULL, NULL, 2.70, 3.50, 3, 15, 1, 1, '2026-01-23 19:15:12', '2026-01-23 19:20:46'),
(7, 2, 8, 1, 'Piqueos medianos', NULL, NULL, 1.20, 2.00, 1, 10, 1, 1, '2026-01-23 19:15:42', '2026-01-23 19:21:21'),
(8, 2, 8, 1, 'Cuantes pequeños', NULL, NULL, 0.80, 1.00, 1, 10, 1, 1, '2026-01-23 19:16:22', '2026-01-23 19:20:39'),
(9, 2, 8, 1, 'Cuantes medianos', NULL, NULL, 1.70, 2.20, 2, 10, 1, 1, '2026-01-23 19:16:56', '2026-01-23 19:20:30'),
(10, 2, 7, 1, 'Nik mediano', NULL, NULL, 1.00, 1.50, 1, 12, 1, 1, '2026-01-23 19:17:25', '2026-01-23 19:20:57'),
(11, 2, 7, 1, 'Choco chips', NULL, NULL, 0.70, 1.00, 1, 12, 1, 1, '2026-01-23 19:18:03', '2026-01-23 19:20:22'),
(12, 2, 8, 1, 'Papas Lays mediano', NULL, NULL, 0.70, 1.00, 1, 10, 1, 1, '2026-01-23 19:19:14', '2026-01-23 19:21:07'),
(13, 2, 2, 1, 'Pepsi 500 ml', NULL, NULL, 0.70, 1.00, 1, 15, 1, 1, '2026-01-23 19:19:47', '2026-01-23 19:21:15'),
(14, 2, 8, 1, 'Chessitos', NULL, NULL, 0.80, 1.50, 3, 10, 1, 1, '2026-01-26 19:35:08', '2026-01-26 19:36:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiendas`
--

CREATE TABLE `tiendas` (
  `id` int(11) NOT NULL,
  `nombre_negocio` varchar(200) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `ruc` varchar(11) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `igv` decimal(5,2) NOT NULL DEFAULT 18.00,
  `estado` enum('ACTIVA','SUSPENDIDA') NOT NULL DEFAULT 'ACTIVA',
  `fecha_activacion` date DEFAULT NULL,
  `plan` varchar(50) DEFAULT 'BASICO',
  `monto_mensual` decimal(10,2) DEFAULT 0.00,
  `fecha_ultimo_pago` date DEFAULT NULL,
  `fecha_proximo_pago` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tiendas`
--

INSERT INTO `tiendas` (`id`, `nombre_negocio`, `slug`, `ruc`, `direccion`, `telefono`, `email`, `logo`, `igv`, `estado`, `fecha_activacion`, `plan`, `monto_mensual`, `fecha_ultimo_pago`, `fecha_proximo_pago`, `created_at`, `updated_at`) VALUES
(1, 'Don Pepe', 'don-pepe', '20123456789', 'Av. Principal 123', '987654321', 'donpepe@gmail.com', 'uploads/logos/tienda_1.png', 18.00, 'ACTIVA', '2025-12-25', 'BASICO', 150.00, NULL, '2026-02-25', '2026-01-22 12:59:08', '2026-01-22 18:54:20'),
(2, 'Skina', 'skina', '20987654321', 'Jr. Comercio 456', '912345678', 'skina@gmail.com', 'uploads/logos/tienda_2.png', 18.00, 'ACTIVA', '2026-01-22', 'BASICO', 150.00, NULL, '2026-02-22', '2026-01-22 12:59:08', '2026-01-22 18:53:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tienda_categorias`
--

CREATE TABLE `tienda_categorias` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `asignado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tienda_categorias`
--

INSERT INTO `tienda_categorias` (`id`, `tienda_id`, `categoria_id`, `activo`, `asignado_por`, `created_at`) VALUES
(1, 2, 2, 1, 3, '2026-01-22 13:01:16'),
(2, 2, 7, 1, 3, '2026-01-22 13:01:16'),
(3, 2, 8, 1, 3, '2026-01-22 13:01:16'),
(4, 2, 61, 1, 3, '2026-01-22 13:01:16'),
(5, 1, 11, 1, 3, '2026-01-22 13:01:28'),
(6, 1, 12, 1, 3, '2026-01-22 13:01:28'),
(7, 1, 13, 1, 3, '2026-01-22 13:01:28'),
(8, 1, 14, 1, 3, '2026-01-22 13:01:28'),
(9, 1, 15, 1, 3, '2026-01-22 13:01:28'),
(10, 1, 16, 1, 3, '2026-01-22 13:01:28'),
(11, 1, 17, 1, 3, '2026-01-22 13:01:28'),
(12, 1, 18, 1, 3, '2026-01-22 13:01:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades_medida`
--

CREATE TABLE `unidades_medida` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'Nombre completo (Kilogramo, Litro, Unidad)',
  `abreviatura` varchar(10) NOT NULL COMMENT 'Abreviatura (kg, L, und)',
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `unidades_medida`
--

INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `descripcion`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Unidad', 'und', 'Unidad individual', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(2, 'Docena', 'doc', 'Conjunto de 12 unidades', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(3, 'Par', 'par', 'Conjunto de 2 unidades', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(4, 'Ciento', 'cto', 'Conjunto de 100 unidades', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(5, 'Millar', 'mll', 'Conjunto de 1000 unidades', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(6, 'Kilogramo', 'kg', 'Unidad de peso (1000 gramos)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(7, 'Gramo', 'g', 'Unidad de peso', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(8, 'Libra', 'lb', 'Unidad de peso (454 gramos)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(9, 'Onza', 'oz', 'Unidad de peso (28.35 gramos)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(10, 'Tonelada', 'tn', 'Unidad de peso (1000 kg)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(11, 'Litro', 'L', 'Unidad de volumen (1000 ml)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(12, 'Mililitro', 'ml', 'Unidad de volumen', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(13, 'Galón', 'gal', 'Unidad de volumen (3.785 litros)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(14, 'Metro', 'm', 'Unidad de longitud (100 cm)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(15, 'Centímetro', 'cm', 'Unidad de longitud', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(16, 'Pulgada', 'pulg', 'Unidad de longitud (2.54 cm)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(17, 'Pie', 'pie', 'Unidad de longitud (30.48 cm)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(18, 'Yarda', 'yd', 'Unidad de longitud (91.44 cm)', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(19, 'Metro cuadrado', 'm²', 'Unidad de área', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(20, 'Paquete', 'paq', 'Paquete o bulto', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(21, 'Caja', 'cja', 'Caja o contenedor', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(22, 'Bolsa', 'bls', 'Bolsa o funda', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(23, 'Botella', 'bot', 'Envase tipo botella', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(24, 'Lata', 'lta', 'Envase tipo lata', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(25, 'Sobre', 'sbr', 'Sobre o sachet', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(26, 'Rollo', 'rll', 'Rollo o bobina', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(27, 'Plancha', 'pln', 'Plancha o lámina', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(28, 'Saco', 'sco', 'Saco o costal', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(29, 'Balde', 'bld', 'Balde o cubeta', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(30, 'Frasco', 'fco', 'Frasco o envase pequeño', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(31, 'Tubo', 'tbo', 'Tubo o cilindro', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(32, 'Porción', 'prc', 'Porción individual', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(33, 'Ración', 'rac', 'Ración o servicio', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(34, 'Plato', 'plt', 'Plato servido', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(35, 'Vaso', 'vso', 'Vaso o medida líquida', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(36, 'Taza', 'tza', 'Medida de cocina', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(37, 'Tableta', 'tab', 'Tableta o comprimido', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(38, 'Cápsula', 'cap', 'Cápsula medicinal', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(39, 'Ampolla', 'amp', 'Ampolla inyectable', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12'),
(40, 'Blíster', 'blt', 'Blíster de medicamentos', 1, '2026-01-23 02:36:12', '2026-01-23 02:36:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('SUPER_ADMINISTRADOR','ADMINISTRADOR','CAJERO') NOT NULL DEFAULT 'CAJERO',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `tienda_id`, `nombre`, `usuario`, `password`, `rol`, `activo`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Super Administrador', 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SUPER_ADMINISTRADOR', 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(2, 1, 'Admin Don Pepe', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMINISTRADOR', 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(3, 1, 'Cajero Don Pepe', 'cajero', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CAJERO', 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(5, 2, 'Admin Skina', 'skina', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMINISTRADOR', 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08'),
(6, 2, 'Cajero Skina', 'cajero_skina', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CAJERO', 1, '2026-01-22 12:59:08', '2026-01-22 12:59:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `nro_ticket` varchar(20) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `igv` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `aplica_igv` tinyint(1) NOT NULL DEFAULT 1,
  `estado` enum('PENDIENTE','PAGADA','ANULADA') NOT NULL DEFAULT 'PENDIENTE',
  `motivo_anulacion` text DEFAULT NULL,
  `anulado_por` int(11) DEFAULT NULL,
  `fecha_anulacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `tienda_id`, `nro_ticket`, `caja_id`, `usuario_id`, `cliente_id`, `fecha_hora`, `subtotal`, `igv`, `total`, `aplica_igv`, `estado`, `motivo_anulacion`, `anulado_por`, `fecha_anulacion`) VALUES
(1, 2, 'T00000001', 1, 6, 2, '2026-01-22 16:50:21', 9.40, 0.00, 9.40, 0, 'PAGADA', NULL, NULL, NULL),
(2, 2, 'T00000002', 1, 6, 2, '2026-01-22 16:51:30', 9.40, 0.00, 9.40, 0, 'PAGADA', NULL, NULL, NULL),
(3, 2, 'T00000003', 1, 6, 3, '2026-01-22 16:55:12', 4.70, 0.00, 4.70, 0, 'PAGADA', NULL, NULL, NULL),
(4, 1, 'T00000001', 2, 3, 1, '2026-01-22 18:25:31', 25.00, 0.00, 25.00, 0, 'PAGADA', NULL, NULL, NULL),
(5, 1, 'T00000002', 2, 3, 4, '2026-01-22 18:34:34', 20.00, 0.00, 20.00, 0, 'PAGADA', NULL, NULL, NULL),
(6, 1, 'T00000003', 2, 3, 4, '2026-01-22 18:49:17', 25.00, 0.00, 25.00, 0, 'PAGADA', NULL, NULL, NULL),
(7, 2, 'T00000004', 1, 6, 3, '2026-01-23 02:38:26', 6.90, 0.00, 6.90, 0, 'PAGADA', NULL, NULL, NULL),
(8, 2, 'T00000005', 1, 6, 3, '2026-01-23 17:53:43', 10.30, 0.00, 10.30, 0, 'ANULADA', 'no pago completo el cliente', 5, '2026-01-23 18:46:09');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cajas`
--
ALTER TABLE `cajas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cajas_usuario` (`usuario_id`),
  ADD KEY `idx_tienda_cajas` (`tienda_id`,`estado`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_global` (`es_global`),
  ADD KEY `idx_tipo_negocio` (`tipo_negocio`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dni_tienda` (`dni_ruc`,`tienda_id`),
  ADD KEY `idx_tienda_clientes` (`tienda_id`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detalle_venta_venta` (`venta_id`),
  ADD KEY `fk_detalle_venta_producto` (`producto_id`),
  ADD KEY `idx_tienda_detalle` (`tienda_id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_movimientos_producto` (`producto_id`),
  ADD KEY `fk_movimientos_usuario` (`usuario_id`),
  ADD KEY `idx_tienda_movimientos` (`tienda_id`,`created_at`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pagos_venta` (`venta_id`),
  ADD KEY `idx_tienda_pagos` (`tienda_id`);

--
-- Indices de la tabla `pagos_suscripcion`
--
ALTER TABLE `pagos_suscripcion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ps_usuario` (`registrado_por`),
  ADD KEY `idx_tienda_pago` (`tienda_id`,`fecha_pago`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_producto_tienda` (`nombre`,`categoria_id`,`tienda_id`),
  ADD KEY `fk_productos_categoria` (`categoria_id`),
  ADD KEY `idx_tienda_productos` (`tienda_id`,`activo`),
  ADD KEY `fk_productos_unidad_medida` (`unidad_medida_id`);

--
-- Indices de la tabla `tiendas`
--
ALTER TABLE `tiendas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indices de la tabla `tienda_categorias`
--
ALTER TABLE `tienda_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tienda_categoria` (`tienda_id`,`categoria_id`),
  ADD KEY `fk_tc_usuario` (`asignado_por`),
  ADD KEY `idx_tienda` (`tienda_id`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `unidades_medida`
--
ALTER TABLE `unidades_medida`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nombre` (`nombre`),
  ADD UNIQUE KEY `unique_abreviatura` (`abreviatura`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_tienda_usuario` (`tienda_id`,`usuario`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ventas_caja` (`caja_id`),
  ADD KEY `fk_ventas_usuario` (`usuario_id`),
  ADD KEY `fk_ventas_cliente` (`cliente_id`),
  ADD KEY `idx_tienda_ventas` (`tienda_id`,`fecha_hora`),
  ADD KEY `idx_tienda_estado` (`tienda_id`,`estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cajas`
--
ALTER TABLE `cajas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pagos_suscripcion`
--
ALTER TABLE `pagos_suscripcion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `tiendas`
--
ALTER TABLE `tiendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tienda_categorias`
--
ALTER TABLE `tienda_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `unidades_medida`
--
ALTER TABLE `unidades_medida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cajas`
--
ALTER TABLE `cajas`
  ADD CONSTRAINT `fk_cajas_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cajas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_detalle_venta_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detalle_venta_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detalle_venta_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `fk_movimientos_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_movimientos_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pagos_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos_suscripcion`
--
ALTER TABLE `pagos_suscripcion`
  ADD CONSTRAINT `fk_ps_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ps_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_productos_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_productos_unidad_medida` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidades_medida` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tienda_categorias`
--
ALTER TABLE `tienda_categorias`
  ADD CONSTRAINT `fk_tc_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tc_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tc_usuario` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_caja` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ventas_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

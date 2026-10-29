-- ==========================================================
-- CerriFlash - Base de Datos Relacional Completa
-- Plataforma de Envíos y Domicilios
-- ==========================================================

DROP DATABASE IF EXISTS `cerriflash`;
CREATE DATABASE `cerriflash`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `cerriflash`;

-- ----------------------------------------------------------
-- 1. Tabla: roles
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(50) NOT NULL UNIQUE,
  `descripcion` VARCHAR(150) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 2. Tabla: usuarios
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `rol_id` INT UNSIGNED NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `telefono` VARCHAR(30) NOT NULL,
  `estado` ENUM('pendiente', 'aprobado', 'rechazado', 'suspendido') NOT NULL DEFAULT 'pendiente',
  `api_token` VARCHAR(80) NULL UNIQUE,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `idx_usuarios_email` ON `usuarios` (`email`);
CREATE INDEX `idx_usuarios_estado` ON `usuarios` (`estado`);

-- ----------------------------------------------------------
-- 3. Tabla: restaurantes (comercios y empresas)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `restaurantes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL UNIQUE,
  `nombre_comercial` VARCHAR(150) NOT NULL,
  `razon_social` VARCHAR(150) NULL,
  `nit` VARCHAR(50) NOT NULL,
  `nombre_responsable` VARCHAR(120) NOT NULL,
  `telefono_contacto` VARCHAR(30) NOT NULL,
  `email_contacto` VARCHAR(120) NULL,
  `direccion` VARCHAR(255) NOT NULL,
  `ciudad` VARCHAR(100) NOT NULL DEFAULT 'El Cerrito',
  `barrio` VARCHAR(100) NULL,
  `latitud` DECIMAL(10, 8) NULL,
  `longitud` DECIMAL(11, 8) NULL,
  `tipo_negocio` VARCHAR(80) NOT NULL DEFAULT 'Restaurante',
  `horarios` VARCHAR(150) NULL,
  `logo` VARCHAR(255) NULL,
  `documento_rut` VARCHAR(255) NULL,
  `documento_camara` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 4. Tabla: domiciliarios
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `domiciliarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL UNIQUE,
  `identificacion` VARCHAR(50) NOT NULL UNIQUE,
  `fecha_nacimiento` DATE NULL,
  `direccion` VARCHAR(255) NULL,
  `ciudad` VARCHAR(100) NOT NULL DEFAULT 'El Cerrito',
  `foto_perfil` VARCHAR(255) NULL,
  `tipo_vehiculo` ENUM('bicicleta', 'motocicleta', 'automovil', 'otro') NOT NULL DEFAULT 'motocicleta',
  `placa_vehiculo` VARCHAR(20) NULL,
  `modelo_vehiculo` VARCHAR(60) NULL,
  `licencia_conduccion` VARCHAR(255) NULL,
  `soat` VARCHAR(255) NULL,
  `tarjeta_propiedad` VARCHAR(255) NULL,
  `disponible` TINYINT(1) NOT NULL DEFAULT 0,
  `latitud_actual` DECIMAL(10, 8) NULL,
  `longitud_actual` DECIMAL(11, 8) NULL,
  `ultima_conexion` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `idx_domiciliarios_disponible` ON `domiciliarios` (`disponible`);

-- ----------------------------------------------------------
-- 5. Tabla: clientes
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL UNIQUE,
  `identificacion` VARCHAR(50) NULL,
  `direccion_frecuente` VARCHAR(255) NULL,
  `ciudad` VARCHAR(100) NOT NULL DEFAULT 'El Cerrito',
  `barrio` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 6. Tabla: direcciones (libreta de direcciones de clientes)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `direcciones` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `etiqueta` VARCHAR(50) NOT NULL DEFAULT 'Casa', -- Casa, Trabajo, Pareja, etc.
  `direccion` VARCHAR(255) NOT NULL,
  `barrio` VARCHAR(100) NULL,
  `indicaciones` TEXT NULL,
  `latitud` DECIMAL(10, 8) NULL,
  `longitud` DECIMAL(11, 8) NULL,
  `es_predeterminada` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 7. Tabla: tarifas (configuración por el administrador)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tarifas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL DEFAULT 'Tarifa Estándar',
  `tarifa_base` DECIMAL(10, 2) NOT NULL DEFAULT 3500.00,
  `precio_por_km` DECIMAL(10, 2) NOT NULL DEFAULT 1200.00,
  `tarifa_minima` DECIMAL(10, 2) NOT NULL DEFAULT 5000.00,
  `recargo_nocturno` DECIMAL(10, 2) NOT NULL DEFAULT 1500.00,
  `recargo_alta_demanda` DECIMAL(10, 2) NOT NULL DEFAULT 2000.00,
  `comision_plataforma_porcentaje` DECIMAL(5, 2) NOT NULL DEFAULT 20.00, -- 20%
  `activa` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 8. Tabla: pedidos / domicilios
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `domicilios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo_seguimiento` VARCHAR(20) NOT NULL UNIQUE,
  `restaurante_id` INT UNSIGNED NOT NULL,
  `domiciliario_id` INT UNSIGNED NULL,
  `cliente_id` INT UNSIGNED NULL,
  `numero_pedido_origen` VARCHAR(50) NULL, -- Número de factura o comanda interna del restaurante
  `direccion_recogida` VARCHAR(255) NOT NULL,
  `lat_recogida` DECIMAL(10, 8) NULL,
  `lng_recogida` DECIMAL(11, 8) NULL,
  `direccion_entrega` VARCHAR(255) NOT NULL,
  `lat_entrega` DECIMAL(10, 8) NULL,
  `lng_entrega` DECIMAL(11, 8) NULL,
  `nombre_cliente` VARCHAR(120) NOT NULL,
  `telefono_cliente` VARCHAR(30) NOT NULL,
  `descripcion_paquete` TEXT NOT NULL,
  `observaciones` TEXT NULL,
  `valor_declarado_pedido` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `hora_recogida_estimada` TIME NULL,
  
  -- Cálculo económico y distancias
  `distancia_km` DECIMAL(6, 2) NOT NULL DEFAULT 1.00,
  `tiempo_estimado_min` INT UNSIGNED NOT NULL DEFAULT 15,
  `tarifa_base` DECIMAL(10, 2) NOT NULL,
  `precio_km` DECIMAL(10, 2) NOT NULL,
  `recargos` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `costo_total_envio` DECIMAL(10, 2) NOT NULL,
  `ganancia_domiciliario` DECIMAL(10, 2) NOT NULL,
  `comision_cerriflash` DECIMAL(10, 2) NOT NULL,
  
  -- Métodos y estados de pago
  `metodo_pago` ENUM('efectivo', 'digital', 'contraentrega') NOT NULL DEFAULT 'contraentrega',
  `estado_pago` ENUM('pendiente', 'pagado', 'rechazado', 'reembolsado') NOT NULL DEFAULT 'pendiente',
  
  -- Máquina de estados operativa
  `estado` ENUM(
    'creado',
    'publicado',
    'buscando_domiciliario',
    'aceptado',
    'en_camino_recogida',
    'llego_recogida',
    'pedido_recogido',
    'en_camino_entrega',
    'entregado',
    'finalizado',
    'cancelado',
    'no_entregado',
    'incidencia'
  ) NOT NULL DEFAULT 'creado',
  
  -- Tiempos de ciclo
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_asignacion` DATETIME NULL,
  `fecha_recogida` DATETIME NULL,
  `fecha_entrega` DATETIME NULL,
  `fecha_finalizacion` DATETIME NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`domiciliario_id`) REFERENCES `domiciliarios`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `idx_domicilios_estado` ON `domicilios` (`estado`);
CREATE INDEX `idx_domicilios_codigo` ON `domicilios` (`codigo_seguimiento`);

-- ----------------------------------------------------------
-- 9. Tabla: historial_estados (Trazabilidad detallada)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historial_estados` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `domicilio_id` INT UNSIGNED NOT NULL,
  `estado` VARCHAR(50) NOT NULL,
  `observaciones` VARCHAR(255) NULL,
  `usuario_id` INT UNSIGNED NULL,
  `latitud` DECIMAL(10, 8) NULL,
  `longitud` DECIMAL(11, 8) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`domicilio_id`) REFERENCES `domicilios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 10. Tabla: pagos (Detalle financiero)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `domicilio_id` INT UNSIGNED NOT NULL,
  `monto` DECIMAL(10, 2) NOT NULL,
  `metodo_pago` ENUM('efectivo', 'digital', 'contraentrega') NOT NULL,
  `estado` ENUM('pendiente', 'pagado', 'rechazado', 'reembolsado') NOT NULL DEFAULT 'pendiente',
  `referencia` VARCHAR(100) NULL,
  `detalles` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`domicilio_id`) REFERENCES `domicilios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 11. Tabla: comisiones (Balance financiero de CerriFlash)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comisiones` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `domicilio_id` INT UNSIGNED NOT NULL UNIQUE,
  `monto_total` DECIMAL(10, 2) NOT NULL,
  `ganancia_domiciliario` DECIMAL(10, 2) NOT NULL,
  `comision_plataforma` DECIMAL(10, 2) NOT NULL,
  `porcentaje_aplicado` DECIMAL(5, 2) NOT NULL,
  `liquidado` TINYINT(1) DEFAULT 0,
  `fecha_liquidacion` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`domicilio_id`) REFERENCES `domicilios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 12. Tabla: notificaciones
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `tipo` VARCHAR(50) NOT NULL DEFAULT 'info', -- info, success, warning, danger
  `url_accion` VARCHAR(255) NULL,
  `leido` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `idx_notificaciones_usuario` ON `notificaciones` (`usuario_id`, `leido`);

-- ----------------------------------------------------------
-- 13. Tabla: incidencias
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `incidencias` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `domicilio_id` INT UNSIGNED NOT NULL,
  `reportado_por_usuario_id` INT UNSIGNED NOT NULL,
  `tipo_incidencia` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `estado` ENUM('abierta', 'en_proceso', 'resuelta', 'cerrada') NOT NULL DEFAULT 'abierta',
  `resolucion` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`domicilio_id`) REFERENCES `domicilios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reportado_por_usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 14. Tabla: calificaciones
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `domicilio_id` INT UNSIGNED NOT NULL UNIQUE,
  `cliente_id` INT UNSIGNED NULL,
  `domiciliario_id` INT UNSIGNED NOT NULL,
  `puntuacion` TINYINT UNSIGNED NOT NULL CHECK (`puntuacion` BETWEEN 1 AND 5),
  `comentario` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`domicilio_id`) REFERENCES `domicilios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`domiciliario_id`) REFERENCES `domiciliarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- DATOS SEMILLA (ROLES, ADMIN Y CONFIGURACIÓN INICIAL)
-- ==========================================================

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'admin', 'Administrador total de la plataforma'),
(2, 'restaurante', 'Restaurantes, comercios y empresas que envían pedidos'),
(3, 'domiciliario', 'Conductores y repartidores que prestan el servicio'),
(4, 'cliente', 'Clientes que reciben los pedidos')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Tarifas iniciales por defecto
INSERT INTO `tarifas` (`id`, `nombre`, `tarifa_base`, `precio_por_km`, `tarifa_minima`, `recargo_nocturno`, `recargo_alta_demanda`, `comision_plataforma_porcentaje`, `activa`)
VALUES
(1, 'Tarifa Valle del Cauca - El Cerrito', 3500.00, 1200.00, 5000.00, 1500.00, 2000.00, 20.00, 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Usuario Administrador Principal (password: admin123)
INSERT INTO `usuarios` (`id`, `rol_id`, `email`, `password`, `nombre`, `telefono`, `estado`, `api_token`)
VALUES
(1, 1, 'admin@cerriflash.com', '$2y$10$2JqPfFQYwzE2GiDojQQif.aKrL4pke9P3/78mJO31sSb2oOSHd4b6', 'Administrador CerriFlash', '3100000000', 'aprobado', 'cf_adm_token_seed_998877')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Usuario Restaurante Demo (password: admin123)
INSERT INTO `usuarios` (`id`, `rol_id`, `email`, `password`, `nombre`, `telefono`, `estado`, `api_token`)
VALUES
(2, 2, 'pizzas@cerriflash.com', '$2y$10$2JqPfFQYwzE2GiDojQQif.aKrL4pke9P3/78mJO31sSb2oOSHd4b6', 'Carlos Pizzería', '3151234567', 'aprobado', 'cf_rest_token_seed_112233')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `restaurantes` (`id`, `usuario_id`, `nombre_comercial`, `razon_social`, `nit`, `nombre_responsable`, `telefono_contacto`, `email_contacto`, `direccion`, `ciudad`, `barrio`, `latitud`, `longitud`, `tipo_negocio`, `horarios`)
VALUES
(1, 2, 'Pizzería Napolitana Express', 'Pizzas del Valle SAS', '901234567-1', 'Carlos Martínez', '3151234567', 'pizzas@cerriflash.com', 'Calle 6 # 10-25', 'El Cerrito', 'Centro', 3.68412000, -76.31345000, 'Pizzería y Comidas Rápidas', '12:00 PM - 10:30 PM')
ON DUPLICATE KEY UPDATE `nombre_comercial` = VALUES(`nombre_comercial`);

-- Usuario Domiciliario Demo (password: admin123)
INSERT INTO `usuarios` (`id`, `rol_id`, `email`, `password`, `nombre`, `telefono`, `estado`, `api_token`)
VALUES
(3, 3, 'domi@cerriflash.com', '$2y$10$2JqPfFQYwzE2GiDojQQif.aKrL4pke9P3/78mJO31sSb2oOSHd4b6', 'Juan Domicilios', '3187654321', 'aprobado', 'cf_domi_token_seed_445566')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `domiciliarios` (`id`, `usuario_id`, `identificacion`, `fecha_nacimiento`, `direccion`, `ciudad`, `tipo_vehiculo`, `placa_vehiculo`, `modelo_vehiculo`, `disponible`, `latitud_actual`, `longitud_actual`)
VALUES
(1, 3, '1114567890', '1995-04-15', 'Carrera 8 # 12-40', 'El Cerrito', 'motocicleta', 'XYZ-98F', 'Yamaha Crypton 115', 1, 3.68350000, -76.31200000)
ON DUPLICATE KEY UPDATE `identificacion` = VALUES(`identificacion`);

-- Usuario Cliente Demo (password: admin123)
INSERT INTO `usuarios` (`id`, `rol_id`, `email`, `password`, `nombre`, `telefono`, `estado`, `api_token`)
VALUES
(4, 4, 'cliente@cerriflash.com', '$2y$10$2JqPfFQYwzE2GiDojQQif.aKrL4pke9P3/78mJO31sSb2oOSHd4b6', 'Laura Rodríguez', '3119876543', 'aprobado', 'cf_clie_token_seed_778899')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `clientes` (`id`, `usuario_id`, `identificacion`, `direccion_frecuente`, `ciudad`, `barrio`)
VALUES
(1, 4, '1113222333', 'Diagonal 4 # 15-30', 'El Cerrito', 'La Esperanza')
ON DUPLICATE KEY UPDATE `identificacion` = VALUES(`identificacion`);

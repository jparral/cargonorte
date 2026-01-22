SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABLA FLETEROS (Transportistas)
CREATE TABLE IF NOT EXISTS `fleteros` (
  `id_fletero` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_externo` VARCHAR(50) DEFAULT NULL, -- El "0003" del PDF
  `nombre` VARCHAR(100) NOT NULL,
  `activo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABLA ZONAS
CREATE TABLE IF NOT EXISTS `zonas` (
  `id_zona` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_externo` VARCHAR(50) DEFAULT NULL, -- El "0001" del PDF
  `nombre_zona` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABLA CLIENTES
CREATE TABLE IF NOT EXISTS `clientes` (
  `id_cliente` VARCHAR(20) NOT NULL, -- Conservamos el ID del PDF como Primary
  `nombre_cliente` VARCHAR(150) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABLA SUCURSALES (Direcciones)
CREATE TABLE IF NOT EXISTS `sucursales` (
  `id_sucursal` INT AUTO_INCREMENT PRIMARY KEY,
  `id_cliente` VARCHAR(20) NOT NULL,
  
  -- Datos Crudos (Tal cual vienen del PDF/JSON)
  `calle_pdf` VARCHAR(150) DEFAULT NULL,
  `altura_pdf` VARCHAR(50) DEFAULT NULL, -- Varchar porque a veces es null o texto
  `localidad_pdf` VARCHAR(50) DEFAULT NULL,
  `texto_busqueda` VARCHAR(255) DEFAULT NULL, -- Concatenación para buscar
  `dias_horarios` VARCHAR(150) DEFAULT NULL,
  
  -- Datos Normalizados (Google / Humano)
  `direccion_formateada` VARCHAR(255) DEFAULT NULL, -- La dirección limpia
  `latitud` DECIMAL(10, 8) DEFAULT NULL,
  `longitud` DECIMAL(11, 8) DEFAULT NULL,
  `google_place_id` VARCHAR(100) DEFAULT NULL,
  
  -- Estados
  `estado_direccion` ENUM('pendiente', 'verificada', 'error') DEFAULT 'pendiente',
  
  KEY `idx_cliente_sucursal` (`id_cliente`),
  CONSTRAINT `fk_sucursal_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABLA PARTES (Lotes de envío)
CREATE TABLE IF NOT EXISTS `partes` (
  `id_parte` INT NOT NULL,
  `fecha_envio` DATE NOT NULL,
  `fecha_importacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_parte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TABLA ENTREGAS (Remitos individuales)
CREATE TABLE IF NOT EXISTS `entregas` (
  `id_entrega` INT AUTO_INCREMENT PRIMARY KEY,
  `id_parte` INT NOT NULL,
  `comprobante` VARCHAR(50) NOT NULL,
  
  -- Relaciones
  `id_cliente` VARCHAR(20) NOT NULL,
  `id_sucursal` INT NOT NULL,
  `id_fletero_asignado` INT DEFAULT NULL, -- FK a tabla fleteros (elección humana)
  
  -- Datos Originales de Importación
  `fletero_pdf_nombre` VARCHAR(100) DEFAULT NULL,
  `zona_pdf_nombre` VARCHAR(100) DEFAULT NULL,
  
  -- Métricas
  `bultos` INT DEFAULT 0,
  `vacunas` INT DEFAULT 0,
  `bolsas` INT DEFAULT 0,
  `cajas` INT DEFAULT 0,
  `otros` INT DEFAULT 0,
  
  -- Logística
  `estado_entrega` ENUM('pendiente_asignacion', 'asignado', 'en_ruta', 'entregado', 'rechazado') DEFAULT 'pendiente_asignacion',
  `orden_recorrido` INT DEFAULT NULL, -- Orden optimizado por Google
  
  -- Auditoría
  `pdf_origen` VARCHAR(255) DEFAULT NULL,
  
  UNIQUE KEY `uk_comprobante` (`comprobante`),
  CONSTRAINT `fk_entrega_parte` FOREIGN KEY (`id_parte`) REFERENCES `partes` (`id_parte`) ON DELETE CASCADE,
  CONSTRAINT `fk_entrega_sucursal` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`),
  CONSTRAINT `fk_entrega_fletero` FOREIGN KEY (`id_fletero_asignado`) REFERENCES `fleteros` (`id_fletero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
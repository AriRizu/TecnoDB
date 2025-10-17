<?php
// ========== Archivo: api/db_setup.php ==========
// This script handles the database connection and table creation if they don't exist.

// Function to get the database connection
function getDbConnection() {
    // Database configuration is loaded from config.php
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        // In a production environment, it's better to log this error than to display it.
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Function to initialize the database
function initializeDatabase($conn) {
    $sql = "
    CREATE TABLE IF NOT EXISTS `autos` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `marca` VARCHAR(100) NOT NULL,
      `modelo` VARCHAR(100) NOT NULL,
      `anio_inicio` INT,
      `anio_fin` INT,
      `spec1` VARCHAR(255) NULL,
      `spec2` VARCHAR(255) NULL,
      `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `items` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nombre` VARCHAR(150) NOT NULL,
      `nombres_secundarios` TEXT NULL,
      `stock` INT NOT NULL DEFAULT 1,
      `stock_threshold` INT NOT NULL DEFAULT 0,
      `imagen` MEDIUMBLOB NULL,
      `imagen_mime` VARCHAR(100) NULL,
      `ubicacion` VARCHAR(255) NULL,
      `imagen_detalle` MEDIUMBLOB NULL,
      `imagen_detalle_mime` VARCHAR(100) NULL,
      `descripcion` TEXT NULL,
      `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS `equipos` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nombre` VARCHAR(150) NOT NULL,
      `descripcion` TEXT NULL,
      `imagen` MEDIUMBLOB NULL,
      `imagen_mime` VARCHAR(100) NULL,
      `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `tags` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nombre` VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS `cortes` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nombre` VARCHAR(100) NOT NULL UNIQUE,
      `bitting` TEXT NULL,
      `imagen` MEDIUMBLOB NULL,
      `imagen_mime` VARCHAR(100) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `auto_items` (
      `auto_id` INT NOT NULL,
      `item_id` INT NOT NULL,
      PRIMARY KEY (`auto_id`, `item_id`),
      FOREIGN KEY (`auto_id`) REFERENCES `autos`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS `auto_equipos` (
      `auto_id` INT NOT NULL,
      `equipo_id` INT NOT NULL,
      `notas` TEXT NULL,
      PRIMARY KEY (`auto_id`, `equipo_id`),
      FOREIGN KEY (`auto_id`) REFERENCES `autos`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`equipo_id`) REFERENCES `equipos`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `item_tags` (
      `item_id` INT NOT NULL,
      `tag_id` INT NOT NULL,
      PRIMARY KEY (`item_id`, `tag_id`),
      FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `item_cortes` (
      `item_id` INT NOT NULL,
      `corte_id` INT NOT NULL,
      PRIMARY KEY (`item_id`, `corte_id`),
      FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`corte_id`) REFERENCES `cortes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `tipos_trabajo` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nombre` VARCHAR(150) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS `clientes` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nombre` VARCHAR(255) NOT NULL,
      `telefono` VARCHAR(100) NULL,
      `cvu` VARCHAR(255) NULL,
      `notas` TEXT NULL,
      `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `trabajos` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `net_profit` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
      `gastos` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
      `tipo_trabajo_id` INT NULL,
      `cliente_patente` VARCHAR(50) NULL,
      `cliente_pincode` VARCHAR(100) NULL,
      `cliente_corte` VARCHAR(100) NULL,
      `corte_id` INT NULL,
      `auto_id` INT NULL,
      `cliente_id` INT NULL,
      `detalle` TEXT NULL,
      `notas` TEXT NULL,
      `imagen` MEDIUMBLOB NULL,
      `imagen_mime` VARCHAR(100) NULL,
      `is_paid` TINYINT(1) NOT NULL DEFAULT 1,
      `fecha_edicion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`tipo_trabajo_id`) REFERENCES `tipos_trabajo`(`id`) ON DELETE SET NULL,
      FOREIGN KEY (`corte_id`) REFERENCES `cortes`(`id`) ON DELETE SET NULL,
      FOREIGN KEY (`auto_id`) REFERENCES `autos`(`id`) ON DELETE SET NULL,
      FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `trabajo_items` (
      `trabajo_id` INT NOT NULL,
      `item_id` INT NOT NULL,
      `cantidad_usada` INT NOT NULL DEFAULT 1,
      PRIMARY KEY (`trabajo_id`, `item_id`),
      FOREIGN KEY (`trabajo_id`) REFERENCES `trabajos`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `trabajo_equipos` (
      `trabajo_id` INT NOT NULL,
      `equipo_id` INT NOT NULL,
      PRIMARY KEY (`trabajo_id`, `equipo_id`),
      FOREIGN KEY (`trabajo_id`) REFERENCES `trabajos`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`equipo_id`) REFERENCES `equipos`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (!$conn->multi_query($sql)) {
        echo "Error creating tables: " . $conn->error;
    }
    while ($conn->next_result()) {;} // Important to clear results

    // --- Migration & Alter Table Logic ---
    $result_items = $conn->query("SHOW TABLES LIKE 'items'");
    if($result_items && $result_items->num_rows > 0) {
        $col_threshold_exists = $conn->query("SHOW COLUMNS FROM `items` LIKE 'stock_threshold'");
        if ($col_threshold_exists && $col_threshold_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `items` ADD COLUMN `stock_threshold` INT NOT NULL DEFAULT 0 AFTER `stock`");
        }
    }
    
    $result_equipos = $conn->query("SHOW TABLES LIKE 'equipos'");
    if($result_equipos && $result_equipos->num_rows > 0) {
        $col_imagen_exists = $conn->query("SHOW COLUMNS FROM `equipos` LIKE 'imagen'");
        if ($col_imagen_exists && $col_imagen_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `equipos` ADD COLUMN `imagen` MEDIUMBLOB NULL AFTER `descripcion`");
        }
        $col_mime_exists = $conn->query("SHOW COLUMNS FROM `equipos` LIKE 'imagen_mime'");
        if ($col_mime_exists && $col_mime_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `equipos` ADD COLUMN `imagen_mime` VARCHAR(100) NULL AFTER `imagen`");
        }
    }
    
    $result_cortes = $conn->query("SHOW TABLES LIKE 'cortes'");
    if ($result_cortes && $result_cortes->num_rows > 0) {
        $col_bitting_exists = $conn->query("SHOW COLUMNS FROM `cortes` LIKE 'bitting'");
        if (!$col_bitting_exists || $col_bitting_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `cortes` ADD COLUMN `bitting` TEXT NULL AFTER `nombre`");
        }
        $col_imagen_exists = $conn->query("SHOW COLUMNS FROM `cortes` LIKE 'imagen'");
        if (!$col_imagen_exists || $col_imagen_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `cortes` ADD COLUMN `imagen` MEDIUMBLOB NULL AFTER `bitting`");
        }
        $col_mime_exists = $conn->query("SHOW COLUMNS FROM `cortes` LIKE 'imagen_mime'");
        if (!$col_mime_exists || $col_mime_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `cortes` ADD COLUMN `imagen_mime` VARCHAR(100) NULL AFTER `imagen`");
        }
    }


    // --- MODIFICATION: Trabajos Table Migrations ---
    $result_trabajos = $conn->query("SHOW TABLES LIKE 'trabajos'");
    if($result_trabajos && $result_trabajos->num_rows > 0) {
        // Add cliente_id column if it doesn't exist
        $col_cliente_id_exists = $conn->query("SHOW COLUMNS FROM `trabajos` LIKE 'cliente_id'");
        if ($col_cliente_id_exists && $col_cliente_id_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `trabajos` ADD COLUMN `cliente_id` INT NULL AFTER `auto_id`, ADD CONSTRAINT `fk_trabajo_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL");
        }

        // Add is_paid column if it doesn't exist
        $col_is_paid_exists = $conn->query("SHOW COLUMNS FROM `trabajos` LIKE 'is_paid'");
        if ($col_is_paid_exists && $col_is_paid_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `trabajos` ADD COLUMN `is_paid` TINYINT(1) NOT NULL DEFAULT 1 AFTER `imagen_mime`");
        }
        
        // --- MODIFICATION: Ensure both 'cliente_corte' and 'corte_id' exist correctly ---
        $col_cliente_corte_exists = $conn->query("SHOW COLUMNS FROM `trabajos` LIKE 'cliente_corte'");
        if (!$col_cliente_corte_exists || $col_cliente_corte_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `trabajos` ADD COLUMN `cliente_corte` VARCHAR(100) NULL AFTER `cliente_pincode`");
        }
        
        $col_corte_id_exists = $conn->query("SHOW COLUMNS FROM `trabajos` LIKE 'corte_id'");
        if (!$col_corte_id_exists || $col_corte_id_exists->num_rows == 0) {
            $conn->query("ALTER TABLE `trabajos` ADD COLUMN `corte_id` INT NULL AFTER `cliente_corte`");
        }

        $fk_check_result = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajos' AND COLUMN_NAME = 'corte_id' AND REFERENCED_TABLE_NAME = 'cortes'");
        if ($fk_check_result && $fk_check_result->num_rows == 0) {
            $conn->query("ALTER TABLE `trabajos` ADD CONSTRAINT `fk_trabajo_corte` FOREIGN KEY (`corte_id`) REFERENCES `cortes`(`id`) ON DELETE SET NULL");
        }
    }
}
?>


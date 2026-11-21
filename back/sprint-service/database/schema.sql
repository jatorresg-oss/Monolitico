CREATE DATABASE IF NOT EXISTS `registro_retro_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `registro_retro_db`;

CREATE TABLE IF NOT EXISTS `sprints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `fecha_inicio` DATE NOT NULL,
    `fecha_fin` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `retro_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sprint_id` INT NOT NULL,
    `categoria` ENUM('accion', 'logro', 'impedimento', 'comentario', 'otro') NOT NULL,
    `descripcion` TEXT NOT NULL,
    `cumplida` BOOLEAN DEFAULT NULL,
    `fecha_revision` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`sprint_id`) REFERENCES `sprints` (`id`) ON DELETE CASCADE
);

-- ============================================================
-- Base de Datos: retrospectivas_db
-- Tablas para Sprint Service
-- ============================================================

CREATE DATABASE IF NOT EXISTS `retrospectivas_db` 
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `retrospectivas_db`;

-- ============================================================
-- TABLA: teams (Equipos)
-- ============================================================
CREATE TABLE IF NOT EXISTS `teams` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `scrum_master` VARCHAR(100),
    `product_owner` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_teams_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: sprints (Sprints)
-- ============================================================
CREATE TABLE IF NOT EXISTS `sprints` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `team_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `number` INT UNSIGNED NOT NULL,
    `goal` TEXT,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('planned', 'active', 'completed', 'cancelled') DEFAULT 'planned',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sprints_team_number` (`team_id`, `number`),
    KEY `idx_sprints_team` (`team_id`),
    KEY `idx_sprints_dates` (`start_date`, `end_date`),
    KEY `idx_sprints_status` (`status`),
    CONSTRAINT `fk_sprints_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE PRUEBA (SEED)
-- ============================================================

-- Equipos
INSERT INTO `teams` (`name`, `description`, `scrum_master`, `product_owner`) VALUES
('Equipo Alpha', 'Equipo de desarrollo principal', 'María González', 'Carlos Ruiz'),
('Equipo Beta', 'Equipo de frontend y UX', 'Juan Pérez', 'Ana López');

-- Sprints
INSERT INTO `sprints` (`team_id`, `name`, `number`, `goal`, `start_date`, `end_date`, `status`) VALUES
(1, 'Sprint 1 - Fundación', 1, 'Establecer arquitectura base y CI/CD', '2026-01-01', '2026-01-14', 'completed'),
(1, 'Sprint 2 - Autenticación', 2, 'Implementar login, registro y roles', '2026-01-15', '2026-01-28', 'completed'),
(1, 'Sprint 3 - Dashboard', 3, 'Crear dashboard principal con métricas', '2026-01-29', '2026-02-11', 'active'),
(1, 'Sprint 4 - Reportes', 4, 'Generar reportes PDF y exportación', '2026-02-12', '2026-02-25', 'planned'),
(2, 'Sprint 1 - Setup UI', 1, 'Configurar diseño system y componentes base', '2026-01-01', '2026-01-14', 'completed'),
(2, 'Sprint 2 - Pantallas Auth', 2, 'Diseñar e implementar pantallas de autenticación', '2026-01-15', '2026-01-28', 'completed');
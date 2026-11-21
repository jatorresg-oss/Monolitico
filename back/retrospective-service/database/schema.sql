-- ============================================================
-- Base de Datos: retrospectivas_db
-- Tablas para Retrospective Service
-- ============================================================

USE `retrospectivas_db`;

-- ============================================================
-- TABLA: retrospectives
-- ============================================================
CREATE TABLE IF NOT EXISTS `retrospectives` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sprint_id` INT UNSIGNED NOT NULL,
    `facilitator` VARCHAR(100),
    `date` DATE NOT NULL,
    `duration_minutes` INT UNSIGNED DEFAULT 60,
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_retrospectives_sprint` (`sprint_id`),
    KEY `idx_retrospectives_date` (`date`),
    KEY `idx_retrospectives_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: retrospective_participants
-- ============================================================
CREATE TABLE IF NOT EXISTS `retrospective_participants` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `retrospective_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('developer', 'scrum_master', 'product_owner', 'stakeholder', 'other') DEFAULT 'developer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_participants_retrospective` (`retrospective_id`),
    CONSTRAINT `fk_participants_retrospective` FOREIGN KEY (`retrospective_id`) REFERENCES `retrospectives` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

INSERT INTO `retrospectives` (`sprint_id`, `facilitator`, `date`, `duration_minutes`, `status`, `notes`) VALUES
(1, 'María González', '2026-01-14', 90, 'completed', 'Buena participación del equipo. Se identificaron 3 acciones clave.'),
(2, 'María González', '2026-01-28', 75, 'completed', 'Equipo más confiado. Mejor estimación de tareas.'),
(5, 'Juan Pérez', '2026-01-14', 60, 'completed', 'Diseño system aprobado por stakeholders.'),
(6, 'Juan Pérez', '2026-01-28', 90, 'completed', 'Buen feedback de UX testing.');

INSERT INTO `retrospective_participants` (`retrospective_id`, `name`, `role`) VALUES
(1, 'María González', 'scrum_master'),
(1, 'Carlos Ruiz', 'product_owner'),
(1, 'Pedro Martín', 'developer'),
(1, 'Laura Sánchez', 'developer'),
(2, 'María González', 'scrum_master'),
(2, 'Carlos Ruiz', 'product_owner'),
(3, 'Juan Pérez', 'scrum_master'),
(3, 'Ana López', 'product_owner');
-- ============================================================
-- Base de Datos: retrospectivas_db
-- Tablas para Action Tracking Service
-- ============================================================

USE `retrospectivas_db`;

-- ============================================================
-- TABLA: action_tracking
-- ============================================================
CREATE TABLE IF NOT EXISTS `action_tracking` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `original_feedback_id` INT UNSIGNED NOT NULL COMMENT 'ID del feedback original (action) en feedback_db',
    `review_sprint_id` INT UNSIGNED NOT NULL COMMENT 'Sprint donde se revisa el cumplimiento',
    `status` ENUM('completed', 'partial', 'not_completed', 'carried_over') NOT NULL,
    `completion_percentage` TINYINT UNSIGNED DEFAULT 0 COMMENT '0-100%',
    `comments` TEXT COMMENT 'Observaciones sobre el cumplimiento',
    `reviewed_by` VARCHAR(100) COMMENT 'Quién revisó',
    `reviewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_action_tracking_unique` (`original_feedback_id`, `review_sprint_id`),
    KEY `idx_action_tracking_original` (`original_feedback_id`),
    KEY `idx_action_tracking_review_sprint` (`review_sprint_id`),
    KEY `idx_action_tracking_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

INSERT INTO `action_tracking` (`original_feedback_id`, `review_sprint_id`, `status`, `completion_percentage`, `comments`, `reviewed_by`) VALUES
(5, 2, 'completed', 100, 'DoD documentada y aprobada por todo el equipo', 'María González'),
(6, 2, 'completed', 100, 'Workshop realizado, estimaciones mejoraron notablemente', 'María González'),
(7, 2, 'completed', 100, 'Dailys ahora duran 12-14 min consistentemente', 'Equipo completo'),
(11, 3, 'partial', 60, 'Tests paralelos implementados, pero quedan 2 tests frágiles', 'DevOps'),
(12, 3, 'not_completed', 0, 'Equipo decidió posponer para siguiente sprint por carga', 'Equipo completo'),
(16, 6, 'completed', 100, 'Auditoría completada, todos los componentes base cumplen AA', 'Ana López');
-- ============================================================
-- Base de Datos: retrospectivas_db
-- Tablas para Feedback Service
-- ============================================================

USE `retrospectivas_db`;

-- ============================================================
-- TABLA: feedback_items
-- ============================================================
CREATE TABLE IF NOT EXISTS `feedback_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `retrospective_id` INT UNSIGNED NOT NULL,
    `type` ENUM('positive', 'negative', 'action', 'comment') NOT NULL COMMENT 'positive=Logro, negative=Impedimento, action=Acción, comment=Comentario',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `assignee` VARCHAR(100) COMMENT 'Responsable de la acción',
    `due_date` DATE COMMENT 'Fecha límite para acciones',
    `order_index` INT UNSIGNED DEFAULT 0 COMMENT 'Orden de visualización',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_feedback_retrospective` (`retrospective_id`),
    KEY `idx_feedback_type` (`type`),
    KEY `idx_feedback_priority` (`priority`),
    KEY `idx_feedback_assignee` (`assignee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

INSERT INTO `feedback_items` (`retrospective_id`, `type`, `title`, `description`, `priority`, `assignee`, `due_date`, `order_index`) VALUES
(1, 'positive', 'Buena comunicación', 'El equipo se comunicó bien durante el sprint', 'medium', NULL, NULL, 1),
(1, 'positive', 'CI/CD funcionando', 'Pipeline automatizado desde día 1', 'medium', NULL, NULL, 2),
(1, 'negative', 'Estimaciones imprecisas', 'Las historias se subestimaron consistentemente', 'high', NULL, NULL, 1),
(1, 'negative', 'Falta de definición de done', 'No teníamos criterios claros de finalización', 'high', NULL, NULL, 2),
(1, 'action', 'Definir Definition of Done', 'Crear checklist compartido para DoD', 'high', 'María González', '2026-01-21', 1),
(1, 'action', 'Workshop de estimación', 'Sesión de Planning Poker para mejorar estimaciones', 'medium', 'Carlos Ruiz', '2026-01-21', 2),
(1, 'action', 'Daily standups más enfocadas', 'Limitar a 15 min estrictos, solo bloqueos', 'low', 'Equipo completo', '2026-01-16', 3),
(1, 'comment', 'Buen ambiente de trabajo', 'El equipo disfrutó colaborando', 'low', NULL, NULL, 1),

(2, 'positive', 'DoD implementada', 'Definition of Done ayudó a cerrar historias', 'medium', NULL, NULL, 1),
(2, 'positive', 'Mejor estimación', 'Planning Poker mejoró precisión en 40%', 'medium', NULL, NULL, 2),
(2, 'negative', 'Pruebas lentas', 'Suite de tests tarda >10 min', 'high', NULL, NULL, 1),
(2, 'negative', 'Merge conflicts frecuentes', 'Ramitas de vida larga causan conflictos', 'medium', NULL, NULL, 2),
(2, 'action', 'Optimizar tests', 'Paralelizar suite y eliminar tests frágiles', 'high', 'DevOps', '2026-02-04', 1),
(2, 'action', 'Trunk-based development', 'Adoptar commits pequeños a main', 'medium', 'Equipo completo', '2026-02-04', 2),

(3, 'positive', 'Diseño system consistente', 'Componentes reutilizables aceleran desarrollo', 'medium', NULL, NULL, 1),
(3, 'positive', 'Buena colaboración diseño-dev', 'Handoff fluido con Figma', 'medium', NULL, NULL, 2),
(3, 'negative', 'Accesibilidad pendiente', 'No validamos WCAG en componentes', 'high', NULL, NULL, 1),
(3, 'action', 'Auditoría accesibilidad', 'Revisar todos los componentes vs WCAG 2.1 AA', 'high', 'Ana López', '2026-01-21', 1),

(4, 'positive', 'Accesibilidad mejorada', 'Componentes base cumplen WCAG AA', 'medium', NULL, NULL, 1),
(4, 'negative', 'Tests visuales faltantes', 'No hay regression testing visual', 'medium', NULL, NULL, 1),
(4, 'action', 'Implementar Chromatic/Storybook', 'Visual testing automatizado', 'medium', 'Juan Pérez', '2026-02-04', 1);
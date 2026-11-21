-- Base de datos del proyecto de retrospectivas Scrum.
-- Ubicada dentro de back/ para mantener toda la infraestructura del backend junta,
-- igual que la configuración de Eloquent de cada microservicio.
--
-- Aplicar con:  php back/setup_database.php
-- O manualmente: C:\xampp\mysql\bin\mysql.exe -u root < back\registro_retro_db.sql

CREATE DATABASE IF NOT EXISTS registro_retro_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE registro_retro_db;

-- Tabla de Sprints
CREATE TABLE IF NOT EXISTS sprints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla única para items de retrospectiva (acciones, logros, impedimentos, etc.)
CREATE TABLE IF NOT EXISTS retro_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sprint_id INT NOT NULL,
    categoria ENUM('accion', 'logro', 'impedimento', 'comentario', 'otro') NOT NULL,
    descripcion TEXT NOT NULL,
    cumplida BOOLEAN DEFAULT NULL, -- sólo aplica para acciones; NULL para los demás
    fecha_revision DATE DEFAULT NULL, -- fecha para revisar cumplimiento o seguimiento
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de demostración. INSERT IGNORE + ids fijos hace que volver a ejecutar
-- el archivo no duplique registros ni sobrescriba datos creados por el usuario.
INSERT IGNORE INTO sprints (id, nombre, fecha_inicio, fecha_fin) VALUES
    (1, 'Sprint 1 - Fundamentos del producto', '2026-10-01', '2026-10-14'),
    (2, 'Sprint 2 - Registro de retrospectivas', '2026-10-15', '2026-10-28');

INSERT IGNORE INTO retro_items (id, sprint_id, categoria, descripcion, cumplida, fecha_revision) VALUES
    (1,  1, 'logro',       'El equipo logró entregar el módulo de sprints dentro del tiempo acordado.', NULL, NULL),
    (2,  1, 'impedimento', 'Faltó comunicación al integrar los endpoints del backend y el frontend.', NULL, NULL),
    (3,  1, 'accion',      'Definir un contrato de API antes de iniciar cada historia de usuario.', 1, '2026-10-14'),
    (4,  1, 'accion',      'Agregar pruebas de integración para los servicios.', 0, '2026-10-28'),
    (5,  1, 'comentario',  'La reunión se realizó con buena participación de todo el equipo.', NULL, NULL),
    (6,  2, 'logro',       'Se completó el registro de retrospectivas por sprint con todas sus categorías.', NULL, NULL),
    (7,  2, 'logro',       'El tablero de historial permite consultar fácilmente los acuerdos anteriores.', NULL, NULL),
    (8,  2, 'impedimento', 'La base de datos de desarrollo se reinició y se perdió tiempo recuperando el entorno.', NULL, NULL),
    (9,  2, 'accion',      'Documentar el proceso de instalación y arranque de los microservicios.', 0, '2026-11-05'),
    (10, 2, 'accion',      'Revisar en el próximo sprint las acciones pendientes del Sprint 1.', 0, '2026-11-05'),
    (11, 2, 'comentario',  'El equipo acuerda mantener las retrospectivas al finalizar cada sprint.', NULL, NULL);

-- ============================================================================
-- PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
-- DESCRIPCIÓN: Script de creación de Base de Datos y Datos de Prueba
-- MOTOR: InnoDB | CHARSET: utf8mb4 | COMPATIBILIDAD: MySQL 5.7+ / 8.0+ / MariaDB
-- ============================================================================

-- 1. CREACIÓN DE LA BASE DE DATOS
CREATE DATABASE IF NOT EXISTS `sfs_access_control` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `sfs_access_control`;

-- 2. ELIMINACIÓN DE TABLAS PREVIAS (SI EXISTEN) PARA IMPORTACIÓN LIMPIA
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `registros_acceso`;
DROP TABLE IF EXISTS `sesiones_admin`;
DROP TABLE IF EXISTS `blog_articulos`;
DROP TABLE IF EXISTS `dispositivos_sensores`;
DROP TABLE IF EXISTS `huellas_dactilares`;
DROP TABLE IF EXISTS `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 3. TABLA: usuarios
-- Almacena información de estudiantes, docentes, directivos y sensores
-- ============================================================================
CREATE TABLE `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `documento` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Documento de identidad (T.I, C.C, etc.)',
    `correo` VARCHAR(100) NULL UNIQUE COMMENT 'Correo electrónico para inicio de sesión',
    `nombre` VARCHAR(120) NOT NULL COMMENT 'Nombre completo del usuario',
    `grado` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Grado escolar (Ej. 10°A, 11°B) o Cargo',
    `huella_template` TEXT NULL COMMENT 'Hash o template biométrico de la huella dactilar',
    `password` VARCHAR(255) NULL COMMENT 'Hash bcrypt para inicio de sesión en panel administrativo',
    `rol` ENUM('ADMINISTRADOR', 'ESTUDIANTE', 'DOCENTE', 'SENSOR') NOT NULL DEFAULT 'ESTUDIANTE',
    `estado` ENUM('ACTIVO', 'INACTIVO', 'SUSPENDIDO') NOT NULL DEFAULT 'ACTIVO' COMMENT 'Permiso de acceso a la institución',
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_usuarios_documento` (`documento`),
    INDEX `idx_usuarios_correo` (`correo`),
    INDEX `idx_usuarios_rol` (`rol`),
    INDEX `idx_usuarios_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3.1. TABLA: huellas_dactilares
-- Almacena hasta 6 huellas dactilares por usuario (1 por dedo / slot)
-- ============================================================================
CREATE TABLE `huellas_dactilares` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL COMMENT 'ID del usuario propietario de la huella',
    `slot_numero` TINYINT NOT NULL COMMENT 'Número de slot: 1 al 6 (máximo 6 huellas por usuario)',
    `dedo` VARCHAR(50) NOT NULL COMMENT 'Nombre del dedo (Ej: Pulgar Derecho, Índice Izquierdo)',
    `huella_template` TEXT NOT NULL COMMENT 'Hash SHA-256 o template biométrico de la huella',
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_usuario_slot` (`usuario_id`, `slot_numero`),
    UNIQUE KEY `uk_huella_template` (`huella_template`(255)),
    INDEX `idx_huella_usuario` (`usuario_id`),
    CONSTRAINT `fk_huella_usuario`
        FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. TABLA: registros_acceso
-- Almacena el historial cronológico de entradas y salidas registradas
-- ============================================================================
CREATE TABLE `registros_acceso` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NULL DEFAULT NULL COMMENT 'ID del usuario asociado (NULL si la huella no existe)',
    `sensor_id` INT NULL DEFAULT NULL COMMENT 'ID del dispositivo sensor que registró el acceso',
    `tipo_evento` ENUM('ENTRADA', 'SALIDA') NOT NULL COMMENT 'Tipo de movimiento registrado',
    `fecha_hora` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Momento exacto del registro',
    `estado_acceso` ENUM('APROBADO', 'RECHAZADO') NOT NULL COMMENT 'Resultado de la validación biométrica',
    `observaciones` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Detalle del evento (ej. Usuario inactivo, Huella no reconocida, etc.)',
    INDEX `idx_acceso_fecha_hora` (`fecha_hora`),
    INDEX `idx_acceso_tipo` (`tipo_evento`),
    INDEX `idx_acceso_estado` (`estado_acceso`),
    INDEX `idx_acceso_usuario` (`usuario_id`),
    INDEX `idx_acceso_sensor` (`sensor_id`),
    CONSTRAINT `fk_acceso_usuario` 
        FOREIGN KEY (`usuario_id`) 
        REFERENCES `usuarios` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_acceso_sensor`
        FOREIGN KEY (`sensor_id`)
        REFERENCES `dispositivos_sensores` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. DATOS DE PRUEBA INSTITUCIONALES (DML)
-- ============================================================================

INSERT INTO `usuarios` (`id`, `documento`, `correo`, `nombre`, `grado`, `huella_template`, `password`, `rol`, `estado`) VALUES
-- Directivos y Docentes (Contraseña por defecto: admin123)
(1, '10000001', 'rectoria@jorgerobledo.edu.co', 'Prof. Carlos Andrés Restrepo', 'RECTORÍA', 'FINGERPRINT_HASH_ADMIN_001', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ADMINISTRADOR', 'ACTIVO'),
(2, '10000002', 'sistemas@jorgerobledo.edu.co', 'Ing. Valeria Zapata', 'SISTEMAS', 'FINGERPRINT_HASH_ADMIN_002', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ADMINISTRADOR', 'ACTIVO'),
(3, '10000003', 'docente.arango@jorgerobledo.edu.co', 'Lic. Fernando Arango', 'DOCENTE', 'FINGERPRINT_HASH_DOC_001', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'DOCENTE', 'ACTIVO'),
(4, '10000004', 'coordinacion@jorgerobledo.edu.co', 'Lic. Martha Lucía Pérez', 'COORDINACIÓN', 'FINGERPRINT_HASH_COORD_001', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ADMINISTRADOR', 'ACTIVO'),

-- Estudiantes Grado 11°A
(5, '10359001', 'alejandra.martinez@estudiante.edu.co', 'Alejandra Martínez', '11°A', 'HUELLA_HEX_SAMPLE_001', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO'),
(6, '10359002', 'santiago.morales@estudiante.edu.co', 'Santiago Morales', '11°A', 'HUELLA_HEX_SAMPLE_004', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO'),
(7, '10359003', 'lucia.gomez@estudiante.edu.co', 'Lucía Gómez', '11°A', 'HUELLA_HEX_SAMPLE_003', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'INACTIVO'),

-- Estudiantes Grado 11°B
(8, '10359004', 'valentina.henao@estudiante.edu.co', 'Valentina Henao', '11°B', 'HUELLA_HEX_SAMPLE_005', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO'),
(9, '10359005', 'mateo.quintero@estudiante.edu.co', 'Mateo Quintero', '11°B', 'HUELLA_HEX_SAMPLE_006', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO'),

-- Estudiantes Grado 10°A
(10, '10359006', 'manuela.correa@estudiante.edu.co', 'Manuela Correa', '10°A', 'HUELLA_HEX_SAMPLE_007', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO'),
(11, '10359007', 'daniel.jaramillo@estudiante.edu.co', 'Daniel Jaramillo', '10°A', 'HUELLA_HEX_SAMPLE_008', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO'),

-- Estudiantes Grado 10°B
(12, '10359008', 'carlos.rodriguez@estudiante.edu.co', 'Carlos Rodríguez', '10°B', 'HUELLA_HEX_SAMPLE_002', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO'),
(13, '10359009', 'mariana.osorio@estudiante.edu.co', 'Mariana Osorio', '10°B', 'HUELLA_HEX_SAMPLE_009', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'SUSPENDIDO'),

-- Estudiantes Grado 9°A
(14, '10359010', 'juan.estrada@estudiante.edu.co', 'Juan José Estrada', '9°A', 'HUELLA_HEX_SAMPLE_010', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO'),
(15, '10359011', 'isabella.rincon@estudiante.edu.co', 'Isabella Rincón', '9°A', 'HUELLA_HEX_SAMPLE_011', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ESTUDIANTE', 'ACTIVO');

-- Historial de Accesos de Prueba
INSERT INTO `registros_acceso` (`usuario_id`, `tipo_evento`, `fecha_hora`, `estado_acceso`, `observaciones`) VALUES
(1, 'ENTRADA', NOW() - INTERVAL 240 MINUTE, 'APROBADO', 'Ingreso Directivo - Portería Principal'),
(3, 'ENTRADA', NOW() - INTERVAL 230 MINUTE, 'APROBADO', 'Ingreso Docente - Portería Principal'),
(4, 'ENTRADA', NOW() - INTERVAL 220 MINUTE, 'APROBADO', 'Ingreso Coordinación Académica'),
(5, 'ENTRADA', NOW() - INTERVAL 210 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(6, 'ENTRADA', NOW() - INTERVAL 205 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(8, 'ENTRADA', NOW() - INTERVAL 200 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(9, 'ENTRADA', NOW() - INTERVAL 195 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(10, 'ENTRADA', NOW() - INTERVAL 190 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(11, 'ENTRADA', NOW() - INTERVAL 185 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(12, 'ENTRADA', NOW() - INTERVAL 180 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(14, 'ENTRADA', NOW() - INTERVAL 170 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(15, 'ENTRADA', NOW() - INTERVAL 165 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(9, 'SALIDA', NOW() - INTERVAL 90 MINUTE, 'APROBADO', 'Salida autorizada - Permiso médico'),
(7, 'ENTRADA', NOW() - INTERVAL 60 MINUTE, 'RECHAZADO', 'Acceso denegado: Usuario en estado INACTIVO'),
(13, 'ENTRADA', NOW() - INTERVAL 45 MINUTE, 'RECHAZADO', 'Acceso denegado: Usuario en estado SUSPENDIDO'),
(NULL, 'ENTRADA', NOW() - INTERVAL 15 MINUTE, 'RECHAZADO', 'Huella o documento no registrado en el sistema SFS');

-- ============================================================================
-- 6. TABLA: dispositivos_sensores
-- Registra los lectores biométricos físicos (ESP32, Arduino, etc.) instalados
-- ============================================================================
CREATE TABLE `dispositivos_sensores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único del dispositivo (ej. SENSOR-PORTERIA-01)',
    `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo del lector biométrico',
    `ubicacion` VARCHAR(150) NOT NULL COMMENT 'Ubicación física (ej. Portería Principal, Portería Secundaria, Bloque Aulas)',
    `tipo` ENUM('ENTRADA', 'SALIDA', 'BIDIRECCIONAL') NOT NULL DEFAULT 'BIDIRECCIONAL' COMMENT 'Función del sensor',
    `modelo` VARCHAR(80) NULL COMMENT 'Modelo del hardware (ej. ESP32-CAM + AS608)',
    `ip_local` VARCHAR(45) NULL COMMENT 'IP local del dispositivo en la red del colegio',
    `estado` ENUM('ACTIVO', 'INACTIVO', 'MANTENIMIENTO') NOT NULL DEFAULT 'ACTIVO',
    `ultimo_ping` TIMESTAMP NULL DEFAULT NULL COMMENT 'Última vez que el sensor respondió',
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sensor_codigo` (`codigo`),
    INDEX `idx_sensor_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. TABLA: blog_articulos
-- Artículos y publicaciones del blog institucional (gestión desde el panel admin)
-- ============================================================================
CREATE TABLE `blog_articulos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(200) NOT NULL COMMENT 'Título del artículo',
    `resumen` VARCHAR(400) NOT NULL COMMENT 'Extracto o descripción breve',
    `contenido` TEXT NOT NULL COMMENT 'Cuerpo completo del artículo en HTML o Markdown',
    `icono_fa` VARCHAR(80) NULL DEFAULT 'fa-solid fa-newspaper' COMMENT 'Clase FontAwesome para el icono visual',
    `color_icono` VARCHAR(30) NULL DEFAULT 'icon-blue' COMMENT 'Clase CSS del color del icono',
    `autor_id` INT NULL COMMENT 'ID del usuario administrador autor del artículo',
    `publicado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = visible en el blog, 0 = borrador',
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_blog_publicado` (`publicado`),
    INDEX `idx_blog_autor` (`autor_id`),
    CONSTRAINT `fk_blog_autor`
        FOREIGN KEY (`autor_id`)
        REFERENCES `usuarios` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. TABLA: sesiones_admin
-- Auditoría de inicio y cierre de sesión de usuarios con rol administrativo
-- ============================================================================
CREATE TABLE `sesiones_admin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL COMMENT 'ID del usuario que inició sesión',
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'Dirección IP del cliente',
    `user_agent` VARCHAR(300) NULL COMMENT 'Navegador y sistema operativo del cliente',
    `inicio_sesion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Momento del login',
    `cierre_sesion` TIMESTAMP NULL DEFAULT NULL COMMENT 'Momento del logout (NULL si aún está activa)',
    -- Nota: duracion_minutos se calcula en consulta con TIMESTAMPDIFF(MINUTE, inicio_sesion, cierre_sesion)
    `resultado` ENUM('EXITOSO', 'FALLIDO') NOT NULL DEFAULT 'EXITOSO' COMMENT 'Resultado del intento de login',
    INDEX `idx_sesion_usuario` (`usuario_id`),
    INDEX `idx_sesion_inicio` (`inicio_sesion`),
    INDEX `idx_sesion_resultado` (`resultado`),
    CONSTRAINT `fk_sesion_usuario`
        FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. DATOS DE PRUEBA - DISPOSITIVOS SENSORES
-- ============================================================================
INSERT INTO `dispositivos_sensores` (`codigo`, `nombre`, `ubicacion`, `tipo`, `modelo`, `ip_local`, `estado`, `ultimo_ping`) VALUES
('SENSOR-PORT-01', 'Lector Portería Principal', 'Portería Principal - Acceso Calle', 'BIDIRECCIONAL', 'ESP32 + AS608', '192.168.1.101', 'ACTIVO', NOW() - INTERVAL 5 MINUTE),
('SENSOR-PORT-02', 'Lector Portería Trasera', 'Portería Trasera - Patio Central', 'BIDIRECCIONAL', 'ESP32 + R307', '192.168.1.102', 'ACTIVO', NOW() - INTERVAL 12 MINUTE),
('SENSOR-AULA-01', 'Lector Bloque Aulas', 'Entrada Bloque Académico - Bloque B', 'BIDIRECCIONAL', 'ESP32 + AS608', '192.168.1.103', 'ACTIVO', NOW() - INTERVAL 3 MINUTE),
('SENSOR-SALA-01', 'Lector Sala de Sistemas', 'Sala de Sistemas - Tercer Piso', 'BIDIRECCIONAL', 'ESP32 + AS608', '192.168.1.104', 'MANTENIMIENTO', NOW() - INTERVAL 60 MINUTE);

-- ============================================================================
-- 10. DATOS DE PRUEBA - BLOG ARTÍCULOS
-- ============================================================================
INSERT INTO `blog_articulos` (`titulo`, `resumen`, `contenido`, `icono_fa`, `color_icono`, `autor_id`, `publicado`) VALUES
('Seguridad Garantizada con Biometría',
 'Las plantillas de huella son procesadas mediante hashes seguros, imposibilitando la falsificación o suplantación de identidad estudiantil.',
 '<p>Las plantillas de huella son procesadas mediante hashes seguros (SHA-256 + salt), imposibilitando la falsificación o suplantación de identidad estudiantil. Cada registro biométrico es único e irrepetible, y el template nunca es almacenado como imagen sino como una representación matemática cifrada.</p><p>El sistema SFS Access cumple con los estándares ISO/IEC 19794-2 para plantillas de huella dactilar, garantizando la protección de los datos biométricos de estudiantes y docentes.</p>',
 'fa-solid fa-shield-halved', 'icon-cyan', 1, 1),

('Acceso Rápido y Fluido en Horas Pico',
 'Verificación en menos de 400 milisegundos que evita aglomeraciones en las porterías durante las horas pico de ingreso escolar.',
 '<p>El módulo de verificación biométrica del sistema SFS Access opera con tiempos de respuesta inferiores a 400 milisegundos por validación. Esto permite procesar un flujo continuo de hasta 800 estudiantes por hora sin generar colas en la portería.</p><p>Los sensores ESP32 conectados vía WiFi transmiten el resultado de cada lectura en tiempo real al servidor central, que actualiza el aforo y el panel de control de forma instantánea.</p>',
 'fa-solid fa-bolt', 'icon-blue', 2, 1),

('Control de Asistencia a Clases en Tiempo Real',
 'Monitoreo automático de asistencia por aula y grado en tiempo real, optimizando el seguimiento académico docente.',
 '<p>El módulo de asistencia a clases del sistema SFS Access registra automáticamente el ingreso de cada estudiante al plantel y genera el consolidado por aula. Esto permite a los profesores y directivas validar la permanencia en clases sin perder tiempo llamando a lista manualmente.</p><p>Los docentes y directivas pueden descargar un reporte CSV en cualquier momento con el desglose exacto de asistencia por grado y grupo.</p>',
 'fa-solid fa-chalkboard-user', 'icon-purple', 1, 1),

('Reportes Directivos y Auditoría CSV',
 'Generación de auditorías en tiempo real y descarga de reportes en Excel/CSV para rectoría y coordinaciones académicas.',
 '<p>El sistema SFS Access genera reportes detallados del movimiento diario de estudiantes y personal. Los administradores pueden filtrar por fecha, grado, rol o tipo de evento (entrada/salida) y exportar los datos directamente a formato CSV compatible con Microsoft Excel.</p><p>Rectoría y coordinación cuentan con acceso a métricas históricas para análisis de asistencia, identificación de estudiantes con patrones de llegada tardía y planificación de recursos institucionales.</p>',
 'fa-solid fa-chart-line', 'icon-red', 2, 1);

-- ============================================================================
-- 11. DATOS DE PRUEBA - SESIONES ADMIN
-- ============================================================================
INSERT INTO `sesiones_admin` (`usuario_id`, `ip_address`, `user_agent`, `inicio_sesion`, `cierre_sesion`, `resultado`) VALUES
(1, '192.168.1.50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0', NOW() - INTERVAL 4 HOUR, NOW() - INTERVAL 3 HOUR, 'EXITOSO'),
(2, '192.168.1.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/127.0', NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 1 HOUR, 'EXITOSO'),
(1, '192.168.1.50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0', NOW() - INTERVAL 45 MINUTE, NULL, 'EXITOSO'),
(4, '192.168.1.55', 'Mozilla/5.0 (Android 14; Mobile) Chrome/126.0', NOW() - INTERVAL 30 MINUTE, NULL, 'EXITOSO');

-- ============================================================================
-- PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
-- ARCHIVO: database/datos_prueba.sql
-- DESCRIPCIÓN: Script DML de datos de prueba completos para testing institucional
-- ============================================================================

USE `sfs_access_control`;

-- 1. LIMPIEZA PREVIA DE REGISTROS DE PRUEBA
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `registros_acceso`;
DELETE FROM `usuarios`;
ALTER TABLE `usuarios` AUTO_INCREMENT = 1;
ALTER TABLE `registros_acceso` AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 2. POBLADO DE USUARIOS (Directivos, Docentes, Personal y Estudiantes)
-- Password para usuarios administrativos: admin123 ($2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm)
-- ============================================================================
INSERT INTO `usuarios` (`id`, `documento`, `correo`, `nombre`, `grado`, `huella_template`, `password`, `rol`, `estado`) VALUES
-- Personal Directivo y Administrativo
(1, '10000001', 'rectoria@jorgerobledo.edu.co', 'Prof. Carlos Andrés Restrepo', 'RECTORÍA', 'FINGERPRINT_HASH_ADMIN_001', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ADMINISTRADOR', 'ACTIVO'),
(2, '10000002', 'sistemas@jorgerobledo.edu.co', 'Ing. Valeria Zapata', 'SISTEMAS', 'FINGERPRINT_HASH_ADMIN_002', '$2y$10$uowo3GisUraIhhOYoXfhoOM0qjB0mfTxrioUF0jjCn4zp0Zy.Dj9i', 'ADMINISTRADOR', 'ACTIVO'),
(3, '10000003', 'docente.arango@jorgerobledo.edu.co', 'Lic. Fernando Arango', 'DOCENTE', 'FINGERPRINT_HASH_DOC_001', NULL, 'DOCENTE', 'ACTIVO'),
(4, '10000004', 'coordinacion@jorgerobledo.edu.co', 'Lic. Martha Lucía Pérez', 'COORDINACIÓN', 'FINGERPRINT_HASH_COORD_001', NULL, 'ADMINISTRADOR', 'ACTIVO'),

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

-- ============================================================================
-- 3. POBLADO DE HISTORIAL DE ACCESOS (Simulación de Jornada Escolar Hoy)
-- ============================================================================

INSERT INTO `registros_acceso` (`usuario_id`, `tipo_evento`, `fecha_hora`, `estado_acceso`, `observaciones`) VALUES
-- Ingresos de personal directivo y docentes temprano en la mañana
(1, 'ENTRADA', NOW() - INTERVAL 240 MINUTE, 'APROBADO', 'Ingreso Directivo - Portería Principal'),
(3, 'ENTRADA', NOW() - INTERVAL 230 MINUTE, 'APROBADO', 'Ingreso Docente - Portería Principal'),
(4, 'ENTRADA', NOW() - INTERVAL 220 MINUTE, 'APROBADO', 'Ingreso Coordinación Académica - Portería Principal'),

-- Ingresos de Estudiantes (Grado 11°A)
(5, 'ENTRADA', NOW() - INTERVAL 210 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(6, 'ENTRADA', NOW() - INTERVAL 205 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),

-- Ingresos de Estudiantes (Grado 11°B)
(8, 'ENTRADA', NOW() - INTERVAL 200 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(9, 'ENTRADA', NOW() - INTERVAL 195 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),

-- Ingresos de Estudiantes (Grado 10°A)
(10, 'ENTRADA', NOW() - INTERVAL 190 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(11, 'ENTRADA', NOW() - INTERVAL 185 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),

-- Ingreso Estudiante (Grado 10°B)
(12, 'ENTRADA', NOW() - INTERVAL 180 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),

-- Ingresos de Estudiantes (Grado 9°A)
(14, 'ENTRADA', NOW() - INTERVAL 170 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),
(15, 'ENTRADA', NOW() - INTERVAL 165 MINUTE, 'APROBADO', 'Acceso biométrico concedido (ENTRADA)'),

-- Caso de Salida Temprana con permiso (Estudiante 9: Mateo Quintero 11°B se retiró a cita médica)
(9, 'SALIDA', NOW() - INTERVAL 90 MINUTE, 'APROBADO', 'Salida autorizada - Permiso médico'),

-- Casos de Auditoría de Seguridad / ACCESOS RECHAZADOS:
(7, 'ENTRADA', NOW() - INTERVAL 60 MINUTE, 'RECHAZADO', 'Acceso denegado: Usuario en estado INACTIVO'),
(13, 'ENTRADA', NOW() - INTERVAL 45 MINUTE, 'RECHAZADO', 'Acceso denegado: Usuario en estado SUSPENDIDO'),
(NULL, 'ENTRADA', NOW() - INTERVAL 15 MINUTE, 'RECHAZADO', 'Huella o documento no registrado en el sistema SFS');

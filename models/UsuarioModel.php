<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: models/UsuarioModel.php
 * DESCRIPCIÓN: Modelo para la gestión de usuarios, roles, autenticación, 
 *              estudiantes, contraseñas y huellas biométricas.
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';

class UsuarioModel {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->asegurarEstructura();
    }

    /**
     * Auto-migración y verificación de esquema en tiempo de ejecución.
     * Garantiza que las columnas de roles, contraseñas, tablas y cuentas demo existan.
     */
    private function asegurarEstructura(): void {
        try {
            // 1. Ampliar el ENUM de roles para soportar todos los roles
            $this->db->exec("ALTER TABLE usuarios MODIFY COLUMN rol ENUM('ADMINISTRADOR', 'DOCENTE', 'CELADOR', 'COORDINADOR', 'RECTOR', 'ESTUDIANTE', 'SENSOR') NOT NULL DEFAULT 'ESTUDIANTE'");

            // 2. Comprobar si existe la columna debe_cambiar_password
            $colCheck = $this->db->query("SHOW COLUMNS FROM usuarios LIKE 'debe_cambiar_password'")->fetch();
            if (!$colCheck) {
                $this->db->exec("ALTER TABLE usuarios ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 si debe cambiar contraseña en primer ingreso' AFTER rol");
            }

            // 3. Crear tabla de asistencias_clase si no existe
            $this->db->exec("CREATE TABLE IF NOT EXISTS `asistencias_clase` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `estudiante_id` INT NOT NULL,
                `docente_id` INT NOT NULL,
                `grado` VARCHAR(20) NOT NULL,
                `fecha` DATE NOT NULL,
                `estado_asistencia` ENUM('PRESENTE', 'FALTA_INJUSTIFICADA', 'FALTA_JUSTIFICADA', 'RETARDO') NOT NULL DEFAULT 'PRESENTE',
                `materia` VARCHAR(100) NULL DEFAULT 'GENERAL',
                `observaciones` VARCHAR(255) NULL DEFAULT NULL,
                `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_asist_estudiante` (`estudiante_id`),
                INDEX `idx_asist_docente` (`docente_id`),
                INDEX `idx_asist_fecha` (`fecha`),
                INDEX `idx_asist_grado` (`grado`),
                INDEX `idx_asist_estado` (`estado_asistencia`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 4. Sincronizar cuentas de demostración institucionales con contraseña 123456
            $passHash = password_hash('123456', PASSWORD_BCRYPT);

            // 4.1. Cuenta Administrador (admin@jorgerobledo.edu.co / sistemas@jorgerobledo.edu.co)
            $checkAdmin = $this->db->query("SELECT id FROM usuarios WHERE correo = 'admin@jorgerobledo.edu.co' OR documento = '10000001' LIMIT 1")->fetch();
            if ($checkAdmin) {
                $this->db->prepare("UPDATE usuarios SET correo = 'admin@jorgerobledo.edu.co', rol = 'ADMINISTRADOR', password = :pass, estado = 'ACTIVO' WHERE id = :id")
                    ->execute([':pass' => $passHash, ':id' => $checkAdmin['id']]);
            } else {
                $this->db->prepare("INSERT INTO usuarios (documento, matricula, correo, nombre, grado, password, rol, debe_cambiar_password, estado) VALUES ('10000001', 'DOC-ADMIN-01', 'admin@jorgerobledo.edu.co', 'Ing. Valeria Zapata (Administrador)', 'SISTEMAS', :pass, 'ADMINISTRADOR', 0, 'ACTIVO')")
                    ->execute([':pass' => $passHash]);
            }

            // 4.2. Cuenta Rector (rectoria@jorgerobledo.edu.co)
            $checkRector = $this->db->query("SELECT id FROM usuarios WHERE correo = 'rectoria@jorgerobledo.edu.co' OR documento = '10000002' LIMIT 1")->fetch();
            if ($checkRector) {
                $this->db->prepare("UPDATE usuarios SET correo = 'rectoria@jorgerobledo.edu.co', rol = 'RECTOR', password = :pass, estado = 'ACTIVO' WHERE id = :id")
                    ->execute([':pass' => $passHash, ':id' => $checkRector['id']]);
            } else {
                $this->db->prepare("INSERT INTO usuarios (documento, matricula, correo, nombre, grado, password, rol, debe_cambiar_password, estado) VALUES ('10000002', 'DOC-RECT-01', 'rectoria@jorgerobledo.edu.co', 'Prof. Carlos Andrés Restrepo (Rector)', 'RECTORÍA', :pass, 'RECTOR', 0, 'ACTIVO')")
                    ->execute([':pass' => $passHash]);
            }

            // 4.3. Cuenta Coordinador (coordinacion@jorgerobledo.edu.co)
            $checkCoord = $this->db->query("SELECT id FROM usuarios WHERE correo = 'coordinacion@jorgerobledo.edu.co' OR documento = '10000003' LIMIT 1")->fetch();
            if ($checkCoord) {
                $this->db->prepare("UPDATE usuarios SET correo = 'coordinacion@jorgerobledo.edu.co', rol = 'COORDINADOR', password = :pass, estado = 'ACTIVO' WHERE id = :id")
                    ->execute([':pass' => $passHash, ':id' => $checkCoord['id']]);
            } else {
                $this->db->prepare("INSERT INTO usuarios (documento, matricula, correo, nombre, grado, password, rol, debe_cambiar_password, estado) VALUES ('10000003', 'DOC-COORD-01', 'coordinacion@jorgerobledo.edu.co', 'Lic. Martha Lucía Pérez (Coordinadora)', 'COORDINACIÓN', :pass, 'COORDINADOR', 0, 'ACTIVO')")
                    ->execute([':pass' => $passHash]);
            }

            // 4.4. Cuenta Docente (docente.arango@jorgerobledo.edu.co)
            $checkDoc = $this->db->query("SELECT id FROM usuarios WHERE correo = 'docente.arango@jorgerobledo.edu.co' OR documento = '10000004' LIMIT 1")->fetch();
            if ($checkDoc) {
                $this->db->prepare("UPDATE usuarios SET correo = 'docente.arango@jorgerobledo.edu.co', rol = 'DOCENTE', password = :pass, estado = 'ACTIVO' WHERE id = :id")
                    ->execute([':pass' => $passHash, ':id' => $checkDoc['id']]);
            } else {
                $this->db->prepare("INSERT INTO usuarios (documento, matricula, correo, nombre, grado, password, rol, debe_cambiar_password, estado) VALUES ('10000004', 'DOC-PROF-01', 'docente.arango@jorgerobledo.edu.co', 'Lic. Fernando Arango (Profesor)', 'DOCENCIA', :pass, 'DOCENTE', 0, 'ACTIVO')")
                    ->execute([':pass' => $passHash]);
            }

            // 4.5. Cuenta Celador (porteria@jorgerobledo.edu.co)
            $checkCel = $this->db->query("SELECT id FROM usuarios WHERE correo = 'porteria@jorgerobledo.edu.co' OR documento = '10000005' LIMIT 1")->fetch();
            if ($checkCel) {
                $this->db->prepare("UPDATE usuarios SET correo = 'porteria@jorgerobledo.edu.co', rol = 'CELADOR', password = :pass, estado = 'ACTIVO' WHERE id = :id")
                    ->execute([':pass' => $passHash, ':id' => $checkCel['id']]);
            } else {
                $this->db->prepare("INSERT INTO usuarios (documento, matricula, correo, nombre, grado, password, rol, debe_cambiar_password, estado) VALUES ('10000005', 'PER-CEL-01', 'porteria@jorgerobledo.edu.co', 'Don Jaime Alberto Gómez (Celador)', 'PORTERÍA', :pass, 'CELADOR', 0, 'ACTIVO')")
                    ->execute([':pass' => $passHash]);
            }

        } catch (Exception $e) {
            error_log("Aviso en asegurarEstructura(): " . $e->getMessage());
        }
    }

    /**
     * Busca un usuario por su ID primario.
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT id, documento, matricula, correo, nombre, grado, rol, debe_cambiar_password, estado, creado_en 
                FROM usuarios 
                WHERE id = :id 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Busca un usuario por su correo electrónico (para el módulo de login).
     */
    public function obtenerPorCorreo(string $correo): ?array {
        $sql = "SELECT id, documento, matricula, correo, nombre, grado, huella_template, password, rol, debe_cambiar_password, estado, creado_en 
                FROM usuarios 
                WHERE correo = :correo 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':correo', strtolower(trim($correo)), PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Busca un usuario mediante su número de documento de identidad.
     */
    public function obtenerPorDocumento(string $documento): ?array {
        $sql = "SELECT id, documento, matricula, correo, nombre, grado, huella_template, password, rol, debe_cambiar_password, estado, creado_en 
                FROM usuarios 
                WHERE documento = :documento 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento', trim($documento), PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Busca un estudiante mediante su número de matrícula escolar.
     */
    public function obtenerPorMatricula(string $matricula): ?array {
        $sql = "SELECT id, documento, matricula, correo, nombre, grado, huella_template, password, rol, debe_cambiar_password, estado, creado_en 
                FROM usuarios 
                WHERE matricula = :matricula 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':matricula', trim($matricula), PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Busca un usuario coincidente con el hash o plantilla biométrica de huella.
     */
    public function obtenerPorHuella(string $huellaTemplate): ?array {
        $huellaTemplate = trim($huellaTemplate);

        // 1. Buscar en tabla de huellas múltiples
        $sql = "SELECT u.id, u.documento, u.matricula, u.correo, u.nombre, u.grado, u.rol, u.debe_cambiar_password, u.estado, u.creado_en,
                       h.dedo AS dedo_identificado, h.slot_numero AS slot_identificado, h.huella_template
                FROM huellas_dactilares h
                INNER JOIN usuarios u ON h.usuario_id = u.id
                WHERE h.huella_template = :huella
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':huella', $huellaTemplate, PDO::PARAM_STR);
        $stmt->execute();
        $resultado = $stmt->fetch();

        if ($resultado) {
            return $resultado;
        }

        // 2. Respaldo: Buscar en la columna huella_template de la tabla usuarios
        $sqlUsuarios = "SELECT id, documento, matricula, correo, nombre, grado, rol, debe_cambiar_password, estado, creado_en,
                               'Huella Principal' AS dedo_identificado, 1 AS slot_identificado, huella_template
                        FROM usuarios 
                        WHERE huella_template = :huella 
                        LIMIT 1";
        
        $stmtUsuarios = $this->db->prepare($sqlUsuarios);
        $stmtUsuarios->bindValue(':huella', $huellaTemplate, PDO::PARAM_STR);
        $stmtUsuarios->execute();
        
        $resultadoUsuarios = $stmtUsuarios->fetch();
        return $resultadoUsuarios ?: null;
    }

    /**
     * Obtiene todas las huellas dactilares registradas para un usuario (hasta 6 slots).
     */
    public function obtenerHuellasPorUsuario(int $usuarioId): array {
        $sql = "SELECT id, usuario_id, slot_numero, dedo, huella_template, creado_en, actualizado_en
                FROM huellas_dactilares
                WHERE usuario_id = :usuario_id
                ORDER BY slot_numero ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Registra o actualiza una huella dactilar en un slot específico (1 al 6).
     */
    public function registrarHuellaSlot(int $usuarioId, int $slotNumero, string $dedo, string $huellaTemplate): bool {
        if ($slotNumero < 1 || $slotNumero > 6) {
            return false;
        }

        $sql = "INSERT INTO huellas_dactilares (usuario_id, slot_numero, dedo, huella_template)
                VALUES (:usuario_id, :slot_numero, :dedo, :huella_template)
                ON DUPLICATE KEY UPDATE 
                    dedo = VALUES(dedo),
                    huella_template = VALUES(huella_template),
                    actualizado_en = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':slot_numero', $slotNumero, PDO::PARAM_INT);
        $stmt->bindValue(':dedo', trim($dedo), PDO::PARAM_STR);
        $stmt->bindValue(':huella_template', trim($huellaTemplate), PDO::PARAM_STR);

        $resultado = $stmt->execute();

        // Sincronizar también con la columna huella_template del usuario si es el slot 1 o 2
        if ($resultado && ($slotNumero === 1 || $slotNumero === 2)) {
            $this->actualizarHuella($usuarioId, $huellaTemplate);
        }

        return $resultado;
    }

    /**
     * Elimina una huella dactilar de un slot específico de un usuario.
     */
    public function eliminarHuellaSlot(int $usuarioId, int $slotNumero): bool {
        $sql = "DELETE FROM huellas_dactilares WHERE usuario_id = :usuario_id AND slot_numero = :slot_numero";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':slot_numero', $slotNumero, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Obtiene el listado de todos los estudiantes junto con el conteo de huellas registradas (0/6 a 6/6).
     */
    public function obtenerEstudiantesConConteoHuellas(): array {
        $sql = "SELECT u.id, u.documento, u.matricula, u.nombre, u.grado, u.rol, u.estado, u.creado_en,
                       COUNT(h.id) AS total_huellas
                FROM usuarios u
                LEFT JOIN huellas_dactilares h ON u.id = h.usuario_id
                WHERE u.rol = 'ESTUDIANTE'
                GROUP BY u.id, u.documento, u.matricula, u.nombre, u.grado, u.rol, u.estado, u.creado_en
                ORDER BY u.grado ASC, u.nombre ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los estudiantes de un grado específico.
     */
    public function obtenerEstudiantesPorGrado(string $grado): array {
        $sql = "SELECT id, documento, matricula, nombre, grado, estado
                FROM usuarios
                WHERE rol = 'ESTUDIANTE' AND grado = :grado
                ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':grado', trim($grado), PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el listado del personal institucional (ADMINISTRADOR, DOCENTE, CELADOR, COORDINADOR, RECTOR).
     */
    public function obtenerPersonal(): array {
        $sql = "SELECT u.id, u.documento, u.correo, u.nombre, u.grado, u.rol, u.debe_cambiar_password, u.estado, u.creado_en
                FROM usuarios u
                WHERE u.rol IN ('ADMINISTRADOR', 'DOCENTE', 'CELADOR', 'COORDINADOR', 'RECTOR')
                ORDER BY FIELD(u.rol, 'ADMINISTRADOR', 'RECTOR', 'COORDINADOR', 'DOCENTE', 'CELADOR'), u.nombre ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el listado completo de todos los usuarios registrados.
     */
    public function obtenerTodos(): array {
        $sql = "SELECT u.id, u.documento, u.matricula, u.correo, u.nombre, u.grado, u.rol, u.debe_cambiar_password, u.estado, u.creado_en,
                       COUNT(h.id) AS total_huellas
                FROM usuarios u
                LEFT JOIN huellas_dactilares h ON u.id = h.usuario_id
                GROUP BY u.id, u.documento, u.matricula, u.correo, u.nombre, u.grado, u.rol, u.debe_cambiar_password, u.estado, u.creado_en
                ORDER BY u.nombre ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Valida si un usuario tiene autorización de acceso activo.
     */
    public function validarPermisoAcceso(int $usuarioId): bool {
        $sql = "SELECT estado FROM usuarios WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        
        $usuario = $stmt->fetch();
        return ($usuario && $usuario['estado'] === 'ACTIVO');
    }

    /**
     * Asocia o actualiza la plantilla de huella dactilar de un usuario.
     */
    public function actualizarHuella(int $usuarioId, string $huellaTemplate): bool {
        $sql = "UPDATE usuarios 
                SET huella_template = :huella 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':huella', trim($huellaTemplate), PDO::PARAM_STR);
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Registra un nuevo miembro del personal (Docente, Celador, Coordinador, Rector, Administrador)
     * Contraseña por defecto: 123456 y debe_cambiar_password = 1.
     */
    public function crearUsuarioPersonal(array $datos): int {
        $passwordPlana = !empty($datos['password']) ? trim($datos['password']) : '123456';
        $passwordHash = password_hash($passwordPlana, PASSWORD_BCRYPT);
        $correo = !empty($datos['correo']) ? strtolower(trim($datos['correo'])) : null;
        $debeCambiar = isset($datos['debe_cambiar_password']) ? (int)$datos['debe_cambiar_password'] : 1;

        $sql = "INSERT INTO usuarios (documento, correo, nombre, grado, password, rol, debe_cambiar_password, estado) 
                VALUES (:documento, :correo, :nombre, :grado, :password, :rol, :debe_cambiar_password, :estado)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento', trim($datos['documento']), PDO::PARAM_STR);
        $stmt->bindValue(':correo', $correo, $correo ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':nombre', trim($datos['nombre']), PDO::PARAM_STR);
        $stmt->bindValue(':grado', $datos['cargo'] ?? $datos['grado'] ?? 'PERSONAL', PDO::PARAM_STR);
        $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':rol', $datos['rol'] ?? 'DOCENTE', PDO::PARAM_STR);
        $stmt->bindValue(':debe_cambiar_password', $debeCambiar, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $datos['estado'] ?? 'ACTIVO', PDO::PARAM_STR);

        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza los datos de un usuario del personal.
     */
    public function actualizarUsuarioPersonal(int $id, array $datos): bool {
        $correo = !empty($datos['correo']) ? strtolower(trim($datos['correo'])) : null;

        $sql = "UPDATE usuarios 
                SET documento = :documento,
                    correo = :correo,
                    nombre = :nombre,
                    grado = :grado,
                    rol = :rol,
                    estado = :estado
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento', trim($datos['documento']), PDO::PARAM_STR);
        $stmt->bindValue(':correo', $correo, $correo ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':nombre', trim($datos['nombre']), PDO::PARAM_STR);
        $stmt->bindValue(':grado', $datos['cargo'] ?? $datos['grado'] ?? 'PERSONAL', PDO::PARAM_STR);
        $stmt->bindValue(':rol', $datos['rol'] ?? 'DOCENTE', PDO::PARAM_STR);
        $stmt->bindValue(':estado', $datos['estado'] ?? 'ACTIVO', PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un usuario del sistema.
     */
    public function eliminarUsuario(int $id): bool {
        $sql = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Reinicia la contraseña de un usuario a la contraseña por defecto ('123456')
     * y marca debe_cambiar_password = 1 para forzar el cambio en su próximo ingreso.
     */
    public function reiniciarPasswordDefecto(int $id, string $passwordDefecto = '123456'): bool {
        $passwordHash = password_hash($passwordDefecto, PASSWORD_BCRYPT);
        $sql = "UPDATE usuarios 
                SET password = :password,
                    debe_cambiar_password = 1
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Cambia la contraseña voluntaria o forzosamente por parte del usuario.
     * Al cambiarla, se desactiva el flag debe_cambiar_password = 0.
     */
    public function cambiarPassword(int $id, string $nuevaPassword): bool {
        $passwordHash = password_hash(trim($nuevaPassword), PASSWORD_BCRYPT);
        $sql = "UPDATE usuarios 
                SET password = :password,
                    debe_cambiar_password = 0
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Crea un nuevo estudiante en el sistema.
     */
    public function crearEstudiante(array $datos): int {
        $sql = "INSERT INTO usuarios (documento, matricula, nombre, grado, rol, estado) 
                VALUES (:documento, :matricula, :nombre, :grado, 'ESTUDIANTE', :estado)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento', trim($datos['documento']), PDO::PARAM_STR);
        $stmt->bindValue(':matricula', !empty($datos['matricula']) ? trim($datos['matricula']) : trim($datos['documento']), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', strtoupper(trim($datos['nombre'])), PDO::PARAM_STR);
        $stmt->bindValue(':grado', trim($datos['grado']), PDO::PARAM_STR);
        $stmt->bindValue(':estado', $datos['estado'] ?? 'ACTIVO', PDO::PARAM_STR);

        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza los datos de un estudiante.
     */
    public function actualizarEstudiante(int $id, array $datos): bool {
        $sql = "UPDATE usuarios 
                SET documento = :documento,
                    matricula = :matricula,
                    nombre = :nombre,
                    grado = :grado,
                    estado = :estado
                WHERE id = :id AND rol = 'ESTUDIANTE'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':documento', trim($datos['documento']), PDO::PARAM_STR);
        $stmt->bindValue(':matricula', !empty($datos['matricula']) ? trim($datos['matricula']) : trim($datos['documento']), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', strtoupper(trim($datos['nombre'])), PDO::PARAM_STR);
        $stmt->bindValue(':grado', trim($datos['grado']), PDO::PARAM_STR);
        $stmt->bindValue(':estado', $datos['estado'] ?? 'ACTIVO', PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}

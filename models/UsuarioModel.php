<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: models/UsuarioModel.php
 * DESCRIPCIÓN: Modelo para la gestión de usuarios, roles, autenticación y huellas.
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';

class UsuarioModel {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Busca un usuario por su ID primario.
     *
     * @param int $id ID del usuario en base de datos.
     * @return array|null Datos del usuario o null si no se encuentra.
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT id, documento, matricula, correo, nombre, grado, rol, estado, creado_en 
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
     *
     * @param string $correo Correo electrónico.
     * @return array|null Datos del usuario o null.
     */
    public function obtenerPorCorreo(string $correo): ?array {
        $sql = "SELECT id, documento, matricula, correo, nombre, grado, huella_template, password, rol, estado, creado_en 
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
     *
     * @param string $documento Número de documento.
     * @return array|null Datos del usuario o null.
     */
    public function obtenerPorDocumento(string $documento): ?array {
        $sql = "SELECT id, documento, matricula, correo, nombre, grado, huella_template, password, rol, estado, creado_en 
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
     *
     * @param string $matricula Código o número de matrícula.
     * @return array|null Datos del usuario o null.
     */
    public function obtenerPorMatricula(string $matricula): ?array {
        $sql = "SELECT id, documento, matricula, correo, nombre, grado, huella_template, password, rol, estado, creado_en 
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
     * Busca primero en la tabla multi-huella (huellas_dactilares) y como respaldo en usuarios.
     *
     * @param string $huellaTemplate Hash/template de la huella enviado por el sensor.
     * @return array|null Datos del usuario o null si no existe.
     */
    public function obtenerPorHuella(string $huellaTemplate): ?array {
        $huellaTemplate = trim($huellaTemplate);

        // 1. Buscar en tabla de huellas múltiples
        $sql = "SELECT u.id, u.documento, u.matricula, u.correo, u.nombre, u.grado, u.rol, u.estado, u.creado_en,
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
        $sqlUsuarios = "SELECT id, documento, matricula, correo, nombre, grado, rol, estado, creado_en,
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
     *
     * @param int $usuarioId ID del usuario.
     * @return array Lista de huellas ordenadas por slot.
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
     *
     * @param int $usuarioId ID del estudiante/usuario.
     * @param int $slotNumero Número del slot (1 al 6).
     * @param string $dedo Nombre del dedo.
     * @param string $huellaTemplate Template o hash biométrico.
     * @return bool True en caso de éxito.
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
     *
     * @param int $usuarioId ID del usuario.
     * @param int $slotNumero Número de slot a eliminar.
     * @return bool True si se eliminó.
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
     *
     * @return array
     */
    public function obtenerEstudiantesConConteoHuellas(): array {
        $sql = "SELECT u.id, u.documento, u.matricula, u.nombre, u.grado, u.rol, u.estado,
                       COUNT(h.id) AS total_huellas
                FROM usuarios u
                LEFT JOIN huellas_dactilares h ON u.id = h.usuario_id
                WHERE u.rol = 'ESTUDIANTE'
                GROUP BY u.id, u.documento, u.matricula, u.nombre, u.grado, u.rol, u.estado
                ORDER BY u.grado ASC, u.nombre ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el listado completo de todos los usuarios registrados.
     *
     * @return array Lista de usuarios.
     */
    public function obtenerTodos(): array {
        $sql = "SELECT u.id, u.documento, u.matricula, u.correo, u.nombre, u.grado, u.rol, u.estado, u.creado_en,
                       COUNT(h.id) AS total_huellas
                FROM usuarios u
                LEFT JOIN huellas_dactilares h ON u.id = h.usuario_id
                GROUP BY u.id, u.documento, u.matricula, u.correo, u.nombre, u.grado, u.rol, u.estado, u.creado_en
                ORDER BY u.nombre ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Valida si un usuario tiene autorización de acceso activo.
     *
     * @param int $usuarioId ID del usuario a validar.
     * @return bool True si el usuario existe y su estado es ACTIVO, false en caso contrario.
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
     *
     * @param int $usuarioId ID del usuario.
     * @param string $huellaTemplate Cadena con el template o hash biométrico.
     * @return bool True si se actualizó correctamente.
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
     * Registra un nuevo usuario en la base de datos (con correo y contraseña cifrada).
     *
     * @param array $datos Datos del usuario (documento, correo, nombre, grado, rol, huella, password).
     * @return int ID del usuario insertado.
     */
    public function crearUsuario(array $datos): int {
        $sql = "INSERT INTO usuarios (documento, correo, nombre, grado, huella_template, password, rol, estado) 
                VALUES (:documento, :correo, :nombre, :grado, :huella, :password, :rol, :estado)";
        
        $stmt = $this->db->prepare($sql);
        
        $passwordHash = !empty($datos['password']) 
            ? password_hash($datos['password'], PASSWORD_BCRYPT) 
            : null;

        $correo = !empty($datos['correo']) ? strtolower(trim($datos['correo'])) : null;

        $stmt->bindValue(':documento', trim($datos['documento']), PDO::PARAM_STR);
        $stmt->bindValue(':correo', $correo, $correo ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':nombre', trim($datos['nombre']), PDO::PARAM_STR);
        $stmt->bindValue(':grado', $datos['grado'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':huella', $datos['huella'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':rol', $datos['rol'] ?? 'ESTUDIANTE', PDO::PARAM_STR);
        $stmt->bindValue(':estado', $datos['estado'] ?? 'ACTIVO', PDO::PARAM_STR);

        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }
}

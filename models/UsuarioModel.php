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
        $sql = "SELECT id, documento, correo, nombre, grado, rol, estado, creado_en 
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
        $sql = "SELECT id, documento, correo, nombre, grado, huella_template, password, rol, estado, creado_en 
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
        $sql = "SELECT id, documento, correo, nombre, grado, huella_template, password, rol, estado, creado_en 
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
     * Busca un usuario coincidente con el hash o plantilla biométrica de huella.
     *
     * @param string $huellaTemplate Hash/template de la huella enviado por el sensor.
     * @return array|null Datos del usuario o null si no existe.
     */
    public function obtenerPorHuella(string $huellaTemplate): ?array {
        $sql = "SELECT id, documento, correo, nombre, grado, rol, estado, creado_en 
                FROM usuarios 
                WHERE huella_template = :huella 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':huella', trim($huellaTemplate), PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Obtiene el listado completo de todos los usuarios registrados.
     *
     * @return array Lista de usuarios.
     */
    public function obtenerTodos(): array {
        $sql = "SELECT id, documento, correo, nombre, grado, rol, estado, creado_en 
                FROM usuarios 
                ORDER BY nombre ASC";
        
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

<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: models/AccesoModel.php
 * DESCRIPCIÓN: Modelo para el registro, consulta y métricas de accesos biométricos.
 * ============================================================================
 */

require_once __DIR__ . '/../config/database.php';

class AccesoModel {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Inserta un nuevo registro de acceso (entrada/salida) en la base de datos.
     *
     * @param int|null $usuarioId ID del usuario (o null si no fue reconocido).
     * @param string $tipoEvento 'ENTRADA' o 'SALIDA'.
     * @param string $estadoAcceso 'APROBADO' o 'RECHAZADO'.
     * @param string|null $observaciones Detalle o motivo del resultado.
     * @return int ID del registro insertado.
     */
    public function registrarAcceso(?int $usuarioId, string $tipoEvento, string $estadoAcceso, ?string $observaciones = null): int {
        $sql = "INSERT INTO registros_acceso (usuario_id, tipo_evento, estado_acceso, observaciones) 
                VALUES (:usuario_id, :tipo_evento, :estado_acceso, :observaciones)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':tipo_evento', strtoupper($tipoEvento), PDO::PARAM_STR);
        $stmt->bindValue(':estado_acceso', strtoupper($estadoAcceso), PDO::PARAM_STR);
        $stmt->bindValue(':observaciones', $observaciones, PDO::PARAM_STR);
        
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    /**
     * Obtiene el último registro de acceso exitoso de un usuario hoy.
     * Permite alternar automáticamente entre ENTRADA y SALIDA.
     *
     * @param int $usuarioId ID del usuario.
     * @return array|null Último registro de acceso o null.
     */
    public function obtenerUltimoEventoUsuario(int $usuarioId): ?array {
        $sql = "SELECT id, usuario_id, tipo_evento, fecha_hora, estado_acceso 
                FROM registros_acceso 
                WHERE usuario_id = :usuario_id 
                  AND estado_acceso = 'APROBADO' 
                  AND DATE(fecha_hora) = CURDATE()
                ORDER BY fecha_hora DESC, id DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Obtiene los accesos más recientes con información del usuario.
     *
     * @param int $limite Cantidad máxima de registros a retornar.
     * @return array Lista de registros de acceso con datos de usuario.
     */
    public function obtenerAccesosRecientes(int $limite = 20): array {
        $sql = "SELECT 
                    r.id,
                    r.usuario_id,
                    r.tipo_evento,
                    r.fecha_hora,
                    r.estado_acceso,
                    r.observaciones,
                    COALESCE(u.nombre, 'Persona Desconocida / No Registrada') AS nombre_usuario,
                    COALESCE(u.documento, '---') AS documento_usuario,
                    COALESCE(u.grado, 'N/A') AS grado_usuario,
                    COALESCE(u.rol, 'DESCONOCIDO') AS rol_usuario
                FROM registros_acceso r
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                ORDER BY r.fecha_hora DESC, r.id DESC
                LIMIT :limite";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Consulta el historial de accesos para una fecha específica (YYYY-MM-DD).
     *
     * @param string $fecha Fecha en formato 'YYYY-MM-DD'.
     * @return array Registros de esa jornada.
     */
    public function obtenerAccesosPorFecha(string $fecha): array {
        $sql = "SELECT 
                    r.id,
                    r.usuario_id,
                    r.tipo_evento,
                    r.fecha_hora,
                    r.estado_acceso,
                    r.observaciones,
                    COALESCE(u.nombre, 'No Registrado') AS nombre_usuario,
                    COALESCE(u.documento, '---') AS documento_usuario,
                    COALESCE(u.grado, 'N/A') AS grado_usuario,
                    u.rol AS rol_usuario
                FROM registros_acceso r
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                WHERE DATE(r.fecha_hora) = :fecha
                ORDER BY r.fecha_hora DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Calcula el número total de estudiantes presentes actualmente dentro del colegio.
     * Un estudiante está dentro si su último evento aprobado en el día de hoy fue una 'ENTRADA'.
     * 
     * @return int Cantidad de estudiantes dentro de la institución.
     */
    public function contarEstudiantesDentro(): int {
        $sql = "SELECT COUNT(*) as total_dentro
                FROM (
                    SELECT r.usuario_id, r.tipo_evento
                    FROM registros_acceso r
                    INNER JOIN usuarios u ON r.usuario_id = u.id
                    INNER JOIN (
                        -- Obtener el ID del último registro aprobado de cada usuario hoy
                        SELECT usuario_id, MAX(id) as max_id
                        FROM registros_acceso
                        WHERE DATE(fecha_hora) = CURDATE()
                          AND estado_acceso = 'APROBADO'
                        GROUP BY usuario_id
                    ) ultimos ON r.id = ultimos.max_id
                    WHERE u.rol = 'ESTUDIANTE'
                      AND r.tipo_evento = 'ENTRADA'
                ) as activos";
        
        $stmt = $this->db->query($sql);
        $resultado = $stmt->fetch();
        return (int) ($resultado['total_dentro'] ?? 0);
    }

    /**
     * Obtiene el listado detallado de estudiantes actualmente presentes dentro de la institución.
     * Muy útil para la logística y control de raciones en el restaurante escolar.
     *
     * @return array Lista de estudiantes en el plantel.
     */
    public function obtenerEstudiantesDentroDetalle(): array {
        $sql = "SELECT 
                    u.id AS usuario_id,
                    u.documento,
                    u.nombre,
                    u.grado,
                    r.fecha_hora AS hora_ingreso
                FROM registros_acceso r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN (
                    SELECT usuario_id, MAX(id) as max_id
                    FROM registros_acceso
                    WHERE DATE(fecha_hora) = CURDATE()
                      AND estado_acceso = 'APROBADO'
                    GROUP BY usuario_id
                ) ultimos ON r.id = ultimos.max_id
                WHERE u.rol = 'ESTUDIANTE'
                  AND r.tipo_evento = 'ENTRADA'
                ORDER BY u.grado ASC, u.nombre ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Agrupa los estudiantes actualmente dentro por cada grado escolar.
     * Permite al restaurante escolar planificar porciones por aula.
     *
     * @return array Conteo agrupado por grado (ej: ['10°A' => 24, '11°B' => 19]).
     */
    public function obtenerEstudiantesDentroPorGrado(): array {
        $sql = "SELECT 
                    COALESCE(u.grado, 'Sin Grado') as grado,
                    COUNT(*) as total
                FROM registros_acceso r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN (
                    SELECT usuario_id, MAX(id) as max_id
                    FROM registros_acceso
                    WHERE DATE(fecha_hora) = CURDATE()
                      AND estado_acceso = 'APROBADO'
                    GROUP BY usuario_id
                ) ultimos ON r.id = ultimos.max_id
                WHERE u.rol = 'ESTUDIANTE'
                  AND r.tipo_evento = 'ENTRADA'
                GROUP BY u.grado
                ORDER BY u.grado ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene métricas estadísticas globales del día de hoy.
     *
     * @return array Estadísticas con totales de aprobados, rechazados, entradas y salidas.
     */
    public function obtenerEstadisticasHoy(): array {
        $sql = "SELECT 
                    COUNT(*) as total_eventos,
                    SUM(CASE WHEN estado_acceso = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                    SUM(CASE WHEN estado_acceso = 'RECHAZADO' THEN 1 ELSE 0 END) as rechazados,
                    SUM(CASE WHEN tipo_evento = 'ENTRADA' AND estado_acceso = 'APROBADO' THEN 1 ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN tipo_evento = 'SALIDA' AND estado_acceso = 'APROBADO' THEN 1 ELSE 0 END) as total_salidas
                FROM registros_acceso
                WHERE DATE(fecha_hora) = CURDATE()";
        
        $stmt = $this->db->query($sql);
        $stats = $stmt->fetch();
        
        return [
            'total_eventos' => (int) ($stats['total_eventos'] ?? 0),
            'aprobados' => (int) ($stats['aprobados'] ?? 0),
            'rechazados' => (int) ($stats['rechazados'] ?? 0),
            'total_entradas' => (int) ($stats['total_entradas'] ?? 0),
            'total_salidas' => (int) ($stats['total_salidas'] ?? 0),
            'estudiantes_dentro' => $this->contarEstudiantesDentro()
        ];
    }
}

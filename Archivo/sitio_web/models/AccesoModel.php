<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: models/AccesoModel.php
 * DESCRIPCIÓN: Modelo para el registro, consulta y métricas de accesos biométricos,
 *              auditoría de ausentismo y toma de asistencia a clases.
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
     */
    public function contarEstudiantesDentro(): int {
        $sql = "SELECT COUNT(*) as total_dentro
                FROM (
                    SELECT r.usuario_id, r.tipo_evento
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
                ) as activos";
        
        $stmt = $this->db->query($sql);
        $resultado = $stmt->fetch();
        return (int) ($resultado['total_dentro'] ?? 0);
    }

    /**
     * Agrupa los estudiantes actualmente dentro por cada grado escolar.
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

    /**
     * Guarda la toma de asistencia en el aula realizada por un Docente.
     * $asistencias es un array de [ estudiante_id => 'PRESENTE'|'FALTA_INJUSTIFICADA'|'RETARDO'|'FALTA_JUSTIFICADA' ]
     */
    public function guardarTomaAsistencia(int $docenteId, string $grado, string $fecha, string $materia, array $asistencias, ?string $observacionGeneral = null): bool {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO asistencias_clase (estudiante_id, docente_id, grado, fecha, estado_asistencia, materia, observaciones)
                    VALUES (:estudiante_id, :docente_id, :grado, :fecha, :estado, :materia, :observaciones)
                    ON DUPLICATE KEY UPDATE
                        docente_id = VALUES(docente_id),
                        estado_asistencia = VALUES(estado_asistencia),
                        observaciones = VALUES(observaciones),
                        actualizado_en = CURRENT_TIMESTAMP";

            $stmt = $this->db->prepare($sql);

            foreach ($asistencias as $estId => $estado) {
                $stmt->bindValue(':estudiante_id', (int)$estId, PDO::PARAM_INT);
                $stmt->bindValue(':docente_id', $docenteId, PDO::PARAM_INT);
                $stmt->bindValue(':grado', $grado, PDO::PARAM_STR);
                $stmt->bindValue(':fecha', $fecha, PDO::PARAM_STR);
                $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
                $stmt->bindValue(':materia', $materia ?: 'GENERAL', PDO::PARAM_STR);
                $stmt->bindValue(':observaciones', $observacionGeneral, $observacionGeneral ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en guardarTomaAsistencia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el listado de asistencia de clase por grado, fecha y materia.
     */
    public function obtenerAsistenciaClase(string $grado, string $fecha, string $materia = 'GENERAL'): array {
        $sql = "SELECT u.id AS estudiante_id, u.documento, u.matricula, u.nombre, u.grado,
                       COALESCE(a.id, NULL) AS asistencia_id,
                       COALESCE(a.estado_asistencia, 'SIN_REGISTRO') AS estado_asistencia,
                       a.observaciones,
                       d.nombre AS nombre_docente,
                       a.actualizado_en
                FROM usuarios u
                LEFT JOIN asistencias_clase a 
                       ON u.id = a.estudiante_id 
                      AND a.fecha = :fecha 
                      AND a.materia = :materia
                LEFT JOIN usuarios d ON a.docente_id = d.id
                WHERE u.rol = 'ESTUDIANTE' AND u.grado = :grado
                ORDER BY u.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':grado', trim($grado), PDO::PARAM_STR);
        $stmt->bindValue(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindValue(':materia', $materia, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene todas las inasistencias / faltas del día para supervisión del Coordinador y Rector.
     */
    public function obtenerFaltasSupervision(?string $grado = null, ?string $fecha = null): array {
        $fecha = $fecha ?: date('Y-m-d');
        $whereGrado = $grado ? " AND u.grado = :grado " : "";

        $sql = "SELECT a.id, a.estudiante_id, a.docente_id, a.grado, a.fecha, a.estado_asistencia, a.materia, a.observaciones,
                       u.nombre AS nombre_estudiante, u.documento AS documento_estudiante, u.matricula,
                       d.nombre AS nombre_docente
                FROM asistencias_clase a
                INNER JOIN usuarios u ON a.estudiante_id = u.id
                LEFT JOIN usuarios d ON a.docente_id = d.id
                WHERE a.fecha = :fecha 
                  AND a.estado_asistencia IN ('FALTA_INJUSTIFICADA', 'FALTA_JUSTIFICADA', 'RETARDO')
                  {$whereGrado}
                ORDER BY a.grado ASC, u.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':fecha', $fecha, PDO::PARAM_STR);
        if ($grado) {
            $stmt->bindValue(':grado', $grado, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Justifica una falta de estudiante (Función de Coordinación).
     */
    public function justificarFalta(int $asistenciaId, string $motivoJustificacion): bool {
        $sql = "UPDATE asistencias_clase 
                SET estado_asistencia = 'FALTA_JUSTIFICADA',
                    observaciones = CONCAT(COALESCE(observaciones, ''), ' [JUSTIFICADA: ', :motivo, ']')
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':motivo', trim($motivoJustificacion), PDO::PARAM_STR);
        $stmt->bindValue(':id', $asistenciaId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Resumen consolidado para Rectoría y Coordinación.
     */
    public function obtenerResumenConsolidado(?string $fecha = null): array {
        $fecha = $fecha ?: date('Y-m-d');

        // Total estudiantes registrados
        $totalEstudiantes = (int)$this->db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'ESTUDIANTE' AND estado = 'ACTIVO'")->fetchColumn();

        // Conteo de asistencias en aula hoy
        $sqlAula = "SELECT 
                        SUM(CASE WHEN estado_asistencia = 'PRESENTE' THEN 1 ELSE 0 END) AS presentes_aula,
                        SUM(CASE WHEN estado_asistencia = 'FALTA_INJUSTIFICADA' THEN 1 ELSE 0 END) AS faltas_injustificadas,
                        SUM(CASE WHEN estado_asistencia = 'FALTA_JUSTIFICADA' THEN 1 ELSE 0 END) AS faltas_justificadas,
                        SUM(CASE WHEN estado_asistencia = 'RETARDO' THEN 1 ELSE 0 END) AS retardos
                    FROM asistencias_clase 
                    WHERE fecha = :fecha";
        $stmtAula = $this->db->prepare($sqlAula);
        $stmtAula->bindValue(':fecha', $fecha, PDO::PARAM_STR);
        $stmtAula->execute();
        $aula = $stmtAula->fetch();

        // Estadísticas de portería
        $statsPorteria = $this->obtenerEstadisticasHoy();

        return [
            'total_matriculados' => $totalEstudiantes,
            'estudiantes_en_plantel' => $statsPorteria['estudiantes_dentro'],
            'ingresos_porteria' => $statsPorteria['total_entradas'],
            'salidas_porteria' => $statsPorteria['total_salidas'],
            'accesos_rechazados' => $statsPorteria['rechazados'],
            'presentes_aula' => (int)($aula['presentes_aula'] ?? 0),
            'faltas_injustificadas' => (int)($aula['faltas_injustificadas'] ?? 0),
            'faltas_justificadas' => (int)($aula['faltas_justificadas'] ?? 0),
            'retardos' => (int)($aula['retardos'] ?? 0),
        ];
    }

    /**
     * Obtiene el listado de dispositivos y sensores biométricos instalados.
     */
    public function obtenerSensores(): array {
        try {
            $sql = "SELECT id, codigo, nombre, ubicacion, tipo, modelo, ip_local, estado, ultimo_ping 
                    FROM dispositivos_sensores 
                    ORDER BY id ASC";
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Reinicia los registros de asistencia (aula y/o portería).
     * @param string $tipo 'aula', 'acceso', 'todo'
     * @param string $alcance 'hoy', 'todo'
     * @return array
     */
    public function reiniciarRegistrosAsistencia(string $tipo = 'todo', string $alcance = 'todo'): array {
        try {
            $borradosAula = 0;
            $borradosAcceso = 0;
            $whereFechaAula = ($alcance === 'hoy') ? " WHERE fecha = CURDATE()" : "";
            $whereFechaAcceso = ($alcance === 'hoy') ? " WHERE DATE(fecha_hora) = CURDATE()" : "";

            if ($tipo === 'aula' || $tipo === 'todo') {
                $stmt = $this->db->prepare("DELETE FROM asistencias_clase" . $whereFechaAula);
                $stmt->execute();
                $borradosAula = $stmt->rowCount();
                if ($alcance === 'todo') {
                    try { $this->db->exec("ALTER TABLE asistencias_clase AUTO_INCREMENT = 1"); } catch (Exception $e) {}
                }
            }

            if ($tipo === 'acceso' || $tipo === 'todo') {
                $stmt = $this->db->prepare("DELETE FROM registros_acceso" . $whereFechaAcceso);
                $stmt->execute();
                $borradosAcceso = $stmt->rowCount();
                if ($alcance === 'todo') {
                    try { $this->db->exec("ALTER TABLE registros_acceso AUTO_INCREMENT = 1"); } catch (Exception $e) {}
                }
            }

            return [
                'exito' => true,
                'borrados_aula' => $borradosAula,
                'borrados_acceso' => $borradosAcceso
            ];
        } catch (Exception $e) {
            error_log("Error al reiniciar registros de asistencia: " . $e->getMessage());
            return [
                'exito' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
}

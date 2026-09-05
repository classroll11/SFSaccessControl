<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: controllers/ReporteController.php
 * DESCRIPCIÓN: Controlador para la generación de reportes y métricas directivas,
 *              con foco en el control y consolidación de asistencia a clases.
 * ============================================================================
 */

require_once __DIR__ . '/../models/AccesoModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class ReporteController {
    private AccesoModel $accesoModel;
    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->accesoModel = new AccesoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Muestra la vista o datos consolidados de asistencia escolar.
     */
    public function asistencia(): void {
        $totalEstudiantes = $this->accesoModel->contarEstudiantesDentro();
        $estudiantesPorGrado = $this->accesoModel->obtenerEstudiantesDentroPorGrado();
        $listadoEstudiantes = $this->accesoModel->obtenerEstudiantesDentroDetalle();
        $fechaHoy = date('d/m/Y');

        // Si la petición solicita formato JSON
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'institucion' => 'I.E. Jorge Robledo',
                'modulo' => 'Control de Asistencia a Clases',
                'fecha' => date('Y-m-d'),
                'total_estudiantes_dentro' => $totalEstudiantes,
                'desglose_por_grado' => $estudiantesPorGrado,
                'estudiantes' => $listadoEstudiantes
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // Si no, renderiza vista o modal
        require_once __DIR__ . '/../views/dashboard.php';
    }

    /**
     * Alias de retrocompatibilidad
     */
    public function restaurante(): void {
        $this->asistencia();
    }

    /**
     * Exporta el listado de estudiantes presentes en formato CSV descargable.
     * Ideal para directivas, docentes y control de asistencia a clases.
     */
    public function exportarCsv(): void {
        $listado = $this->accesoModel->obtenerEstudiantesDentroDetalle();
        $fecha = date('Y-m-d_His');

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=reporte_asistencia_clases_{$fecha}.csv");

        $salida = fopen('php://output', 'w');
        // Agregar BOM para compatibilidad con caracteres especiales (tildes, eñes) en Excel
        fprintf($salida, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados del archivo CSV
        fputcsv($salida, ['ID', 'Documento', 'Nombre Estudiante', 'Grado', 'Hora de Ingreso']);

        foreach ($listado as $estudiante) {
            fputcsv($salida, [
                $estudiante['usuario_id'],
                $estudiante['documento'],
                $estudiante['nombre'],
                $estudiante['grado'],
                $estudiante['hora_ingreso']
            ]);
        }

        fclose($salida);
        exit;
    }
}

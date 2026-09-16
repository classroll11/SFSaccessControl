<?php
/**
 * controllers/AdminController.php
 * Controlador para la administración institucional: Usuarios, Estudiantes y Sensores
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/AccesoModel.php';

class AdminController {
    private UsuarioModel $usuarioModel;
    private AccesoModel $accesoModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
        $this->accesoModel = new AccesoModel();
    }

    public function usuarios(): void {
        requireLogin();

        if (!esAdmin()) {
            header('Location: dashboard.php?acceso=denegado');
            exit;
        }

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $personalUsuarios = $this->usuarioModel->obtenerPersonal();

        require __DIR__ . '/../views/admin/usuarios.php';
    }

    public function estudiantes(): void {
        requireLogin();

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $estudiantes = $this->usuarioModel->obtenerEstudiantesConConteoHuellas();

        require __DIR__ . '/../views/admin/estudiantes.php';
    }

    public function sensores(): void {
        requireLogin();

        if (!esAdmin()) {
            header('Location: dashboard.php?acceso=denegado');
            exit;
        }

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $sensores = $this->accesoModel->obtenerSensores();

        require __DIR__ . '/../views/admin/sensores.php';
    }

    public function reiniciarAsistencia(): void {
        requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        if (!esAdmin()) {
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Acceso denegado: solo el Administrador puede reiniciar registros.'
            ]);
            exit;
        }

        $tipo = $_POST['tipo'] ?? 'todo';
        $alcance = $_POST['alcance'] ?? 'todo';

        $resultado = $this->accesoModel->reiniciarRegistrosAsistencia($tipo, $alcance);

        if ($resultado['exito']) {
            $totalBorrados = ($resultado['borrados_aula'] ?? 0) + ($resultado['borrados_acceso'] ?? 0);
            echo json_encode([
                'status' => 'ok',
                'mensaje' => "Se han reiniciado los registros con éxito. Total eliminados: {$totalBorrados} (Aula: {$resultado['borrados_aula']}, Portería: {$resultado['borrados_acceso']}).",
                'detalles' => $resultado
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Error al reiniciar los registros: ' . ($resultado['mensaje'] ?? 'Error desconocido')
            ]);
        }
        exit;
    }
}

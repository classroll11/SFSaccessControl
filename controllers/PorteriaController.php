<?php
/**
 * controllers/PorteriaController.php
 * Controlador para la terminal y monitoreo de portería
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/AccesoModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class PorteriaController {
    private AccesoModel $accesoModel;
    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->accesoModel = new AccesoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index(): void {
        requireLogin();

        if (!esCelador() && !esAdmin()) {
            header('Location: dashboard.php?acceso=denegado');
            exit;
        }

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $estadisticas     = $this->accesoModel->obtenerEstadisticasHoy();
        $accesosRecientes = $this->accesoModel->obtenerAccesosRecientes(20);
        $estudiantesDentro = $this->accesoModel->contarEstudiantesDentro();

        require __DIR__ . '/../views/porteria/index.php';
    }
}

<?php
/**
 * controllers/HistorialController.php
 * Controlador para el historial completo de accesos y auditoría
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/AccesoModel.php';

class HistorialController {
    private AccesoModel $accesoModel;

    public function __construct() {
        $this->accesoModel = new AccesoModel();
    }

    public function index(): void {
        requireLogin();

        if (!esAdmin() && !esCelador() && !esCoordinador() && !esRector()) {
            header('Location: dashboard.php?acceso=denegado');
            exit;
        }

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $accesos = $this->accesoModel->obtenerAccesosRecientes(150);

        require __DIR__ . '/../views/historial/index.php';
    }
}

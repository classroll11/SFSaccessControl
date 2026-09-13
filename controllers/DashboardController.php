<?php
/**
 * controllers/DashboardController.php
 * Controlador para el Panel Principal y métricas institucionales
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/AccesoModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class DashboardController {
    private AccesoModel $accesoModel;
    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->accesoModel = new AccesoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index(): void {
        requireLogin();

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $estadisticas        = $this->accesoModel->obtenerEstadisticasHoy();
        $accesosRecientes    = $this->accesoModel->obtenerAccesosRecientes(15);
        $estudiantesDentro   = $this->accesoModel->contarEstudiantesDentro();
        $estudiantesPorGrado = $this->accesoModel->obtenerEstudiantesDentroPorGrado();
        $resumenConsolidado  = ($rolActual === 'RECTOR' || $rolActual === 'COORDINADOR' || esAdmin())
            ? $this->accesoModel->obtenerResumenConsolidado()
            : [];

        require __DIR__ . '/../views/dashboard/index.php';
    }
}

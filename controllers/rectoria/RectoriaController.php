<?php
/**
 * controllers/rectoria/RectoriaController.php
 * Controlador para la rectoría: Aforo institucional por grados, métricas de ocupación y supervisión directiva
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/AccesoModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

class RectoriaController {
    private AccesoModel $accesoModel;
    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->accesoModel = new AccesoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index(): void {
        $this->aforo();
    }

    public function aforo(): void {
        requireLogin();

        if (!esCoordinador() && !esRector() && !esAdmin()) {
            header('Location: dashboard.php?acceso=denegado');
            exit;
        }

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $estudiantesDentro   = $this->accesoModel->contarEstudiantesDentro();
        $estudiantesPorGrado = $this->accesoModel->obtenerEstudiantesDentroPorGrado();

        require __DIR__ . '/../../views/rectoria/aforo.php';
    }
}

<?php
/**
 * controllers/HuellaController.php
 * Controlador para la gestión y enrolamiento de huellas dactilares
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class HuellaController {
    private UsuarioModel $usuarioModel;

    public function __construct() {
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

        $estudiantesHuellas = $this->usuarioModel->obtenerEstudiantesConConteoHuellas();

        require __DIR__ . '/../views/huellas/index.php';
    }
}

<?php
/**
 * controllers/AsistenciaController.php
 * Controlador para la toma y control de asistencia en aula
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/AccesoModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class AsistenciaController {
    private AccesoModel $accesoModel;
    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->accesoModel = new AccesoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index(): void {
        requireLogin();

        if (!esDocente() && !esAdmin()) {
            header('Location: dashboard.php?acceso=denegado');
            exit;
        }

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $gradosDisponibles = [
            '6°01', '6°02', '6°03',
            '7°01', '7°02', '7°03',
            '8°01', '8°02', '8°03',
            '9°01', '9°02', '9°03',
            '10°01', '10°02',
            '11°01', '11°02'
        ];

        $gradoInicial = $usuarioSesion['grado'] ?: '6°01';
        if (!in_array($gradoInicial, $gradosDisponibles)) {
            $gradoInicial = '6°01';
        }

        require __DIR__ . '/../views/asistencia/index.php';
    }
}

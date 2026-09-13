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
}

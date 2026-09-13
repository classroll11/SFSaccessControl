<?php
/**
 * controllers/SupervisionController.php
 * Controlador para la supervisión directiva: Faltas, inasistencias y aforo por grados
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/AccesoModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class SupervisionController {
    private AccesoModel $accesoModel;
    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->accesoModel = new AccesoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function faltas(): void {
        requireLogin();

        if (!esCoordinador() && !esRector() && !esAdmin()) {
            header('Location: dashboard.php?acceso=denegado');
            exit;
        }

        $usuarioSesion = getUsuarioSesion();
        $rolActual     = getRolActual();
        $nombreSesion  = $usuarioSesion['nombre'];

        $faltasSupervision  = $this->accesoModel->obtenerFaltasSupervision();
        $resumenConsolidado = $this->accesoModel->obtenerResumenConsolidado();

        $totalInjustificadas = 0;
        $totalJustificadas   = 0;
        $totalRetardos       = 0;

        foreach ($faltasSupervision as $f) {
            if ($f['estado_asistencia'] === 'FALTA_INJUSTIFICADA') $totalInjustificadas++;
            elseif ($f['estado_asistencia'] === 'FALTA_JUSTIFICADA') $totalJustificadas++;
            elseif ($f['estado_asistencia'] === 'RETARDO') $totalRetardos++;
        }

        require __DIR__ . '/../views/faltas/index.php';
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

        require __DIR__ . '/../views/aforo/index.php';
    }
}

<?php
/**
 * controllers/coordinacion/CoordinacionController.php
 * Controlador para la supervisión de coordinación: Control de faltas, inasistencias y alertas tempranas
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/AccesoModel.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

class CoordinacionController {
    private AccesoModel $accesoModel;
    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->accesoModel = new AccesoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index(): void {
        $this->faltas();
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

        require __DIR__ . '/../../views/coordinacion/faltas.php';
    }
}

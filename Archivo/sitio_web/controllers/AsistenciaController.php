<?php
/**
 * controllers/AsistenciaController.php
 * Wrapper de compatibilidad que delega a DocenteController
 */

require_once __DIR__ . '/docente/DocenteController.php';

// Si la clase AsistenciaController no se cargó mediante el alias de DocenteController
if (!class_exists('AsistenciaController', false)) {
    class AsistenciaController extends DocenteController {}
}

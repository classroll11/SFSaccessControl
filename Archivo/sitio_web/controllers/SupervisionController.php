<?php
/**
 * controllers/SupervisionController.php
 * Wrapper de compatibilidad que delega a CoordinacionController y RectoriaController
 */

require_once __DIR__ . '/coordinacion/CoordinacionController.php';
require_once __DIR__ . '/rectoria/RectoriaController.php';

class SupervisionController {
    private CoordinacionController $coordinacion;
    private RectoriaController $rectoria;

    public function __construct() {
        $this->coordinacion = new CoordinacionController();
        $this->rectoria = new RectoriaController();
    }

    public function faltas(): void {
        $this->coordinacion->faltas();
    }

    public function aforo(): void {
        $this->rectoria->aforo();
    }
}

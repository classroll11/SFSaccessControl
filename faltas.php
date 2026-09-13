<?php
/**
 * faltas.php - Entry Point
 * Delegador MVC para el Control de Faltas y Ausentismo
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/SupervisionController.php';

$controller = new SupervisionController();
$controller->faltas();

<?php
/**
 * huellas.php - Entry Point
 * Delegador MVC para la Gestión de Huellas Dactilares
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/HuellaController.php';

$controller = new HuellaController();
$controller->index();

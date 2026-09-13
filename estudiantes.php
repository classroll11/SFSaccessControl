<?php
/**
 * estudiantes.php - Entry Point
 * Delegador MVC para el Directorio Estudiantil
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AdminController.php';

$controller = new AdminController();
$controller->estudiantes();

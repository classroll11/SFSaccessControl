<?php
/**
 * asistencia.php - Entry Point
 * Delegador MVC para la Toma de Asistencia en el Aula
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AsistenciaController.php';

$controller = new AsistenciaController();
$controller->index();

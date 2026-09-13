<?php
/**
 * historial.php - Entry Point
 * Delegador MVC para el Historial de Accesos y Auditoría
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/HistorialController.php';

$controller = new HistorialController();
$controller->index();

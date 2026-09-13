<?php
/**
 * dashboard.php - Entry Point
 * Delegador MVC para el Panel Principal
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/DashboardController.php';

$controller = new DashboardController();
$controller->index();

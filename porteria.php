<?php
/**
 * porteria.php - Entry Point
 * Delegador MVC para la Terminal de Portería
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/PorteriaController.php';

$controller = new PorteriaController();
$controller->index();

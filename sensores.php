<?php
/**
 * sensores.php - Entry Point
 * Delegador MVC para Sensores Biométricos y Hardware
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AdminController.php';

$controller = new AdminController();
$controller->sensores();

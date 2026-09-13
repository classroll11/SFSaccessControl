<?php
/**
 * usuarios.php - Entry Point
 * Delegador MVC para la Gestión de Usuarios y Claves
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AdminController.php';

$controller = new AdminController();
$controller->usuarios();

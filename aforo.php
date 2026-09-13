<?php
/**
 * aforo.php - Entry Point
 * Delegador MVC para el Aforo Institucional por Grados
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/SupervisionController.php';

$controller = new SupervisionController();
$controller->aforo();

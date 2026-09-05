<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: dashboard.php
 * DESCRIPCIÓN: Panel de Control de Accesos y Asistencia a Clases.
 *              ACCESO RESTRINGIDO: Solo usuarios con sesión activa.
 * ============================================================================
 */

// 1. Cargar middleware de autenticación y verificar sesión
require_once __DIR__ . '/config/auth.php';
requireLogin(); // Redirige a login.php si no hay sesión activa

// 2. Cargar dependencias y renderizar el panel
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AccesoController.php';

$controller = new AccesoController();
$controller->index();

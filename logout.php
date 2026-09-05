<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: logout.php
 * DESCRIPCION: Cierra la sesion activa y redirige al login.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar todas las variables de sesion
$_SESSION = [];

// Destruir la cookie de sesion si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruir la sesion del servidor
session_destroy();

// Redirigir al login
header('Location: login.php');
exit;

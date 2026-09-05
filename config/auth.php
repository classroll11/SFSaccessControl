<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: config/auth.php
 * DESCRIPCION: Middleware de autenticacion. Protege rutas privadas verificando
 *              que exista una sesion activa valida. Si no hay sesion, redirige
 *              inmediatamente al login.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica que el usuario haya iniciado sesion.
 * Si no hay sesion activa, redirige al login y detiene la ejecucion.
 */
function requireLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        $base = getBaseUrl();
        header('Location: ' . $base . 'login.php');
        exit;
    }
}

/**
 * Verifica que el usuario tenga un rol especifico.
 */
function requireRol(array $rolesPermitidos): void {
    requireLogin();
    $rolActual = $_SESSION['usuario_rol'] ?? '';
    if (!in_array($rolActual, $rolesPermitidos)) {
        header('Location: ' . getBaseUrl() . 'dashboard.php?acceso=denegado');
        exit;
    }
}

/**
 * Retorna la URL base del proyecto de forma dinamica.
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = dirname($_SERVER['SCRIPT_NAME']);
    $base     = rtrim(str_replace('\\', '/', $script), '/');
    return $protocol . '://' . $host . $base . '/';
}

/**
 * Retorna los datos del usuario en sesion como array.
 */
function getUsuarioSesion(): array {
    return [
        'id'     => $_SESSION['usuario_id']     ?? null,
        'nombre' => $_SESSION['usuario_nombre'] ?? 'Desconocido',
        'rol'    => $_SESSION['usuario_rol']    ?? 'ESTUDIANTE',
        'grado'  => $_SESSION['usuario_grado']  ?? '',
        'correo' => $_SESSION['usuario_correo'] ?? '',
    ];
}

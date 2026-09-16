<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Front Controller & Enrutador Principal MVC (PHP Nativo).
 * ============================================================================
 */

// Zona horaria y manejo de errores
date_default_timezone_set('America/Bogota');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar configuración de base de datos y autenticación
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------------
// 1. OBTENER RUTA SOLICITADA
// -----------------------------------------------------------------------------
$route = $_GET['route'] ?? '';

// Si la ruta viene vacía o por REQUEST_URI
if (empty($route)) {
    $requestUri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $scriptDir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (!empty($scriptDir) && stripos($requestUri, $scriptDir) === 0) {
        $requestUri = substr($requestUri, strlen($scriptDir));
    }
    $route = $requestUri;
} else {
    $route = rawurldecode($route);
}

// Remover subdirectorio si aún está presente en la ruta
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$scriptDirRel = trim($scriptDir, '/');
if (!empty($scriptDirRel) && stripos(trim($route, '/'), $scriptDirRel) === 0) {
    $route = substr(trim($route, '/'), strlen($scriptDirRel));
}

// Limpiar slashes y extensiones .php
$route = trim($route, '/');
$route = preg_replace('/\.php$/i', '', $route);
if ($route === 'index') {
    $route = '';
}

// -----------------------------------------------------------------------------
// 2. SOPORTE PARA PARÁMETROS LEGADOS (?c=controlador&a=accion)
// -----------------------------------------------------------------------------
if (isset($_GET['c'])) {
    $c = ucfirst(strtolower(trim($_GET['c']))) . 'Controller';
    $a = isset($_GET['a']) ? trim($_GET['a']) : 'index';
    despacharControlador($c, $a);
    exit;
}

// -----------------------------------------------------------------------------
// 3. TABLA DE RUTAS PRINCIPALES DEL SISTEMA
// -----------------------------------------------------------------------------
$rutas = [
    // Páginas Públicas
    ''                  => ['HomeController', 'index'],
    'index'             => ['HomeController', 'index'],
    'inicio'            => ['HomeController', 'index'],
    'nosotros'          => ['HomeController', 'nosotros'],
    'blog'              => ['HomeController', 'blog'],

    // Autenticación
    'login'             => ['AuthController', 'login'],
    'logout'            => ['AuthController', 'logout'],
    'registro'          => ['AuthController', 'registro'],
    'cambiar_password'  => ['AuthController', 'cambiarPassword'],

    // Perfil
    'perfil'            => ['PerfilController', 'index'],

    // Módulos del Sistema (Protegidos)
    'dashboard'         => ['DashboardController', 'index'],
    'panel'             => ['DashboardController', 'index'],
    'asistencia'        => ['AsistenciaController', 'index'],
    'porteria'          => ['PorteriaController', 'index'],
    'huellas'           => ['HuellaController', 'index'],
    'usuarios'          => ['AdminController', 'usuarios'],
    'estudiantes'       => ['AdminController', 'estudiantes'],
    'sensores'          => ['AdminController', 'sensores'],
    'faltas'            => ['SupervisionController', 'faltas'],
    'aforo'             => ['SupervisionController', 'aforo'],
    'historial'         => ['HistorialController', 'index'],
];

// Si la ruta coincide directamente en la tabla
if (array_key_exists($route, $rutas)) {
    [$controlador, $accion] = $rutas[$route];
    despacharControlador($controlador, $accion);
    exit;
}

// -----------------------------------------------------------------------------
// 4. DESPACHO DINÁMICO (ej: ruta = acceso/obtenerHuellasEstudiante)
// -----------------------------------------------------------------------------
$partes = explode('/', $route);
if (count($partes) >= 1 && !empty($partes[0])) {
    $controladorNombre = ucfirst(strtolower($partes[0])) . 'Controller';
    $accionNombre      = $partes[1] ?? 'index';

    $archivoControlador = __DIR__ . '/controllers/' . $controladorNombre . '.php';
    if (file_exists($archivoControlador)) {
        despacharControlador($controladorNombre, $accionNombre);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 5. RUTA 404 NO ENCONTRADA
// -----------------------------------------------------------------------------
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada | SFS Access</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;height:100vh;background:#090d16;color:#fff;font-family:'Segoe UI',sans-serif;text-align:center;margin:0;">
    <div style="max-width:500px;padding:2rem;">
        <h1 style="font-size:4rem;color:#38bdf8;margin:0 0 1rem;">404</h1>
        <h2 style="margin:0 0 1rem;font-weight:600;">Página no encontrada</h2>
        <p style="color:#94a3b8;margin-bottom:2rem;">La ruta solicitada <code>/<?= htmlspecialchars($route) ?></code> no existe en la aplicación.</p>
        <a href="<?= getBaseUrl() ?>" style="display:inline-block;padding:0.75rem 1.5rem;background:linear-gradient(135deg,#0284c7,#2563eb);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">Volver al Inicio</a>
    </div>
</body>
</html>
<?php
exit;

// -----------------------------------------------------------------------------
// FUNCIÓN AUXILIAR DE DESPACHO
// -----------------------------------------------------------------------------
function despacharControlador(string $controladorNombre, string $accion): void {
    $archivo = __DIR__ . '/controllers/' . $controladorNombre . '.php';

    if (!file_exists($archivo)) {
        http_response_code(404);
        die("Controlador [{$controladorNombre}] no encontrado.");
    }

    require_once $archivo;

    if (!class_exists($controladorNombre)) {
        http_response_code(500);
        die("La clase [{$controladorNombre}] no está definida.");
    }

    $instancia = new $controladorNombre();

    if (!method_exists($instancia, $accion)) {
        http_response_code(404);
        die("La acción [{$accion}] no existe en el controlador [{$controladorNombre}].");
    }

    $instancia->$accion();
}

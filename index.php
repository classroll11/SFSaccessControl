<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Enrutador Front Controller & Página Principal Institucional (PHP Nativo).
 * ============================================================================
 */

// Configuración básica de zona horaria y reporte de errores
date_default_timezone_set('America/Bogota');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar configuración de base de datos
require_once __DIR__ . '/config/database.php';

// Si se recibe el parámetro de controlador por URL (?c=acceso, ?c=reporte, ?c=auth), despachar al controlador correspondiente
if (isset($_GET['c'])) {
    $controladorNombre = ucfirst(strtolower(trim($_GET['c']))) . 'Controller';
    $accion = isset($_GET['a']) ? trim($_GET['a']) : 'index';

    $controladoresDisponibles = [
        'AccesoController'  => __DIR__ . '/controllers/AccesoController.php',
        'ReporteController' => __DIR__ . '/controllers/ReporteController.php',
        'AuthController'    => __DIR__ . '/controllers/AuthController.php'
    ];

    if (!array_key_exists($controladorNombre, $controladoresDisponibles)) {
        http_response_code(404);
        die("<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>
                <h1 style='color: #e11d48;'>Error 404 - Controlador no encontrado</h1>
                <p>El recurso solicitado <code>{$controladorNombre}</code> no existe en el sistema SFS Access.</p>
                <a href='index.php' style='color: #0062ff; text-decoration: none; font-weight: bold;'>&larr; Volver al Inicio</a>
             </div>");
    }

    require_once $controladoresDisponibles[$controladorNombre];
    $instanciaControlador = new $controladorNombre();

    if (!method_exists($instanciaControlador, $accion)) {
        http_response_code(404);
        die("<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>
                <h1 style='color: #e11d48;'>Error 404 - Acción no encontrada</h1>
                <p>El método <code>{$accion}</code> no existe en el controlador <code>{$controladorNombre}</code>.</p>
                <a href='index.php' style='color: #0062ff; text-decoration: none; font-weight: bold;'>&larr; Volver al Inicio</a>
             </div>");
    }

    $instanciaControlador->$accion();
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SFS Access Control - Seguridad y Control Biométrico Institucional</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- FontAwesome 6 Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="index.php" class="logo">
            <div class="logo-icon-emblem">
                <img src="assets/img/sfs-logo-emblem.png" alt="SFS Logo" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-fingerprint\'></i>'">
            </div>
            <div class="logo-title-group">
                <span class="logo-brand-text">SFS <span>ACCESS</span></span>
                <span class="logo-tag-text">CONTROL BIOMÉTRICO</span>
            </div>
        </a>
        <ul class="nav-links">
            <li><a href="index.php" class="active"><i class="fa-solid fa-house-chimney"></i> INICIO</a></li>
            <li><a href="nosotros.php"><i class="fa-solid fa-users"></i> NOSOTROS</a></li>
            <li><a href="blog.php"><i class="fa-solid fa-newspaper"></i> BLOG</a></li>
            <li><a href="login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> LOGIN</a></li>
            <li><a href="registro.php"><i class="fa-solid fa-user-plus"></i> REGISTRO</a></li>
            <li><a href="dashboard.php" class="btn-perfil"><i class="fa-solid fa-gauge-high"></i> PANEL EN VIVO</a></li>
        </ul>
    </nav>

    <!-- HERO SECTION -->
    <main class="hero-dark">
        <div style="width: 100%; max-width: 1320px; margin: auto; position: relative; z-index: 10;">
            <div class="inicio-container">
                <div>
                    <div class="tag-badge">
                        <i class="fa-solid fa-shield-halved"></i> SEGURIDAD BIOMÉTRICA CERTIFICADA &bull; I.E. JORGE ROBLEDO
                    </div>
                    <h1 class="hero-title">Gestiona el ingreso, simplifica el aforo y protege tu comunidad.</h1>
                    <p class="hero-desc">
                        Optimizamos el control de acceso en la <strong>I.E. Jorge Robledo</strong> con tecnología biométrica dactilar de vanguardia, reemplazando registros manuales en cuadernos y calculando raciones para el restaurante escolar en tiempo real.
                    </p>
                    <div class="hero-btn-group">
                        <a href="login.php" class="btn-primary">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Iniciar Sesión
                        </a>
                        <a href="dashboard.php" class="btn-secondary-dark">
                            <i class="fa-solid fa-desktop"></i> Ver Terminal en Vivo
                        </a>
                    </div>
                </div>

                <div class="cards-2x2">
                    <div class="feature-card">
                        <i class="fa-solid fa-fingerprint"></i>
                        <h3>Verificación Biométrica</h3>
                        <p>Lectura dactilar instantánea que elimina la suplantación de identidad y agiliza el flujo en portería.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fa-solid fa-utensils"></i>
                        <h3>Restaurante Escolar</h3>
                        <p>Cálculo y auditoría automática de raciones de comida según los alumnos presentes en el plantel.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fa-solid fa-chart-pie"></i>
                        <h3>Métricas y Reportes</h3>
                        <p>Exportación a CSV y estadísticas en tiempo real por grado escolar para directivas y coordinadores.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fa-solid fa-shield-halved"></i>
                        <h3>Entorno Protegido</h3>
                        <p>Auditoría de seguridad continua con bloqueo inmediato de intrusos y usuarios no autorizados.</p>
                    </div>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-strip">
                <div class="stat-item">
                    <div class="stat-number">+1,250</div>
                    <div class="stat-label">Estudiantes Registrados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">&lt; 0.4s</div>
                    <div class="stat-label">Tiempo de Verificación</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Precisión de Aforo PAE</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Filas y Suplantaciones</div>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="index.php" class="logo" style="margin-bottom: 1.2rem; display: inline-flex;">
                    <div class="logo-icon-emblem" style="width: 32px; height: 32px;">
                        <img src="assets/img/sfs-logo-emblem.png" alt="SFS Logo">
                    </div>
                    <div class="logo-title-group">
                        <span class="logo-brand-text">SFS <span>ACCESS</span></span>
                        <span class="logo-tag-text">I.E. JORGE ROBLEDO</span>
                    </div>
                </a>
                <p>Líderes en soluciones de control de acceso estudiantil y corporativo. Seguridad, tecnología y precisión integradas para la I.E. Jorge Robledo.</p>
            </div>
            <div class="footer-col">
                <h4>Contacto Directo</h4>
                <p><i class="fa-regular fa-envelope"></i> sfsaccesscontrol@gmail.com</p>
                <p style="margin-top: 0.5rem;"><i class="fa-solid fa-location-dot"></i> Sede Central - Medellín, Colombia</p>
            </div>
            <div class="footer-col">
                <h4>Navegación</h4>
                <ul>
                    <li><a href="index.php"><i class="fa-solid fa-chevron-right me-1" style="font-size: 0.7rem;"></i> Inicio</a></li>
                    <li><a href="nosotros.php"><i class="fa-solid fa-chevron-right me-1" style="font-size: 0.7rem;"></i> Nosotros</a></li>
                    <li><a href="blog.php"><i class="fa-solid fa-chevron-right me-1" style="font-size: 0.7rem;"></i> Blog</a></li>
                    <li><a href="login.php"><i class="fa-solid fa-chevron-right me-1" style="font-size: 0.7rem;"></i> Iniciar Sesión</a></li>
                    <li><a href="registro.php"><i class="fa-solid fa-chevron-right me-1" style="font-size: 0.7rem;"></i> Registro</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Redes Sociales</h4>
                <div class="social-icons">
                    <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                    <a href="#" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>

<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: nosotros.php
 * DESCRIPCIÓN: Página institucional "Nosotros" en PHP nativo.
 * ============================================================================
 */
require_once __DIR__ . '/../../config/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nosotros - SFS Access Control</title>
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
                <img src="assets/img/logo.jpeg" alt="SFS Logo" onerror="this.src='logo.jpeg'">
            </div>
            <div class="logo-title-group">
                <span class="logo-brand-text">SFS <span>ACCESS</span></span>
                <span class="logo-tag-text">CONTROL BIOMÉTRICO</span>
            </div>
        </a>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-house-chimney"></i> INICIO</a></li>
            <li><a href="nosotros.php" class="active"><i class="fa-solid fa-users"></i> NOSOTROS</a></li>
            <li><a href="blog.php"><i class="fa-solid fa-newspaper"></i> BLOG</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="dashboard.php" class="btn-perfil"><i class="fa-solid fa-gauge-high"></i> PANEL EN VIVO</a></li>
                <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> SALIR</a></li>
            <?php else: ?>
                <li><a href="login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> LOGIN</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="page-container">
        <span class="section-tag"><i class="fa-solid fa-code me-1"></i> NUESTRO EQUIPO DE DESARROLLO</span>
        <h1 class="section-title">Innovación, Precisión y Seguridad en cada acceso</h1>
        <p class="section-subtitle">
            En SFS ACCESS CONTROL unimos ingeniería de software, arquitectura biométrica y diseño centrado en el usuario para transformar la seguridad en la I.E. Jorge Robledo.
        </p>

        <div class="team-grid">
            
            <div class="team-card">
                <img src="assets/img/Sofia.jpg" alt="Sofía Ocampo" class="team-img" onerror="this.src='img/Sofia.jpg'">
                <div class="team-info">
                    <h3 class="team-name">Sofía Ocampo Loaiza</h3>
                    <p class="team-role"><i class="fa-solid fa-code"></i> Desarrolladora Full-Stack</p>
                    <p class="team-desc">Arquitectura backend en PHP nativo, diseño relacional de base de datos MySQL y seguridad de API biométrica.</p>
                </div>
            </div>

            <div class="team-card">
                <div class="team-img-placeholder">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="team-info">
                    <h3 class="team-name">Solangy Arias Rivas</h3>
                    <p class="team-role"><i class="fa-solid fa-palette"></i> Diseñadora UX / UI</p>
                    <p class="team-desc">Diseño de experiencia de usuario, interfaces visuales responsivas y accesibilidad para la comunidad escolar.</p>
                </div>
            </div>

            <div class="team-card">
                <img src="assets/img/Fernanda.jpg" alt="Fernanda Sánchez" class="team-img" onerror="this.src='img/Fernanda.jpg'">
                <div class="team-info">
                    <h3 class="team-name">Fernanda Sánchez Colina</h3>
                    <p class="team-role"><i class="fa-solid fa-diagram-project"></i> Product Manager</p>
                    <p class="team-desc">Estrategia de producto, requerimientos de control de asistencia a clases y coordinación con el personal directivo.</p>
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
                        <img src="assets/img/logo.jpeg" alt="SFS Logo" onerror="this.src='logo.jpeg'">
                    </div>
                    <div class="logo-title-group">
                        <span class="logo-brand-text">SFS <span>ACCESS</span></span>
                        <span class="logo-tag-text">I.E. JORGE ROBLEDO</span>
                    </div>
                </a>
                <p>Líderes en soluciones de control de acceso estudiantil y corporativo. Seguridad, tecnología y precisión integradas para su tranquilidad.</p>
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

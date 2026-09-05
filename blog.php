<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: blog.php
 * DESCRIPCIÓN: Página del Blog institucional en PHP nativo.
 * ============================================================================
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog y Artículos - SFS Access Control</title>
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
            <li><a href="index.php"><i class="fa-solid fa-house-chimney"></i> INICIO</a></li>
            <li><a href="nosotros.php"><i class="fa-solid fa-users"></i> NOSOTROS</a></li>
            <li><a href="blog.php" class="active"><i class="fa-solid fa-newspaper"></i> BLOG</a></li>
            <li><a href="login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> LOGIN</a></li>
            <li><a href="dashboard.php" class="btn-perfil"><i class="fa-solid fa-gauge-high"></i> PANEL EN VIVO</a></li>
        </ul>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="page-container">
        <div style="text-align: center; margin-bottom: 3.5rem;">
            <span class="section-tag"><i class="fa-solid fa-microchip me-1"></i> ARTÍCULOS Y TECNOLOGÍA</span>
            <h1 class="section-title">Lo que debes saber sobre el control de acceso biométrico</h1>
            <p class="section-subtitle" style="margin: 0 auto;">Descubre cómo la autenticación biométrica y el aforo automatizado mejoran la convivencia y eficiencia en instituciones educativas.</p>
        </div>

        <div class="blog-grid">
            <div class="blog-card">
                <div class="blog-icon icon-cyan">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3>Seguridad Garantizada</h3>
                <p>Las plantillas de huella son procesadas mediante hashes seguros, imposibilitando la falsificación o suplantación de identidad estudiantil.</p>
            </div>

            <div class="blog-card">
                <div class="blog-icon icon-blue">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <h3>Acceso Rápido y Fluido</h3>
                <p>Verificación en menos de 400 milisegundos que evita aglomeraciones en las porterías durante las horas pico de ingreso escolar.</p>
            </div>

            <div class="blog-card">
                <div class="blog-icon icon-purple">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <h3>Control de Asistencia a Clases</h3>
                <p>Monitoreo automático de asistencia por aula y grado en tiempo real, optimizando el seguimiento académico docente.</p>
            </div>

            <div class="blog-card">
                <div class="blog-icon icon-red">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3>Reportes Directivos</h3>
                <p>Generación de auditorías en tiempo real y descarga de reportes en Excel/CSV para rectoría y coordinaciones académicas.</p>
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

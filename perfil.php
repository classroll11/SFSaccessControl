<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: perfil.php
 * DESCRIPCIÓN: Perfil de usuario en PHP nativo con datos de sesión dinámica.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AccesoModel.php';

// Obtener datos del usuario logueado o usar datos predeterminados
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Prof. Carlos Andrés Restrepo';
$usuarioRol = $_SESSION['usuario_rol'] ?? 'ADMINISTRADOR';
$usuarioGrado = $_SESSION['usuario_grado'] ?? 'RECTORÍA';
$usuarioCorreo = $_SESSION['usuario_correo'] ?? 'rectoria@jorgerobledo.edu.co';
$usuarioDoc = $_SESSION['usuario_documento'] ?? '10000001';

$accesoModel = new AccesoModel();
$movimientos = $accesoModel->obtenerAccesosRecientes(5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - SFS Access Control</title>
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
            <li><a href="blog.php"><i class="fa-solid fa-newspaper"></i> BLOG</a></li>
            <li><a href="login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> LOGIN</a></li>
            <li><a href="dashboard.php" class="btn-perfil"><i class="fa-solid fa-gauge-high"></i> PANEL EN VIVO</a></li>
        </ul>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="page-container">
        <span class="section-tag"><i class="fa-solid fa-shield-halved me-1"></i> PANEL DE USUARIO</span>
        <h1 class="section-title">Perfil Institucional</h1>
        <p class="section-subtitle">Gestiona tu información de seguridad y supervisa los registros de acceso en la I.E. Jorge Robledo.</p>

        <!-- TARJETA CABECERA DE PERFIL -->
        <div class="profile-header">
            <div class="profile-avatar-container">
                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300" alt="Usuario SFS" class="profile-avatar">
                <div class="badge-check"><i class="fa-solid fa-check"></i></div>
            </div>
            <div>
                <h2 style="color: var(--text-dark); font-size: 1.55rem;"><?= htmlspecialchars($usuarioNombre) ?></h2>
                <p style="margin-top: 0.4rem; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span class="status-tag"><i class="fa-solid fa-circle-check"></i> Activo</span> 
                    <span style="color: var(--text-muted); font-size: 0.9rem;"><i class="fa-solid fa-user-shield me-1"></i> <?= htmlspecialchars($usuarioGrado) ?> (<?= htmlspecialchars($usuarioRol) ?>)</span>
                    <span style="color: var(--text-muted); font-size: 0.9rem;"><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($usuarioCorreo) ?></span>
                </p>
                <p style="font-size: 0.84rem; color: #64748b; margin-top: 0.5rem; font-weight: 600;">
                    <i class="fa-solid fa-id-card me-1"></i> DOCUMENTO: <?= htmlspecialchars($usuarioDoc) ?> &bull; <i class="fa-solid fa-fingerprint text-success me-1"></i> Huella Digital Vinculada
                </p>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Tabla Historial Reciente -->
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <h3 style="font-size: 1.25rem; color: var(--text-dark);">
                        <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Movimientos Recientes en la Sede
                    </h3>
                    <a href="dashboard.php" style="color: var(--accent-blue); font-size: 0.88rem; font-weight: 700; text-decoration: none;">
                        VER MONITOR EN VIVO &rarr;
                    </a>
                </div>

                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>ESTUDIANTE / USUARIO</th>
                            <th>HORA</th>
                            <th>EVENTO</th>
                            <th>ESTADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movimientos)): ?>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($m['nombre_usuario']) ?></strong></td>
                                    <td><?= date('h:i:s A', strtotime($m['fecha_hora'])) ?></td>
                                    <td>
                                        <?php if ($m['tipo_evento'] === 'ENTRADA'): ?>
                                            <span style="background: #dbeafe; color: #1d4ed8; font-size: 0.76rem; padding: 4px 10px; border-radius: 6px; font-weight: 700;">ENTRADA</span>
                                        <?php else: ?>
                                            <span style="background: #fef3c7; color: #b45309; font-size: 0.76rem; padding: 4px 10px; border-radius: 6px; font-weight: 700;">SALIDA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($m['estado_acceso'] === 'APROBADO'): ?>
                                            <span class="badge-status status-approved"><i class="fa-solid fa-check me-1"></i> Aprobado</span>
                                        <?php else: ?>
                                            <span class="badge-status status-denied"><i class="fa-solid fa-xmark me-1"></i> Rechazado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #64748b; padding: 2.5rem;">
                                    <i class="fa-solid fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                    No hay registros de accesos recientes hoy.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tarjeta de Acciones Rápidas -->
            <div class="dashboard-card">
                <h3 style="font-size: 1.25rem; color: var(--text-dark); margin-bottom: 1.5rem;">
                    <i class="fa-solid fa-bolt text-warning me-2"></i> Accesos Rápidos
                </h3>

                <a href="dashboard.php" class="quick-action-item">
                    <i class="fa-solid fa-desktop" style="font-size: 1.3rem; color: var(--accent-blue);"></i>
                    <div>
                        <strong style="display: block; font-size: 0.92rem;">Monitor en Tiempo Real</strong>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">Ver aforo y asistencia a clases</span>
                    </div>
                </a>

                <a href="index.php?c=reporte&a=exportarCsv" class="quick-action-item">
                    <i class="fa-solid fa-file-csv" style="font-size: 1.3rem; color: #10b981;"></i>
                    <div>
                        <strong style="display: block; font-size: 0.92rem;">Descargar Reporte Hoy</strong>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">Exportar listado de asistencia en CSV</span>
                    </div>
                </a>

                <a href="index.php?c=auth&a=logout" class="quick-action-item danger">
                    <i class="fa-solid fa-power-off" style="font-size: 1.3rem; color: #dc2626;"></i>
                    <div>
                        <strong style="display: block; font-size: 0.92rem; color: #dc2626;">Cerrar Sesión</strong>
                        <span style="font-size: 0.78rem; color: #ef4444;">Desconectar sesión actual</span>
                    </div>
                </a>
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
                <p style="margin-top: 0.5rem;"><i class="fa-solid fa-location-dot"></i> Sede Central - Medellín CO</p>
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

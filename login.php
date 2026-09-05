<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: login.php
 * DESCRIPCIÓN: Página de inicio de sesión.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si el usuario ya tiene sesión activa, redirigir al panel directamente
if (!empty($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/UsuarioModel.php';

$mensajeError = '';
$mensajeExito = '';

if (isset($_GET['registro']) && $_GET['registro'] === 'exitoso') {
    $mensajeExito = '¡Registro completado con éxito! Ya puedes iniciar sesión con tus credenciales.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($correo) || empty($password)) {
        $mensajeError = 'Por favor ingresa tu correo y contraseña.';
    } else {
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->obtenerPorCorreo($correo);

        if (!$usuario) {
            $usuario = $usuarioModel->obtenerPorDocumento($correo);
        }

        if ($usuario && !empty($usuario['password']) && password_verify($password, $usuario['password'])) {
            if ($usuario['estado'] !== 'ACTIVO') {
                $mensajeError = "Acceso denegado: Tu cuenta se encuentra en estado '{$usuario['estado']}'.";
            } else {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_documento'] = $usuario['documento'];
                $_SESSION['usuario_correo'] = $usuario['correo'] ?? $correo;
                $_SESSION['usuario_rol'] = $usuario['rol'];
                $_SESSION['usuario_grado'] = $usuario['grado'];

                header('Location: dashboard.php');
                exit;
            }
        } else {
            $mensajeError = 'Credenciales incorrectas. Verifique su correo y contraseña.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SFS Access Control</title>
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
            <li><a href="login.php" class="active"><i class="fa-solid fa-arrow-right-to-bracket"></i> LOGIN</a></li>
            <li><a href="registro.php"><i class="fa-solid fa-user-plus"></i> REGISTRO</a></li>
            <li><a href="dashboard.php" class="btn-perfil"><i class="fa-solid fa-gauge-high"></i> PANEL EN VIVO</a></li>
        </ul>
    </nav>

    <!-- MAIN LOGIN -->
    <main class="hero-dark">
        <div class="login-wrapper">
            
            <!-- Columna Izquierda: Información de Seguridad -->
            <div class="side-column">
                <div class="side-info-card">
                    <div class="side-card-icon-box">
                        <i class="fa-solid fa-fingerprint"></i>
                    </div>
                    <h4>Control Biométrico</h4>
                    <p>Acceso seguro, encriptado y automatizado mediante lector de huella dactilar de alta precisión.</p>
                </div>
                <div class="side-info-card">
                    <div class="side-card-icon-box">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h4>Máxima Precisión</h4>
                    <p>Prevención de suplantación de identidad institucional y eliminación de registros manuales.</p>
                </div>
            </div>

            <!-- Tarjeta Central: Formulario de Iniciar Sesión -->
            <div class="login-card">
                <div class="login-icon-box">
                    <img src="assets/img/sfs-logo-emblem.png" alt="Logo SFS Access" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-lock\'></i>'">
                </div>
                <h2>Iniciar Sesión</h2>
                <p class="subtitle">Ingresa tus credenciales para acceder a la plataforma</p>

                <?php if (!empty($mensajeError)): ?>
                    <div class="alert-auth alert-auth-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($mensajeError) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($mensajeExito)): ?>
                    <div class="alert-auth alert-auth-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= htmlspecialchars($mensajeExito) ?></span>
                    </div>
                <?php endif; ?>

                <!-- FORMULARIO DE LOGIN -->
                <form action="login.php" method="POST">
                    
                    <!-- Campo 1: Correo Electrónico -->
                    <div class="form-group">
                        <label for="correo"><i class="fa-solid fa-envelope"></i> Correo Electrónico o Documento</label>
                        <div class="input-with-icon">
                            <input type="text" id="correo" name="correo" placeholder="ejemplo@jorgerobledo.edu.co" required autofocus value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
                            <i class="fa-solid fa-at input-icon-left"></i>
                        </div>
                    </div>

                    <!-- Campo 2: Contraseña -->
                    <div class="form-group">
                        <label for="password"><i class="fa-solid fa-lock"></i> Contraseña</label>
                        <div class="input-with-icon">
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                            <i class="fa-solid fa-key input-icon-left"></i>
                        </div>
                    </div>

                    <!-- Opciones adicionales: Recordarme y Olvidé Contraseña -->
                    <div class="form-options">
                        <label class="form-check-inline">
                            <input type="checkbox" name="recordar">
                            <span>Recordar sesión</span>
                        </label>
                        <a href="javascript:void(0)" onclick="alert('Por favor contacte a la administración del colegio para restablecer su contraseña institucional.');">¿Olvidaste tu contraseña?</a>
                    </div>

                    <!-- Botón de Ingreso -->
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-right-to-bracket"></i> Ingresar al Sistema
                    </button>
                </form>

                <!-- Enlace para Registrarse -->
                <div class="auth-footer">
                    ¿No tienes una cuenta aún? <a href="registro.php">Regístrate aquí</a>
                </div>
            </div>

            <!-- Columna Derecha: Beneficios Institucionales -->
            <div class="side-column">
                <div class="side-info-card">
                    <div class="side-card-icon-box">
                        <i class="fa-solid fa-utensils"></i>
                    </div>
                    <h4>Restaurante Escolar</h4>
                    <p>Cálculo automático de raciones según el aforo de estudiantes en tiempo real.</p>
                </div>
                <div class="side-info-card">
                    <div class="side-card-icon-box">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h4>Acceso Instantáneo</h4>
                    <p>Agilidad en la portería evitando filas y demoras en los horarios de ingreso escolar.</p>
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
                <p>Líderes en soluciones de control de acceso estudiantil. Seguridad, tecnología y precisión integradas para la I.E. Jorge Robledo.</p>
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

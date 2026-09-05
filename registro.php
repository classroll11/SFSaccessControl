<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: registro.php
 * DESCRIPCIÓN: Página de registro estudiantil y docente en PHP nativo.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/UsuarioModel.php';

$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $grado = trim($_POST['grado'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($nombre) || empty($documento) || empty($correo) || empty($password)) {
        $mensajeError = 'Por favor completa todos los campos obligatorios.';
    } elseif ($password !== $confirmPassword) {
        $mensajeError = 'Las contraseñas no coinciden. Por favor verifícalas.';
    } elseif (strlen($password) < 6) {
        $mensajeError = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $usuarioModel = new UsuarioModel();

        if ($usuarioModel->obtenerPorDocumento($documento)) {
            $mensajeError = 'El documento ingresado ya se encuentra registrado en el sistema.';
        } elseif ($usuarioModel->obtenerPorCorreo($correo)) {
            $mensajeError = 'El correo electrónico ya está registrado. Intenta iniciar sesión.';
        } else {
            $rol = in_array($grado, ['DOCENTE', 'ADMINISTRATIVO']) ? $grado : 'ESTUDIANTE';

            $nuevoId = $usuarioModel->crearUsuario([
                'documento' => $documento,
                'correo' => $correo,
                'nombre' => $nombre,
                'grado' => $grado,
                'password' => $password,
                'rol' => $rol,
                'estado' => 'ACTIVO'
            ]);

            if ($nuevoId) {
                header('Location: login.php?registro=exitoso');
                exit;
            } else {
                $mensajeError = 'Ocurrió un error inesperado al registrar el usuario. Inténtalo nuevamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - SFS Access Control</title>
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
            <li><a href="registro.php" class="active"><i class="fa-solid fa-user-plus"></i> REGISTRO</a></li>
            <li><a href="dashboard.php" class="btn-perfil"><i class="fa-solid fa-gauge-high"></i> PANEL EN VIVO</a></li>
        </ul>
    </nav>

    <!-- MAIN REGISTRO -->
    <main class="hero-dark">
        <div class="login-wrapper">
            
            <!-- Columna Izquierda: Información -->
            <div class="side-column">
                <div class="side-info-card">
                    <div class="side-card-icon-box">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h4>Registro Institucional</h4>
                    <p>Crea tu cuenta oficial para asociar tu huella dactilar al sistema de acceso de la I.E. Jorge Robledo.</p>
                </div>
                <div class="side-info-card">
                    <div class="side-card-icon-box">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <h4>Identificación Única</h4>
                    <p>Tu documento garantiza el registro exacto de asistencia a clases y el control de acceso seguro.</p>
                </div>
            </div>

            <!-- Tarjeta Central: Formulario de Registro -->
            <div class="login-card" style="max-width: 580px;">
                <div class="login-icon-box">
                    <img src="assets/img/sfs-logo-emblem.png" alt="Logo SFS" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-user-shield\'></i>'">
                </div>
                <h2>Crear Nueva Cuenta</h2>
                <p class="subtitle">Completa el formulario para registrarte en la plataforma</p>

                <?php if (!empty($mensajeError)): ?>
                    <div class="alert-auth alert-auth-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($mensajeError) ?></span>
                    </div>
                <?php endif; ?>

                <!-- FORMULARIO DE REGISTRO -->
                <form action="registro.php" method="POST">
                    
                    <div class="form-grid-2">
                        <!-- Campo 1: Nombre Completo -->
                        <div class="form-group">
                            <label for="nombre"><i class="fa-solid fa-user"></i> Nombre Completo</label>
                            <div class="input-with-icon">
                                <input type="text" id="nombre" name="nombre" placeholder="Ej: Alejandra Martínez" required autofocus value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                                <i class="fa-solid fa-id-badge input-icon-left"></i>
                            </div>
                        </div>

                        <!-- Campo 2: Documento -->
                        <div class="form-group">
                            <label for="documento"><i class="fa-solid fa-address-card"></i> Documento (T.I / C.C)</label>
                            <div class="input-with-icon">
                                <input type="text" id="documento" name="documento" placeholder="Ej: 10359001" required value="<?= htmlspecialchars($_POST['documento'] ?? '') ?>">
                                <i class="fa-solid fa-hashtag input-icon-left"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <!-- Campo 3: Correo Institucional -->
                        <div class="form-group">
                            <label for="correo"><i class="fa-solid fa-envelope"></i> Correo Electrónico</label>
                            <div class="input-with-icon">
                                <input type="email" id="correo" name="correo" placeholder="usuario@jorgerobledo.edu.co" required value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
                                <i class="fa-solid fa-at input-icon-left"></i>
                            </div>
                        </div>

                        <!-- Campo 4: Grado Escolar / Rol -->
                        <div class="form-group">
                            <label for="grado"><i class="fa-solid fa-graduation-cap"></i> Grado / Rol</label>
                            <div class="input-with-icon">
                                <select id="grado" name="grado" required>
                                    <option value="">Selecciona grado...</option>
                                    <optgroup label="Estudiantes">
                                        <option value="11°A" <?= (isset($_POST['grado']) && $_POST['grado'] === '11°A') ? 'selected' : '' ?>>Grado 11°A</option>
                                        <option value="11°B" <?= (isset($_POST['grado']) && $_POST['grado'] === '11°B') ? 'selected' : '' ?>>Grado 11°B</option>
                                        <option value="10°A" <?= (isset($_POST['grado']) && $_POST['grado'] === '10°A') ? 'selected' : '' ?>>Grado 10°A</option>
                                        <option value="10°B" <?= (isset($_POST['grado']) && $_POST['grado'] === '10°B') ? 'selected' : '' ?>>Grado 10°B</option>
                                        <option value="9°A" <?= (isset($_POST['grado']) && $_POST['grado'] === '9°A') ? 'selected' : '' ?>>Grado 9°A</option>
                                        <option value="9°B" <?= (isset($_POST['grado']) && $_POST['grado'] === '9°B') ? 'selected' : '' ?>>Grado 9°B</option>
                                    </optgroup>
                                    <optgroup label="Personal Institucional">
                                        <option value="DOCENTE" <?= (isset($_POST['grado']) && $_POST['grado'] === 'DOCENTE') ? 'selected' : '' ?>>Docente</option>
                                        <option value="ADMINISTRATIVO" <?= (isset($_POST['grado']) && $_POST['grado'] === 'ADMINISTRATIVO') ? 'selected' : '' ?>>Administrativo / Portería</option>
                                    </optgroup>
                                </select>
                                <i class="fa-solid fa-school input-icon-left"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <!-- Campo 5: Contraseña -->
                        <div class="form-group">
                            <label for="password"><i class="fa-solid fa-lock"></i> Contraseña</label>
                            <div class="input-with-icon">
                                <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
                                <i class="fa-solid fa-key input-icon-left"></i>
                            </div>
                        </div>

                        <!-- Campo 6: Confirmar Contraseña -->
                        <div class="form-group">
                            <label for="confirm_password"><i class="fa-solid fa-shield-check"></i> Confirmar</label>
                            <div class="input-with-icon">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite contraseña" required>
                                <i class="fa-solid fa-check-double input-icon-left"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Registro -->
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-user-check"></i> Completar Registro
                    </button>
                </form>

                <!-- Enlace a Iniciar Sesión -->
                <div class="auth-footer">
                    ¿Ya tienes una cuenta registrada? <a href="login.php">Inicia sesión aquí</a>
                </div>
            </div>

            <!-- Columna Derecha: Información -->
            <div class="side-column">
                <div class="side-info-card">
                    <div class="side-card-icon-box">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h4>Datos Seguros</h4>
                    <p>Tus credenciales y contraseñas son encriptadas con hashes de grado criptográfico moderno.</p>
                </div>
                <div class="side-info-card">
                    <div class="side-card-icon-box">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <h4>Multiplataforma</h4>
                    <p>Consulta tus asistencias y perfil en tiempo real desde cualquier celular, tablet o computador.</p>
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

<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: controllers/AuthController.php
 * DESCRIPCIÓN: Controlador para la autenticación de usuarios (Login, Registro y Logout).
 * ============================================================================
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class AuthController {
    private UsuarioModel $usuarioModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Procesa el inicio de sesión recibiendo ÚNICAMENTE correo y contraseña.
     */
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = $_POST['correo'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($correo) || empty($password)) {
                $mensajeError = 'Por favor complete todos los campos (correo y contraseña).';
                require_once __DIR__ . '/../login.php';
                return;
            }

            // Buscar usuario por correo electrónico
            $usuario = $this->usuarioModel->obtenerPorCorreo($correo);

            // Si no se encuentra por correo, intentar por documento como respaldo
            if (!$usuario) {
                $usuario = $this->usuarioModel->obtenerPorDocumento($correo);
            }

            // Validar existencia y verificación de hash bcrypt de la contraseña
            if ($usuario && !empty($usuario['password']) && password_verify($password, $usuario['password'])) {
                if ($usuario['estado'] !== 'ACTIVO') {
                    $mensajeError = 'Su cuenta se encuentra inactiva o suspendida. Contacte a la administración.';
                    require_once __DIR__ . '/../login.php';
                    return;
                }

                // Guardar datos del usuario en sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_documento'] = $usuario['documento'] ?? '';
                $_SESSION['usuario_rol'] = $usuario['rol'];
                $_SESSION['usuario_grado'] = $usuario['grado'];
                $_SESSION['usuario_correo'] = $usuario['correo'] ?? $correo;

                // Verificar si tiene contraseña por defecto o debe cambiarla
                $esPasswordPorDefecto = password_verify('123456', $usuario['password']);
                $debeCambiar = !empty($usuario['debe_cambiar_password']) || $esPasswordPorDefecto;
                $_SESSION['usuario_debe_cambiar_password'] = $debeCambiar ? 1 : 0;

                if ($debeCambiar) {
                    header('Location: ' . getBaseUrl() . 'cambiar_password.php');
                    exit;
                }

                // Redirigir al Dashboard principal (acceso protegido)
                header('Location: ' . getBaseUrl() . 'dashboard.php');
                exit;
            } else {
                $mensajeError = 'Credenciales incorrectas. Verifique su correo y contraseña.';
                require_once __DIR__ . '/../login.php';
                return;
            }
        }

        // Si es petición GET, mostrar página de login
        require_once __DIR__ . '/../login.php';
    }

    /**
     * Procesa el cambio de contraseña obligatorio o voluntario.
     */
    public function cambiarPassword(): void {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . getBaseUrl() . 'login.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $passwordActual = $_POST['password_actual'] ?? '';
            $passwordNueva = $_POST['password_nueva'] ?? '';
            $passwordConfirmar = $_POST['password_confirmar'] ?? '';

            if (empty($passwordNueva) || empty($passwordConfirmar)) {
                $mensajeError = 'Por favor complete todos los campos de contraseña nueva.';
                require_once __DIR__ . '/../cambiar_password.php';
                return;
            }

            if (strlen($passwordNueva) < 6) {
                $mensajeError = 'La nueva contraseña debe tener al menos 6 caracteres.';
                require_once __DIR__ . '/../cambiar_password.php';
                return;
            }

            if ($passwordNueva !== $passwordConfirmar) {
                $mensajeError = 'Las contraseñas nuevas no coinciden.';
                require_once __DIR__ . '/../cambiar_password.php';
                return;
            }

            if ($passwordNueva === '123456') {
                $mensajeError = 'No puedes usar la contraseña por defecto (123456). Elige una clave personalizada.';
                require_once __DIR__ . '/../cambiar_password.php';
                return;
            }

            $usuarioId = (int)$_SESSION['usuario_id'];
            $exito = $this->usuarioModel->cambiarPassword($usuarioId, $passwordNueva);

            if ($exito) {
                $_SESSION['usuario_debe_cambiar_password'] = 0;
                header('Location: ' . getBaseUrl() . 'dashboard.php?clave=actualizada');
                exit;
            } else {
                $mensajeError = 'Error al actualizar la contraseña en el servidor.';
                require_once __DIR__ . '/../cambiar_password.php';
                return;
            }
        }

        require_once __DIR__ . '/../cambiar_password.php';
    }

    /**
     * El registro público está deshabilitado. Redirige a login.
     */
    public function registro(): void {
        header('Location: login.php');
        exit;
    }

    /**
     * Cierra la sesión activa y redirige al login.
     */
    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ' . getBaseUrl() . 'login.php');
        exit;
    }
}

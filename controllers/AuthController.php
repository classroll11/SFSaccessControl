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
                $_SESSION['usuario_rol'] = $usuario['rol'];
                $_SESSION['usuario_grado'] = $usuario['grado'];
                $_SESSION['usuario_correo'] = $usuario['correo'] ?? $correo;

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
     * Procesa el registro de nuevos usuarios / estudiantes con sus datos completos.
     */
    public function registro(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $documento = trim($_POST['documento'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $grado = trim($_POST['grado'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validaciones básicas
            if (empty($nombre) || empty($documento) || empty($correo) || empty($password)) {
                $mensajeError = 'Todos los campos obligatorios deben ser diligenciados.';
                require_once __DIR__ . '/../registro.php';
                return;
            }

            if ($password !== $confirmPassword) {
                $mensajeError = 'Las contraseñas no coinciden. Por favor verifíquelas.';
                require_once __DIR__ . '/../registro.php';
                return;
            }

            // Verificar si el documento o correo ya existen
            if ($this->usuarioModel->obtenerPorDocumento($documento)) {
                $mensajeError = 'El documento ingresado ya se encuentra registrado en el sistema.';
                require_once __DIR__ . '/../registro.php';
                return;
            }

            if ($this->usuarioModel->obtenerPorCorreo($correo)) {
                $mensajeError = 'El correo electrónico ya se encuentra registrado.';
                require_once __DIR__ . '/../registro.php';
                return;
            }

            // Determinar rol acorde a la selección
            $rol = in_array($grado, ['DOCENTE', 'ADMINISTRATIVO']) ? $grado : 'ESTUDIANTE';

            // Insertar el nuevo usuario en base de datos
            $nuevoId = $this->usuarioModel->crearUsuario([
                'documento' => $documento,
                'correo' => $correo,
                'nombre' => $nombre,
                'grado' => $grado,
                'password' => $password,
                'rol' => $rol,
                'estado' => 'ACTIVO'
            ]);

            if ($nuevoId) {
                // Redirigir al login con mensaje de éxito
                header('Location: login.php?registro=exitoso');
                exit;
            } else {
                $mensajeError = 'Ocurrió un error al registrar el usuario. Inténtelo de nuevo.';
                require_once __DIR__ . '/../registro.php';
                return;
            }
        }

        // Si es GET, mostrar vista de registro
        require_once __DIR__ . '/../registro.php';
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

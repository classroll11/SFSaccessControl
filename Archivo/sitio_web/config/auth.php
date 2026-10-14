<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: config/auth.php
 * DESCRIPCIÓN: Middleware de autenticación, control de roles y cambio forzoso
 *              de contraseña predeterminada.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna true si hay una sesión de usuario activa.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['usuario_id']);
}

/**
 * Verifica que el usuario haya iniciado sesión.
 * Si debe cambiar contraseña, lo redirige forzosamente a cambiar_password.php.
 */
function requireLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        $base = getBaseUrl();
        header('Location: ' . $base . 'login');
        exit;
    }

    // Si los datos en sesión contienen caracteres antiguos o corruptos, sincronizar con la BD
    if (strpos($_SESSION['usuario_nombre'] ?? '', '?') !== false || strpos($_SESSION['usuario_nombre'] ?? '', '├') !== false || strpos($_SESSION['usuario_grado'] ?? '', '?') !== false) {
        try {
            require_once __DIR__ . '/database.php';
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT nombre, grado FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            if ($u = $stmt->fetch()) {
                $_SESSION['usuario_nombre'] = $u['nombre'];
                $_SESSION['usuario_grado']  = $u['grado'];
            }
        } catch (Throwable $t) {
            // Ignorar silenciosamente si hay algún problema transitorio
        }
    }

    // Si el usuario tiene marcada la obligación de cambiar contraseña por defecto
    if (!empty($_SESSION['usuario_debe_cambiar_password'])) {
        $reqUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($reqUri, 'cambiar_password') === false && strpos($reqUri, 'logout') === false) {
            header('Location: ' . getBaseUrl() . 'cambiar_password');
            exit;
        }
    }
}

/**
 * Verifica que el usuario tenga un rol específico dentro de la lista permitida.
 */
function requireRol(array $rolesPermitidos): void {
    requireLogin();
    $rolActual = $_SESSION['usuario_rol'] ?? '';
    if (!in_array($rolActual, $rolesPermitidos) && !in_array('ADMINISTRADOR', $rolesPermitidos)) {
        header('Location: ' . getBaseUrl() . 'dashboard?acceso=denegado');
        exit;
    }
}

/**
 * Retorna la URL base del proyecto de forma dinámica.
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = dirname($_SERVER['SCRIPT_NAME']);
    $base     = rtrim(str_replace('\\', '/', $script), '/');
    return $protocol . '://' . $host . $base . '/';
}

/**
 * Retorna los datos del usuario en sesión como array estructurado.
 */
function getUsuarioSesion(): array {
    return [
        'id'                    => $_SESSION['usuario_id']     ?? null,
        'nombre'                => $_SESSION['usuario_nombre'] ?? 'Usuario',
        'documento'             => $_SESSION['usuario_documento'] ?? '',
        'rol'                   => $_SESSION['usuario_rol']    ?? 'DOCENTE',
        'grado'                 => $_SESSION['usuario_grado']  ?? '',
        'correo'                => $_SESSION['usuario_correo'] ?? '',
        'debe_cambiar_password' => $_SESSION['usuario_debe_cambiar_password'] ?? 0,
    ];
}

/* ==========================================================================
   HELPERS PARA VERIFICACIÓN RÁPIDA DE ROLES
   ========================================================================== */

function esAdmin(): bool {
    return ($_SESSION['usuario_rol'] ?? '') === 'ADMINISTRADOR';
}

function esDocente(): bool {
    return in_array($_SESSION['usuario_rol'] ?? '', ['DOCENTE', 'PROFESOR', 'ADMINISTRADOR']);
}

function esCelador(): bool {
    return in_array($_SESSION['usuario_rol'] ?? '', ['CELADOR', 'PORTERIA', 'ADMINISTRADOR']);
}

function esCoordinador(): bool {
    return in_array($_SESSION['usuario_rol'] ?? '', ['COORDINADOR', 'COORDINACION', 'ADMINISTRADOR']);
}

function esRector(): bool {
    return in_array($_SESSION['usuario_rol'] ?? '', ['RECTOR', 'RECTORIA', 'ADMINISTRADOR']);
}

/**
 * Retorna el rol normalizado en mayúsculas.
 */
function getRolActual(): string {
    return strtoupper($_SESSION['usuario_rol'] ?? 'DOCENTE');
}

/**
 * Retorna la configuración de estilos, títulos e íconos para cada rol del sistema.
 */
function getRolConfig(?string $rol = null): array {
    $rol = $rol ?? getRolActual();
    $estilosRol = [
        'ADMINISTRADOR' => ['titulo'=>'ADMINISTRADOR DEL SISTEMA','badge_bg'=>'linear-gradient(135deg,#7c3aed,#a855f7)','badge_txt'=>'#fff','icono'=>'fa-solid fa-crown','icono_color'=>'#fbbf24','tag'=>'Control Total'],
        'DOCENTE'       => ['titulo'=>'DOCENTE DE AULA','badge_bg'=>'linear-gradient(135deg,#1d4ed8,#3b82f6)','badge_txt'=>'#fff','icono'=>'fa-solid fa-chalkboard-user','icono_color'=>'#60a5fa','tag'=>'Asistencia de Clases'],
        'CELADOR'       => ['titulo'=>'PORTERÍA Y SEGURIDAD','badge_bg'=>'linear-gradient(135deg,#059669,#10b981)','badge_txt'=>'#fff','icono'=>'fa-solid fa-shield-halved','icono_color'=>'#34d399','tag'=>'Control de Portería'],
        'COORDINADOR'   => ['titulo'=>'COORDINACIÓN INSTITUCIONAL','badge_bg'=>'linear-gradient(135deg,#d97706,#f59e0b)','badge_txt'=>'#fff','icono'=>'fa-solid fa-clipboard-check','icono_color'=>'#fbbf24','tag'=>'Auditoría y Faltas'],
        'RECTOR'        => ['titulo'=>'RECTORÍA EJECUTIVA','badge_bg'=>'linear-gradient(135deg,#0f172a,#1e293b)','badge_txt'=>'#38bdf8','icono'=>'fa-solid fa-building-columns','icono_color'=>'#38bdf8','tag'=>'Métricas Estratégicas'],
    ];
    return $estilosRol[$rol] ?? $estilosRol['DOCENTE'];
}

/**
 * Retorna las iniciales (1 o 2 letras) a partir de un nombre completo.
 */
function getUsuarioIniciales(string $nombre): string {
    $partes = explode(' ', trim($nombre));
    return strtoupper(substr($partes[0] ?? 'U', 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
}

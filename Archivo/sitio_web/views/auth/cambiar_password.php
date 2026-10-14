<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: cambiar_password.php
 * DESCRIPCIÓN: Interfaz obligatoria de cambio de contraseña.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$mensajeError = '';
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuarioRol    = $_SESSION['usuario_rol']    ?? 'USUARIO';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = trim($_POST['password_nueva']     ?? '');
    $p2 = trim($_POST['password_confirmar'] ?? '');

    if (!$p1 || !$p2) {
        $mensajeError = 'Por favor completa todos los campos.';
    } elseif (strlen($p1) < 6) {
        $mensajeError = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($p1 !== $p2) {
        $mensajeError = 'Las contraseñas no coinciden.';
    } elseif ($p1 === '123456') {
        $mensajeError = 'No puedes usar la contraseña por defecto (123456). Elige una clave personal.';
    } else {
        $um = new UsuarioModel();
        if ($um->cambiarPassword((int)$_SESSION['usuario_id'], $p1)) {
            $_SESSION['usuario_debe_cambiar_password'] = 0;
            header('Location: dashboard.php?clave=actualizada_exito');
            exit;
        }
        $mensajeError = 'Error al guardar la contraseña. Inténtalo de nuevo.';
    }
}

$rolLabels = [
    'ADMINISTRADOR' => 'Administrador', 'RECTOR'    => 'Rector',
    'COORDINADOR'   => 'Coordinador',   'DOCENTE'   => 'Docente',
    'CELADOR'       => 'Celador Portería',
];
$rolLabel = $rolLabels[$usuarioRol] ?? $usuarioRol;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Contraseña · SFS Access Control</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-page:   #050d1f;
            --bg-card:   #0d1b3e;
            --bg-panel:  rgba(13, 27, 62, 0.85);
            --blue:      #2563eb;
            --cyan:      #00c6ff;
            --accent:    linear-gradient(135deg, #2563eb 0%, #00c6ff 100%);
            --border:    rgba(56, 189, 248, 0.2);
            --text-main: #f1f5f9;
            --text-muted:#94a3b8;
            --danger:    #ef4444;
            --success:   #10b981;
            --warn:      #f59e0b;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-page);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* ── Fondo animado ─────────────────────────────────────────────── */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 60% 45% at 20% 15%, rgba(37,99,235,.22) 0%, transparent 70%),
                radial-gradient(ellipse 50% 40% at 80% 80%, rgba(0,198,255,.14) 0%, transparent 60%);
            pointer-events: none; z-index: 0;
        }

        /* ── Navbar ────────────────────────────────────────────────────── */
        .navbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: .9rem 2rem;
            background: rgba(5, 13, 31, .85);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }
        .logo { display: flex; align-items: center; gap: .75rem; text-decoration: none; }
        .logo img { width: 38px; height: 38px; border-radius: 10px; object-fit: contain; background: #ffffff; padding: 2px; }
        .logo-text strong { display: block; font-size: 1rem; font-weight: 800; color: #fff; letter-spacing: .5px; }
        .logo-text strong span { color: var(--cyan); }
        .logo-text small { font-size: .65rem; font-weight: 500; color: var(--text-muted); letter-spacing: 1.5px; text-transform: uppercase; }
        .nav-logout { display: flex; align-items: center; gap: .4rem; text-decoration: none;
            color: #fca5a5; font-size: .83rem; font-weight: 600; padding: .4rem .9rem;
            border: 1px solid rgba(239,68,68,.3); border-radius: 8px;
            transition: all .2s ease; }
        .nav-logout:hover { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.5); }

        /* ── Página principal ──────────────────────────────────────────── */
        .page {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 2.5rem 1.5rem;
            position: relative; z-index: 1;
        }

        .layout {
            display: grid;
            grid-template-columns: 280px 1fr 280px;
            gap: 1.8rem;
            max-width: 980px;
            width: 100%;
            align-items: center;
        }

        /* ── Tarjetas laterales ────────────────────────────────────────── */
        .panel-col { display: flex; flex-direction: column; gap: 1.2rem; }

        .info-card {
            background: var(--bg-panel);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.6rem 1.4rem;
            text-align: center;
            position: relative; overflow: hidden;
            transition: transform .25s ease, border-color .25s ease;
        }
        .info-card::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(37,99,235,.05) 0%, transparent 70%);
            pointer-events: none;
        }
        .info-card:hover { transform: translateY(-3px); border-color: rgba(56,189,248,.45); }

        .info-card .ic-icon {
            width: 54px; height: 54px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin: 0 auto 1rem;
        }
        .info-card h4 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: .5rem; }
        .info-card p  { font-size: .82rem; line-height: 1.65; color: var(--text-muted); }

        /* ── Tarjeta de formulario (centro) ───────────────────────────── */
        .form-card {
            background: #0b1635;
            border: 1px solid rgba(56,189,248,.25);
            border-radius: 22px;
            padding: 2.4rem 2.2rem;
            box-shadow: 0 30px 70px -15px rgba(0,0,0,.6), 0 0 40px rgba(0,198,255,.08);
        }

        .fc-header { text-align: center; margin-bottom: 1.8rem; }
        .fc-icon-wrap {
            width: 72px; height: 72px; border-radius: 20px; margin: 0 auto 1.1rem;
            background: rgba(37,99,235,.18);
            border: 1px solid rgba(56,189,248,.35);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.9rem; color: #38bdf8;
            box-shadow: 0 0 30px rgba(0,198,255,.15);
        }
        .fc-title { font-size: 1.45rem; font-weight: 800; color: #fff; }
        .fc-sub { font-size: .84rem; color: var(--text-muted); margin-top: .35rem; }
        .fc-sub strong { color: #38bdf8; }

        /* ── Alerta ───────────────────────────────────────────────────── */
        .alert-err {
            display: flex; align-items: center; gap: .6rem;
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.35);
            color: #fca5a5; font-size: .84rem;
            padding: .75rem 1rem; border-radius: 10px; margin-bottom: 1.4rem;
        }

        /* ── Formulario ───────────────────────────────────────────────── */
        .form-label {
            display: block; font-size: .75rem; font-weight: 700;
            color: #cbd5e1; text-transform: uppercase; letter-spacing: .6px;
            margin-bottom: .45rem;
        }
        .form-label i { color: #38bdf8; margin-right: 4px; }

        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%; padding: .78rem 2.8rem .78rem 1rem;
            background: rgba(10,20,50,.65);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 10px; color: #fff; font-family: 'Outfit', sans-serif;
            font-size: .95rem; outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .input-wrap input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56,189,248,.15);
        }
        .input-wrap input::placeholder { color: #475569; }
        .toggle-btn {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #475569; cursor: pointer;
            font-size: .9rem; transition: color .2s;
        }
        .toggle-btn:hover { color: #38bdf8; }

        .strength-bar { height: 4px; border-radius: 99px; background: #1e293b; margin-top: 7px; overflow: hidden; }
        .strength-fill { height: 100%; width: 0; transition: width .3s ease, background .3s ease; border-radius: 99px; }
        .strength-hint { font-size: .72rem; color: #475569; margin-top: 4px; display: block; }

        .field-group { margin-bottom: 1.2rem; }

        /* ── Botón principal ──────────────────────────────────────────── */
        .btn-save {
            width: 100%; padding: .88rem; border: none; border-radius: 11px; cursor: pointer;
            background: var(--accent);
            color: #fff; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: .97rem;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            box-shadow: 0 6px 20px rgba(37,99,235,.4);
            transition: opacity .2s ease, transform .15s ease;
            margin-top: 1.5rem;
        }
        .btn-save:hover { opacity: .92; transform: translateY(-1px); }
        .btn-save:active { transform: translateY(0); }

        .fc-footer { text-align: center; margin-top: 1.2rem; padding-top: .9rem; border-top: 1px solid rgba(255,255,255,.07); }
        .fc-footer a { color: #64748b; font-size: .8rem; text-decoration: none; transition: color .2s; }
        .fc-footer a:hover { color: #94a3b8; }

        /* ── Responsive ───────────────────────────────────────────────── */
        @media (max-width: 860px) {
            .layout { grid-template-columns: 1fr; max-width: 480px; }
            .panel-col { flex-direction: row; }
        }
        @media (max-width: 560px) {
            .panel-col { flex-direction: column; }
            .form-card { padding: 1.8rem 1.4rem; }
        }
    </style>
</head>
<body>

<!-- ── NAVBAR ─────────────────────────────────────────────────────────── -->
<nav class="navbar">
    <a href="index.php" class="logo">
        <img src="assets/img/logo.jpeg" alt="SFS Logo" onerror="this.src='logo.jpeg'">
        <div class="logo-text">
            <strong>SFS <span>ACCESS</span></strong>
            <small>Control Biométrico</small>
        </div>
    </a>
    <a href="logout.php" class="nav-logout">
        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
    </a>
</nav>

<!-- ── PÁGINA ─────────────────────────────────────────────────────────── -->
<div class="page">
    <div class="layout">

        <!-- Columna izquierda -->
        <div class="panel-col">
            <div class="info-card">
                <div class="ic-icon" style="background:rgba(56,189,248,.15); color:#38bdf8;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h4>Seguridad Obligatoria</h4>
                <p>Ingresaste con la contraseña temporal <strong style="color:#38bdf8;">123456</strong>. Debes establecer tu clave personal antes de continuar.</p>
            </div>
            <div class="info-card">
                <div class="ic-icon" style="background:rgba(52,211,153,.15); color:#34d399;">
                    <i class="fa-solid fa-lightbulb"></i>
                </div>
                <h4>Recomendaciones</h4>
                <p>Usa mínimo 6 caracteres. Combina letras, números y símbolos para mayor seguridad.</p>
            </div>
        </div>

        <!-- Formulario central -->
        <div class="form-card">
            <div class="fc-header">
                <div class="fc-icon-wrap">
                    <i class="fa-solid fa-lock-open"></i>
                </div>
                <div class="fc-title">Configurar Contraseña</div>
                <div class="fc-sub">
                    Bienvenido(a), <strong><?= htmlspecialchars($usuarioNombre) ?></strong>
                    <br><span style="color:#64748b;font-size:.78rem;"><?= htmlspecialchars($rolLabel) ?></span>
                </div>
            </div>

            <?php if ($mensajeError): ?>
            <div class="alert-err">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($mensajeError) ?></span>
            </div>
            <?php endif; ?>

            <form action="cambiar_password.php" method="POST" onsubmit="return validarForm()">

                <div class="field-group">
                    <label class="form-label" for="password_nueva">
                        <i class="fa-solid fa-key"></i> Nueva Contraseña
                    </label>
                    <div class="input-wrap">
                        <input type="password" id="password_nueva" name="password_nueva"
                               placeholder="Mínimo 6 caracteres" required minlength="6"
                               oninput="evalStrength(this.value)" autocomplete="new-password">
                        <button type="button" class="toggle-btn" onclick="toggleVis('password_nueva', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="sf"></div></div>
                    <small class="strength-hint" id="sh">Ingresa tu nueva contraseña personal.</small>
                </div>

                <div class="field-group">
                    <label class="form-label" for="password_confirmar">
                        <i class="fa-solid fa-check-double"></i> Confirmar Contraseña
                    </label>
                    <div class="input-wrap">
                        <input type="password" id="password_confirmar" name="password_confirmar"
                               placeholder="Repite la contraseña" required minlength="6"
                               autocomplete="new-password">
                        <button type="button" class="toggle-btn" onclick="toggleVis('password_confirmar', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-shield-check"></i> Guardar y Entrar al Sistema
                </button>
            </form>

            <div class="fc-footer">
                <a href="logout.php"><i class="fa-solid fa-arrow-left"></i> Cerrar sesión y salir</a>
            </div>
        </div>

        <!-- Columna derecha -->
        <div class="panel-col">
            <div class="info-card">
                <div class="ic-icon" style="background:rgba(168,85,247,.15); color:#a855f7;">
                    <i class="fa-solid fa-user-lock"></i>
                </div>
                <h4>Privacidad Total</h4>
                <p>Tu nueva clave es completamente privada. Ni el administrador podrá verla una vez guardada.</p>
            </div>
            <div class="info-card">
                <div class="ic-icon" style="background:rgba(245,158,11,.15); color:#f59e0b;">
                    <i class="fa-solid fa-rotate-right"></i>
                </div>
                <h4>¿Olvidaste tu clave?</h4>
                <p>Si necesitas restablecer tu contraseña en el futuro, contacta al administrador del sistema.</p>
            </div>
        </div>

    </div>
</div>

<script>
function toggleVis(id, btn) {
    const inp = document.getElementById(id);
    const ico = btn.querySelector('i');
    inp.type = (inp.type === 'password') ? 'text' : 'password';
    ico.className = (inp.type === 'text') ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}

function evalStrength(v) {
    const fill = document.getElementById('sf');
    const hint = document.getElementById('sh');
    if (!v) { fill.style.width='0%'; hint.textContent='Ingresa tu nueva contraseña personal.'; hint.style.color='#475569'; return; }
    if (v === '123456') {
        fill.style.width='15%'; fill.style.background='#ef4444';
        hint.textContent='⚠ Contraseña no permitida (es la clave por defecto).';
        hint.style.color='#ef4444'; return;
    }
    let s = 0;
    if (v.length >= 6)  s += 30;
    if (v.length >= 9)  s += 20;
    if (/[A-Z]/.test(v)) s += 20;
    if (/[0-9]/.test(v)) s += 15;
    if (/[^A-Za-z0-9]/.test(v)) s += 15;
    fill.style.width = s + '%';
    if (s < 40)      { fill.style.background='#ef4444'; hint.textContent='Contraseña débil'; hint.style.color='#ef4444'; }
    else if (s < 75) { fill.style.background='#f59e0b'; hint.textContent='Contraseña aceptable'; hint.style.color='#f59e0b'; }
    else             { fill.style.background='#10b981'; hint.textContent='¡Contraseña segura!'; hint.style.color='#10b981'; }
}

function validarForm() {
    const p1 = document.getElementById('password_nueva').value;
    const p2 = document.getElementById('password_confirmar').value;
    if (p1 === '123456') { alert('No puedes usar 123456 como tu contraseña.'); return false; }
    if (p1.length < 6)   { alert('La contraseña debe tener mínimo 6 caracteres.'); return false; }
    if (p1 !== p2)        { alert('Las contraseñas no coinciden.'); return false; }
    return true;
}
</script>
</body>
</html>

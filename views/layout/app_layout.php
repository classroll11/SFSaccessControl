<?php
/**
 * views/layout/app_layout.php
 * Layout compartido: incluye sidebar + topnav + head + footer.
 * 
 * Variables que el archivo padre debe definir ANTES de incluir este layout:
 *   $paginaActual  (string) - slug de la página: 'panel', 'asistencia', 'faltas', etc.
 *   $tituloPagina  (string) - Título para el <title>
 *   $contenido     (string) - HTML del contenido principal (ob_get_clean())
 *   $configRol     (array)  - Configuración del rol del usuario activo
 *   $nombreSesion  (string) - Nombre del usuario
 *   $rolActual     (string) - Rol actual
 *   $iniciales     (string) - Iniciales del nombre
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Panel') ?> · SFS Access Control</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── Variables ────────────────────────────────────────── */
        :root {
            --primary:      #0a1128;
            --primary-dark: #060b18;
            --accent-blue:  #2563eb;
            --accent-cyan:  #00f2fe;
            --accent-glow:  #38bdf8;
            --bg-body:      #f0f4f8;
            --card-radius:  18px;
            --transition:   all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3, h4, h5, h6 { font-family: 'Outfit', sans-serif; font-weight: 700; letter-spacing: -0.02em; }

        .app-wrapper { display: flex; min-height: 100vh; width: 100%; }

        /* ════════════════════════════════════════════════════════
           SIDEBAR
           ════════════════════════════════════════════════════════ */
        .sidebar {
            width: 275px; min-width: 275px;
            background: linear-gradient(180deg, #07111f 0%, #050d1a 100%);
            color: #f1f5f9;
            height: 100vh;
            position: sticky; top: 0;
            overflow-y: auto; overflow-x: hidden;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(56,189,248,0.1);
            z-index: 1040;
            transition: transform 0.32s cubic-bezier(0.4,0,0.2,1);
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(56,189,248,0.18); border-radius: 4px; }

        /* Marca */
        .sb-brand {
            padding: 1.1rem 1.3rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(0,0,0,0.25);
        }
        .sb-logo-box {
            width: 36px; height: 36px; border-radius: 10px;
            background: #ffffff; border: 1.5px solid rgba(56,189,248,0.5);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            padding: 3px;
        }
        .sb-logo-box img {
            width: 100%; height: 100%; object-fit: contain;
        }

        /* Tarjeta de usuario */
        .sb-user-card {
            margin: 0.9rem 0.85rem 0;
            padding: 0.85rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 13px;
            display: flex; align-items: center; gap: 10px;
        }
        .sb-avatar {
            width: 40px; height: 40px; border-radius: 11px;
            font-weight: 800; font-size: 0.9rem;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35);
            border: 1.5px solid rgba(255,255,255,0.18);
            color: #fff;
        }

        /* Secciones del menú */
        .sb-nav { padding: 0.4rem 0 1rem; flex-grow: 1; }
        .sb-section { margin: 0 0.7rem; }
        .sb-section + .sb-section {
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: 0.25rem; padding-top: 0.15rem;
        }

        /* Encabezado de grupo */
        .sb-heading {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.64rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1px;
            color: #475569;
            padding: 0.85rem 0.4rem 0.3rem;
        }
        .sb-heading .sh-dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
        .sb-heading .sh-line { flex: 1; height: 1px; background: rgba(255,255,255,0.07); }

        /* Ítem de navegación */
        .sb-item {
            display: flex; align-items: center; gap: 9px;
            padding: 0.58rem 0.75rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.83rem; font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s ease;
            margin-bottom: 0.15rem;
            border: 1px solid transparent;
            cursor: pointer;
            position: relative;
        }
        .sb-item::before {
            content: ''; position: absolute;
            left: 0; top: 22%; bottom: 22%;
            width: 3px; border-radius: 0 3px 3px 0;
            background: transparent; transition: background 0.2s ease;
        }
        .sb-item:hover {
            color: #e2e8f0; background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.07);
            transform: translateX(3px);
        }
        .sb-item.active {
            color: #fff;
            background: rgba(37,99,235,0.22);
            border-color: rgba(56,189,248,0.3);
            box-shadow: 0 3px 12px rgba(0,198,255,0.08);
        }
        .sb-item.active::before { background: #38bdf8; }

        /* Ícono */
        .sb-icon {
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.84rem; border-radius: 8px;
            flex-shrink: 0; transition: all 0.2s ease;
            background: rgba(255,255,255,0.05); color: #64748b;
        }
        .sb-item:hover .sb-icon { background: rgba(255,255,255,0.1); color: #e2e8f0; }
        .sb-item.active .sb-icon { color: #fff; box-shadow: 0 0 10px rgba(56,189,248,0.35); }

        /* Badge */
        .sb-badge {
            margin-left: auto; font-size: 0.62rem; font-weight: 700;
            padding: 0.12rem 0.45rem; border-radius: 20px;
            white-space: nowrap; letter-spacing: 0.3px;
        }

        /* Ítem de peligro */
        .sb-item.danger { color: #f87171; }
        .sb-item.danger .sb-icon { color: #f87171; background: rgba(239,68,68,0.1); }
        .sb-item.danger:hover { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.2); color: #fca5a5; }
        .sb-item.danger::before { display: none; }

        /* Footer del sidebar */
        .sb-footer {
            padding: 0.8rem 1rem;
            border-top: 1px solid rgba(255,255,255,0.07);
            background: rgba(0,0,0,0.3);
            margin-top: auto;
        }

        /* Backdrop móvil */
        .sb-backdrop {
            position: fixed; inset: 0;
            background: rgba(4,10,20,0.75);
            backdrop-filter: blur(4px);
            z-index: 1035; display: none; opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sb-backdrop.show { display: block; opacity: 1; }

        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed; left: 0; top: 0; bottom: 0;
                transform: translateX(-100%);
                box-shadow: 4px 0 40px rgba(0,0,0,0.7);
            }
            .sidebar.open { transform: translateX(0); }
        }

        /* ── Área de contenido ───────────────────────────────── */
        .main-content { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; }

        /* ── Top Navbar ──────────────────────────────────────── */
        .top-nav {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.65rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 1020;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        }

        /* ── Cards & Superficies ─────────────────────────────── */
        .card-surface {
            background: #fff;
            border-radius: var(--card-radius);
            padding: 1.6rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
        }

        /* ── KPI Cards ───────────────────────────────────────── */
        .kpi-card {
            background: #fff; border-radius: 16px;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .kpi-icon-wrapper { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .kpi-green  { background: rgba(16,185,129,0.12); color: #10b981; }
        .kpi-blue   { background: rgba(37,99,235,0.12);  color: #2563eb; }
        .kpi-amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
        .kpi-rose   { background: rgba(239,68,68,0.12);  color: #ef4444; }
        .kpi-purple { background: rgba(168,85,247,0.12); color: #a855f7; }

        /* ── Hero Section ────────────────────────────────────── */
        .page-hero {
            background: radial-gradient(circle at 50% 20%, #112349 0%, #060b18 90%);
            color: #fff; padding: 2rem 1.5rem; margin-bottom: 0;
        }

        /* ── Tabla Premium ───────────────────────────────────── */
        .table-pro thead { background: #f8fafc; }
        .table-pro thead th { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 0.7rem 1rem; }
        .table-pro tbody tr { transition: background 0.15s ease; }
        .table-pro tbody tr:hover { background: #f8fafc; }
        .table-pro td { font-size: 0.88rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; padding: 0.7rem 1rem; }

        /* ── Badges de estado ────────────────────────────────── */
        .pill-badge { display: inline-flex; align-items: center; gap: 5px; padding: 0.28rem 0.75rem; border-radius: 99px; font-size: 0.74rem; font-weight: 700; letter-spacing: 0.3px; }
        .badge-in       { background: rgba(16,185,129,0.15); color: #065f46; border: 1px solid rgba(16,185,129,0.3); }
        .badge-out      { background: rgba(245,158,11,0.15);  color: #92400e; border: 1px solid rgba(245,158,11,0.3); }
        .badge-approved { background: rgba(37,99,235,0.12);   color: #1e40af; border: 1px solid rgba(37,99,235,0.2); }
        .badge-denied   { background: rgba(239,68,68,0.12);   color: #991b1b; border: 1px solid rgba(239,68,68,0.2); }

        /* ── Avatar Iniciales ────────────────────────────────── */
        .avatar-initials {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, #2563eb, #00c6ff);
            color: #fff; font-weight: 800; font-size: 0.82rem;
            display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        /* ── Live dot ────────────────────────────────────────── */
        .live-dot {
            display: inline-block; width: 7px; height: 7px; border-radius: 50%;
            background: #10b981; animation: pulse-dot 1.8s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: .5; transform: scale(1.4); }
        }

        /* ── Biometric scanner ───────────────────────────────── */
        .biometric-scanner-box {
            background: radial-gradient(circle, #0d2847 0%, #060b18 100%);
            border: 2px solid rgba(56,189,248,0.4);
            border-radius: 20px; padding: 2rem;
            text-align: center; color: #fff; position: relative; overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .scanner-beam {
            position: absolute; top: 0; left: 0; width: 100%; height: 3px;
            background: linear-gradient(90deg, transparent, #38bdf8, transparent);
            animation: scan 2.5s linear infinite;
        }
        @keyframes scan { from { top: 0; } to { top: 100%; } }

        /* ── Finger slots ────────────────────────────────────── */
        .finger-slots-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .finger-slot {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 12px 16px;
            min-height: 130px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 18px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            overflow: hidden;
        }
        .finger-slot::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            opacity: 0;
            transition: opacity 0.25s ease;
            background: radial-gradient(ellipse at center, rgba(56,189,248,0.12) 0%, transparent 70%);
        }
        .finger-slot:hover { border-color: #38bdf8; background: #f0f9ff; }
        .finger-slot:hover::before { opacity: 1; }
        .finger-slot.registered {
            background: linear-gradient(135deg, rgba(16,185,129,0.06) 0%, rgba(5,150,105,0.04) 100%);
            border: 2px solid rgba(16,185,129,0.45);
            box-shadow: 0 2px 12px rgba(16,185,129,0.1);
        }
        .finger-slot.registered::before {
            background: radial-gradient(ellipse at center, rgba(16,185,129,0.1) 0%, transparent 70%);
        }
        .finger-slot.registered:hover {
            background: linear-gradient(135deg, rgba(239,68,68,0.06) 0%, rgba(220,38,38,0.04) 100%);
            border-color: rgba(239,68,68,0.5);
            box-shadow: 0 2px 12px rgba(239,68,68,0.1);
        }
        .slot-badge {
            position: absolute;
            top: 8px; right: 8px;
            width: 20px; height: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(16,185,129,0.35);
        }
        .slot-delete {
            position: absolute;
            top: 8px; right: 8px;
            width: 22px; height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            font-size: 0.6rem;
            display: none;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(239,68,68,0.35);
            transition: transform 0.2s ease;
        }
        .slot-delete:hover { transform: scale(1.15); }
        .finger-slot.registered:hover .slot-delete { display: flex; }
        .finger-slot.registered:hover .slot-badge  { display: none; }
        .slot-number-badge {
            position: absolute;
            top: 8px; left: 10px;
            font-size: 0.6rem;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 0.03em;
        }
        .finger-slot.registered .slot-number-badge { color: #6ee7b7; }
        .huella-progress { height: 5px; border-radius: 99px; background: #e2e8f0; }
        .huella-progress-bar { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #2563eb, #00f2fe); }

        /* ── Tabs ────────────────────────────────────────────── */
        .dash-tabs { border-bottom: none; gap: 4px; padding-top: 8px; }
        .dash-tabs .nav-link { border-radius: 10px 10px 0 0; color: #64748b; font-weight: 600; font-size: 0.85rem; border: 1px solid transparent; padding: 0.5rem 1.2rem; }
        .dash-tabs .nav-link.active { color: #2563eb; background: #fff; border-color: #e2e8f0 #e2e8f0 #fff; }
        .dash-tabs .nav-link:hover:not(.active) { background: rgba(37,99,235,0.06); color: #2563eb; }

        /* ── Student cards ───────────────────────────────────── */
        .student-finger-card:hover { background: #f0f7ff !important; border-color: #2563eb !important; }
        .student-finger-card.selected { background: rgba(37,99,235,0.08) !important; border-color: #2563eb !important; box-shadow: 0 0 0 2px rgba(37,99,235,0.2); }

        /* ── Attendance button group ─────────────────────────── */
        .asist-btn-group {
            pointer-events: none !important;
            user-select: none;
        }
        .asist-btn-group .btn {
            pointer-events: none !important;
            cursor: default !important;
        }
        .asist-btn-group .btn-check:checked + .btn-outline-success { background: #10b981; color: #fff; border-color: #10b981; }
        .asist-btn-group .btn-check:checked + .btn-outline-danger  { background: #ef4444; color: #fff; border-color: #ef4444; }
        .asist-btn-group .btn-check:checked + .btn-outline-warning { background: #f59e0b; color: #fff; border-color: #f59e0b; }
        .asist-btn-group .btn-check:checked + .btn-outline-info    { background: #0284c7; color: #fff; border-color: #0284c7; }

        /* ── Page Hero Banner ────────────────────────────────── */
        .page-hero { background: radial-gradient(circle at 50% 20%, #112349 0%, #060b18 90%); color: #fff; padding: 1.8rem 1.5rem 2rem; }
        .page-hero h1 { font-size: 1.6rem; }

        /* ── Footer ──────────────────────────────────────────── */
        .app-footer { background: #fff; border-top: 1px solid #e2e8f0; padding: 0.8rem 1.5rem; text-align: center; color: #94a3b8; font-size: 0.78rem; margin-top: auto; }

        /* ══════════════════════════════════════════════════════
           CORRECCIONES DE CONTRASTE Y LEGIBILIDAD GLOBAL
           Soluciona "no se ven las letras" en recuadros/badges
           ══════════════════════════════════════════════════════ */

        /* Textos base en tarjetas y superficies */
        .card-surface, .kpi-card {
            color: #1e293b !important;
        }
        .card-surface h4, .card-surface h5, .card-surface h6,
        .kpi-card h2, .kpi-card h3, .kpi-card h4, .kpi-card h5 {
            color: #0f172a !important;
        }
        .card-surface .text-muted,
        .kpi-card .text-muted {
            color: #475569 !important;
        }

        /* ── Badges de Estado con buen contraste ─────────────── */
        /* Entrada / Éxito sólido */
        .badge.bg-success { background-color: #059669 !important; color: #ffffff !important; }
        /* Salida / Advertencia sólido */
        .badge.bg-warning  { background-color: #d97706 !important; color: #ffffff !important; }
        /* Peligro / Denegado sólido */
        .badge.bg-danger   { background-color: #dc2626 !important; color: #ffffff !important; }
        /* Info sólido */
        .badge.bg-info     { background-color: #0284c7 !important; color: #ffffff !important; }
        /* Gris */
        .badge.bg-secondary { background-color: #475569 !important; color: #ffffff !important; }
        /* Badges claros con texto oscuro */
        .badge.bg-light    { background-color: #f1f5f9 !important; color: #334155 !important; border: 1px solid #cbd5e1 !important; }

        /* ── Badges semitransparentes → hacerlos sólidos y legibles ── */
        /* Entrada (verde semitransparente) */
        .badge.bg-success.bg-opacity-15,
        .badge.bg-success.bg-opacity-20,
        .badge.bg-success.bg-opacity-25 {
            background-color: #d1fae5 !important;
            color: #065f46 !important;
            border: 1px solid #6ee7b7 !important;
        }
        /* Salida (amarillo semitransparente) */
        .badge.bg-warning.bg-opacity-15,
        .badge.bg-warning.bg-opacity-20,
        .badge.bg-warning.bg-opacity-25 {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            border: 1px solid #fcd34d !important;
        }
        /* Peligro semitransparente */
        .badge.bg-danger.bg-opacity-15,
        .badge.bg-danger.bg-opacity-20,
        .badge.bg-danger.bg-opacity-25 {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
            border: 1px solid #fca5a5 !important;
        }
        /* Info semitransparente */
        .badge.bg-info.bg-opacity-15,
        .badge.bg-info.bg-opacity-20,
        .badge.bg-info.bg-opacity-25 {
            background-color: #e0f2fe !important;
            color: #075985 !important;
            border: 1px solid #7dd3fc !important;
        }
        /* Primario semitransparente */
        .badge.bg-primary.bg-opacity-10,
        .badge.bg-primary.bg-opacity-15,
        .badge.bg-primary.bg-opacity-20 {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
            border: 1px solid #93c5fd !important;
        }

        /* ── Texto sobre fondos semitransparentes (Bootstrap) ─── */
        .text-success { color: #059669 !important; }
        .text-warning { color: #b45309 !important; }
        .text-danger  { color: #dc2626 !important; }
        .text-info    { color: #0284c7 !important; }
        .text-muted   { color: #475569 !important; }
        .text-primary { color: #2563eb !important; }
        .text-dark    { color: #0f172a !important; }
        .text-white   { color: #ffffff !important; }
        /* Excepciones para elementos sobre fondos oscuros */
        .page-hero .text-white, .page-hero .text-white-50,
        .biometric-scanner-box .text-white,
        [style*="background: radial-gradient"] .text-white {
            color: #ffffff !important;
        }
        .text-white-50 { color: rgba(255,255,255,0.65) !important; }

        /* ── KPI Labels y valores ─────────────────────────────── */
        .kpi-card span.text-muted { color: #64748b !important; font-weight: 600; }
        .kpi-card h2.text-success  { color: #059669 !important; }
        .kpi-card h2.text-primary  { color: #2563eb !important; }
        .kpi-card h2.text-warning  { color: #d97706 !important; }
        .kpi-card h2.text-danger   { color: #dc2626 !important; }

        /* ── Tabla con buen contraste ─────────────────────────── */
        .table thead th { color: #334155 !important; }
        .table tbody td { color: #1e293b !important; }
        .table tbody .text-muted { color: #64748b !important; }
        .table-light th { color: #334155 !important; background: #f1f5f9 !important; }

        /* ── Recuadros de info clara sobre blanco ─────────────── */
        .bg-light .text-muted, .rounded-3.bg-light .text-muted,
        .p-3.bg-light .text-muted { color: #475569 !important; }
        .bg-light strong, .bg-light .fw-bold { color: #0f172a !important; }

        /* ── Formularios dentro de recuadros ──────────────────── */
        .form-control, .form-select {
            color: #1e293b !important;
        }
        .form-control::placeholder { color: #94a3b8 !important; }

        /* ── Protocolo Steps (listas en portería / asistencia) ── */
        strong.d-block.text-dark { color: #0f172a !important; }
        .list-unstyled .text-muted { color: #475569 !important; }

        /* ── Biometric scanner (texto interior claro) ─────────── */
        .biometric-scanner-box h6,
        [style*="background: radial-gradient"] h6 {
            color: #ffffff !important;
        }

        <?= $estiloExtra ?? '' ?>
    </style>
</head>
<body>
<div class="app-wrapper">

    <!-- Backdrop móvil -->
    <div class="sb-backdrop" id="sb-backdrop" onclick="sbClose()"></div>

    <!-- ══════════════════════════════════════════════════
         SIDEBAR
         ══════════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar">

        <!-- Marca -->
        <div class="sb-brand">
            <a href="dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
                <div class="sb-logo-box">
                    <img src="assets/img/logo.jpeg" alt="SFS Access" style="width:100%;height:100%;object-fit:contain;"
                         onerror="this.src='logo.jpeg'">
                </div>
                <div>
                    <span class="d-block text-white fw-bold" style="font-family:'Outfit',sans-serif; font-size:1rem; letter-spacing:-0.3px;">SFS ACCESS</span>
                    <small class="d-block text-info fw-semibold" style="font-size:0.64rem; margin-top:-2px;">I.E. JORGE ROBLEDO</small>
                </div>
            </a>
            <button class="btn p-1 text-white-50 d-lg-none" onclick="sbClose()" style="background:none;border:none;">
                <i class="fa-solid fa-xmark fs-5"></i>
            </button>
        </div>

        <!-- Tarjeta de usuario -->
        <div class="sb-user-card">
            <div class="sb-avatar" style="background:<?= $configRol['badge_bg'] ?>;">
                <?= $iniciales ?>
            </div>
            <div style="min-width:0; flex:1;">
                <strong class="d-block text-white text-truncate" style="font-size:0.82rem;"><?= htmlspecialchars($nombreSesion) ?></strong>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="badge" style="background:<?= $configRol['badge_bg'] ?>; color:<?= $configRol['badge_txt'] ?>; font-size:0.6rem; padding:2px 6px;">
                        <i class="<?= $configRol['icono'] ?> me-1" style="color:<?= $configRol['icono_color'] ?>;"></i>
                        <?= $rolActual ?>
                    </span>
                    <span class="ms-auto d-flex align-items-center gap-1" style="font-size:0.65rem; color:#34d399;">
                        <span class="live-dot" style="width:5px;height:5px;"></span> En línea
                    </span>
                </div>
            </div>
        </div>

        <!-- Navegación -->
        <nav class="sb-nav">

            <!-- ── GENERAL ── -->
            <div class="sb-section">
                <div class="sb-heading">
                    <span class="sh-dot" style="background:#38bdf8;"></span>
                    <span>General</span>
                    <span class="sh-line"></span>
                </div>

                <a class="sb-item <?= ($paginaActual === 'panel') ? 'active' : '' ?>" href="dashboard.php">
                    <span class="sb-icon" style="<?= ($paginaActual === 'panel') ? 'background:rgba(37,99,235,0.3);color:#60a5fa;' : 'background:rgba(37,99,235,0.15);color:#60a5fa;' ?>">
                        <i class="fa-solid fa-gauge-high"></i>
                    </span>
                    <span>Panel Principal</span>
                    <span class="sb-badge" style="background:rgba(16,185,129,0.18);color:#34d399;border:1px solid rgba(16,185,129,0.3);">En vivo</span>
                </a>

                <?php if (esAdmin() || esCelador() || esCoordinador() || esRector()): ?>
                <a class="sb-item <?= ($paginaActual === 'historial') ? 'active' : '' ?>" href="historial.php">
                    <span class="sb-icon" style="background:rgba(56,189,248,0.12);color:#38bdf8;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>
                    <span>Historial de Accesos</span>
                </a>
                <?php endif; ?>
            </div>

            <?php if (esDocente() || esAdmin()): ?>
            <!-- ── MÓDULO DOCENTE ── -->
            <div class="sb-section">
                <div class="sb-heading">
                    <span class="sh-dot" style="background:#60a5fa;"></span>
                    <span>Módulo Docente</span>
                    <span class="sh-line"></span>
                </div>

                <a class="sb-item <?= ($paginaActual === 'asistencia') ? 'active' : '' ?>" href="asistencia.php">
                    <span class="sb-icon" style="background:rgba(59,130,246,0.22);color:#60a5fa;">
                        <i class="fa-solid fa-clipboard-user"></i>
                    </span>
                    <span style="<?= ($paginaActual === 'asistencia') ? 'color:#fff;font-weight:700;' : '' ?>">Tomar Asistencia</span>
                    <span class="sb-badge" style="background:rgba(37,99,235,0.25);color:#93c5fd;border:1px solid rgba(59,130,246,0.3);">Aula</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (esCoordinador() || esRector() || esAdmin()): ?>
            <!-- ── SUPERVISIÓN ── -->
            <div class="sb-section">
                <div class="sb-heading">
                    <span class="sh-dot" style="background:#fbbf24;"></span>
                    <span>Supervisión</span>
                    <span class="sh-line"></span>
                </div>

                <a class="sb-item <?= ($paginaActual === 'faltas') ? 'active' : '' ?>" href="faltas.php">
                    <span class="sb-icon" style="background:rgba(245,158,11,0.18);color:#fbbf24;">
                        <i class="fa-solid fa-user-xmark"></i>
                    </span>
                    <span>Control de Faltas</span>
                </a>

                <a class="sb-item <?= ($paginaActual === 'aforo') ? 'active' : '' ?>" href="aforo.php">
                    <span class="sb-icon" style="background:rgba(245,158,11,0.12);color:#fcd34d;">
                        <i class="fa-solid fa-chart-pie"></i>
                    </span>
                    <span>Aforo por Grados</span>
                    <span class="sb-badge" style="background:rgba(255,255,255,0.07);color:#94a3b8;border:1px solid rgba(255,255,255,0.1);">16 grados</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (esCelador() || esAdmin()): ?>
            <!-- ── CONTROL DE PORTERÍA ── -->
            <div class="sb-section">
                <div class="sb-heading">
                    <span class="sh-dot" style="background:#34d399;"></span>
                    <span>Control de Portería</span>
                    <span class="sh-line"></span>
                </div>

                <a class="sb-item <?= ($paginaActual === 'porteria') ? 'active' : '' ?>" href="porteria.php">
                    <span class="sb-icon" style="background:rgba(16,185,129,0.2);color:#34d399;">
                        <i class="fa-solid fa-door-open"></i>
                    </span>
                    <span>Terminal Portería</span>
                    <span class="sb-badge" style="background:rgba(16,185,129,0.18);color:#6ee7b7;border:1px solid rgba(16,185,129,0.3);">Activo</span>
                </a>

                <a class="sb-item <?= ($paginaActual === 'huellas') ? 'active' : '' ?>" href="huellas.php">
                    <span class="sb-icon" style="background:rgba(0,198,255,0.12);color:#22d3ee;">
                        <i class="fa-solid fa-fingerprint"></i>
                    </span>
                    <span>Biometría & Huellas</span>
                    <span class="sb-badge" style="background:rgba(0,198,255,0.15);color:#67e8f9;border:1px solid rgba(0,198,255,0.25);">6 slots</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (esAdmin()): ?>
            <!-- ── ADMINISTRACIÓN ── -->
            <div class="sb-section">
                <div class="sb-heading">
                    <span class="sh-dot" style="background:#c084fc;"></span>
                    <span>Administración</span>
                    <span class="sh-line"></span>
                </div>

                <a class="sb-item <?= ($paginaActual === 'usuarios') ? 'active' : '' ?>" href="usuarios.php">
                    <span class="sb-icon" style="background:rgba(168,85,247,0.22);color:#c084fc;">
                        <i class="fa-solid fa-users-gear"></i>
                    </span>
                    <span style="<?= ($paginaActual === 'usuarios') ? 'color:#fff;font-weight:700;' : '' ?>">Usuarios y Claves</span>
                    <span class="sb-badge" style="background:rgba(168,85,247,0.2);color:#d8b4fe;border:1px solid rgba(168,85,247,0.3);">Gestión</span>
                </a>

                <a class="sb-item <?= ($paginaActual === 'estudiantes') ? 'active' : '' ?>" href="estudiantes.php">
                    <span class="sb-icon" style="background:rgba(99,102,241,0.18);color:#a5b4fc;">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </span>
                    <span>Estudiantes</span>
                    <span class="sb-badge" style="background:rgba(99,102,241,0.2);color:#a5b4fc;border:1px solid rgba(99,102,241,0.3);">515</span>
                </a>

                <a class="sb-item <?= ($paginaActual === 'sensores') ? 'active' : '' ?>" href="sensores.php">
                    <span class="sb-icon" style="background:rgba(14,165,233,0.15);color:#38bdf8;">
                        <i class="fa-solid fa-satellite-dish"></i>
                    </span>
                    <span>Sensores & Hardware</span>
                </a>

                <a class="sb-item" href="javascript:void(0)" onclick="abrirModalReiniciarAsistencias()">
                    <span class="sb-icon" style="background:rgba(239,68,68,0.18);color:#f87171;">
                        <i class="fa-solid fa-rotate-left"></i>
                    </span>
                    <span style="color:#fca5a5; font-weight:600;">Reiniciar Asistencias</span>
                    <span class="sb-badge" style="background:rgba(239,68,68,0.22);color:#fca5a5;border:1px solid rgba(239,68,68,0.35);">Reset</span>
                </a>
            </div>
            <?php else: ?>
            <!-- Directorio para otros roles -->
            <div class="sb-section">
                <div class="sb-heading">
                    <span class="sh-dot" style="background:#38bdf8;"></span>
                    <span>Consultas</span>
                    <span class="sh-line"></span>
                </div>
                <a class="sb-item <?= ($paginaActual === 'estudiantes') ? 'active' : '' ?>" href="estudiantes.php">
                    <span class="sb-icon" style="background:rgba(14,165,233,0.15);color:#38bdf8;">
                        <i class="fa-solid fa-users-viewfinder"></i>
                    </span>
                    <span>Directorio Estudiantil</span>
                    <span class="sb-badge" style="background:rgba(14,165,233,0.15);color:#7dd3fc;border:1px solid rgba(14,165,233,0.25);">515</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- ── SISTEMA ── -->
            <div class="sb-section">
                <div class="sb-heading">
                    <span class="sh-dot" style="background:#64748b;"></span>
                    <span>Sistema</span>
                    <span class="sh-line"></span>
                </div>
                <a class="sb-item danger" href="logout.php" onclick="return confirm('¿Deseas cerrar sesión?')" style="margin-top:0.3rem;">
                    <span class="sb-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                    <span>Cerrar Sesión</span>
                </a>
            </div>

        </nav>

        <!-- Footer del sidebar -->
        <div class="sb-footer">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                <span style="font-size:0.7rem;color:#475569;display:flex;align-items:center;gap:5px;">
                    <i class="fa-solid fa-clock" style="color:#38bdf8;"></i>
                    <span id="sb-clock" style="color:#94a3b8;"><?= date('h:i A') ?></span>
                </span>
                <span style="font-size:0.58rem;color:#334155;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);padding:2px 6px;border-radius:20px;">v3.1</span>
            </div>
            <small style="font-size:0.65rem;color:#334155;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                I.E. Jorge Robledo &bull; Medellín
            </small>
        </div>

    </aside>

    <!-- ══════════════════════════════════════════════════
         CONTENIDO PRINCIPAL
         ══════════════════════════════════════════════════ -->
    <div class="main-content">

        <!-- Top Navbar -->
        <nav class="top-nav">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm d-lg-none" onclick="sbOpen()"
                        style="background:none;border:1px solid #e2e8f0;border-radius:8px;padding:0.35rem 0.7rem;color:#64748b;">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="d-none d-sm-flex align-items-center gap-2">
                    <span class="fw-bold text-dark" style="font-family:'Outfit',sans-serif;font-size:0.95rem;">I.E. JORGE ROBLEDO</span>
                    <span class="text-muted small d-none d-md-inline">&bull; SFS Access Control</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Badge rol -->
                <span class="badge rounded-pill px-3 py-2 d-none d-md-inline-flex align-items-center gap-1"
                      style="background:<?= $configRol['badge_bg'] ?>;color:<?= $configRol['badge_txt'] ?>;font-size:0.72rem;">
                    <i class="<?= $configRol['icono'] ?>" style="color:<?= $configRol['icono_color'] ?>;"></i>
                    <?= htmlspecialchars($configRol['titulo']) ?>
                </span>

                <!-- Breadcrumb de página -->
                <?php if (isset($breadcrumb)): ?>
                <span class="text-muted small d-none d-lg-inline">&bull; <?= htmlspecialchars($breadcrumb) ?></span>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-sm rounded-pill px-3"
                   style="border:1px solid rgba(239,68,68,0.3);color:#ef4444;font-size:0.8rem;"
                   onclick="return confirm('¿Deseas cerrar sesión?')">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
                </a>
            </div>
        </nav>

        <!-- Contenido de la página -->
        <?= $contenido ?? '' ?>

        <!-- Footer -->
        <footer class="app-footer">
            <strong>SFS Access Control</strong> &copy; <?= date('Y') ?> &bull; I.E. Jorge Robledo &bull; Medellín
        </footer>

    </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<?php if (esAdmin()): ?>
<!-- Modal Reiniciar Registros de Asistencia (Administrador) -->
<div class="modal fade" id="modalReiniciarAsistencias" tabindex="-1" aria-labelledby="modalReiniciarAsistenciasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <h5 class="modal-title fw-bold" id="modalReiniciarAsistenciasLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Reiniciar Registros de Asistencia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning border-0 rounded-3 mb-3 d-flex align-items-start gap-2 py-2 px-3 small">
                    <i class="fa-solid fa-circle-exclamation fs-5 text-warning mt-1"></i>
                    <span><strong>Acción de Administrador:</strong> Esta acción limpiará los registros seleccionados en la base de datos institucional. Selecciona las opciones deseadas:</span>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark mb-2">1. ¿Qué registros deseas reiniciar?</label>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check p-2 rounded-3 border bg-light">
                            <input class="form-check-input ms-1" type="radio" name="reinicio_tipo" id="tipo_todo" value="todo" checked>
                            <label class="form-check-label ms-2 small fw-semibold text-dark" for="tipo_todo">
                                <i class="fa-solid fa-layer-group text-danger me-1"></i> Todos los Registros (Aula + Portería)
                                <span class="d-block text-muted fw-normal" style="font-size:0.75rem;">Borra listas de clases de docentes y accesos de torniquete/portería.</span>
                            </label>
                        </div>
                        <div class="form-check p-2 rounded-3 border bg-light">
                            <input class="form-check-input ms-1" type="radio" name="reinicio_tipo" id="tipo_aula" value="aula">
                            <label class="form-check-label ms-2 small fw-semibold text-dark" for="tipo_aula">
                                <i class="fa-solid fa-clipboard-user text-primary me-1"></i> Solo Asistencia en el Aula
                                <span class="d-block text-muted fw-normal" style="font-size:0.75rem;">Borra los estados de listas de clases (Presentes, Faltas, Retardos).</span>
                            </label>
                        </div>
                        <div class="form-check p-2 rounded-3 border bg-light">
                            <input class="form-check-input ms-1" type="radio" name="reinicio_tipo" id="tipo_acceso" value="acceso">
                            <label class="form-check-label ms-2 small fw-semibold text-dark" for="tipo_acceso">
                                <i class="fa-solid fa-door-open text-success me-1"></i> Solo Accesos de Portería
                                <span class="d-block text-muted fw-normal" style="font-size:0.75rem;">Borra el historial de ingresos y salidas del sensor de portería.</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark mb-2">2. Alcance temporal:</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-check p-2 rounded-3 border bg-light h-100">
                                <input class="form-check-input ms-1" type="radio" name="reinicio_alcance" id="alcance_hoy" value="hoy" checked>
                                <label class="form-check-label ms-2 small fw-semibold text-dark" for="alcance_hoy">
                                    <i class="fa-solid fa-calendar-day text-info me-1"></i> Solo Hoy
                                    <span class="d-block text-muted fw-normal" style="font-size:0.75rem;"><?= date('d/m/Y') ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check p-2 rounded-3 border bg-light h-100">
                                <input class="form-check-input ms-1" type="radio" name="reinicio_alcance" id="alcance_todo" value="todo">
                                <label class="form-check-label ms-2 small fw-semibold text-dark" for="alcance_todo">
                                    <i class="fa-solid fa-clock-rotate-left text-danger me-1"></i> Todo el Historial
                                    <span class="d-block text-muted fw-normal" style="font-size:0.75rem;">Histórico completo</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="reinicio-feedback" class="alert d-none py-2 px-3 small rounded-3 mb-0"></div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 border-top">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold btn-sm" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold btn-sm" id="btn-ejecutar-reinicio" onclick="ejecutarReinicioAsistencias()">
                    <i class="fa-solid fa-trash-can me-1"></i> Confirmar Reinicio
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /* Sidebar mobile toggle */
    function sbOpen()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sb-backdrop').classList.add('show'); }
    function sbClose() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sb-backdrop').classList.remove('show'); }

    /* Reloj en tiempo real */
    function actualizarReloj() {
        const el = document.getElementById('sb-clock');
        if (!el) return;
        const now = new Date();
        const h = now.getHours() % 12 || 12;
        const m = String(now.getMinutes()).padStart(2,'0');
        const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
        el.textContent = `${h}:${m} ${ampm}`;
    }
    setInterval(actualizarReloj, 30000);

    /* Funciones globales de administración para reinicio de asistencias */
    function abrirModalReiniciarAsistencias() {
        const modalEl = document.getElementById('modalReiniciarAsistencias');
        if (!modalEl) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const feedback = document.getElementById('reinicio-feedback');
        if (feedback) feedback.classList.add('d-none');
        const btn = document.getElementById('btn-ejecutar-reinicio');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i> Confirmar Reinicio';
        }
        modal.show();
    }

    async function ejecutarReinicioAsistencias() {
        const btn = document.getElementById('btn-ejecutar-reinicio');
        const feedback = document.getElementById('reinicio-feedback');
        const tipo = document.querySelector('input[name="reinicio_tipo"]:checked')?.value || 'todo';
        const alcance = document.querySelector('input[name="reinicio_alcance"]:checked')?.value || 'todo';

        if (!confirm('¿Estás COMPLETAMENTE seguro(a) de que deseas reiniciar los registros seleccionados? Esta acción no se puede deshacer.')) {
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Reiniciando...';
        }

        const fd = new FormData();
        fd.append('tipo', tipo);
        fd.append('alcance', alcance);

        try {
            const resp = await fetch('index.php?c=admin&a=reiniciarAsistencia', {
                method: 'POST',
                body: fd
            });
            const data = await resp.json();

            if (feedback) {
                feedback.classList.remove('d-none', 'alert-success', 'alert-danger');
                if (data.status === 'ok') {
                    feedback.classList.add('alert-success');
                    feedback.innerHTML = `<i class="fa-solid fa-circle-check me-1"></i> ${data.mensaje}`;
                    setTimeout(() => {
                        location.reload();
                    }, 1800);
                } else {
                    feedback.classList.add('alert-danger');
                    feedback.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> ${data.mensaje}`;
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i> Confirmar Reinicio';
                    }
                }
            }
        } catch (e) {
            if (feedback) {
                feedback.classList.remove('d-none');
                feedback.classList.add('alert-danger');
                feedback.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Error de comunicación con el servidor.';
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-trash-can me-1"></i> Confirmar Reinicio';
            }
        }
    }

    <?= $scriptExtra ?? '' ?>
</script>
</body>
</html>

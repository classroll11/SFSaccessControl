<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SFS Access Control - Panel de Control Institucional</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0a1128;
            --primary-dark: #060b18;
            --accent-blue: #2563eb;
            --accent-cyan: #00f2fe;
            --accent-glow: #38bdf8;
            --bg-body: #f8fafc;
            --card-radius: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        /* Navbar */
        .navbar-custom {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 4%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .brand-logo {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-logo-emblem {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #060b18;
            border: 1px solid rgba(56, 189, 248, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .brand-logo-emblem img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Top Hero Header */
        .dashboard-hero {
            background: radial-gradient(circle at 50% 20%, #112349 0%, #060b18 90%);
            color: #ffffff;
            padding: 2.8rem 0;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .dashboard-hero::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0; left: 0;
            background: radial-gradient(circle at right, rgba(0, 242, 254, 0.1) 0%, transparent 60%);
            pointer-events: none;
        }

        /* KPI Metric Cards */
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--card-radius);
            padding: 1.5rem 1.6rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.07);
            border-color: #cbd5e1;
        }

        .kpi-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .kpi-green { background: #ecfdf5; color: #059669; }
        .kpi-blue { background: #eff6ff; color: #2563eb; }
        .kpi-amber { background: #fffbeb; color: #d97706; }
        .kpi-rose { background: #fff1f2; color: #e11d48; }

        /* Main Content Cards */
        .card-surface {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--card-radius);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            padding: 1.8rem;
            margin-bottom: 1.8rem;
        }

        .live-dot {
            width: 9px;
            height: 9px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-ring 1.8s infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Tabla estilizada */
        .table-pro {
            margin-bottom: 0;
        }

        .table-pro th {
            font-family: 'Outfit', sans-serif;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc;
            padding: 1rem 1.2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-pro td {
            padding: 1.1rem 1.2rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.92rem;
        }

        .table-pro tbody tr {
            transition: var(--transition);
        }

        .table-pro tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Avatar de Iniciales */
        .avatar-initials {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0a1128 0%, #1e293b 100%);
            color: #38bdf8;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        /* Badges de Estado */
        .pill-badge {
            font-weight: 700;
            font-size: 0.76rem;
            padding: 5px 14px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-denied { background: #fee2e2; color: #b91c1c; }
        .badge-in { background: #dbeafe; color: #1d4ed8; }
        .badge-out { background: #fef3c7; color: #b45309; }

        /* Simulador Biométrico Card */
        .biometric-scanner-box {
            background: linear-gradient(145deg, #0d1b38 0%, #060b18 100%);
            border-radius: 16px;
            padding: 1.5rem;
            color: white;
            text-align: center;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(56, 189, 248, 0.2);
            position: relative;
            overflow: hidden;
        }

        .scanner-beam {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00f2fe, transparent);
            top: 0;
            left: 0;
            animation: scan-move 2.5s ease-in-out infinite;
        }

        @keyframes scan-move {
            0% { top: 0; opacity: 0; }
            30% { opacity: 1; }
            70% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .scanner-fingerprint-icon {
            font-size: 3rem;
            color: #38bdf8;
            margin-bottom: 0.5rem;
            display: inline-block;
            transition: var(--transition);
        }

        /* Footer */
        .footer-dashboard {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 1.6rem 0;
            margin-top: auto;
            color: #64748b;
            font-size: 0.88rem;
        }

        /* ================================================================
           GESTIÓN DE HUELLAS DACTILARES
           ================================================================ */

        /* Tab navigation */
        .dash-tabs .nav-link {
            color: #64748b;
            font-weight: 600;
            font-size: 0.88rem;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            padding: 0.7rem 1.2rem;
            transition: var(--transition);
        }
        .dash-tabs .nav-link:hover { color: #2563eb; background: transparent; }
        .dash-tabs .nav-link.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
            background: transparent;
        }

        /* Student fingerprint card */
        .student-finger-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.2rem;
            transition: var(--transition);
            cursor: pointer;
        }
        .student-finger-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 8px 24px rgba(37,99,235,0.08);
            transform: translateY(-2px);
        }
        .student-finger-card.selected {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        /* Finger slot grid */
        .finger-slots-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }
        .finger-slot {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 1rem 0.6rem;
            text-align: center;
            transition: var(--transition);
            position: relative;
            cursor: pointer;
        }
        .finger-slot.registered {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #34d399;
            border-style: solid;
        }
        .finger-slot.registering {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 2px solid #60a5fa;
            border-style: solid;
            animation: slot-pulse 1.5s infinite;
        }
        @keyframes slot-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(37,99,235,0.3); }
            50% { box-shadow: 0 0 0 6px rgba(37,99,235,0); }
        }
        .finger-slot .slot-icon {
            font-size: 1.8rem;
            margin-bottom: 0.4rem;
            display: block;
        }
        .finger-slot .slot-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .finger-slot.registered .slot-label { color: #059669; }
        .finger-slot .slot-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #10b981;
            color: white;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }
        .finger-slot .slot-delete {
            position: absolute;
            top: -6px;
            left: -6px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #ef4444;
            color: white;
            font-size: 0.6rem;
            display: none;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            cursor: pointer;
            transition: var(--transition);
        }
        .finger-slot.registered:hover .slot-delete { display: flex; }
        .finger-slot.registered:hover .slot-badge { display: none; }

        /* Huella identification scanner */
        .identify-scanner-box {
            background: linear-gradient(160deg, #0d1b38 0%, #060b18 100%);
            border: 1px solid rgba(56,189,248,0.25);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .identify-scanner-box::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(0,242,254,0.08) 0%, transparent 65%);
        }
        .big-fingerprint-icon {
            font-size: 5rem;
            color: #38bdf8;
            transition: var(--transition);
            position: relative;
            z-index: 1;
        }
        .big-fingerprint-icon.scanning {
            animation: fp-scan 1.2s ease-in-out infinite;
            color: #00f2fe;
            filter: drop-shadow(0 0 12px rgba(0,242,254,0.7));
        }
        .big-fingerprint-icon.identified {
            color: #34d399;
            filter: drop-shadow(0 0 12px rgba(52,211,153,0.7));
        }
        .big-fingerprint-icon.unknown {
            color: #f87171;
            filter: drop-shadow(0 0 12px rgba(248,113,113,0.6));
        }
        @keyframes fp-scan {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        /* Progress bar for fingerprint slots */
        .huella-progress {
            height: 6px;
            border-radius: 99px;
            background: #e2e8f0;
        }
        .huella-progress-bar {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, #2563eb, #00f2fe);
            transition: width 0.5s ease;
        }

        /* Identification result card */
        .id-result-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.4rem;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        .id-result-card.result-success { border-color: #34d399; background: #ecfdf5; }
        .id-result-card.result-error { border-color: #f87171; background: #fef2f2; }

        /* Search input for student list */
        .search-student-wrap {
            position: relative;
        }
        .search-student-wrap input {
            padding-left: 2.4rem;
        }
        .search-student-wrap .search-icon {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        @media (max-width: 576px) {
            .finger-slots-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

    <!-- NAVBAR DE NAVEGACIÓN -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="brand-logo" href="index.php">
                <div class="brand-logo-emblem">
                    <img src="assets/img/sfs-logo-emblem.png" alt="SFS Emblem" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-fingerprint text-info\'></i>'">
                </div>
                <span>SFS ACCESS CONTROL</span>
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <div class="d-none d-md-flex align-items-center gap-2 bg-light px-3 py-1 rounded-pill border">
                    <img src="assets/img/jorge-robledo-logo.png" alt="Escudo Jorge Robledo" style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover;" onerror="this.style.display='none'">
                    <span class="small fw-semibold text-secondary">I.E. Jorge Robledo - Medellín</span>
                </div>
                
                <div class="bg-dark text-light px-3 py-1 rounded-pill small fw-semibold d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock text-info"></i>
                    <span id="live-clock"><?= date('h:i:s A') ?></span>
                </div>

                <a href="index.php?c=reporte&a=exportarCsv" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Asistencia CSV
                </a>

                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('¿Deseas cerrar sesión?')">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- DASHBOARD HERO BANNER -->
    <section class="dashboard-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-25 text-info border border-info border-opacity-25 small mb-2">
                        <span class="live-dot"></span> Terminal de Monitoreo Biométrico & Aforo
                    </div>
                    <h1 class="h2 fw-bold text-white mb-2">Panel de Control de Acceso Estudiantil</h1>
                    <p class="text-light text-opacity-75 mb-0 small">
                        Supervisión en tiempo real del ingreso y salida de estudiantes, auditoría de seguridad y control automatizado de asistencia a clases.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <button class="btn btn-info text-dark fw-bold px-4 py-2 rounded-pill shadow-sm" onclick="recargarDatosHistorial()">
                        <i class="fa-solid fa-rotate me-1" id="icon-refresh"></i> Actualizar en Vivo
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- CUERPO PRINCIPAL -->
    <main class="container py-4">

        <!-- 1. TARJETAS DE ESTADÍSTICAS / KPIS GLOBALES -->
        <div class="row g-3 mb-4">
            <!-- Asistencia a Clases / Estudiantes en Plantel -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Estudiantes en Plantel</span>
                            <h2 class="fw-bold my-1 text-success" id="kpi-estudiantes-dentro"><?= $estudiantesDentro ?? 0 ?></h2>
                            <span class="small text-muted"><i class="fa-solid fa-graduation-cap text-success me-1"></i> Presentes en Clase</span>
                        </div>
                        <div class="kpi-icon-wrapper kpi-green">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ingresos Hoy -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Ingresos Hoy</span>
                            <h2 class="fw-bold my-1 text-primary" id="kpi-entradas"><?= $estadisticas['total_entradas'] ?? 0 ?></h2>
                            <span class="small text-muted"><i class="fa-solid fa-arrow-down-left text-primary me-1"></i> Accesos Concedidos</span>
                        </div>
                        <div class="kpi-icon-wrapper kpi-blue">
                            <i class="fa-solid fa-door-open"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salidas Hoy -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Salidas Hoy</span>
                            <h2 class="fw-bold my-1 text-warning" id="kpi-salidas"><?= $estadisticas['total_salidas'] ?? 0 ?></h2>
                            <span class="small text-muted"><i class="fa-solid fa-arrow-up-right text-warning me-1"></i> Salidas Registradas</span>
                        </div>
                        <div class="kpi-icon-wrapper kpi-amber">
                            <i class="fa-solid fa-person-walking-dashed-line-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Intentos Rechazados -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Accesos Denegados</span>
                            <h2 class="fw-bold my-1 text-danger" id="kpi-rechazados"><?= $estadisticas['rechazados'] ?? 0 ?></h2>
                            <span class="small text-danger fw-semibold"><i class="fa-solid fa-shield-xmark me-1"></i> Alertas de Seguridad</span>
                        </div>
                        <div class="kpi-icon-wrapper kpi-rose">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- 2. TABLA DE HISTORIAL DE ACCESOS EN TIEMPO REAL -->
            <div class="col-lg-8">
                <div class="card-surface h-100">
                    
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold text-primary mb-0">
                                <i class="fa-solid fa-list-check me-2 text-primary"></i>Historial de Accesos en Vivo
                            </h5>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 small">
                                <span class="live-dot me-1"></span> Tiempo Real
                            </span>
                        </div>
                        
                        <!-- Buscador dinámico de accesos -->
                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 bg-light" id="buscador-tabla" placeholder="Filtrar por nombre o doc..." onkeyup="filtrarTabla()">
                        </div>
                    </div>

                    <!-- Notificación de alerta interactiva -->
                    <div id="live-alert" class="alert d-none py-2 px-3 small rounded-3 mb-3"></div>

                    <div class="table-responsive">
                        <table class="table table-pro align-middle" id="tabla-accesos">
                            <thead>
                                <tr>
                                    <th>Usuario / Estudiante</th>
                                    <th>Grado / Rol</th>
                                    <th>Hora</th>
                                    <th>Evento</th>
                                    <th>Estado</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-accesos-body">
                                <?php if (!empty($accesosRecientes)): ?>
                                    <?php foreach ($accesosRecientes as $acc): ?>
                                        <?php 
                                            $partes = explode(' ', trim($acc['nombre_usuario']));
                                            $iniciales = strtoupper(substr($partes[0] ?? 'X', 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="avatar-initials"><?= htmlspecialchars($iniciales) ?></span>
                                                    <div>
                                                        <strong class="d-block text-dark"><?= htmlspecialchars($acc['nombre_usuario']) ?></strong>
                                                        <span class="text-muted small">Doc: <?= htmlspecialchars($acc['documento_usuario']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($acc['grado_usuario']) ?></span>
                                            </td>
                                            <td class="small fw-semibold text-secondary">
                                                <?= date('h:i:s A', strtotime($acc['fecha_hora'])) ?>
                                            </td>
                                            <td>
                                                <?php if ($acc['tipo_evento'] === 'ENTRADA'): ?>
                                                    <span class="pill-badge badge-in"><i class="fa-solid fa-arrow-down-to-bracket"></i> ENTRADA</span>
                                                <?php else: ?>
                                                    <span class="pill-badge badge-out"><i class="fa-solid fa-arrow-up-from-bracket"></i> SALIDA</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($acc['estado_acceso'] === 'APROBADO'): ?>
                                                    <span class="pill-badge badge-approved"><i class="fa-solid fa-check"></i> Aprobado</span>
                                                <?php else: ?>
                                                    <span class="pill-badge badge-denied"><i class="fa-solid fa-xmark"></i> Rechazado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($acc['observaciones'] ?? '') ?>">
                                                <?= htmlspecialchars($acc['observaciones'] ?? 'N/A') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i>
                                            No hay registros de acceso para mostrar el día de hoy.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. SIMULADOR BIOMÉTRICO & ASISTENCIA POR GRADO -->
            <div class="col-lg-4">
                
                <!-- SIMULADOR BIOMÉTRICO DE PORTERÍA -->
                <div class="card-surface border-top border-primary border-3 mb-4">
                    <h5 class="fw-bold text-primary mb-2">
                        <i class="fa-solid fa-fingerprint me-2 text-info"></i>Simulador de Huella / Portería
                    </h5>
                    <p class="small text-muted mb-3">
                        Prueba la validación biométrica en tiempo real sin requerir sensor físico conectado.
                    </p>

                    <div class="biometric-scanner-box">
                        <div class="scanner-beam"></div>
                        <i class="fa-solid fa-fingerprint scanner-fingerprint-icon" id="scanner-icon"></i>
                        <h6 class="fw-bold mb-0">Terminal Biométrica Lista</h6>
                        <small class="text-info opacity-75">Esperando captura dactilar...</small>
                    </div>

                    <form id="form-simulador-biometrico" onsubmit="enviarSimulacion(event)">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Caso Rápido de Prueba:</label>
                            <select class="form-select form-select-sm" id="select-usuario-demo" onchange="seleccionarUsuarioDemo(this.value)">
                                <option value="">-- Seleccionar caso de prueba --</option>
                                <optgroup label="✅ Estudiantes Activos (Grado 11)">
                                    <option value="HUELLA_HEX_SAMPLE_001|10359001">Alejandra Martínez (11°A - ACTIVA)</option>
                                    <option value="HUELLA_HEX_SAMPLE_004|10359002">Santiago Morales (11°A - ACTIVO)</option>
                                    <option value="HUELLA_HEX_SAMPLE_005|10359004">Valentina Henao (11°B - ACTIVA)</option>
                                    <option value="HUELLA_HEX_SAMPLE_006|10359005">Mateo Quintero (11°B - ACTIVO)</option>
                                </optgroup>
                                <optgroup label="✅ Estudiantes Activos (Grados 10 y 9)">
                                    <option value="HUELLA_HEX_SAMPLE_007|10359006">Manuela Correa (10°A - ACTIVA)</option>
                                    <option value="HUELLA_HEX_SAMPLE_008|10359007">Daniel Jaramillo (10°A - ACTIVO)</option>
                                    <option value="HUELLA_HEX_SAMPLE_002|10359008">Carlos Rodríguez (10°B - ACTIVO)</option>
                                    <option value="HUELLA_HEX_SAMPLE_010|10359010">Juan José Estrada (9°A - ACTIVO)</option>
                                    <option value="HUELLA_HEX_SAMPLE_011|10359011">Isabella Rincón (9°A - ACTIVA)</option>
                                </optgroup>
                                <optgroup label="🚫 Casos de Denegación (Inactivos / Intrusos)">
                                    <option value="HUELLA_HEX_SAMPLE_003|10359003">Lucía Gómez (11°A - INACTIVA / RECHAZAR)</option>
                                    <option value="HUELLA_HEX_SAMPLE_009|10359009">Mariana Osorio (10°B - SUSPENDIDA / RECHAZAR)</option>
                                    <option value="HUELLA_DESCONOCIDA|99999999">Persona No Registrada (INTRUSO / RECHAZAR)</option>
                                </optgroup>
                                <optgroup label="👔 Directivos y Docentes">
                                    <option value="FINGERPRINT_HASH_ADMIN_001|10000001">Prof. Carlos Restrepo (RECTORÍA)</option>
                                    <option value="FINGERPRINT_HASH_DOC_001|10000003">Lic. Fernando Arango (DOCENTE)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Template Huella Dactilar:</label>
                            <input type="text" class="form-control form-control-sm" id="input-huella" name="huella" placeholder="Ej: HUELLA_HEX_SAMPLE_001" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Documento (Opcional):</label>
                            <input type="text" class="form-control form-control-sm" id="input-documento" name="documento" placeholder="Ej: 10359001">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tipo de Evento:</label>
                            <select class="form-select form-select-sm" id="select-tipo-evento" name="tipo_evento">
                                <option value="">Automático (Alternar Entrada/Salida)</option>
                                <option value="ENTRADA">Forzar ENTRADA</option>
                                <option value="SALIDA">Forzar SALIDA</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm" id="btn-simular">
                            <i class="fa-solid fa-fingerprint me-1"></i> Simular Lectura Biométrica
                        </button>
                    </form>
                </div>

                <!-- CARD DESGLOSE ASISTENCIA POR GRADO -->
                <div class="card-surface">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fa-solid fa-graduation-cap text-primary me-2"></i>Asistencia por Grado
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1">Académico</span>
                    </div>
                    <p class="small text-muted mb-3">
                        Distribución de estudiantes presentes en clases hoy según el registro biométrico:
                    </p>

                    <div id="lista-desglose-grados">
                        <?php if (!empty($estudiantesPorGrado)): ?>
                            <ul class="list-group list-group-flush small">
                                <?php foreach ($estudiantesPorGrado as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-light">
                                        <span><i class="fa-solid fa-graduation-cap text-primary me-2"></i>Grado <strong><?= htmlspecialchars($item['grado']) ?></strong></span>
                                        <span class="badge bg-primary rounded-pill"><?= $item['total'] ?> alumnos</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-3 text-muted small">
                                <i class="fa-solid fa-info-circle me-1"></i> No hay estudiantes dentro actualmente.
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr class="my-3">
                    <a href="index.php?c=reporte&a=exportarCsv" class="btn btn-outline-dark btn-sm w-100 rounded-pill">
                        <i class="fa-solid fa-download me-1"></i> Descargar Planilla de Asistencia
                    </a>
                </div>

            </div>

        </div>

    </main>

    <!-- ================================================================
         SECCIÓN: GESTIÓN DE HUELLAS DACTILARES
         ================================================================ -->
    <section class="container py-4" id="seccion-huellas">
        <div class="card-surface" style="border-radius:24px; padding: 0; overflow:hidden;">

            <!-- Header de sección -->
            <div style="background: radial-gradient(circle at 20% 50%, #112349 0%, #060b18 100%); padding: 1.8rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.08);">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2"
                             style="background:rgba(56,189,248,0.15); border:1px solid rgba(56,189,248,0.25); font-size:0.8rem; color:#38bdf8; font-weight:600;">
                            <i class="fa-solid fa-fingerprint"></i> Módulo Biométrico
                        </div>
                        <h2 class="h4 fw-bold text-white mb-1">Gestión de Huellas Dactilares</h2>
                        <p class="text-light text-opacity-75 small mb-0">
                            Registra hasta <strong>6 huellas</strong> por estudiante e identifica a quién pertenece una huella dactilar.
                        </p>
                    </div>
                    <div class="text-md-end">
                        <span class="badge rounded-pill px-3 py-2" style="background:rgba(52,211,153,0.15); color:#34d399; border:1px solid rgba(52,211,153,0.3); font-size:0.82rem;">
                            <i class="fa-solid fa-shield-check me-1"></i> SHA-256 Cifrado
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div style="padding: 0 2rem; border-bottom: 1px solid #e2e8f0; background:#fafbfc;">
                <ul class="nav dash-tabs" id="huellasTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-registrar-btn" data-bs-toggle="tab" data-bs-target="#tab-registrar" type="button">
                            <i class="fa-solid fa-hand-pointer me-1"></i> Registrar Huellas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-identificar-btn" data-bs-toggle="tab" data-bs-target="#tab-identificar" type="button">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Identificar Huella
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" style="padding: 2rem;">

                <!-- TAB 1: REGISTRAR HUELLAS -->
                <div class="tab-pane fade show active" id="tab-registrar" role="tabpanel">
                    <div class="row g-4">

                        <!-- Columna izquierda: Lista de estudiantes -->
                        <div class="col-lg-5">
                            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-users text-primary me-2"></i>Seleccionar Estudiante</h6>
                            <p class="small text-muted mb-3">Haz clic en un estudiante para gestionar sus huellas.</p>

                            <!-- Buscador -->
                            <div class="search-student-wrap mb-3">
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                <input type="text" class="form-control form-control-sm" id="buscador-estudiantes"
                                       placeholder="Buscar por nombre o documento..." oninput="filtrarEstudiantes()">
                            </div>

                            <!-- Lista de estudiantes -->
                            <div id="lista-estudiantes-huellas" style="max-height: 420px; overflow-y: auto;">
                                <?php if (!empty($estudiantesHuellas)): ?>
                                    <?php foreach ($estudiantesHuellas as $est): ?>
                                        <?php
                                            $partes = explode(' ', trim($est['nombre']));
                                            $iniciales = strtoupper(substr($partes[0] ?? 'X', 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
                                            $pct = round(($est['total_huellas'] / 6) * 100);
                                        ?>
                                        <div class="student-finger-card mb-2 estudiante-item"
                                             data-id="<?= $est['id'] ?>"
                                             data-nombre="<?= htmlspecialchars($est['nombre']) ?>"
                                             data-doc="<?= htmlspecialchars($est['documento']) ?>"
                                             data-grado="<?= htmlspecialchars($est['grado']) ?>"
                                             onclick="seleccionarEstudiante(<?= $est['id'] ?>, '<?= htmlspecialchars(addslashes($est['nombre'])) ?>', '<?= htmlspecialchars($est['grado']) ?>')">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="avatar-initials" style="flex-shrink:0;"><?= $iniciales ?></span>
                                                <div class="flex-grow-1 min-w-0">
                                                    <strong class="d-block text-dark text-truncate" style="font-size:0.9rem;"><?= htmlspecialchars($est['nombre']) ?></strong>
                                                    <small class="text-muted">Doc: <?= htmlspecialchars($est['documento']) ?> &bull; <?= htmlspecialchars($est['grado']) ?></small>
                                                    <div class="d-flex align-items-center gap-2 mt-1">
                                                        <div class="huella-progress flex-grow-1">
                                                            <div class="huella-progress-bar" style="width:<?= $pct ?>%"></div>
                                                        </div>
                                                        <span class="small fw-bold <?= $est['total_huellas'] == 6 ? 'text-success' : 'text-muted' ?>">
                                                            <?= $est['total_huellas'] ?>/6
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4 text-muted small">
                                        <i class="fa-solid fa-users-slash fs-3 d-block mb-2 opacity-50"></i>
                                        No hay estudiantes registrados en el sistema.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Columna derecha: Panel de slots de huellas -->
                        <div class="col-lg-7">
                            <!-- Estado: sin selección -->
                            <div id="panel-sin-seleccion" class="text-center py-5">
                                <div class="mb-3" style="font-size:4rem; opacity:0.15;">
                                    <i class="fa-solid fa-hand-pointer"></i>
                                </div>
                                <p class="text-muted fw-semibold">Selecciona un estudiante de la lista para gestionar sus huellas dactilares.</p>
                            </div>

                            <!-- Panel de gestión de slots (oculto hasta selección) -->
                            <div id="panel-gestion-huellas" class="d-none">
                                <!-- Info del estudiante seleccionado -->
                                <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-4" style="background:#f8fafc; border:1px solid #e2e8f0;">
                                    <span class="avatar-initials" id="slot-avatar" style="width:48px; height:48px; font-size:1rem;">XX</span>
                                    <div>
                                        <strong id="slot-nombre" class="d-block text-dark">—</strong>
                                        <span id="slot-grado" class="small text-muted">—</span>
                                    </div>
                                    <div class="ms-auto text-end">
                                        <span id="slot-conteo-badge" class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2">0/6 slots</span>
                                    </div>
                                </div>

                                <!-- Grid de 6 slots -->
                                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-hand me-2 text-primary"></i>Slots de Huellas Dactilares</h6>
                                <div class="finger-slots-grid mb-4" id="finger-slots-grid">
                                    <!-- Se carga dinámicamente por JS -->
                                </div>

                                <!-- Alerta de resultado -->
                                <div id="huellas-alert" class="alert d-none py-2 px-3 small rounded-3 mb-3"></div>

                                <!-- Modal de registro de huella -->
                                <div id="form-registro-huella" class="d-none p-3 rounded-3" style="background:#f0f7ff; border:1px solid #bfdbfe;">
                                    <h6 class="fw-bold mb-3">
                                        <i class="fa-solid fa-fingerprint text-primary me-2"></i>
                                        Registrar Huella — <span id="form-slot-label">Slot X</span>
                                    </h6>
                                    <input type="hidden" id="form-slot-numero" value="">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Nombre del Dedo:</label>
                                        <select class="form-select form-select-sm" id="form-dedo">
                                            <option value="">— Seleccionar dedo —</option>
                                            <option value="Pulgar Derecho">👍 Pulgar Derecho</option>
                                            <option value="Índice Derecho">☝️ Índice Derecho</option>
                                            <option value="Medio Derecho">🖕 Medio Derecho</option>
                                            <option value="Anular Derecho">💍 Anular Derecho</option>
                                            <option value="Meñique Derecho">🤙 Meñique Derecho</option>
                                            <option value="Pulgar Izquierdo">👍 Pulgar Izquierdo</option>
                                            <option value="Índice Izquierdo">☝️ Índice Izquierdo</option>
                                            <option value="Medio Izquierdo">🖕 Medio Izquierdo</option>
                                            <option value="Anular Izquierdo">💍 Anular Izquierdo</option>
                                            <option value="Meñique Izquierdo">🤙 Meñique Izquierdo</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Template / Hash de Huella:</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" id="form-huella-template" placeholder="Ej: HUELLA_HEX_SAMPLE_XXX o hash del sensor">
                                            <button class="btn btn-outline-secondary" type="button" onclick="generarHashDemo()" title="Generar hash demo">
                                                <i class="fa-solid fa-dice"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Ingresa el template enviado por el sensor AS608/R307, o usa el botón para simular.</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" onclick="guardarHuella()">
                                            <i class="fa-solid fa-save me-1"></i> Guardar Huella
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="cancelarRegistroHuella()">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: IDENTIFICAR HUELLA -->
                <div class="tab-pane fade" id="tab-identificar" role="tabpanel">
                    <div class="row g-4 justify-content-center">

                        <!-- Escáner de identificación -->
                        <div class="col-lg-5">
                            <div class="identify-scanner-box mb-4">
                                <i class="fa-solid fa-fingerprint big-fingerprint-icon" id="id-fp-icon"></i>
                                <h5 class="text-white fw-bold mt-3 mb-1 position-relative" style="z-index:1;">Terminal de Identificación</h5>
                                <p class="small mb-0 position-relative" style="z-index:1; color:rgba(255,255,255,0.6);">
                                    Ingresa la huella capturada por el sensor para identificar al estudiante.
                                </p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Template / Hash de Huella a Identificar:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="id-huella-input"
                                           placeholder="Ej: HUELLA_HEX_SAMPLE_001"
                                           onkeydown="if(event.key==='Enter') identificarHuella()">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('id-huella-input').value='HUELLA_HEX_SAMPLE_00'+Math.floor(Math.random()*11+1)">
                                        <i class="fa-solid fa-dice"></i>
                                    </button>
                                </div>
                            </div>

                            <button class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm mb-3" id="btn-identificar" onclick="identificarHuella()">
                                <i class="fa-solid fa-fingerprint me-2"></i> Identificar Huella
                            </button>

                            <p class="text-center text-muted small">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                También puedes usar huellas de prueba: <code>HUELLA_HEX_SAMPLE_001</code> a <code>011</code>
                            </p>
                        </div>

                        <!-- Resultado de identificación -->
                        <div class="col-lg-7">
                            <!-- Estado inicial -->
                            <div id="id-resultado-placeholder" class="text-center py-5">
                                <div style="font-size:4rem; opacity:0.12; color:#0a1128;">
                                    <i class="fa-solid fa-user-secret"></i>
                                </div>
                                <p class="text-muted fw-semibold mt-3">El resultado de la identificación aparecerá aquí.</p>
                                <p class="small text-muted">El sistema buscará la huella en todos los slots registrados de todos los estudiantes.</p>
                            </div>

                            <!-- Resultado (oculto hasta búsqueda) -->
                            <div id="id-resultado-panel" class="d-none">
                                <!-- Se llena dinámicamente por JS -->
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /tab-content -->
        </div><!-- /card-surface -->
    </section>

    <!-- FOOTER -->
    <footer class="footer-dashboard text-center">
        <div class="container">
            <p class="mb-1"><strong>SFS Access Control</strong> &copy; <?= date('Y') ?> - Institución Educativa Jorge Robledo</p>
            <p class="mb-0 text-muted small">Sistema Integral de Control de Asistencia Biométrica y Gestión Académica</p>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SCRIPT DINÁMICO & AJAX EN TIEMPO REAL -->
    <script>
        // Actualizar reloj digital en vivo
        setInterval(() => {
            const ahora = new Date();
            document.getElementById('live-clock').textContent = ahora.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }, 1000);

        // Helper para llenar el formulario de simulación
        function seleccionarUsuarioDemo(valor) {
            if (!valor) return;
            const partes = valor.split('|');
            document.getElementById('input-huella').value = partes[0] || '';
            document.getElementById('input-documento').value = partes[1] || '';
        }

        // Filtro instantáneo de la tabla en el cliente
        function filtrarTabla() {
            const input = document.getElementById('buscador-tabla').value.toLowerCase();
            const filas = document.querySelectorAll('#tabla-accesos-body tr');
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(input) ? '' : 'none';
            });
        }

        // Envío AJAX de simulación biométrica
        async function enviarSimulacion(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-simular');
            const alertBox = document.getElementById('live-alert');
            const scannerIcon = document.getElementById('scanner-icon');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Leyendo huella...';
            if (scannerIcon) scannerIcon.style.color = '#00f2fe';

            const formData = new FormData(document.getElementById('form-simulador-biometrico'));

            try {
                const response = await fetch('index.php?c=acceso&a=simularManual', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                if (data.status === 'exito') {
                    alertBox.classList.add('alert-success');
                    alertBox.innerHTML = `<strong><i class="fa-solid fa-circle-check me-1"></i> ${data.mensaje}</strong><br><small>Estudiante: ${data.usuario ? data.usuario.nombre : ''} (${data.usuario ? data.usuario.grado : ''}) - Movimiento: ${data.tipo_evento}</small>`;
                } else {
                    alertBox.classList.add('alert-danger');
                    alertBox.innerHTML = `<strong><i class="fa-solid fa-triangle-exclamation me-1"></i> ${data.mensaje}</strong>`;
                }

                // Recargar historial y contadores de inmediato
                recargarDatosHistorial();

            } catch (error) {
                console.error('Error en simulación:', error);
                alertBox.classList.remove('d-none');
                alertBox.classList.add('alert-danger');
                alertBox.innerHTML = '<strong>Error de conexión</strong> al procesar la huella dactilar.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-fingerprint me-1"></i> Simular Lectura Biométrica';
                if (scannerIcon) scannerIcon.style.color = '#38bdf8';
            }
        }

        // Función AJAX para actualizar en tiempo real el historial y los KPIs
        async function recargarDatosHistorial() {
            const icon = document.getElementById('icon-refresh');
            if (icon) icon.classList.add('fa-spin');

            try {
                const response = await fetch('index.php?c=acceso&a=obtenerHistorialJson');
                const data = await response.json();

                if (data.status === 'ok') {
                    // Actualizar KPIs
                    document.getElementById('kpi-estudiantes-dentro').textContent = data.estadisticas.estudiantes_dentro;
                    document.getElementById('kpi-entradas').textContent = data.estadisticas.total_entradas;
                    document.getElementById('kpi-salidas').textContent = data.estadisticas.total_salidas;
                    document.getElementById('kpi-rechazados').textContent = data.estadisticas.rechazados;

                    // Actualizar Tabla de Accesos
                    const tbody = document.getElementById('tabla-accesos-body');
                    if (data.historial && data.historial.length > 0) {
                        tbody.innerHTML = data.historial.map(acc => {
                            const partes = (acc.nombre_usuario || 'X').trim().split(' ');
                            const iniciales = (partes[0].charAt(0) + (partes[1] ? partes[1].charAt(0) : '')).toUpperCase();
                            const hora = new Date(acc.fecha_hora).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

                            const badgeEvento = acc.tipo_evento === 'ENTRADA' 
                                ? '<span class="pill-badge badge-in"><i class="fa-solid fa-arrow-down-to-bracket"></i> ENTRADA</span>'
                                : '<span class="pill-badge badge-out"><i class="fa-solid fa-arrow-up-from-bracket"></i> SALIDA</span>';

                            const badgeEstado = acc.estado_acceso === 'APROBADO'
                                ? '<span class="pill-badge badge-approved"><i class="fa-solid fa-check"></i> Aprobado</span>'
                                : '<span class="pill-badge badge-denied"><i class="fa-solid fa-xmark"></i> Rechazado</span>';

                            return `
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="avatar-initials">${iniciales}</span>
                                            <div>
                                                <strong class="d-block text-dark">${acc.nombre_usuario}</strong>
                                                <span class="text-muted small">Doc: ${acc.documento_usuario}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1">${acc.grado_usuario}</span></td>
                                    <td class="small fw-semibold text-secondary">${hora}</td>
                                    <td>${badgeEvento}</td>
                                    <td>${badgeEstado}</td>
                                    <td class="small text-muted text-truncate" style="max-width: 170px;" title="${acc.observaciones || ''}">${acc.observaciones || 'N/A'}</td>
                                </tr>
                            `;
                        }).join('');
                    }

                    // Actualizar desglose de asistencia por grado
                    const listaGrados = document.getElementById('lista-desglose-grados');
                    if (data.por_grado && data.por_grado.length > 0) {
                        listaGrados.innerHTML = '<ul class="list-group list-group-flush small">' + 
                            data.por_grado.map(item => `
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-light">
                                    <span><i class="fa-solid fa-graduation-cap text-primary me-2"></i>Grado <strong>${item.grado}</strong></span>
                                    <span class="badge bg-primary rounded-pill">${item.total} alumnos</span>
                                </li>
                            `).join('') + '</ul>';
                    } else {
                        listaGrados.innerHTML = '<div class="text-center py-3 text-muted small"><i class="fa-solid fa-info-circle me-1"></i> No hay estudiantes dentro actualmente.</div>';
                    }

                    // Re-aplicar filtro si hay texto en el buscador
                    filtrarTabla();
                }
            } catch (err) {
                console.error('Error al actualizar datos en tiempo real:', err);
            } finally {
                if (icon) icon.classList.remove('fa-spin');
            }
        }

        // Auto-refresco cada 5 segundos
        setInterval(recargarDatosHistorial, 5000);
    </script>

    <!-- ================================================================
         JAVASCRIPT: MÓDULO DE GESTIÓN DE HUELLAS DACTILARES
         ================================================================ -->
    <script>
        // Estado global del módulo de huellas
        const huellasState = {
            usuarioId: null,
            usuarioNombre: '',
            usuarioGrado: '',
            huellas: [],       // Array de huellas ya registradas
            slotActivo: null   // Slot actualmente siendo editado
        };

        // Emojis de dedos por nombre
        const DEDOS_EMOJI = {
            'Pulgar Derecho': '👍', 'Índice Derecho': '☝️', 'Medio Derecho': '🖕',
            'Anular Derecho': '💍', 'Meñique Derecho': '🤙',
            'Pulgar Izquierdo': '👍', 'Índice Izquierdo': '☝️', 'Medio Izquierdo': '🖕',
            'Anular Izquierdo': '💍', 'Meñique Izquierdo': '🤙'
        };

        // ─── FILTRAR LISTA DE ESTUDIANTES ────────────────────────────────────
        function filtrarEstudiantes() {
            const q = document.getElementById('buscador-estudiantes').value.toLowerCase();
            document.querySelectorAll('.estudiante-item').forEach(el => {
                const txt = (el.dataset.nombre + ' ' + el.dataset.doc + ' ' + el.dataset.grado).toLowerCase();
                el.style.display = txt.includes(q) ? '' : 'none';
            });
        }

        // ─── SELECCIONAR UN ESTUDIANTE ───────────────────────────────────────
        async function seleccionarEstudiante(id, nombre, grado) {
            // Highlight selected card
            document.querySelectorAll('.student-finger-card').forEach(c => c.classList.remove('selected'));
            const card = document.querySelector(`.student-finger-card[data-id="${id}"]`);
            if (card) card.classList.add('selected');

            // Actualizar estado
            huellasState.usuarioId = id;
            huellasState.usuarioNombre = nombre;
            huellasState.usuarioGrado = grado;

            // Mostrar panel
            document.getElementById('panel-sin-seleccion').classList.add('d-none');
            const panel = document.getElementById('panel-gestion-huellas');
            panel.classList.remove('d-none');

            // Actualizar header del panel
            const partes = nombre.trim().split(' ');
            const iniciales = ((partes[0]?.charAt(0) || '') + (partes[1]?.charAt(0) || '')).toUpperCase();
            document.getElementById('slot-avatar').textContent = iniciales;
            document.getElementById('slot-nombre').textContent = nombre;
            document.getElementById('slot-grado').textContent = grado;

            // Cargar huellas del estudiante
            await cargarHuellasEstudiante(id);
        }

        // ─── CARGAR HUELLAS DEL ESTUDIANTE ──────────────────────────────────
        async function cargarHuellasEstudiante(id) {
            const grid = document.getElementById('finger-slots-grid');
            grid.innerHTML = '<div class="col-span-3 text-center py-3 text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i> Cargando huellas...</div>';

            try {
                const resp = await fetch(`index.php?c=acceso&a=obtenerHuellasEstudiante&usuario_id=${id}`);
                const data = await resp.json();

                if (data.status !== 'ok') throw new Error(data.mensaje);

                huellasState.huellas = data.huellas;
                renderizarSlotsHuellas(data.huellas, data.total_registradas);

            } catch (err) {
                grid.innerHTML = `<div class="text-danger small"><i class="fa-solid fa-exclamation-circle me-1"></i> Error al cargar huellas: ${err.message}</div>`;
            }
        }

        // ─── RENDERIZAR GRID DE 6 SLOTS ──────────────────────────────────────
        function renderizarSlotsHuellas(huellas, totalRegistradas) {
            const grid = document.getElementById('finger-slots-grid');
            const badge = document.getElementById('slot-conteo-badge');
            badge.textContent = `${totalRegistradas}/6 slots`;
            badge.className = `badge fw-bold px-3 py-2 ${totalRegistradas === 6 ? 'bg-success bg-opacity-15 text-success' : 'bg-primary bg-opacity-10 text-primary'}`;

            // Ocultar form de registro si estaba abierto
            document.getElementById('form-registro-huella').classList.add('d-none');
            document.getElementById('huellas-alert').classList.add('d-none');
            huellasState.slotActivo = null;

            // Crear mapa slot→huella
            const slotMap = {};
            huellas.forEach(h => { slotMap[h.slot_numero] = h; });

            let html = '';
            for (let slot = 1; slot <= 6; slot++) {
                const huella = slotMap[slot];
                if (huella) {
                    const emoji = DEDOS_EMOJI[huella.dedo] || '👆';
                    html += `
                        <div class="finger-slot registered" onclick="abrirFormHuella(${slot})" data-slot="${slot}">
                            <span class="slot-badge"><i class="fa-solid fa-check" style="font-size:0.6rem;"></i></span>
                            <span class="slot-delete" onclick="eliminarSlot(event, ${slot})" title="Eliminar huella">
                                <i class="fa-solid fa-times" style="font-size:0.55rem;"></i>
                            </span>
                            <span class="slot-icon">${emoji}</span>
                            <div class="slot-label">${huella.dedo}</div>
                            <div style="font-size:0.65rem; color:#059669; margin-top:2px;">Slot ${slot}</div>
                        </div>`;
                } else {
                    html += `
                        <div class="finger-slot" onclick="abrirFormHuella(${slot})" data-slot="${slot}">
                            <span class="slot-icon" style="color:#cbd5e1;">
                                <i class="fa-regular fa-hand" style="font-size:1.6rem;"></i>
                            </span>
                            <div class="slot-label">Slot ${slot}<br><small style="font-size:0.65rem; font-weight:400;">Vacío</small></div>
                        </div>`;
                }
            }

            grid.innerHTML = html;
        }

        // ─── ABRIR FORMULARIO DE REGISTRO PARA UN SLOT ──────────────────────
        function abrirFormHuella(slotNumero) {
            huellasState.slotActivo = slotNumero;

            const form = document.getElementById('form-registro-huella');
            document.getElementById('form-slot-numero').value = slotNumero;
            document.getElementById('form-slot-label').textContent = `Slot ${slotNumero}`;
            document.getElementById('huellas-alert').classList.add('d-none');

            // Pre-cargar si el slot ya tiene datos
            const slotMap = {};
            huellasState.huellas.forEach(h => { slotMap[h.slot_numero] = h; });
            const existente = slotMap[slotNumero];

            if (existente) {
                document.getElementById('form-dedo').value = existente.dedo;
                document.getElementById('form-huella-template').value = existente.huella_template;
            } else {
                document.getElementById('form-dedo').value = '';
                document.getElementById('form-huella-template').value = '';
            }

            // Marcar slot activo visualmente
            document.querySelectorAll('.finger-slot').forEach(s => s.classList.remove('registering'));
            const activeSlot = document.querySelector(`.finger-slot[data-slot="${slotNumero}"]`);
            if (activeSlot) activeSlot.classList.add('registering');

            form.classList.remove('d-none');
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // ─── GENERAR HASH DEMO (PARA PRUEBAS) ────────────────────────────────
        function generarHashDemo() {
            const chars = 'ABCDEF0123456789';
            let hash = 'HEX_';
            for (let i = 0; i < 16; i++) hash += chars[Math.floor(Math.random() * chars.length)];
            document.getElementById('form-huella-template').value = hash;
        }

        // ─── GUARDAR HUELLA EN EL SLOT ────────────────────────────────────────
        async function guardarHuella() {
            const slot = document.getElementById('form-slot-numero').value;
            const dedo = document.getElementById('form-dedo').value;
            const template = document.getElementById('form-huella-template').value.trim();
            const alertBox = document.getElementById('huellas-alert');

            if (!dedo || !template) {
                mostrarAlertaHuellas('warning', '<i class="fa-solid fa-triangle-exclamation me-1"></i> Completa todos los campos: dedo y template de huella.');
                return;
            }

            const fd = new FormData();
            fd.append('usuario_id', huellasState.usuarioId);
            fd.append('slot_numero', slot);
            fd.append('dedo', dedo);
            fd.append('huella_template', template);

            try {
                const resp = await fetch('index.php?c=acceso&a=guardarHuellaEstudiante', { method: 'POST', body: fd });
                const data = await resp.json();

                if (data.status === 'ok') {
                    mostrarAlertaHuellas('success', `<i class="fa-solid fa-circle-check me-1"></i> ${data.mensaje}`);
                    await cargarHuellasEstudiante(huellasState.usuarioId);
                    actualizarConteoEnLista(huellasState.usuarioId);
                } else {
                    mostrarAlertaHuellas('danger', `<i class="fa-solid fa-xmark-circle me-1"></i> ${data.mensaje}`);
                }
            } catch (e) {
                mostrarAlertaHuellas('danger', '<i class="fa-solid fa-xmark-circle me-1"></i> Error de conexión al guardar la huella.');
            }
        }

        // ─── ELIMINAR HUELLA DE UN SLOT ───────────────────────────────────────
        async function eliminarSlot(event, slotNumero) {
            event.stopPropagation();
            if (!confirm(`¿Eliminar la huella del Slot ${slotNumero}?`)) return;

            const fd = new FormData();
            fd.append('usuario_id', huellasState.usuarioId);
            fd.append('slot_numero', slotNumero);

            try {
                const resp = await fetch('index.php?c=acceso&a=eliminarHuellaEstudiante', { method: 'POST', body: fd });
                const data = await resp.json();

                if (data.status === 'ok') {
                    mostrarAlertaHuellas('success', `<i class="fa-solid fa-circle-check me-1"></i> ${data.mensaje}`);
                    await cargarHuellasEstudiante(huellasState.usuarioId);
                    actualizarConteoEnLista(huellasState.usuarioId);
                } else {
                    mostrarAlertaHuellas('danger', `<i class="fa-solid fa-xmark-circle me-1"></i> ${data.mensaje}`);
                }
            } catch (e) {
                mostrarAlertaHuellas('danger', '<i class="fa-solid fa-xmark-circle me-1"></i> Error de conexión al eliminar la huella.');
            }
        }

        // ─── CANCELAR REGISTRO ────────────────────────────────────────────────
        function cancelarRegistroHuella() {
            document.getElementById('form-registro-huella').classList.add('d-none');
            document.querySelectorAll('.finger-slot').forEach(s => s.classList.remove('registering'));
            huellasState.slotActivo = null;
        }

        // ─── ACTUALIZAR CONTEO EN LA LISTA DE ESTUDIANTES ────────────────────
        async function actualizarConteoEnLista(usuarioId) {
            try {
                const resp = await fetch(`index.php?c=acceso&a=obtenerHuellasEstudiante&usuario_id=${usuarioId}`);
                const data = await resp.json();
                if (data.status !== 'ok') return;

                const total = data.total_registradas;
                const pct = Math.round((total / 6) * 100);
                const card = document.querySelector(`.student-finger-card[data-id="${usuarioId}"]`);
                if (card) {
                    const bar = card.querySelector('.huella-progress-bar');
                    const count = card.querySelector('.small.fw-bold');
                    if (bar) bar.style.width = `${pct}%`;
                    if (count) {
                        count.textContent = `${total}/6`;
                        count.className = `small fw-bold ${total === 6 ? 'text-success' : 'text-muted'}`;
                    }
                }
            } catch (e) { /* silencioso */ }
        }

        // ─── MOSTRAR ALERTA EN EL PANEL DE HUELLAS ────────────────────────────
        function mostrarAlertaHuellas(tipo, html) {
            const box = document.getElementById('huellas-alert');
            box.className = `alert alert-${tipo} py-2 px-3 small rounded-3 mb-3`;
            box.innerHTML = html;
            setTimeout(() => box.classList.add('d-none'), 5000);
        }

        // ─── IDENTIFICAR HUELLA (TAB 2) ──────────────────────────────────────
        async function identificarHuella() {
            const template = document.getElementById('id-huella-input').value.trim();
            if (!template) {
                alert('Ingresa el template de huella a identificar.');
                return;
            }

            const icon = document.getElementById('id-fp-icon');
            const btn = document.getElementById('btn-identificar');
            const placeholder = document.getElementById('id-resultado-placeholder');
            const panel = document.getElementById('id-resultado-panel');

            // Animación de escaneo
            icon.className = 'fa-solid fa-fingerprint big-fingerprint-icon scanning';
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Buscando...';
            placeholder.classList.add('d-none');
            panel.classList.add('d-none');

            try {
                const fd = new FormData();
                fd.append('huella', template);
                fd.append('tipo_evento', '');

                const resp = await fetch('index.php?c=acceso&a=simularManual', { method: 'POST', body: fd });
                const data = await resp.json();

                let html = '';
                if (data.status === 'exito' && data.usuario) {
                    const u = data.usuario;
                    const partes = (u.nombre || 'X').split(' ');
                    const iniciales = ((partes[0]?.charAt(0) || '') + (partes[1]?.charAt(0) || '')).toUpperCase();
                    icon.className = 'fa-solid fa-fingerprint big-fingerprint-icon identified';
                    html = `
                        <div class="id-result-card result-success mb-3">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="avatar-initials" style="width:56px;height:56px;font-size:1.1rem;background:linear-gradient(135deg,#059669,#10b981);">${iniciales}</span>
                                <div>
                                    <div class="badge bg-success bg-opacity-20 text-success fw-bold mb-1 px-2 py-1">
                                        <i class="fa-solid fa-circle-check me-1"></i> Identificado exitosamente
                                    </div>
                                    <h5 class="fw-bold text-dark mb-0">${u.nombre}</h5>
                                    <small class="text-muted">${u.grado} &bull; ${u.rol}</small>
                                </div>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <div class="p-2 rounded-2" style="background:rgba(5,150,105,0.08);">
                                        <div class="text-muted fw-bold text-uppercase" style="font-size:0.7rem;">Dedo detectado</div>
                                        <div class="fw-bold text-success">${u.dedo_identificado || 'Huella Principal'}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded-2" style="background:rgba(5,150,105,0.08);">
                                        <div class="text-muted fw-bold text-uppercase" style="font-size:0.7rem;">Slot biométrico</div>
                                        <div class="fw-bold text-success">Slot ${u.slot_identificado || '—'}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded-2" style="background:rgba(5,150,105,0.08);">
                                        <div class="text-muted fw-bold text-uppercase" style="font-size:0.7rem;">Estado</div>
                                        <div class="fw-bold text-success">${u.estado}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded-2" style="background:rgba(5,150,105,0.08);">
                                        <div class="text-muted fw-bold text-uppercase" style="font-size:0.7rem;">Evento registrado</div>
                                        <div class="fw-bold text-success">${data.tipo_evento}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="small text-muted text-center">
                            <i class="fa-solid fa-check-circle text-success me-1"></i>
                            El evento <strong>${data.tipo_evento}</strong> fue registrado automáticamente en el historial.
                        </p>`;
                } else {
                    icon.className = 'fa-solid fa-fingerprint big-fingerprint-icon unknown';
                    const esInactivo = data.codigo === 'ESTADO_INACTIVO';
                    const u = data.usuario;
                    html = `
                        <div class="id-result-card result-error mb-3">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div style="width:56px;height:56px;border-radius:12px;background:linear-gradient(135deg,#ef4444,#f87171);display:flex;align-items:center;justify-content:center;">
                                    <i class="fa-solid fa-${esInactivo ? 'user-slash' : 'user-secret'} text-white fs-4"></i>
                                </div>
                                <div>
                                    <div class="badge bg-danger bg-opacity-20 text-danger fw-bold mb-1 px-2 py-1">
                                        <i class="fa-solid fa-xmark-circle me-1"></i> ${esInactivo ? 'Usuario Inactivo' : 'Huella No Reconocida'}
                                    </div>
                                    <h6 class="fw-bold text-dark mb-0">${esInactivo && u ? u.nombre : 'Persona no registrada'}</h6>
                                    <small class="text-muted">${data.mensaje}</small>
                                </div>
                            </div>
                            ${esInactivo && u ? `
                            <div class="p-2 rounded-2 small" style="background:rgba(239,68,68,0.08);">
                                <strong>Estado:</strong> ${u.estado} &bull; <strong>Grado:</strong> ${u.grado}
                            </div>` : `
                            <p class="small text-danger mb-0">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                La huella <code>${template}</code> no existe en la base de datos del sistema.
                            </p>`}
                        </div>`;
                }

                panel.innerHTML = html;
                panel.classList.remove('d-none');

            } catch (e) {
                icon.className = 'fa-solid fa-fingerprint big-fingerprint-icon unknown';
                panel.innerHTML = `<div class="id-result-card result-error"><i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Error de conexión al procesar la identificación.</div>`;
                panel.classList.remove('d-none');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Identificar Huella';
                // Restaurar ícono después de 4 segundos
                setTimeout(() => {
                    if (icon.classList.contains('identified') || icon.classList.contains('unknown')) {
                        icon.className = 'fa-solid fa-fingerprint big-fingerprint-icon';
                    }
                }, 4000);
            }
        }
    </script>
</body>
</html>

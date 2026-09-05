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
                    <i class="fa-solid fa-file-excel me-1"></i> Raciones CSV
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
                        Supervisión en tiempo real del ingreso y salida de estudiantes, auditoría de seguridad y cálculo automático de raciones para el restaurante escolar.
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
            <!-- Restaurante Escolar / Estudiantes en Plantel -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Estudiantes en Plantel</span>
                            <h2 class="fw-bold my-1 text-success" id="kpi-estudiantes-dentro"><?= $estudiantesDentro ?? 0 ?></h2>
                            <span class="small text-muted"><i class="fa-solid fa-utensils text-success me-1"></i> Raciones a Servir</span>
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

            <!-- 3. SIMULADOR BIOMÉTRICO & RESTAURANTE ESCOLAR -->
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

                <!-- CARD DESGLOSE RESTAURANTE ESCOLAR -->
                <div class="card-surface">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fa-solid fa-utensils text-success me-2"></i>Raciones por Grado
                        </h6>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">Restaurante</span>
                    </div>
                    <p class="small text-muted mb-3">
                        Distribución de porciones a preparar hoy para los estudiantes presentes en el colegio:
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
                        <i class="fa-solid fa-download me-1"></i> Descargar Planilla de Restaurante
                    </a>
                </div>

            </div>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="footer-dashboard text-center">
        <div class="container">
            <p class="mb-1"><strong>SFS Access Control</strong> &copy; <?= date('Y') ?> - Institución Educativa Jorge Robledo</p>
            <p class="mb-0 text-muted small">Sistema Integral de Control de Asistencia Biométrica y Gestión del Restaurante Escolar</p>
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

                    // Actualizar desglose del restaurante escolar
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
</body>
</html>

<?php
/**
 * views/dashboard/index.php
 * Vista del Panel Principal
 */

$configRol = getRolConfig($rolActual);
$iniciales = getUsuarioIniciales($nombreSesion);

ob_start();
?>
<!-- Hero Banner -->
<section class="page-hero">
    <div class="container-fluid px-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill px-3 py-1" style="background:<?= $configRol['badge_bg'] ?>; color:<?= $configRol['badge_txt'] ?>; font-size:0.75rem;">
                        <i class="<?= $configRol['icono'] ?> me-1" style="color:<?= $configRol['icono_color'] ?>;"></i>
                        <?= htmlspecialchars($configRol['titulo']) ?>
                    </span>
                    <span class="text-white-50 small">&bull; Ciclo Escolar 2026</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">¡Bienvenido(a), <?= htmlspecialchars($nombreSesion) ?>!</h1>
                <p class="text-white-50 mb-0 small">
                    Sistema Biométrico de Control de Accesos y Asistencia Institucional &bull; Sede Principal
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="px-3 py-2 rounded-3 text-end" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);">
                    <small class="text-white-50 d-block" style="font-size:0.7rem;">ESTADO DEL SISTEMA</small>
                    <span class="text-white fw-bold d-flex align-items-center gap-1" style="font-size:0.85rem;">
                        <span class="live-dot" style="width:7px;height:7px;"></span> En Operación Normal
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contenido del Panel Principal -->
<main class="container-fluid px-4 py-4 flex-grow-1">

    <!-- 1. KPIs GLOBALES -->
    <div class="row g-3 mb-4">
        <!-- Estudiantes en Plantel -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Estudiantes en Plantel</span>
                        <h2 class="fw-bold my-1 text-success" id="kpi-estudiantes-dentro"><?= $estudiantesDentro ?? 0 ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-graduation-cap text-success me-1"></i> Aforo Actual</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-green">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ingresos Portería Hoy -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Ingresos Hoy</span>
                        <h2 class="fw-bold my-1 text-primary" id="kpi-entradas"><?= $estadisticas['total_entradas'] ?? 0 ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-door-open text-primary me-1"></i> Entradas Registradas</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-blue">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salidas Portería Hoy -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Salidas Hoy</span>
                        <h2 class="fw-bold my-1 text-warning" id="kpi-salidas"><?= $estadisticas['total_salidas'] ?? 0 ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-person-walking-arrow-right text-warning me-1"></i> Salidas Registradas</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-amber">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rechazados / Faltas -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <?php if (esCoordinador() || esRector()): ?>
                            <span class="text-muted small fw-bold text-uppercase">Faltas Aula</span>
                            <h2 class="fw-bold my-1 text-danger"><?= $resumenConsolidado['faltas_injustificadas'] ?? 0 ?></h2>
                            <span class="small text-danger fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Sin Justificar</span>
                        <?php else: ?>
                            <span class="text-muted small fw-bold text-uppercase">Accesos Denegados</span>
                            <h2 class="fw-bold my-1 text-danger" id="kpi-rechazados"><?= $estadisticas['rechazados'] ?? 0 ?></h2>
                            <span class="small text-danger fw-semibold"><i class="fa-solid fa-ban me-1"></i> Alertas de Portería</span>
                        <?php endif; ?>
                    </div>
                    <div class="kpi-icon-wrapper kpi-rose">
                        <i class="fa-solid fa-shield-xmark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. ACCESOS RÁPIDOS SEGÚN ROL -->
    <div class="card-surface mb-4">
        <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-bolt text-warning"></i>
            Módulos y Acciones Rápidas
        </h5>
        <div class="row g-3">
            <?php if (esDocente() || esAdmin()): ?>
            <div class="col-12 col-md-4">
                <a href="asistencia.php" class="text-decoration-none">
                    <div class="p-3 rounded-3 border h-100 d-flex align-items-center gap-3 bg-light hover-shadow transition" style="border-left: 4px solid #2563eb !important;">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #1d4ed8, #3b82f6); font-size: 1.4rem;">
                            <i class="fa-solid fa-clipboard-user"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Tomar Asistencia</h6>
                            <p class="small text-muted mb-0">Pasar lista por grado o con lector de huella en clase.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if (esCelador() || esAdmin()): ?>
            <div class="col-12 col-md-4">
                <a href="porteria.php" class="text-decoration-none">
                    <div class="p-3 rounded-3 border h-100 d-flex align-items-center gap-3 bg-light hover-shadow transition" style="border-left: 4px solid #059669 !important;">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #059669, #10b981); font-size: 1.4rem;">
                            <i class="fa-solid fa-door-open"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Terminal Portería</h6>
                            <p class="small text-muted mb-0">Validar ingresos y salidas en torniquetes y puertas.</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a href="huellas.php" class="text-decoration-none">
                    <div class="p-3 rounded-3 border h-100 d-flex align-items-center gap-3 bg-light hover-shadow transition" style="border-left: 4px solid #0284c7 !important;">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #0284c7, #38bdf8); font-size: 1.4rem;">
                            <i class="fa-solid fa-fingerprint"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Biometría & Huellas</h6>
                            <p class="small text-muted mb-0">Enrolar y administrar los 6 slots dactilares por alumno.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if (esCoordinador() || esRector() || esAdmin()): ?>
            <div class="col-12 col-md-4">
                <a href="faltas.php" class="text-decoration-none">
                    <div class="p-3 rounded-3 border h-100 d-flex align-items-center gap-3 bg-light hover-shadow transition" style="border-left: 4px solid #d97706 !important;">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #d97706, #f59e0b); font-size: 1.4rem;">
                            <i class="fa-solid fa-user-xmark"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Control de Faltas</h6>
                            <p class="small text-muted mb-0">Auditar ausencias no justificadas y expedir permisos.</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a href="aforo.php" class="text-decoration-none">
                    <div class="p-3 rounded-3 border h-100 d-flex align-items-center gap-3 bg-light hover-shadow transition" style="border-left: 4px solid #475569 !important;">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #334155, #64748b); font-size: 1.4rem;">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Aforo Institucional</h6>
                            <p class="small text-muted mb-0">Distribución de estudiantes dentro del colegio por grado.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if (esAdmin()): ?>
            <div class="col-12 col-md-4">
                <a href="usuarios.php" class="text-decoration-none">
                    <div class="p-3 rounded-3 border h-100 d-flex align-items-center gap-3 bg-light hover-shadow transition" style="border-left: 4px solid #7c3aed !important;">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #7c3aed, #a855f7); font-size: 1.4rem;">
                            <i class="fa-solid fa-users-gear"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Usuarios y Claves</h6>
                            <p class="small text-muted mb-0">Gestión de profesores, celadores y reinicio de contraseñas (123456).</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-4">
                <a href="javascript:void(0)" onclick="abrirModalReiniciarAsistencias()" class="text-decoration-none">
                    <div class="p-3 rounded-3 border h-100 d-flex align-items-center gap-3 bg-light hover-shadow transition" style="border-left: 4px solid #ef4444 !important;">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #dc2626, #ef4444); font-size: 1.4rem;">
                            <i class="fa-solid fa-rotate-left"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-danger">Reiniciar Asistencias</h6>
                            <p class="small text-muted mb-0">Herramienta administrativa para resetear registros de aula o portería.</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. TABLA DE ACTIVIDAD RECIENTE & AFORO RÁPIDO -->
    <div class="row g-4">
        <!-- Actividad Reciente de Portería -->
        <div class="col-12 col-xl-8">
            <div class="card-surface h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-0">Accesos Recientes en Portería</h5>
                        <small class="text-muted">Últimos movimientos registrados en tiempo real</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="historial.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            Ver Historial Completo <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hora</th>
                                <th>Estudiante</th>
                                <th>Grado</th>
                                <th>Movimiento</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-accesos-body">
                            <?php if (empty($accesosRecientes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-inbox fs-3 d-block mb-2"></i>
                                        No hay registros de acceso el día de hoy.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($accesosRecientes, 0, 8) as $acc): 
                                    $nombre = $acc['nombre_usuario'] ?? $acc['nombre'] ?? 'Desconocido';
                                    $documento = $acc['documento_usuario'] ?? $acc['documento'] ?? '—';
                                    $grado = $acc['grado_usuario'] ?? $acc['grado'] ?? '—';
                                    $estadoAcceso = $acc['estado_acceso'] ?? $acc['resultado'] ?? 'APROBADO';
                                    $esAprobado = in_array(strtoupper($estadoAcceso), ['APROBADO', 'PERMITIDO']);
                                ?>
                                    <tr>
                                        <td class="small fw-semibold text-muted">
                                            <?= date('h:i A', strtotime($acc['fecha_hora'])) ?>
                                        </td>
                                        <td>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($nombre) ?></strong>
                                            <small class="text-muted">Doc: <?= htmlspecialchars($documento) ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($grado) ?></span></td>
                                        <td>
                                            <?php if ($acc['tipo_evento'] === 'ENTRADA'): ?>
                                                <span class="badge bg-success bg-opacity-15 text-success">
                                                    <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Entrada
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-15 text-warning">
                                                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Salida
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($esAprobado): ?>
                                                <span class="badge bg-success text-white"><i class="fa-solid fa-check"></i> Permitido</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger text-white"><i class="fa-solid fa-xmark"></i> Denegado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Resumen de Aforo por Grado -->
        <div class="col-12 col-xl-4">
            <div class="card-surface h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0">Aforo por Grado</h5>
                    <a href="aforo.php" class="small text-primary text-decoration-none fw-bold">Ver todos</a>
                </div>
                <p class="small text-muted mb-3">Estudiantes presentes dentro de la institución</p>

                <div class="d-flex flex-column gap-3">
                    <?php 
                    $gradosMuestra = ['6°01', '7°01', '8°01', '9°01', '10°01', '11°01'];
                    $mapAforo = [];
                    if (!empty($estudiantesPorGrado)) {
                        foreach ($estudiantesPorGrado as $g) {
                            $mapAforo[$g['grado']] = (int)($g['total_dentro'] ?? 0);
                        }
                    }
                    foreach ($gradosMuestra as $gNombre):
                        $dentro = $mapAforo[$gNombre] ?? 0;
                        $porc = min(100, round(($dentro / 35) * 100));
                    ?>
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <strong class="text-dark">Grado <?= $gNombre ?></strong>
                                <span class="text-muted fw-bold"><?= $dentro ?> / 35 alum.</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $porc ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 p-3 rounded-3 bg-light border text-center">
                    <span class="small text-muted d-block mb-1">Total de Estudiantes Matriculados</span>
                    <strong class="fs-4 text-primary fw-bold">515</strong>
                    <span class="text-muted small d-block">Distribuidos en 16 grupos</span>
                </div>
            </div>
        </div>
    </div>

</main>
<?php
$contenido = ob_get_clean();
$paginaActual = 'panel';
$tituloPagina = 'Panel Principal';
$breadcrumb = 'Panel Principal';

$scriptExtra = <<<JS
async function recargarKPIs() {
    try {
        const resp = await fetch('index.php?c=acceso&a=obtenerHistorialJson');
        const data = await resp.json();
        if (data.status === 'ok') {
            const kd = document.getElementById('kpi-estudiantes-dentro');
            if (kd) kd.textContent = data.estadisticas.estudiantes_dentro;
            const ke = document.getElementById('kpi-entradas');
            if (ke) ke.textContent = data.estadisticas.total_entradas;
            const ks = document.getElementById('kpi-salidas');
            if (ks) ks.textContent = data.estadisticas.total_salidas;
            const kr = document.getElementById('kpi-rechazados');
            if (kr) kr.textContent = data.estadisticas.rechazados;
        }
    } catch(e) {}
}
setInterval(recargarKPIs, 10000);
JS;

require __DIR__ . '/../layout/app_layout.php';

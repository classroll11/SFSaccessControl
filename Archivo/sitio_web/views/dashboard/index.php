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

    <!-- 2. ACCESOS RÁPIDOS SEGÚN ROL (ACORDEÓN DESPLEGABLE) -->
    <?php
    $accionesRapidas = [];

    if (esDocente() || esAdmin()) {
        $accionesRapidas[] = [
            'url'     => 'asistencia.php',
            'border'  => '#2563eb',
            'grad'    => 'linear-gradient(135deg, #1d4ed8, #3b82f6)',
            'icono'   => 'fa-solid fa-clipboard-user',
            'titulo'  => 'Tomar Asistencia',
            'desc'    => 'Pasar lista por grado o con lector de huella en clase.',
            'onclick' => null
        ];
    }

    if (esCelador() || esAdmin()) {
        $accionesRapidas[] = [
            'url'     => 'porteria.php',
            'border'  => '#059669',
            'grad'    => 'linear-gradient(135deg, #059669, #10b981)',
            'icono'   => 'fa-solid fa-door-open',
            'titulo'  => 'Terminal Portería',
            'desc'    => 'Validar ingresos y salidas en torniquetes y puertas.',
            'onclick' => null
        ];
        $accionesRapidas[] = [
            'url'     => 'huellas.php',
            'border'  => '#0284c7',
            'grad'    => 'linear-gradient(135deg, #0284c7, #38bdf8)',
            'icono'   => 'fa-solid fa-fingerprint',
            'titulo'  => 'Biometría & Huellas',
            'desc'    => 'Enrolar y administrar los 6 slots dactilares por alumno.',
            'onclick' => null
        ];
    }

    if (esCoordinador() || esRector() || esAdmin()) {
        $accionesRapidas[] = [
            'url'     => 'faltas.php',
            'border'  => '#d97706',
            'grad'    => 'linear-gradient(135deg, #d97706, #f59e0b)',
            'icono'   => 'fa-solid fa-user-xmark',
            'titulo'  => 'Control de Faltas',
            'desc'    => 'Auditar ausencias no justificadas y expedir permisos.',
            'onclick' => null
        ];
        $accionesRapidas[] = [
            'url'     => 'aforo.php',
            'border'  => '#475569',
            'grad'    => 'linear-gradient(135deg, #334155, #64748b)',
            'icono'   => 'fa-solid fa-chart-pie',
            'titulo'  => 'Aforo Institucional',
            'desc'    => 'Distribución de estudiantes dentro del colegio por grado.',
            'onclick' => null
        ];
    }

    if (esAdmin()) {
        $accionesRapidas[] = [
            'url'     => 'usuarios.php',
            'border'  => '#7c3aed',
            'grad'    => 'linear-gradient(135deg, #7c3aed, #a855f7)',
            'icono'   => 'fa-solid fa-users-gear',
            'titulo'  => 'Usuarios y Claves',
            'desc'    => 'Gestión de profesores, celadores y reinicio de contraseñas (123456).',
            'onclick' => null
        ];
        $accionesRapidas[] = [
            'url'          => 'javascript:void(0)',
            'border'       => '#ef4444',
            'grad'         => 'linear-gradient(135deg, #dc2626, #ef4444)',
            'icono'        => 'fa-solid fa-rotate-left',
            'titulo'       => 'Reiniciar Asistencias',
            'titulo_class' => 'text-danger',
            'desc'         => 'Herramienta administrativa para resetear registros de aula o portería.',
            'onclick'      => 'abrirModalReiniciarAsistencias()'
        ];
    }

    $totalAcciones = count($accionesRapidas);
    if ($totalAcciones === 1) {
        $colClass = 'col-12 col-md-8 col-lg-6';
    } elseif ($totalAcciones === 2) {
        $colClass = 'col-12 col-md-6';
    } elseif ($totalAcciones === 4) {
        $colClass = 'col-12 col-md-6 col-xl-3';
    } else {
        $colClass = 'col-12 col-md-6 col-lg-4';
    }
    ?>

    <?php if ($totalAcciones > 0): ?>
    <style>
        .header-accordion-modulos {
            transition: background-color 0.2s ease;
        }
        .header-accordion-modulos:hover {
            background-color: #f8fafc !important;
        }
        .header-accordion-modulos .flecha-acordeon {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .header-accordion-modulos.collapsed .flecha-acordeon {
            transform: rotate(180deg);
        }
    </style>
    <div class="card-surface mb-4 p-0 overflow-hidden shadow-sm border">
        <div class="p-3 px-4 d-flex align-items-center justify-content-between header-accordion-modulos"
             role="button"
             data-bs-toggle="collapse"
             data-bs-target="#collapseModulosRapidos"
             aria-expanded="true"
             aria-controls="collapseModulosRapidos"
             id="headerModulosRapidos"
             style="cursor: pointer; user-select: none;">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning" style="width: 38px; height: 38px; font-size: 1.15rem;">
                    <i class="fa-solid fa-bolt"></i>
                </span>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Módulos y Acciones Rápidas</h5>
                    <small class="text-muted">Atajos directos según tu rol en el sistema</small>
                </div>
                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 ms-sm-2 fw-medium">
                    <?= $totalAcciones ?> <?= $totalAcciones === 1 ? 'módulo' : 'módulos' ?>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="small d-none d-sm-inline fw-semibold text-muted">Desplegar / Ocultar</span>
                <span class="btn btn-sm btn-light rounded-circle shadow-none p-0 d-flex align-items-center justify-content-center border"
                      style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-chevron-up flecha-acordeon text-secondary"></i>
                </span>
            </div>
        </div>

        <div class="collapse show" id="collapseModulosRapidos">
            <div class="p-4 pt-3 border-top bg-light-subtle">
                <div class="row g-3 <?= ($totalAcciones === 1) ? 'justify-content-center' : '' ?>">
                    <?php foreach ($accionesRapidas as $item): ?>
                    <div class="<?= $colClass ?>">
                        <a href="<?= $item['url'] ?>" 
                           <?= !empty($item['onclick']) ? 'onclick="' . $item['onclick'] . '"' : '' ?> 
                           class="text-decoration-none d-block h-100">
                            <div class="p-3 rounded-3 border h-100 d-flex align-items-center gap-3 bg-white hover-shadow transition" 
                                 style="border-left: 4px solid <?= $item['border'] ?> !important;">
                                <div class="rounded-3 p-3 text-white flex-shrink-0" 
                                     style="background: <?= $item['grad'] ?>; font-size: 1.4rem; width: 54px; height: 54px; display: flex; align-items: center; justify-content: center;">
                                    <i class="<?= $item['icono'] ?>"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1 <?= $item['titulo_class'] ?? 'text-dark' ?>"><?= $item['titulo'] ?></h6>
                                    <p class="small text-muted mb-0"><?= $item['desc'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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

// Persistencia de estado del acordeón de módulos rápidos
document.addEventListener('DOMContentLoaded', function() {
    const colMod = document.getElementById('collapseModulosRapidos');
    const headMod = document.getElementById('headerModulosRapidos');
    if (colMod && headMod && window.bootstrap) {
        const estado = localStorage.getItem('sfs_modulos_rapidos_estado');
        if (estado === 'cerrado') {
            const bsCol = bootstrap.Collapse.getOrCreateInstance(colMod, { toggle: false });
            bsCol.hide();
            headMod.classList.add('collapsed');
            headMod.setAttribute('aria-expanded', 'false');
        }
        colMod.addEventListener('hidden.bs.collapse', function () {
            localStorage.setItem('sfs_modulos_rapidos_estado', 'cerrado');
        });
        colMod.addEventListener('shown.bs.collapse', function () {
            localStorage.setItem('sfs_modulos_rapidos_estado', 'abierto');
        });
    }
});
JS;

require __DIR__ . '/../layout/app_layout.php';

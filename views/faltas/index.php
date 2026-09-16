<?php
/**
 * views/faltas/index.php
 * Vista de Control de Faltas y Auditoría de Ausentismo
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
                    <span class="badge rounded-pill px-3 py-1 bg-warning text-dark fw-bold" style="font-size:0.75rem;">
                        <i class="fa-solid fa-clipboard-check me-1"></i> SUPERVISIÓN INSTITUCIONAL
                    </span>
                    <span class="text-white-50 small">&bull; Coordinación & Rectoría</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Control de Faltas y Ausentismo</h1>
                <p class="text-white-50 mb-0 small">
                    Auditoría de inasistencias reportadas en aula y expedición de justificaciones médicas e institucionales.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Panel Principal
                </a>
            </div>
        </div>
    </div>
</section>

<main class="container-fluid px-4 py-4 flex-grow-1">

    <!-- KPIs DE SUPERVISIÓN -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Faltas Injustificadas</span>
                        <h2 class="fw-bold my-1 text-danger"><?= $totalInjustificadas ?></h2>
                        <span class="small text-danger fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Requieren Atención</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-rose">
                        <i class="fa-solid fa-user-xmark"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Faltas Justificadas</span>
                        <h2 class="fw-bold my-1 text-primary"><?= $totalJustificadas ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-file-medical text-primary me-1"></i> Con Excusa Aprobada</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-blue">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Retardos a Clase</span>
                        <h2 class="fw-bold my-1 text-warning"><?= $totalRetardos ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-clock text-warning me-1"></i> Llegadas Tardías</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-amber">
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Total Reportes</span>
                        <h2 class="fw-bold my-1 text-dark"><?= count($faltasSupervision) ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-list-check text-muted me-1"></i> Registros de Aula</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-slate">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE FALTAS Y AUDITORÍA -->
    <div class="card-surface">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold mb-0">Auditoría de Inasistencias</h5>
                <small class="text-muted">Registro consolidado de novedades tomadas por los docentes</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control form-control-sm rounded-pill" id="buscador-faltas" placeholder="Buscar alumno, grado o materia..." oninput="filtrarFaltas()" onkeyup="filtrarFaltas()" style="max-width:250px;">
                <select class="form-select form-select-sm rounded-pill" id="filtro-estado-faltas" onchange="filtrarFaltas()" style="max-width:180px;">
                    <option value="">Todos los estados</option>
                    <option value="FALTA_INJUSTIFICADA">Falta Injustificada</option>
                    <option value="FALTA_JUSTIFICADA">Justificada</option>
                    <option value="RETARDO">Retardo</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="tabla-faltas">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Estudiante</th>
                        <th>Grado</th>
                        <th>Asignatura</th>
                        <th>Docente que Reportó</th>
                        <th>Estado</th>
                        <th>Motivo / Observación</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody id="tabla-faltas-body">
                    <?php if (empty($faltasSupervision)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-check fs-2 text-success d-block mb-2"></i>
                                ¡Excelente! No hay inasistencias reportadas pendientes de revisión.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($faltasSupervision as $f): ?>
                            <tr class="fila-falta" data-texto="<?= strtolower(htmlspecialchars($f['nombre_estudiante'] . ' ' . $f['grado'] . ' ' . $f['materia'])) ?>" data-estado="<?= $f['estado_asistencia'] ?>">
                                <td class="small fw-semibold text-muted">
                                    <?= date('d/m/Y', strtotime($f['fecha'])) ?>
                                </td>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($f['nombre_estudiante']) ?></strong>
                                    <small class="text-muted">Doc: <?= htmlspecialchars($f['documento_estudiante'] ?? '—') ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($f['grado']) ?></span></td>
                                <td><span class="fw-semibold small text-primary"><?= htmlspecialchars($f['materia']) ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($f['nombre_docente'] ?? 'Docente') ?></td>
                                <td>
                                    <?php if ($f['estado_asistencia'] === 'FALTA_INJUSTIFICADA'): ?>
                                        <span class="badge bg-danger text-white">❌ Falta Injustificada</span>
                                    <?php elseif ($f['estado_asistencia'] === 'FALTA_JUSTIFICADA'): ?>
                                        <span class="badge bg-info text-white">📝 Justificada</span>
                                    <?php elseif ($f['estado_asistencia'] === 'RETARDO'): ?>
                                        <span class="badge bg-warning text-dark">⏳ Retardo</span>
                                    <?php else: ?>
                                        <span class="badge bg-success text-white">✅ <?= htmlspecialchars($f['estado_asistencia']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted" style="max-width:220px;">
                                    <?= htmlspecialchars($f['observaciones'] ?: 'Sin observaciones') ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($f['estado_asistencia'] === 'FALTA_INJUSTIFICADA' || $f['estado_asistencia'] === 'RETARDO'): ?>
                                        <button class="btn btn-outline-success btn-sm rounded-pill px-3"
                                                onclick="abrirModalJustificar(<?= $f['id'] ?>, '<?= htmlspecialchars(addslashes($f['nombre_estudiante'])) ?>')">
                                            <i class="fa-solid fa-file-signature me-1"></i> Justificar
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Procesada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
<?php
$contenido = ob_get_clean();
$paginaActual = 'faltas';
$tituloPagina = 'Control de Faltas';
$breadcrumb = 'Supervisión &bull; Control de Faltas';

$scriptExtra = <<<JS
function normalizarTextoFaltas(str) {
    return (str || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[°º\-]/g, ' ')
        .trim();
}

function filtrarFaltas() {
    const input = document.getElementById('buscador-faltas');
    if (!input) return;
    const q = normalizarTextoFaltas(input.value);
    const terminos = q.split(/\s+/).filter(t => t.length > 0);
    const estado = document.getElementById('filtro-estado-faltas').value;

    document.querySelectorAll('.fila-falta').forEach(fila => {
        const rawTexto = fila.dataset.texto || fila.textContent;
        const texto = normalizarTextoFaltas(rawTexto);
        const filaEstado = fila.dataset.estado;

        const matchQ = terminos.length === 0 || terminos.every(term => texto.includes(term));
        const matchEstado = !estado || filaEstado === estado;
        fila.style.display = (matchQ && matchEstado) ? '' : 'none';
    });
}

async function abrirModalJustificar(asistenciaId, estudiante) {
    const motivo = prompt(`Ingresa el motivo de la justificación para \${estudiante}:`, 'Cita médica / Excusa institucional');
    if (!motivo) return;

    const fd = new FormData();
    fd.append('asistencia_id', asistenciaId);
    fd.append('motivo', motivo);

    try {
        const resp = await fetch('index.php?c=acceso&a=justificarFalta', { method: 'POST', body: fd });
        const data = await resp.json();
        alert(data.mensaje);
        location.reload();
    } catch (e) {
        alert('Error al justificar la falta.');
    }
}
JS;

require __DIR__ . '/../layout/app_layout.php';

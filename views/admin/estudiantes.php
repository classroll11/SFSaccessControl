<?php
/**
 * views/admin/estudiantes.php
 * Vista de Directorio Estudiantil y Gestión de Alumnos
 */

$configRol = getRolConfig($rolActual);
$iniciales = getUsuarioIniciales($nombreSesion);

$gradosLista = [
    '6°01', '6°02', '6°03',
    '7°01', '7°02', '7°03',
    '8°01', '8°02', '8°03',
    '9°01', '9°02', '9°03',
    '10°01', '10°02',
    '11°01', '11°02'
];

ob_start();
?>
<!-- Hero Banner -->
<section class="page-hero">
    <div class="container-fluid px-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill px-3 py-1 bg-primary text-white" style="font-size:0.75rem;">
                        <i class="fa-solid fa-graduation-cap me-1"></i> DIRECTORIO ESTUDIANTIL
                    </span>
                    <span class="text-white-50 small">&bull; Matrícula 2026</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Directorio de Estudiantes</h1>
                <p class="text-white-50 mb-0 small">
                    Padrón oficial de 515 estudiantes matriculados y registro de huellas dactilares.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (esAdmin()): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" onclick="abrirModalCrearEstudiante()">
                    <i class="fa-solid fa-user-plus me-2"></i> + Nuevo Estudiante
                </button>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Panel
                </a>
            </div>
        </div>
    </div>
</section>

<main class="container-fluid px-4 py-4 flex-grow-1">

    <div class="card-surface">
        <!-- Filtros y buscador -->
        <div class="row g-2 align-items-center mb-4 pb-3 border-bottom">
            <div class="col-12 col-md-5">
                <div class="position-relative">
                    <input type="text" class="form-control form-control-sm ps-4 rounded-pill" id="buscador-estudiantes-dir"
                           placeholder="Buscar por nombre, documento o matrícula..." onkeyup="filtrarEstudiantesDirectorio()">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left:12px; top:50%; transform:translateY(-50%); font-size:0.75rem;"></i>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm rounded-pill" id="filtro-grado-dir" onchange="filtrarEstudiantesDirectorio()">
                    <option value="">Todos los grados (16)</option>
                    <?php foreach ($gradosLista as $g): ?>
                        <option value="<?= $g ?>">Grado <?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm rounded-pill" id="filtro-huellas-dir" onchange="filtrarEstudiantesDirectorio()">
                    <option value="">Todas las huellas</option>
                    <option value="con_huella">Con huellas enroladas</option>
                    <option value="sin_huella">Sin huellas enroladas</option>
                </select>
            </div>

            <div class="col-12 col-md-2 text-md-end">
                <span class="badge bg-light text-dark border px-3 py-2 small" id="contador-estudiantes">
                    <?= count($estudiantes) ?> Estudiantes
                </span>
            </div>
        </div>

        <!-- Tabla oficial -->
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="tabla-estudiantes">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Estudiante</th>
                        <th>Documento</th>
                        <th>Matrícula</th>
                        <th>Grado</th>
                        <th>Huellas Registradas</th>
                        <th>Estado</th>
                        <?php if (esAdmin() || esCelador()): ?>
                        <th class="text-end">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="tabla-estudiantes-body">
                    <?php if (empty($estudiantes)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No se encontraron estudiantes en el sistema.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($estudiantes as $idx => $st): ?>
                            <?php 
                            $huellas = (int)($st['total_huellas'] ?? 0);
                            $pct = round(($huellas / 6) * 100);
                            $txt = strtolower(htmlspecialchars($st['nombre'] . ' ' . $st['documento'] . ' ' . ($st['matricula'] ?? '')));
                            ?>
                            <tr class="fila-estudiante" data-texto="<?= $txt ?>" data-grado="<?= $st['grado'] ?>" data-huellas="<?= $huellas ?>">
                                <td class="text-muted small"><?= $idx + 1 ?></td>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($st['nombre']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($st['correo'] ?? '') ?></small>
                                </td>
                                <td class="small fw-semibold"><?= htmlspecialchars($st['documento']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($st['matricula'] ?? $st['documento']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">
                                        <?= htmlspecialchars($st['grado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress" style="width: 60px; height: 6px;">
                                            <div class="progress-bar <?= $huellas > 0 ? 'bg-success' : 'bg-secondary' ?>" style="width: <?= $pct ?>%"></div>
                                        </div>
                                        <span class="small fw-bold text-muted"><?= $huellas ?>/6</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-15 text-success">Activo</span>
                                </td>
                                <?php if (esAdmin() || esCelador()): ?>
                                <td class="text-end">
                                    <a href="huellas.php" class="btn btn-outline-primary btn-sm rounded-pill px-3" title="Enrolar o ver huellas">
                                        <i class="fa-solid fa-fingerprint me-1"></i> Huellas
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL CREAR ESTUDIANTE (ADMIN) -->
    <?php if (esAdmin()): ?>
    <div class="modal fade" id="modalFormEstudiante" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #07111f, #1e293b);">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-graduation-cap me-2 text-primary"></i>+ Nuevo Estudiante
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-guardar-estudiante" onsubmit="guardarEstudianteSubmit(event)">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id" id="est_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre Completo del Estudiante:</label>
                            <input type="text" class="form-control" name="nombre" id="est_nombre" required placeholder="Ej: Santiago Pérez Montoya">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">N° Documento (TI / CC):</label>
                                <input type="text" class="form-control" name="documento" id="est_documento" required placeholder="Ej: 1017987654">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">N° Matrícula:</label>
                                <input type="text" class="form-control" name="matricula" id="est_matricula" placeholder="Ej: MAT-2026-001">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Grado / Grupo Asignado:</label>
                                <select class="form-select fw-semibold" name="grado" id="est_grado">
                                    <?php foreach ($gradosLista as $g): ?>
                                        <option value="<?= $g ?>">Grado <?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Estado de Matrícula:</label>
                                <select class="form-select" name="estado" id="est_estado">
                                    <option value="ACTIVO">ACTIVO</option>
                                    <option value="INACTIVO">INACTIVO</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 border small text-muted">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                            Una vez registrado, podrás enrolar las huellas dactilares del estudiante en el módulo <strong>Biometría & Huellas</strong>.
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btn-submit-estudiante">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Estudiante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>
<?php
$contenido = ob_get_clean();
$paginaActual = 'estudiantes';
$tituloPagina = 'Directorio Estudiantil';
$breadcrumb = 'Directorio &bull; Estudiantes';

$scriptExtra = <<<JS
function filtrarEstudiantesDirectorio() {
    const q = document.getElementById('buscador-estudiantes-dir').value.toLowerCase().trim();
    const grado = document.getElementById('filtro-grado-dir').value;
    const huellasFiltro = document.getElementById('filtro-huellas-dir').value;

    let visibles = 0;
    document.querySelectorAll('.fila-estudiante').forEach(fila => {
        const texto = fila.dataset.texto;
        const filaGrado = fila.dataset.grado;
        const totalHuellas = parseInt(fila.dataset.huellas || '0', 10);

        const matchQ = !q || texto.includes(q);
        const matchGrado = !grado || filaGrado === grado;
        let matchHuellas = true;
        if (huellasFiltro === 'con_huella') matchHuellas = (totalHuellas > 0);
        else if (huellasFiltro === 'sin_huella') matchHuellas = (totalHuellas === 0);

        if (matchQ && matchGrado && matchHuellas) {
            fila.style.display = '';
            visibles++;
        } else {
            fila.style.display = 'none';
        }
    });

    const contador = document.getElementById('contador-estudiantes');
    if (contador) contador.textContent = `\${visibles} Estudiantes`;
}

function abrirModalCrearEstudiante() {
    const modalEl = document.getElementById('modalFormEstudiante');
    if (!modalEl) return;
    document.getElementById('est_id').value = '';
    document.getElementById('est_nombre').value = '';
    document.getElementById('est_documento').value = '';
    document.getElementById('est_matricula').value = '';
    document.getElementById('est_grado').value = '6°01';
    new bootstrap.Modal(modalEl).show();
}

async function guardarEstudianteSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-estudiante');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...';

    const fd = new FormData(document.getElementById('form-guardar-estudiante'));
    try {
        const resp = await fetch('index.php?c=acceso&a=guardarEstudiante', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.status === 'ok') {
            alert(data.mensaje);
            location.reload();
        } else {
            alert('Error: ' + data.mensaje);
        }
    } catch (err) {
        alert('Error al procesar la solicitud.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Estudiante';
    }
}
JS;

require __DIR__ . '/../layout/app_layout.php';

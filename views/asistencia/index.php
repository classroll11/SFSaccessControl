<?php
/**
 * views/asistencia/index.php
 * Vista de Toma de Asistencia en el Aula
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
                    <span class="badge rounded-pill px-3 py-1 bg-primary text-white" style="font-size:0.75rem;">
                        <i class="fa-solid fa-chalkboard-user me-1"></i> MÓDULO DOCENTE
                    </span>
                    <span class="text-white-50 small">&bull; Registro de Asistencia</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Toma de Asistencia en el Aula</h1>
                <p class="text-white-50 mb-0 small">
                    Pasa lista por grupo o utiliza el sensor biométrico para registrar la asistencia instantánea de cada estudiante.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver al Panel
                </a>
            </div>
        </div>
    </div>
</section>

<main class="container-fluid px-4 py-4 flex-grow-1">

    <!-- LECTOR BIOMÉTRICO DIRECTO EN EL AULA -->
    <div class="card-surface border-start border-primary border-4 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-12 col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1">
                        <i class="fa-solid fa-fingerprint me-1"></i> Identificación Automática
                    </span>
                    <span class="small text-muted">Primero lee la huella, luego busca y marca en el sistema</span>
                </div>
                <h4 class="fw-bold mb-2">Lector Biométrico Directo en Aula</h4>
                <p class="text-muted small mb-3">
                    No necesitas buscar al alumno manualmente. El estudiante posa su dedo en el huellero, el sistema lo identifica en la base de datos institucional y marca automáticamente su asistencia en clase como <strong>PRESENTE</strong>.
                </p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold" id="btn-escanear-aula" onclick="probarHuellaDirectaAula()">
                        <i class="fa-solid fa-fingerprint me-2"></i> Escanear Huella de Alumno
                    </button>
                    <button class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm" onclick="escanearDemoDocente('HUELLA_HEX_SAMPLE_001')">
                        <i class="fa-solid fa-vial me-1"></i> Huella Demo 1
                    </button>
                    <button class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm" onclick="escanearDemoDocente('HUELLA_HEX_SAMPLE_002')">
                        <i class="fa-solid fa-vial me-1"></i> Huella Demo 2
                    </button>
                </div>

                <div id="alerta-asist-aula" class="alert d-none py-2 px-3 small rounded-3 mb-0" role="alert"></div>
            </div>

            <div class="col-12 col-lg-5 text-center">
                <div class="p-4 rounded-4" style="background: radial-gradient(circle, #0b1d3a 0%, #060b18 100%); border: 1px solid rgba(56,189,248,0.2);">
                    <div class="biometric-pulse mx-auto mb-3" style="width:75px;height:75px;cursor:pointer;" onclick="probarHuellaDirectaAula()" title="Click para simular lectura">
                        <i class="fa-solid fa-fingerprint" style="font-size:2.4rem;color:#38bdf8;"></i>
                    </div>
                    <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-3 py-1 mb-1">
                        <span class="live-dot" style="width:6px;height:6px;"></span> Sensor de Aula Listo
                    </span>
                    <small class="d-block text-white-50 mt-1" style="font-size:0.75rem;">
                        Toca el sensor o haz clic en "Escanear Huella"
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS Y PLANILLA DE ASISTENCIA MANUAL -->
    <div class="card-surface">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 pb-3 border-bottom">
            <div>
                <h5 class="fw-bold mb-0">Planilla de Asistencia de Aula</h5>
                <small class="text-muted">Lista oficial de estudiantes matriculados</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold" onclick="marcarTodosPresentes()">
                    <i class="fa-solid fa-check-double me-1"></i> Todos Presentes
                </button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold" onclick="guardarPlanillaAsistencia()">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Planilla
                </button>
            </div>
        </div>

        <!-- Filtros de Grado, Fecha y Asignatura -->
        <div class="row g-2 align-items-end mb-4 bg-light p-3 rounded-3 border">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-bold mb-1"><i class="fa-solid fa-graduation-cap me-1 text-primary"></i> Grado / Grupo:</label>
                <select class="form-select form-select-sm fw-semibold" id="select-asist-grado" onchange="cargarAsistenciaAula()">
                    <?php foreach ($gradosDisponibles as $g): ?>
                        <option value="<?= $g ?>" <?= ($g === $gradoInicial) ? 'selected' : '' ?>>Grado <?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-bold mb-1"><i class="fa-solid fa-calendar me-1 text-primary"></i> Fecha:</label>
                <input type="date" class="form-control form-control-sm" id="input-asist-fecha" value="<?= date('Y-m-d') ?>" onchange="cargarAsistenciaAula()">
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-bold mb-1"><i class="fa-solid fa-book-open me-1 text-primary"></i> Asignatura / Clase:</label>
                <select class="form-select form-select-sm" id="input-asist-materia">
                    <option value="MATEMÁTICAS">Matemáticas</option>
                    <option value="LENGUA CASTELLANA">Lengua Castellana</option>
                    <option value="CIENCIAS NATURALES">Ciencias Naturales</option>
                    <option value="CIENCIAS SOCIALES">Ciencias Sociales</option>
                    <option value="INGLÉS">Inglés</option>
                    <option value="EDUCACIÓN FÍSICA">Educación Física</option>
                    <option value="TECNOLOGÍA E INFORMÁTICA">Tecnología e Informática</option>
                    <option value="ÉTICA Y VALORES">Ética y Valores</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <button type="button" class="btn btn-dark btn-sm w-100 fw-semibold" onclick="cargarAsistenciaAula()">
                    <i class="fa-solid fa-rotate me-1"></i> Recargar Lista
                </button>
            </div>
        </div>

        <!-- Tabla de Asistencia -->
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="tabla-asistencia-aula">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Estudiante</th>
                        <th style="width: 140px;">Matrícula</th>
                        <th class="text-center" style="min-width: 320px;">Estado de Asistencia</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-asistencia-aula-body">
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i> Cargando estudiantes del grado...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <small class="text-muted">
                <i class="fa-solid fa-circle-info me-1"></i> Recuerda hacer clic en <strong>Guardar Planilla</strong> al finalizar la sesión de clase.
            </small>
            <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" onclick="guardarPlanillaAsistencia()">
                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Planilla
            </button>
        </div>
    </div>

</main>
<?php
$contenido = ob_get_clean();
$paginaActual = 'asistencia';
$tituloPagina = 'Tomar Asistencia';
$breadcrumb = 'Tomar Asistencia';

$scriptExtra = <<<JS
async function cargarAsistenciaAula() {
    const gradoSelect = document.getElementById('select-asist-grado');
    if (!gradoSelect) return;
    const grado = gradoSelect.value;
    const fecha = document.getElementById('input-asist-fecha').value;
    const materia = document.getElementById('input-asist-materia').value;
    const tbody = document.getElementById('tabla-asistencia-aula-body');

    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i> Cargando lista de estudiantes...</td></tr>';

    try {
        const resp = await fetch(`index.php?c=acceso&a=obtenerAsistenciaClase&grado=\${encodeURIComponent(grado)}&fecha=\${fecha}&materia=\${encodeURIComponent(materia)}`);
        const data = await resp.json();

        if (data.status === 'ok' && data.alumnos && data.alumnos.length > 0) {
            tbody.innerHTML = data.alumnos.map((a, idx) => {
                const estado = a.estado_asistencia || 'PRESENTE';
                return `
                    <tr data-id="\${a.estudiante_id}" id="fila-est-\${a.estudiante_id}">
                        <td class="text-muted small">\${idx + 1}</td>
                        <td>
                            <strong class="d-block text-dark">\${a.nombre}</strong>
                            <small class="text-muted">Doc: \${a.documento}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">\${a.matricula || a.documento}</span></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm asist-btn-group" role="group">
                                <input type="radio" class="btn-check" name="asist_\${a.estudiante_id}" id="p_\${a.estudiante_id}" value="PRESENTE" \${estado === 'PRESENTE' || estado === 'SIN_REGISTRO' ? 'checked' : ''}>
                                <label class="btn btn-outline-success" for="p_\${a.estudiante_id}">✅ Presente</label>

                                <input type="radio" class="btn-check" name="asist_\${a.estudiante_id}" id="f_\${a.estudiante_id}" value="FALTA_INJUSTIFICADA" \${estado === 'FALTA_INJUSTIFICADA' ? 'checked' : ''}>
                                <label class="btn btn-outline-danger" for="f_\${a.estudiante_id}">❌ Falta</label>

                                <input type="radio" class="btn-check" name="asist_\${a.estudiante_id}" id="r_\${a.estudiante_id}" value="RETARDO" \${estado === 'RETARDO' ? 'checked' : ''}>
                                <label class="btn btn-outline-warning" for="r_\${a.estudiante_id}">⏳ Retardo</label>

                                <input type="radio" class="btn-check" name="asist_\${a.estudiante_id}" id="j_\${a.estudiante_id}" value="FALTA_JUSTIFICADA" \${estado === 'FALTA_JUSTIFICADA' ? 'checked' : ''}>
                                <label class="btn btn-outline-info" for="j_\${a.estudiante_id}">📝 Justificada</label>
                            </div>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm input-obs" style="max-width:200px;" placeholder="Nota opcional..." value="\${a.observaciones || ''}">
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No se encontraron estudiantes registrados para este grado.</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Error al cargar la lista de estudiantes.</td></tr>';
    }
}

function marcarTodosPresentes() {
    document.querySelectorAll('#tabla-asistencia-aula-body tr').forEach(tr => {
        const id = tr.dataset.id;
        const radio = document.getElementById(`p_\${id}`);
        if (radio) radio.checked = true;
    });
}

async function guardarPlanillaAsistencia() {
    const grado = document.getElementById('select-asist-grado').value;
    const fecha = document.getElementById('input-asist-fecha').value;
    const materia = document.getElementById('input-asist-materia').value;
    const alertBox = document.getElementById('alerta-asist-aula');

    const asistencias = {};
    document.querySelectorAll('#tabla-asistencia-aula-body tr').forEach(tr => {
        const id = tr.dataset.id;
        const checked = tr.querySelector(`input[name="asist_\${id}"]:checked`);
        if (checked) {
            asistencias[id] = checked.value;
        }
    });

    const fd = new FormData();
    fd.append('grado', grado);
    fd.append('fecha', fecha);
    fd.append('materia', materia);
    fd.append('asistencias', JSON.stringify(asistencias));

    try {
        const resp = await fetch('index.php?c=acceso&a=guardarTomaAsistencia', { method: 'POST', body: fd });
        const data = await resp.json();

        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
        if (data.status === 'ok') {
            alertBox.classList.add('alert-success');
            alertBox.innerHTML = `<strong><i class="fa-solid fa-circle-check me-1"></i> \${data.mensaje}</strong>`;
        } else {
            alertBox.classList.add('alert-danger');
            alertBox.innerHTML = `<strong><i class="fa-solid fa-triangle-exclamation me-1"></i> \${data.mensaje}</strong>`;
        }
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (e) {
        alert('Error al guardar la planilla de asistencia.');
    }
}

async function probarHuellaDirectaAula(template = null) {
    const huella = template || prompt('Ingresa el template o código de huella a escanear (o presiona Aceptar para prueba automática):', 'HUELLA_HEX_SAMPLE_001');
    if (!huella) return;

    await procesarHuellaDocente(huella);
}

function escanearDemoDocente(template) {
    procesarHuellaDocente(template);
}

async function procesarHuellaDocente(huella) {
    const alertBox = document.getElementById('alerta-asist-aula');
    const materia = document.getElementById('input-asist-materia')?.value || 'MATEMÁTICAS';

    const fd = new FormData();
    fd.append('huella', huella);
    fd.append('materia', materia);

    try {
        const resp = await fetch('index.php?c=acceso&a=simularManual', { method: 'POST', body: fd });
        const data = await resp.json();

        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');

        if (data.status === 'exito') {
            alertBox.classList.add('alert-success');
            alertBox.innerHTML = `<strong><i class="fa-solid fa-circle-check me-1"></i> \${data.mensaje}</strong><br><small>Identificado: <strong>\${data.usuario ? data.usuario.nombre : ''}</strong> (Grado \${data.usuario ? data.usuario.grado : ''}) - Dedo: \${data.usuario ? data.usuario.dedo : ''}</small>`;

            if (data.usuario && data.usuario.id) {
                const radioP = document.getElementById(`p_\${data.usuario.id}`);
                if (radioP) {
                    radioP.checked = true;
                    const fila = document.getElementById(`fila-est-\${data.usuario.id}`);
                    if (fila) {
                        fila.style.transition = 'all 0.4s ease';
                        fila.style.backgroundColor = '#dcfce7';
                        fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => { fila.style.backgroundColor = ''; }, 3500);
                    }
                }
            }
        } else {
            alertBox.classList.add('alert-danger');
            alertBox.innerHTML = `<strong><i class="fa-solid fa-triangle-exclamation me-1"></i> \${data.mensaje}</strong>`;
        }
    } catch (e) {
        alert('Error al procesar lectura de huella.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarAsistenciaAula();
});
JS;

require __DIR__ . '/../layout/app_layout.php';

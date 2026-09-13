<?php
/**
 * views/huellas/index.php
 * Vista de Gestión y Enrolamiento de Huellas Dactilares (6 Slots)
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
                    <span class="badge rounded-pill px-3 py-1 bg-info text-dark fw-bold" style="font-size:0.75rem;">
                        <i class="fa-solid fa-fingerprint me-1"></i> BIOMETRÍA DIGITAL
                    </span>
                    <span class="text-white-50 small">&bull; Sistema Multihuella (6 Slots)</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Gestión de Huellas Dactilares</h1>
                <p class="text-white-50 mb-0 small">
                    Enrola hasta 6 dedos por estudiante para garantizar lecturas rápidas y sin fallos en portería y aulas.
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

    <div class="row g-4">
        <!-- Columna Izquierda: Buscador y Lista de Estudiantes -->
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="card-surface h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0">Seleccionar Estudiante</h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary small fw-bold">
                        <?= count($estudiantesHuellas) ?> Alumnos
                    </span>
                </div>
                <p class="small text-muted mb-3">Busca por nombre, documento o grado</p>

                <!-- Buscador rápido -->
                <div class="position-relative mb-3">
                    <input type="text" class="form-control form-control-sm ps-4 rounded-pill" id="buscador-estudiantes"
                           placeholder="Buscar por nombre, documento o grado..." onkeyup="filtrarEstudiantes()">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left:12px; top:50%; transform:translateY(-50%); font-size:0.75rem;"></i>
                </div>

                <!-- Lista de estudiantes con scroll -->
                <div class="d-flex flex-column gap-2" id="lista-estudiantes-container" style="max-height: 540px; overflow-y: auto; padding-right: 4px;">
                    <?php if (empty($estudiantesHuellas)): ?>
                        <div class="text-center py-4 text-muted small">No hay estudiantes cargados en el sistema.</div>
                    <?php else: ?>
                        <?php foreach ($estudiantesHuellas as $st): ?>
                            <?php 
                            $inicialesSt = getUsuarioIniciales($st['nombre']);
                            $totalHuellas = (int)($st['total_huellas'] ?? 0);
                            ?>
                            <div class="student-finger-card p-2 rounded-3 border bg-light d-flex align-items-center gap-2 cursor-pointer transition estudiante-item"
                                 data-id="<?= $st['id'] ?>"
                                 data-nombre="<?= htmlspecialchars($st['nombre']) ?>"
                                 data-doc="<?= htmlspecialchars($st['documento']) ?>"
                                 data-matricula="<?= htmlspecialchars($st['matricula'] ?? $st['documento']) ?>"
                                 data-grado="<?= htmlspecialchars($st['grado']) ?>"
                                 onclick="seleccionarEstudiante(<?= $st['id'] ?>, '<?= htmlspecialchars(addslashes($st['nombre'])) ?>', '<?= htmlspecialchars($st['grado']) ?>', '<?= htmlspecialchars($st['matricula'] ?? $st['documento']) ?>')">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white small flex-shrink-0"
                                     style="width:34px;height:34px; background:linear-gradient(135deg,#2563eb,#38bdf8); font-size:0.75rem;">
                                    <?= $inicialesSt ?>
                                </div>
                                <div style="min-width:0; flex:1;">
                                    <strong class="d-block text-dark text-truncate small" style="line-height:1.2;"><?= htmlspecialchars($st['nombre']) ?></strong>
                                    <small class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($st['grado']) ?> &bull; Doc: <?= htmlspecialchars($st['documento']) ?></small>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge <?= $totalHuellas > 0 ? 'bg-success' : 'bg-secondary' ?> text-white" style="font-size:0.65rem;">
                                        <?= $totalHuellas ?>/6
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Panel de los 6 Slots de Huellas -->
        <div class="col-12 col-lg-7 col-xl-8">
            <!-- Estado Inicial: Sin selección -->
            <div class="card-surface text-center py-5" id="panel-sin-seleccion">
                <div class="py-5">
                    <div class="rounded-circle bg-light border p-4 d-inline-flex mb-3">
                        <i class="fa-solid fa-fingerprint text-muted" style="font-size:3.5rem; opacity:0.4;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Selecciona un estudiante de la lista</h5>
                    <p class="text-muted small mx-auto" style="max-width:380px;">
                        Haz clic sobre cualquier alumno de la columna izquierda para ver sus slots biométricos registrados y enrolar nuevas huellas dactilares.
                    </p>
                </div>
            </div>

            <!-- Estado Activo: Gestión de Huellas del Estudiante Seleccionado -->
            <div class="card-surface d-none" id="panel-gestion-huellas">
                <!-- Tarjeta de Identificación del Alumno -->
                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border mb-4 flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow-sm"
                             id="slot-avatar"
                             style="width:50px;height:50px;font-size:1.2rem;background:linear-gradient(135deg,#0284c7,#38bdf8);">
                            ST
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark" id="slot-nombre">Nombre del Estudiante</h5>
                            <small class="text-muted" id="slot-grado">Grado &bull; Matrícula</small>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill" id="slot-conteo-badge">
                            0/6 slots
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold mb-0">Ranuras Biométricas (1 a 6)</h6>
                        <small class="text-muted">Haz clic en cualquier ranura para enrolar o actualizar huella</small>
                    </div>
                    <small class="text-muted"><i class="fa-solid fa-info-circle me-1"></i> Pulgar, Índice y Medio recomendados</small>
                </div>

                <!-- Grid de los 6 Slots -->
                <div class="finger-slots-grid mb-4" id="finger-slots-grid">
                    <!-- Se renderiza dinámicamente con JavaScript -->
                </div>

                <!-- Formulario de Enrolamiento en Ranura Activa -->
                <div class="p-3 rounded-3 border bg-light d-none" id="form-registro-huella">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong class="text-dark small d-flex align-items-center gap-2">
                            <i class="fa-solid fa-fingerprint text-primary"></i>
                            Enrolando Huella en <span id="form-slot-label" class="badge bg-primary">Slot 1</span>
                        </strong>
                        <button type="button" class="btn-close btn-sm" onclick="cancelarRegistroHuella()"></button>
                    </div>
                    
                    <input type="hidden" id="form-slot-numero" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-bold mb-1">Dedo correspondiente:</label>
                            <select class="form-select form-select-sm" id="form-dedo">
                                <option value="Pulgar Derecho">👍 Pulgar Derecho</option>
                                <option value="Indice Derecho" selected>☝️ Índice Derecho</option>
                                <option value="Corazon Derecho">🖕 Medio / Corazón Derecho</option>
                                <option value="Anular Derecho">💍 Anular Derecho</option>
                                <option value="Menique Derecho">🤙 Meñique Derecho</option>
                                <option value="Pulgar Izquierdo">👍 Pulgar Izquierdo</option>
                                <option value="Indice Izquierdo">☝️ Índice Izquierdo</option>
                                <option value="Corazon Izquierdo">🖕 Medio / Corazón Izquierdo</option>
                                <option value="Anular Izquierdo">💍 Anular Izquierdo</option>
                                <option value="Menique Izquierdo">🤙 Meñique Izquierdo</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="button" class="btn btn-primary btn-sm w-100 fw-semibold" onclick="asignarHuellaDirecta()">
                                <i class="fa-solid fa-fingerprint me-1"></i> Capturar y Guardar
                            </button>
                        </div>
                        <div class="col-12 col-md-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="cancelarRegistroHuella()">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</main>
<?php
$contenido = ob_get_clean();
$paginaActual = 'huellas';
$tituloPagina = 'Biometría & Huellas';
$breadcrumb = 'Biometría & Huellas';

$scriptExtra = <<<JS
const huellasState = { usuarioId: null, usuarioNombre: '', usuarioGrado: '', huellas: [], slotActivo: null };
const DEDOS_EMOJI = {
    'Pulgar Derecho': '👍', 'Indice Derecho': '☝️', 'Corazon Derecho': '🖕',
    'Anular Derecho': '💍', 'Menique Derecho': '🤙',
    'Pulgar Izquierdo': '👍', 'Indice Izquierdo': '☝️', 'Corazon Izquierdo': '🖕',
    'Anular Izquierdo': '💍', 'Menique Izquierdo': '🤙'
};

function filtrarEstudiantes() {
    const q = document.getElementById('buscador-estudiantes').value.toLowerCase().trim();
    document.querySelectorAll('.estudiante-item').forEach(el => {
        const txt = (el.dataset.nombre + ' ' + el.dataset.doc + ' ' + (el.dataset.matricula || '') + ' ' + el.dataset.grado).toLowerCase();
        el.style.display = txt.includes(q) ? '' : 'none';
    });
}

async function seleccionarEstudiante(id, nombre, grado, matricula = '') {
    huellasState.usuarioId = id;
    huellasState.usuarioNombre = nombre;
    huellasState.usuarioGrado = grado;

    document.querySelectorAll('.student-finger-card').forEach(c => c.classList.remove('selected'));
    const targetCard = document.querySelector(`.student-finger-card[data-id="\${id}"]`);
    if (targetCard) targetCard.classList.add('selected');

    document.getElementById('panel-sin-seleccion').classList.add('d-none');
    document.getElementById('panel-gestion-huellas').classList.remove('d-none');

    const partes = nombre.trim().split(' ');
    const iniciales = ((partes[0]?.charAt(0) || '') + (partes[1]?.charAt(0) || '')).toUpperCase();
    document.getElementById('slot-avatar').textContent = iniciales;
    document.getElementById('slot-nombre').textContent = nombre;
    document.getElementById('slot-grado').innerHTML = `\${grado} &bull; Matrícula: \${matricula || '—'}`;

    await cargarHuellasEstudiante(id);
}

async function cargarHuellasEstudiante(id) {
    const grid = document.getElementById('finger-slots-grid');
    grid.innerHTML = '<div class="text-center py-4 text-muted small"><i class="fa-solid fa-spinner fa-spin me-2"></i> Cargando ranuras de huellas...</div>';
    try {
        const resp = await fetch(`index.php?c=acceso&a=obtenerHuellasEstudiante&usuario_id=\${id}`);
        const data = await resp.json();
        if (data.status !== 'ok') throw new Error(data.mensaje);
        huellasState.huellas = data.huellas;
        renderizarSlotsHuellas(data.huellas, data.total_registradas);
    } catch (err) {
        grid.innerHTML = `<div class="text-danger small py-3">Error: \${err.message}</div>`;
    }
}

function renderizarSlotsHuellas(huellas, totalRegistradas) {
    const grid = document.getElementById('finger-slots-grid');
    const badge = document.getElementById('slot-conteo-badge');
    badge.textContent = `\${totalRegistradas}/6 slots registrados`;

    document.getElementById('form-registro-huella').classList.add('d-none');
    huellasState.slotActivo = null;

    const slotMap = {};
    huellas.forEach(h => { slotMap[h.slot_numero] = h; });

    let html = '';
    for (let slot = 1; slot <= 6; slot++) {
        const huella = slotMap[slot];
        if (huella) {
            const emoji = DEDOS_EMOJI[huella.dedo] || '👆';
            html += `
                <div class="finger-slot registered" onclick="abrirFormHuella(\${slot})" data-slot="\${slot}">
                    <span class="slot-badge"><i class="fa-solid fa-check"></i></span>
                    <span class="slot-delete" onclick="eliminarSlot(event, \${slot})" title="Eliminar huella"><i class="fa-solid fa-times"></i></span>
                    <span class="fs-4 d-block mb-1">\${emoji}</span>
                    <div class="small fw-bold text-success">\${huella.dedo}</div>
                    <div style="font-size:0.68rem; color:#059669;">Slot \${slot} &bull; Registrado</div>
                </div>`;
        } else {
            html += `
                <div class="finger-slot" onclick="abrirFormHuella(\${slot})" data-slot="\${slot}">
                    <span class="fs-4 d-block mb-1 text-muted opacity-50"><i class="fa-regular fa-hand"></i></span>
                    <div class="small text-muted fw-bold">Slot \${slot}</div>
                    <small style="font-size:0.68rem;" class="text-primary fw-semibold">+ Enrolar</small>
                </div>`;
        }
    }
    grid.innerHTML = html;
}

function abrirFormHuella(slotNumero) {
    huellasState.slotActivo = slotNumero;
    const form = document.getElementById('form-registro-huella');
    document.getElementById('form-slot-numero').value = slotNumero;
    document.getElementById('form-slot-label').textContent = `Slot \${slotNumero}`;

    const slotMap = {};
    (huellasState.huellas || []).forEach(h => { slotMap[h.slot_numero] = h; });
    const existente = slotMap[slotNumero];
    document.getElementById('form-dedo').value = existente ? existente.dedo : 'Indice Derecho';

    form.classList.remove('d-none');
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function asignarHuellaDirecta() {
    const dedo = document.getElementById('form-dedo').value;
    if (!dedo) { alert('Selecciona el dedo a registrar.'); return; }
    const slot = huellasState.slotActivo;
    if (!slot || !huellasState.usuarioId) return;

    const chars = '0123456789abcdef';
    let hash = 'bio_';
    for (let i = 0; i < 28; i++) hash += chars[Math.floor(Math.random() * chars.length)];

    try {
        const fd = new FormData();
        fd.append('usuario_id', huellasState.usuarioId);
        fd.append('slot_numero', slot);
        fd.append('dedo', dedo);
        fd.append('huella_template', hash);

        const resp = await fetch('index.php?c=acceso&a=guardarHuellaEstudiante', { method: 'POST', body: fd });
        const data = await resp.json();
        alert(data.mensaje);
        await cargarHuellasEstudiante(huellasState.usuarioId);
    } catch (e) { alert('Error al guardar huella.'); }
}

async function eliminarSlot(event, slotNumero) {
    event.stopPropagation();
    if (!confirm(`¿Eliminar la huella del Slot \${slotNumero}?`)) return;
    const fd = new FormData();
    fd.append('usuario_id', huellasState.usuarioId);
    fd.append('slot_numero', slotNumero);
    try {
        const resp = await fetch('index.php?c=acceso&a=eliminarHuellaEstudiante', { method: 'POST', body: fd });
        const data = await resp.json();
        alert(data.mensaje);
        await cargarHuellasEstudiante(huellasState.usuarioId);
    } catch (e) { alert('Error al eliminar huella.'); }
}

function cancelarRegistroHuella() {
    document.getElementById('form-registro-huella').classList.add('d-none');
}
JS;

require __DIR__ . '/../layout/app_layout.php';

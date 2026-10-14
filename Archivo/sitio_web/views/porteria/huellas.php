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
                    <span class="text-white-50 small">&bull; Una huella por estudiante</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Gestión de Huellas Dactilares</h1>
                <p class="text-white-50 mb-0 small">
                    Cada estudiante puede registrar una sola huella, que puedes reemplazar cuando sea necesario.
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
                    <span class="badge bg-primary bg-opacity-10 text-primary small fw-bold" id="contador-estudiantes-badge">
                        <?= count($estudiantesHuellas) ?> Alumnos
                    </span>
                </div>
                <p class="small text-muted mb-3">Busca por nombre, documento o grado</p>

                <!-- Buscador rápido -->
                <div class="position-relative mb-3">
                    <input type="text" class="form-control form-control-sm ps-4 pe-4 rounded-pill" id="buscador-estudiantes"
                           placeholder="Buscar por nombre, documento o grado..." oninput="filtrarEstudiantes()" onkeyup="filtrarEstudiantes()">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left:12px; top:50%; transform:translateY(-50%); font-size:0.75rem;"></i>
                    <button type="button" id="btn-limpiar-busqueda" class="btn btn-link position-absolute text-muted p-0 d-none"
                            style="right:12px; top:50%; transform:translateY(-50%); font-size:0.75rem; text-decoration:none;"
                            onclick="limpiarBuscadorEstudiantes()" title="Limpiar búsqueda">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>

                <!-- Lista de estudiantes con scroll -->
                <div class="d-flex flex-column gap-2" id="lista-estudiantes-container" style="max-height: 540px; overflow-y: auto; padding-right: 4px;">
                    <div id="sin-resultados-busqueda" class="text-center py-4 text-muted small d-none">
                        <i class="fa-solid fa-magnifying-glass d-block mb-2 text-secondary" style="font-size:1.5rem; opacity:0.4;"></i>
                        No se encontraron estudiantes que coincidan con la búsqueda.
                    </div>
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
                                        <?= $totalHuellas > 0 ? '1/1' : '0/1' ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Panel de la huella del estudiante -->
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
                            0/1 huella
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold mb-0">Huella biométrica</h6>
                        <small class="text-muted">Haz clic en la huella para registrarla o reemplazarla</small>
                    </div>
                    <small class="text-muted"><i class="fa-solid fa-info-circle me-1"></i> Pulgar, Índice y Medio recomendados</small>
                </div>

                <!-- Contenedor para notificaciones y alertas -->
                <div id="feedback-huellas-container"></div>

                <!-- Grid de los 6 Slots -->
                <div class="finger-slots-grid mb-4" id="finger-slots-grid">
                    <!-- Se renderiza dinámicamente con JavaScript -->
                </div>

                <!-- Formulario de Enrolamiento en Ranura Activa -->
                <div class="p-3 rounded-3 border bg-light d-none shadow-sm" id="form-registro-huella">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <strong class="text-dark small d-flex align-items-center gap-2">
                            <i class="fa-solid fa-fingerprint text-primary fs-5"></i>
                            Registro de Huella en <span id="form-slot-label" class="badge bg-primary">Slot 1</span>
                        </strong>
                        <button type="button" class="btn-close btn-sm" onclick="cancelarRegistroHuella()"></button>
                    </div>
                    
                    <input type="hidden" id="form-slot-numero" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-bold mb-1 text-dark">Dedo correspondiente:</label>
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
                        <div class="col-12 col-md-3">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold py-2" id="btn-encender-huellero" onclick="cambiarEstadoHuellero(true)">
                                <i class="fa-solid fa-power-off me-2"></i> Encender huellero
                            </button>
                        </div>
                        <div class="col-12 col-md-3">
                            <button type="button" class="btn btn-primary btn-sm w-100 fw-bold py-2" id="btn-capturar-huella" onclick="iniciarCapturaSensor()">
                                <i class="fa-solid fa-fingerprint me-2"></i> Escanear huella
                            </button>
                        </div>
                        <div class="col-12 col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 py-2" id="btn-apagar-huellero" onclick="cambiarEstadoHuellero(false)" title="Apagar huellero" aria-label="Apagar huellero">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="cancelarRegistroHuella()">
                                Cancelar
                            </button>
                        </div>
                    </div>

                    <!-- Estado del sensor durante enrolamiento -->
                    <div id="sensor-enroll-status" class="d-none mt-3 p-3 rounded-2" style="background: rgba(37,99,235,0.06); border: 1px dashed rgba(37,99,235,0.35);">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <i class="fa-solid fa-fingerprint fa-beat-fade text-primary fs-4 flex-shrink-0"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-primary small mb-1" id="sensor-enroll-msg">Activando lector USB...</div>
                                <div class="progress" style="height: 6px; border-radius: 99px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="sensor-progress-bar" style="width: 100%"></div>
                                </div>
                            </div>
                            <span class="badge bg-primary text-white fw-bold" id="sensor-timer-badge" style="min-width:36px;">90s</span>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelarCapturaSensor(true)">
                                <i class="fa-solid fa-stop-circle me-1"></i> Cancelar escaneo
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

$scriptExtra = <<<'JS'
const huellasState = { usuarioId: null, usuarioNombre: '', usuarioGrado: '', huellas: [], slotActivo: null };
const DEDOS_EMOJI = {
    'Pulgar Derecho': '👍', 'Indice Derecho': '☝️', 'Corazon Derecho': '🖕',
    'Anular Derecho': '💍', 'Menique Derecho': '🤙',
    'Pulgar Izquierdo': '👍', 'Indice Izquierdo': '☝️', 'Corazon Izquierdo': '🖕',
    'Anular Izquierdo': '💍', 'Menique Izquierdo': '🤙'
};

function normalizarTexto(str) {
    return (str || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[°º\-]/g, ' ')
        .trim();
}

function filtrarEstudiantes() {
    const input = document.getElementById('buscador-estudiantes');
    if (!input) return;
    const rawVal = input.value;
    const q = normalizarTexto(rawVal);
    const terminos = q.split(/\s+/).filter(t => t.length > 0);

    const btnLimpiar = document.getElementById('btn-limpiar-busqueda');
    if (btnLimpiar) {
        btnLimpiar.classList.toggle('d-none', rawVal.trim().length === 0);
    }

    let visibles = 0;
    document.querySelectorAll('.estudiante-item').forEach(el => {
        const rawTxt = (el.dataset.nombre || '') + ' ' + (el.dataset.doc || '') + ' ' + (el.dataset.matricula || '') + ' ' + (el.dataset.grado || '');
        const txt = normalizarTexto(rawTxt);

        const match = terminos.length === 0 || terminos.every(term => txt.includes(term));

        if (match) {
            el.classList.remove('d-none');
            el.classList.add('d-flex');
            el.style.removeProperty('display');
            visibles++;
        } else {
            el.classList.remove('d-flex');
            el.classList.add('d-none');
            el.style.setProperty('display', 'none', 'important');
        }
    });

    const contador = document.getElementById('contador-estudiantes-badge');
    if (contador) {
        contador.textContent = `${visibles} ${visibles === 1 ? 'Alumno' : 'Alumnos'}`;
    }

    const sinResultados = document.getElementById('sin-resultados-busqueda');
    if (sinResultados) {
        sinResultados.classList.toggle('d-none', visibles > 0);
    }
}

function limpiarBuscadorEstudiantes() {
    const input = document.getElementById('buscador-estudiantes');
    if (input) {
        input.value = '';
        input.focus();
        filtrarEstudiantes();
    }
}

async function seleccionarEstudiante(id, nombre, grado, matricula = '') {
    huellasState.usuarioId = id;
    huellasState.usuarioNombre = nombre;
    huellasState.usuarioGrado = grado;

    document.querySelectorAll('.student-finger-card').forEach(c => c.classList.remove('selected'));
    const targetCard = document.querySelector(`.student-finger-card[data-id="${id}"]`);
    if (targetCard) targetCard.classList.add('selected');

    document.getElementById('panel-sin-seleccion').classList.add('d-none');
    document.getElementById('panel-gestion-huellas').classList.remove('d-none');

    const partes = nombre.trim().split(' ');
    const iniciales = ((partes[0]?.charAt(0) || '') + (partes[1]?.charAt(0) || '')).toUpperCase();
    document.getElementById('slot-avatar').textContent = iniciales;
    document.getElementById('slot-nombre').textContent = nombre;
    document.getElementById('slot-grado').innerHTML = `${grado} &bull; Matrícula: ${matricula || '—'}`;

    await cargarHuellasEstudiante(id);
}

async function cargarHuellasEstudiante(id) {
    const grid = document.getElementById('finger-slots-grid');
    grid.innerHTML = '<div class="text-center py-4 text-muted small"><i class="fa-solid fa-spinner fa-spin me-2"></i> Cargando ranuras de huellas...</div>';
    try {
        const resp = await fetch(`index.php?c=acceso&a=obtenerHuellasEstudiante&usuario_id=${id}`);
        const data = await resp.json();
        if (data.status !== 'ok') throw new Error(data.mensaje);
        huellasState.huellas = data.huellas;
        renderizarSlotsHuellas(data.huellas, data.total_registradas);
    } catch (err) {
        grid.innerHTML = `<div class="text-danger small py-3">Error: ${err.message}</div>`;
    }
}

function renderizarSlotsHuellas(huellas, totalRegistradas) {
    const grid = document.getElementById('finger-slots-grid');
    const badge = document.getElementById('slot-conteo-badge');
    badge.textContent = `${totalRegistradas > 0 ? 1 : 0}/1 huella registrada`;

    document.getElementById('form-registro-huella').classList.add('d-none');
    huellasState.slotActivo = null;

    const slotMap = {};
    huellas.forEach(h => { slotMap[h.slot_numero] = h; });

    let html = '';
    for (let slot = 1; slot <= 1; slot++) {
        const huella = slotMap[slot];
        if (huella) {
            const emoji = DEDOS_EMOJI[huella.dedo] || '👆';
            html += `
                <div class="finger-slot registered" onclick="abrirFormHuella(${slot})" data-slot="${slot}">
                    <span class="slot-number-badge">S${slot}</span>
                    <span class="slot-badge"><i class="fa-solid fa-check"></i></span>
                    <span class="slot-delete" onclick="eliminarSlot(event, ${slot})" title="Eliminar huella"><i class="fa-solid fa-trash-can"></i></span>
                    <div class="mb-2" style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#059669,#10b981);display:flex;align-items:center;justify-content:center;font-size:1.3rem;box-shadow:0 3px 10px rgba(16,185,129,0.3);">
                        ${emoji}
                    </div>
                    <div class="fw-bold text-success" style="font-size:0.78rem;line-height:1.2;">${huella.dedo}</div>
                    <div class="mt-1 px-2" style="font-size:0.62rem;background:rgba(16,185,129,0.12);color:#059669;border-radius:20px;font-weight:700;">
                        \u2713 Registrado
                    </div>
                </div>`;
        } else {
            html += `
                <div class="finger-slot" onclick="abrirFormHuella(${slot})" data-slot="${slot}">
                    <span class="slot-number-badge">S${slot}</span>
                    <div class="mb-2" style="width:44px;height:44px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-fingerprint" style="font-size:1.2rem;color:#94a3b8;"></i>
                    </div>
                    <div class="fw-semibold text-muted" style="font-size:0.78rem;">Vac\u00edo</div>
                    <div class="mt-1" style="font-size:0.7rem;color:#2563eb;font-weight:700;">+ Enrolar</div>
                </div>`;
        }
    }
    grid.innerHTML = html;
}

function abrirFormHuella(slotNumero) {
    huellasState.slotActivo = slotNumero;
    const form = document.getElementById('form-registro-huella');
    document.getElementById('form-slot-numero').value = slotNumero;
    document.getElementById('form-slot-label').textContent = `Slot ${slotNumero}`;

    // Resaltar visualmente la ranura activa
    document.querySelectorAll('.finger-slot').forEach(s => {
        s.classList.toggle('active-slot', parseInt(s.dataset.slot) === slotNumero);
    });

    const slotMap = {};
    (huellasState.huellas || []).forEach(h => { slotMap[h.slot_numero] = h; });
    const existente = slotMap[slotNumero];
    document.getElementById('form-dedo').value = existente ? existente.dedo : 'Indice Derecho';

    resetBotonesForm();
    const statusBox = document.getElementById('sensor-enroll-status');
    if (statusBox) statusBox.classList.add('d-none');

    form.classList.remove('d-none');
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetBotonesForm() {
    const btnCapturar = document.getElementById('btn-capturar-huella');
    if (btnCapturar) {
        btnCapturar.disabled = false;
        btnCapturar.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Escanear huella';
    }
}

// [Registro de huella exclusivamente con Lector USB]
let timerInterval = null;
let enrollPollingInterval = null;
let powerControlPending = false;

async function cambiarEstadoHuellero(encender) {
    if (powerControlPending) return;

    const btnOn = document.getElementById('btn-encender-huellero');
    const btnOff = document.getElementById('btn-apagar-huellero');
    const accion = encender ? 'reader_on' : 'reader_off';
    const estadoEsperado = encender ? 'lector_encendido' : 'lector_apagado';
    powerControlPending = true;
    if (btnOn) btnOn.disabled = true;
    if (btnOff) btnOff.disabled = true;

    try {
        const response = await fetch('api/fingerprint_controller.php?action=' + accion, { method: 'POST' });
        const result = await response.json();
        if (!response.ok) throw new Error(result.mensaje || 'No se pudo enviar la orden al huellero.');

        for (let attempt = 0; attempt < 20; attempt++) {
            await new Promise(resolve => setTimeout(resolve, 500));
            const pollResponse = await fetch('api/fingerprint_controller.php?action=poll');
            const state = await pollResponse.json();

            if (state.status === estadoEsperado) {
                mostrarFeedbackHuella('success', state.mensaje || (encender ? 'Huellero encendido.' : 'Huellero apagado.'));
                return;
            }
            if (state.status === 'error') {
                throw new Error(state.mensaje || 'El agente no pudo controlar el huellero.');
            }
        }
        mostrarFeedbackHuella('warning', 'La orden fue enviada, pero el agente aún no confirma el estado del huellero.');
    } catch (error) {
        mostrarFeedbackHuella('danger', 'Error del huellero: ' + (error.message || 'No se pudo completar la operación.'));
    } finally {
        powerControlPending = false;
        if (btnOn) btnOn.disabled = false;
        if (btnOff) btnOff.disabled = false;
    }
}

async function iniciarCapturaSensor() {
    const dedo = document.getElementById('form-dedo').value;
    if (!dedo) {
        mostrarFeedbackHuella('warning', 'Selecciona el dedo a registrar.');
        return;
    }
    const slot = huellasState.slotActivo;
    if (!slot || !huellasState.usuarioId) {
        mostrarFeedbackHuella('warning', 'Selecciona una ranura activa primero.');
        return;
    }

    const btnCapturar = document.getElementById('btn-capturar-huella');
    const statusBox   = document.getElementById('sensor-enroll-status');
    const statusMsg   = document.getElementById('sensor-enroll-msg');
    const timerBadge  = document.getElementById('sensor-timer-badge');
    const progressBar = document.getElementById('sensor-progress-bar');

    if (btnCapturar) {
        btnCapturar.disabled = true;
        btnCapturar.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Esperando lector...';
    }
    if (statusBox) statusBox.classList.remove('d-none');
    if (statusMsg) statusMsg.textContent = "Encendiendo el huellero. Coloca el dedo '" + dedo + "' sobre el sensor...";

    // Lanzar enrolamiento en el backend
    try {
        const fd = new FormData();
        fd.append('usuario_id', huellasState.usuarioId);
        fd.append('slot', slot);
        fd.append('dedo', dedo);
        const launchResp = await fetch('api/fingerprint_controller.php?action=launch_enroll', { method: 'POST', body: fd });
        const launchData = await launchResp.json();
        if (!launchResp.ok) {
            throw new Error(launchData.mensaje || 'No se pudo iniciar el enrolamiento.');
        }
    } catch(e) {
        resetBotonesForm();
        if (statusBox) statusBox.classList.add('d-none');
        mostrarFeedbackHuella('danger', '❌ ' + (e.message || 'No se pudo conectar con el agente biométrico.'));
        return;
    }

    // Timer visual con countdown y barra de progreso
    const totalSegundos = 90;
    let segundosRestantes = totalSegundos;
    if (timerBadge)  timerBadge.textContent = segundosRestantes + 's';
    if (progressBar) {
        progressBar.style.width = '100%';
        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
    }

    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        segundosRestantes--;
        const pct = Math.max(0, (segundosRestantes / totalSegundos) * 100);
        if (timerBadge)  timerBadge.textContent = segundosRestantes + 's';
        if (progressBar) {
            progressBar.style.width = pct + '%';
            if (pct < 35) { progressBar.classList.remove('bg-primary'); progressBar.classList.add('bg-warning'); }
            if (pct < 12) { progressBar.classList.remove('bg-warning'); progressBar.classList.add('bg-danger'); }
        }
        if (segundosRestantes <= 0) clearInterval(timerInterval);
    }, 1000);

    // Polling del estado del sensor
    if (enrollPollingInterval) clearInterval(enrollPollingInterval);
    enrollPollingInterval = setInterval(async () => {
        if (segundosRestantes <= 0) {
            await cancelarCapturaSensor(true);
            mostrarFeedbackHuella('warning', '⏱ Tiempo de espera agotado. Intenta de nuevo.');
            return;
        }
        try {
            const resp = await fetch('api/fingerprint_controller.php?action=poll');
            const data = await resp.json();
            const estado  = data.status || '';
            const mensaje = data.mensaje || '';

            // Mostrar mensaje de progreso en estados intermedios
            if (['enrolling', 'capturando', 'captura_parcial', 'mala_captura', 'iniciando', 'esperando_agente'].includes(estado)) {
                if (statusMsg) statusMsg.textContent = mensaje;
            }

            if (estado === 'enrolado' && data.identity_hex) {
                // Huella capturada exitosamente
                clearInterval(enrollPollingInterval);
                clearInterval(timerInterval);
                enrollPollingInterval = null;
                timerInterval = null;

                if (statusMsg) statusMsg.textContent = '¡Huella capturada! Guardando...';

                const sfd = new FormData();
                sfd.append('usuario_id', huellasState.usuarioId);
                sfd.append('slot_numero', slot);
                sfd.append('dedo', dedo);
                sfd.append('huella_template', data.identity_hex);

                const saveResp = await fetch('api/fingerprint_controller.php?action=save_enrolled', { method: 'POST', body: sfd });
                const saveData = await saveResp.json();

                resetBotonesForm();
                if (statusBox) statusBox.classList.add('d-none');
                document.getElementById('form-registro-huella').classList.add('d-none');
                huellasState.slotActivo = null;
                document.querySelectorAll('.finger-slot').forEach(s => s.classList.remove('active-slot'));

                if (saveData.status === 'ok') {
                    await cargarHuellasEstudiante(huellasState.usuarioId);
                    actualizarBadgeEstudianteLista(huellasState.usuarioId);
                    mostrarFeedbackHuella('success', '✅ ¡Huella de \'' + dedo + '\' registrada exitosamente en Ranura S' + slot + '!');
                } else {
                    mostrarFeedbackHuella('danger', '⚠️ ' + (saveData.mensaje || 'Error al guardar la huella.'));
                }
            } else if (estado === 'error') {
                await cancelarCapturaSensor(false);
                mostrarFeedbackHuella('danger', '❌ Error del sensor: ' + (mensaje || 'Coloca el dedo firmemente e intenta de nuevo.'));
            }
        } catch(e) {}
    }, 700);
}

async function cancelarCapturaSensor(llamarCancelApi = true) {
    if (enrollPollingInterval) { clearInterval(enrollPollingInterval); enrollPollingInterval = null; }
    if (timerInterval)         { clearInterval(timerInterval); timerInterval = null; }

    const statusBox   = document.getElementById('sensor-enroll-status');
    const progressBar = document.getElementById('sensor-progress-bar');
    const timerBadge  = document.getElementById('sensor-timer-badge');
    if (statusBox)    statusBox.classList.add('d-none');
    if (progressBar)  {
        progressBar.style.width = '100%';
        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
    }
    if (timerBadge)   timerBadge.textContent = '90s';
    resetBotonesForm();

    if (llamarCancelApi) {
        try { await fetch('api/fingerprint_controller.php?action=cancel'); } catch(e) {}
    }
}

function cancelarRegistroHuella() {
    cancelarCapturaSensor(true);
    document.getElementById('form-registro-huella').classList.add('d-none');
    huellasState.slotActivo = null;
    document.querySelectorAll('.finger-slot').forEach(s => s.classList.remove('active-slot'));
}

async function eliminarSlot(event, slotNumero) {
    event.stopPropagation();
    if (!confirm(`¿Eliminar la huella de la Ranura S${slotNumero}?`)) return;
    const fd = new FormData();
    fd.append('usuario_id', huellasState.usuarioId);
    fd.append('slot_numero', slotNumero);
    try {
        const resp = await fetch('index.php?c=acceso&a=eliminarHuellaEstudiante', { method: 'POST', body: fd });
        const data = await resp.json();
        await cargarHuellasEstudiante(huellasState.usuarioId);
        actualizarBadgeEstudianteLista(huellasState.usuarioId);
        mostrarFeedbackHuella('info', data.mensaje || `Huella de Ranura S${slotNumero} eliminada.`);
    } catch (e) {
        mostrarFeedbackHuella('danger', 'Error al eliminar huella.');
    }
}

function actualizarBadgeEstudianteLista(usuarioId) {
    const card = document.querySelector(`.student-finger-card[data-id="${usuarioId}"]`);
    if (!card) return;
    const badge = card.querySelector('.badge');
    if (!badge) return;
    const count = (huellasState.huellas || []).length;
    badge.textContent = `${count > 0 ? 1 : 0}/1`;
    if (count > 0) {
        badge.classList.remove('bg-secondary');
        badge.classList.add('bg-success');
    } else {
        badge.classList.remove('bg-success');
        badge.classList.add('bg-secondary');
    }
}

function mostrarFeedbackHuella(tipo, mensaje) {
    const container = document.getElementById('feedback-huellas-container');
    if (!container) {
        alert(mensaje);
        return;
    }
    container.innerHTML = `
        <div class="alert alert-${tipo} alert-dismissible fade show py-2 px-3 small d-flex align-items-center justify-content-between mb-3 shadow-sm" role="alert">
            <div>${mensaje}</div>
            <button type="button" class="btn-close btn-sm p-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    setTimeout(() => {
        if (container.firstElementChild) {
            container.innerHTML = '';
        }
    }, 6000);
}
JS;

require __DIR__ . '/../layout/app_layout.php';

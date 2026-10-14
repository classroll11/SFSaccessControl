<?php
/**
 * views/porteria/index.php
 * Vista de Terminal de Portería y Control de Acceso
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
                    <span class="badge rounded-pill px-3 py-1 bg-success text-white" style="font-size:0.75rem;">
                        <i class="fa-solid fa-shield-halved me-1"></i> CONTROL DE PORTERÍA
                    </span>
                    <span class="text-white-50 small">&bull; Acceso Peatonal Principal</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Terminal Biométrica de Portería</h1>
                <p class="text-white-50 mb-0 small">
                    Validación y registro de entradas y salidas de estudiantes y personal en tiempo real.
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

    <!-- KPIs DE PORTERÍA -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Estudiantes Dentro</span>
                        <h2 class="fw-bold my-1 text-success" id="kpi-estudiantes-dentro"><?= $estudiantesDentro ?? 0 ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-school text-success me-1"></i> Aforo Actual</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-green">
                        <i class="fa-solid fa-school"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Entradas Registradas</span>
                        <h2 class="fw-bold my-1 text-primary" id="kpi-entradas"><?= $estadisticas['total_entradas'] ?? 0 ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-door-open text-primary me-1"></i> Hoy</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-blue">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Salidas Registradas</span>
                        <h2 class="fw-bold my-1 text-warning" id="kpi-salidas"><?= $estadisticas['total_salidas'] ?? 0 ?></h2>
                        <span class="small text-muted"><i class="fa-solid fa-person-walking-arrow-right text-warning me-1"></i> Hoy</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-amber">
                        <i class="fa-solid fa-person-walking-arrow-right"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Accesos Denegados</span>
                        <h2 class="fw-bold my-1 text-danger" id="kpi-rechazados"><?= $estadisticas['rechazados'] ?? 0 ?></h2>
                        <span class="small text-danger fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Alertas</span>
                    </div>
                    <div class="kpi-icon-wrapper kpi-rose">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TERMINAL DE ESCÁNER Y LECTURA BIOMÉTRICA DIRECTA -->
    <div class="row g-4 mb-4">
        <!-- Escáner biométrico -->
        <div class="col-12 col-lg-7">
            <div class="card-surface h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Lector Biométrico</h5>
                        <small class="text-muted">Escanea la huella → identifica al estudiante → confirma el movimiento</small>
                    </div>
                    <span class="badge bg-success bg-opacity-15 text-success px-3 py-2" id="badge-sensor-estado">
                        <span class="live-dot" style="width:6px;height:6px;"></span> Sensor Activo
                    </span>
                </div>

                <!-- Visor del sensor -->
                <div class="biometric-scanner-box mb-3 text-center position-relative" id="scanner-box" style="cursor:pointer;" onclick="activarHuelleroPorteria()">
                    <div class="scanner-beam d-none" id="scanner-beam"></div>
                    <div class="biometric-pulse mx-auto mb-3" id="scanner-pulse-icon" style="width:90px;height:90px;">
                        <i class="fa-solid fa-fingerprint" id="scanner-icon-img" style="font-size:3.2rem; color:#38bdf8; transition: all 0.3s ease;"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-1" id="scanner-titulo">Sensor Biométrico Digital</h5>
                    <p class="text-white-50 small mb-3" id="scanner-subtitulo">Haz clic en el botón o sobre el sensor para activar</p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap" onclick="event.stopPropagation()">
                        <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" id="btn-escanear-directo" onclick="activarHuelleroPorteria()">
                            <i class="fa-solid fa-fingerprint me-2"></i> Escanear Huella
                        </button>
                        <button class="btn btn-outline-danger rounded-pill px-3 fw-semibold btn-sm d-none" id="btn-cancelar-escaneo" onclick="cancelarEscaneoPorteria()">
                            <i class="fa-solid fa-xmark me-1"></i> Cancelar
                        </button>
                    </div>
                </div>

                <!-- ══ PANEL DE RESULTADO / CONFIRMACIÓN ══ -->
                <div id="panel-resultado" class="d-none mb-3"></div>

                <!-- Mensaje de error / rechazado -->
                <div id="live-alert" class="d-none mb-3"></div>

                <!-- Registro manual -->
                <div class="p-3 bg-light rounded-3 border mt-2">
                    <span class="d-block fw-bold small text-muted mb-2 text-uppercase">
                        <i class="fa-solid fa-keyboard me-1"></i> Registro Manual por Documento
                    </span>
                    <form id="form-simulador-biometrico" onsubmit="enviarSimulacion(event)">
                        <div class="row g-2">
                            <div class="col-12 col-md-5">
                                <input type="text" class="form-control form-control-sm" name="documento" id="input-documento" placeholder="N° Documento...">
                            </div>
                            <div class="col-12 col-md-4">
                                <select class="form-select form-select-sm" name="tipo_evento">
                                    <option value="">Auto (Alternar Entrada/Salida)</option>
                                    <option value="ENTRADA">Forzar Entrada</option>
                                    <option value="SALIDA">Forzar Salida</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="submit" class="btn btn-dark btn-sm w-100 fw-semibold" id="btn-simular">
                                    <i class="fa-solid fa-check me-1"></i> Registrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel lateral: Protocolo + Último resultado confirmado -->
        <div class="col-12 col-lg-5">
            <div class="card-surface h-100 d-flex flex-column gap-3">
                <div>
                    <h5 class="fw-bold mb-3">Protocolo de Portería</h5>
                    <ul class="list-unstyled d-flex flex-column gap-3">
                        <li class="d-flex align-items-start gap-3">
                            <span class="badge bg-primary rounded-circle p-2" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
                            <div>
                                <strong class="d-block text-dark small">Escanear Huella</strong>
                                <span class="text-muted small">Activa el sensor y pide al estudiante colocar el dedo registrado.</span>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <span class="badge bg-primary rounded-circle p-2" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
                            <div>
                                <strong class="d-block text-dark small">Verificar Identidad</strong>
                                <span class="text-muted small">El sistema busca y muestra al estudiante. <strong>Verifica que sea la persona correcta.</strong></span>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <span class="badge bg-success rounded-circle p-2" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
                            <div>
                                <strong class="d-block text-dark small">Confirmar Movimiento</strong>
                                <span class="text-muted small">Pulsa <strong class="text-success">Confirmar Entrada</strong> o <strong class="text-warning">Confirmar Salida</strong> para registrar el acceso.</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Último acceso confirmado -->
                <div class="p-3 rounded-3 border" id="panel-ultimo-acceso" style="background:rgba(16,185,129,0.05);">
                    <p class="small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-clock-rotate-left me-1"></i> Último Acceso Confirmado</p>
                    <p class="text-muted small mb-0" id="ultimo-acceso-txt">Ninguno aún en esta sesión.</p>
                </div>

                <div class="p-3 rounded-3 bg-light border text-center mt-auto">
                    <span class="small text-muted d-block mb-1">¿Estudiante sin huella registrada?</span>
                    <a href="huellas.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                        <i class="fa-solid fa-fingerprint me-1"></i> Ir a Enrolar Huellas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MONITOR DE ACCESOS EN TIEMPO REAL -->
    <div class="card-surface">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h5 class="fw-bold mb-0">Monitor de Accesos en Tiempo Real</h5>
                <small class="text-muted">Actualización automática cada 5 segundos</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control form-control-sm rounded-pill" id="buscador-porteria" placeholder="Filtrar por nombre o doc..." oninput="filtrarTablaPorteria()" onkeyup="filtrarTablaPorteria()" style="max-width:240px;">
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="recargarDatosHistorial()">
                    <i class="fa-solid fa-rotate me-1"></i> Actualizar
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:100px;">Hora</th>
                        <th>Estudiante / Usuario</th>
                        <th>Grado</th>
                        <th>Movimiento</th>
                        <th>Estado</th>
                        <th>Detalle / Sensor</th>
                    </tr>
                </thead>
                <tbody id="tabla-accesos-body">
                    <?php if (empty($accesosRecientes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-inbox fs-3 d-block mb-2"></i>
                                No hay accesos registrados en portería hoy.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accesosRecientes as $acc): 
                            $nombre = $acc['nombre_usuario'] ?? $acc['nombre'] ?? 'Persona no identificada';
                            $documento = $acc['documento_usuario'] ?? $acc['documento'] ?? '—';
                            $grado = $acc['grado_usuario'] ?? $acc['grado'] ?? 'PERSONAL';
                            $estadoAcceso = $acc['estado_acceso'] ?? $acc['resultado'] ?? 'APROBADO';
                            $esAprobado = in_array(strtoupper($estadoAcceso), ['APROBADO', 'PERMITIDO']);
                        ?>
                            <tr>
                                <td class="small fw-semibold text-muted">
                                    <?= date('h:i:s A', strtotime($acc['fecha_hora'])) ?>
                                </td>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($nombre) ?></strong>
                                    <small class="text-muted">Doc: <?= htmlspecialchars($documento) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($grado) ?></span></td>
                                <td>
                                    <?php if ($acc['tipo_evento'] === 'ENTRADA'): ?>
                                        <span class="badge bg-success bg-opacity-15 text-success">
                                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> ENTRADA
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-15 text-warning">
                                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> SALIDA
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
                                <td class="small text-muted"><?= htmlspecialchars($acc['observaciones'] ?? 'Lectura biométrica') ?></td>
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
$paginaActual = 'porteria';
$tituloPagina = 'Terminal de Portería';
$breadcrumb = 'Control de Portería';

$scriptExtra = <<<'JS'
let scannerPollingInterval = null;
let escanerActivo = false;
let personaIdentificada = null; // Guarda los datos de la persona encontrada

function reproducirSonidoBiometrico(tipo) {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);

        if (tipo === 'exito') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, audioCtx.currentTime);
            osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
            osc.start(); osc.stop(audioCtx.currentTime + 0.35);
        } else if (tipo === 'rechazado') {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, audioCtx.currentTime);
            osc.frequency.setValueAtTime(164.81, audioCtx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
            osc.start(); osc.stop(audioCtx.currentTime + 0.4);
        } else if (tipo === 'beep') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(700, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
            osc.start(); osc.stop(audioCtx.currentTime + 0.1);
        }
    } catch(e) {}
}

// ─── PASO 1: Activar el sensor ───────────────────────────────────────────────
async function activarHuelleroPorteria() {
    if (escanerActivo) { cancelarEscaneoPorteria(); return; }

    escanerActivo = true;
    personaIdentificada = null;
    reproducirSonidoBiometrico('beep');

    // Limpiar paneles previos
    document.getElementById('panel-resultado').classList.add('d-none');
    document.getElementById('live-alert').classList.add('d-none');

    // UI: sensor activo
    const box       = document.getElementById('scanner-box');
    const beam      = document.getElementById('scanner-beam');
    const icon      = document.getElementById('scanner-icon-img');
    const titulo    = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const btnEsc    = document.getElementById('btn-escanear-directo');
    const btnCan    = document.getElementById('btn-cancelar-escaneo');

    if (beam) beam.classList.remove('d-none');
    if (icon) { icon.className = 'fa-solid fa-fingerprint fa-beat-fade'; icon.style.color = '#38bdf8'; }
    if (box)  { box.style.borderColor = '#38bdf8'; box.style.boxShadow = '0 0 25px rgba(56,189,248,0.35)'; }
    if (titulo) titulo.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2 text-info"></i> Huellero Activo';
    if (subtitulo) subtitulo.textContent = 'Coloca el dedo sobre el lector biométrico...';
    if (btnEsc) { btnEsc.classList.replace('btn-primary','btn-info'); btnEsc.innerHTML = '<i class="fa-solid fa-fingerprint fa-bounce me-2"></i> Esperando huella...'; }
    if (btnCan) btnCan.classList.remove('d-none');

    // Enviar la solicitud al agente biométrico interactivo
    try {
        const launchResp = await fetch('api/fingerprint_controller.php?action=launch_identify');
        const launchData = await launchResp.json();
        if (!launchResp.ok) {
            cancelarEscaneoPorteria(launchData.mensaje || 'No se pudo iniciar el lector biométrico.');
            return;
        }
    } catch(e) {
        cancelarEscaneoPorteria('No se pudo conectar con el agente biométrico.');
        return;
    }

    // Polling
    let intentos = 0;
    scannerPollingInterval = setInterval(async () => {
        intentos++;
        if (intentos > 60) { cancelarEscaneoPorteria('Tiempo de espera finalizado.'); return; }
        try {
            const resp = await fetch('api/fingerprint_controller.php?action=poll');
            const data = await resp.json();

            if (data.status === 'esperando_agente') {
                if (subtitulo) subtitulo.textContent = data.mensaje || 'Activando el huellero SecuGen...';
            } else if (data.status === 'capturando') {
                if (subtitulo) subtitulo.textContent = data.mensaje || 'Coloca el dedo sobre el lector...';
            }

            if (data.status === 'identificado' && data.usuario_id) {
                clearInterval(scannerPollingInterval);
                await buscarPersonaPorHuella(data.usuario_id, data.slot, data.origen, data.dedo || '');
            } else if (data.status === 'no_registrado') {
                clearInterval(scannerPollingInterval);
                mostrarHuellaNoEncontrada();
            } else if (data.status === 'error') {
                clearInterval(scannerPollingInterval);
                cancelarEscaneoPorteria('Error del sensor: ' + (data.mensaje || 'Inténtalo de nuevo'));
            }
        } catch(e) {}
    }, 600);
}

// ─── PASO 2: Buscar quién es (identify_only) ─────────────────────────────────
async function buscarPersonaPorHuella(usuarioId, slot, origen, dedo) {
    escanerActivo = false;

    const icon      = document.getElementById('scanner-icon-img');
    const titulo    = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const beam      = document.getElementById('scanner-beam');
    const btnCan    = document.getElementById('btn-cancelar-escaneo');
    const box       = document.getElementById('scanner-box');

    if (beam)    beam.classList.add('d-none');
    if (btnCan)  btnCan.classList.add('d-none');
    if (icon)    { icon.className = 'fa-solid fa-spinner fa-spin'; icon.style.color = '#facc15'; }
    if (titulo)  titulo.innerHTML  = '<i class="fa-solid fa-magnifying-glass me-2 text-warning"></i> Buscando persona...';
    if (subtitulo) subtitulo.textContent = 'Consultando base de datos institucional...';
    if (box)     { box.style.borderColor = '#facc15'; box.style.boxShadow = '0 0 20px rgba(250,204,21,0.3)'; }

    try {
        const fd = new FormData();
        fd.append('usuario_id', usuarioId);
        fd.append('slot', slot);
        fd.append('origen', origen || 'huellas');
        if (dedo) fd.append('dedo', dedo);

        const resp = await fetch('api/fingerprint_controller.php?action=identify_only', { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.status === 'encontrado' && data.usuario) {
            personaIdentificada = data;
            reproducirSonidoBiometrico('beep');
            mostrarTarjetaConfirmacion(data);
        } else {
            mostrarHuellaNoEncontrada();
        }
    } catch(e) {
        mostrarHuellaNoEncontrada();
    }
}

// ─── PASO 3: Mostrar tarjeta de confirmación ─────────────────────────────────
function mostrarTarjetaConfirmacion(data) {
    const u = data.usuario;
    const tipoSug = data.tipo_sugerido || 'ENTRADA';
    const partes = (u.nombre || 'ST').trim().split(' ');
    const iniciales = ((partes[0]?.[0] || '') + (partes[1]?.[0] || '')).toUpperCase();
    const esActivo = u.estado === 'ACTIVO';

    // Actualizar sensor UI a "encontrado"
    const icon      = document.getElementById('scanner-icon-img');
    const titulo    = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const box       = document.getElementById('scanner-box');
    const btnEsc    = document.getElementById('btn-escanear-directo');

    if (icon)    { icon.className = 'fa-solid fa-circle-user'; icon.style.color = '#a78bfa'; }
    if (box)     { box.style.borderColor = '#7c3aed'; box.style.boxShadow = '0 0 25px rgba(124,58,237,0.35)'; }
    if (titulo)  titulo.innerHTML = '<span class="text-purple fw-bold" style="color:#a78bfa;"><i class="fa-solid fa-user-check me-2"></i>Persona Identificada</span>';
    if (subtitulo) subtitulo.textContent = 'Confirma el tipo de movimiento abajo';
    if (btnEsc)  { btnEsc.classList.replace('btn-info','btn-primary'); btnEsc.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Nueva Lectura'; }

    // Construir tarjeta de confirmación
    const panelRes = document.getElementById('panel-resultado');
    panelRes.classList.remove('d-none');
    panelRes.innerHTML = `
        <div class="p-3 rounded-3 border shadow-sm" style="background:linear-gradient(135deg,rgba(124,58,237,0.06),rgba(56,189,248,0.06)); border-color:rgba(124,58,237,0.3)!important;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0 shadow"
                     style="width:56px;height:56px;font-size:1.2rem;background:linear-gradient(135deg,#7c3aed,#a78bfa);">
                    ${iniciales}
                </div>
                <div class="flex-grow-1" style="min-width:0;">
                    <h6 class="fw-bold mb-0 text-dark text-truncate">${u.nombre || '—'}</h6>
                    <div class="small text-muted d-flex flex-wrap gap-2 mt-1">
                        <span><i class="fa-solid fa-id-card me-1 text-primary"></i>${u.documento || '—'}</span>
                        <span><i class="fa-solid fa-graduation-cap me-1 text-primary"></i>${u.grado || '—'}</span>
                        <span><i class="fa-solid fa-fingerprint me-1 text-success"></i>${u.dedo || 'Huella'}</span>
                    </div>
                </div>
                <span class="badge ${esActivo ? 'bg-success' : 'bg-danger'} text-white rounded-pill px-2 py-1 flex-shrink-0">
                    ${esActivo ? 'ACTIVO' : u.estado}
                </span>
            </div>
            ${esActivo ? `
            <p class="small text-muted mb-2 fw-semibold">
                <i class="fa-solid fa-circle-info me-1 text-info"></i>
                Movimiento sugerido: <strong class="${tipoSug === 'ENTRADA' ? 'text-success' : 'text-warning'}">${tipoSug}</strong>
                — Confirma o elige otro tipo:
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-success rounded-pill px-4 fw-bold flex-grow-1" id="btn-conf-entrada" onclick="confirmarAcceso('ENTRADA')">
                    <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Confirmar Entrada
                </button>
                <button class="btn btn-warning rounded-pill px-4 fw-bold flex-grow-1" id="btn-conf-salida" onclick="confirmarAcceso('SALIDA')">
                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Confirmar Salida
                </button>
                <button class="btn btn-outline-secondary rounded-pill px-3 fw-semibold" onclick="cancelarConfirmacion()" title="Cancelar sin registrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            ` : `
            <div class="alert alert-danger mb-0 py-2 small fw-semibold">
                <i class="fa-solid fa-ban me-1"></i>
                Acceso denegado: El estudiante tiene estado <strong>${u.estado}</strong>. No se puede registrar acceso.
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-pill mt-2 px-3" onclick="cancelarConfirmacion()">
                <i class="fa-solid fa-xmark me-1"></i> Cerrar
            </button>
            `}
        </div>
    `;
}

// ─── PASO 4: El portero confirma el acceso ───────────────────────────────────
async function confirmarAcceso(tipoEvento) {
    if (!personaIdentificada || !personaIdentificada.usuario) return;

    const u = personaIdentificada.usuario;
    const btnE = document.getElementById('btn-conf-entrada');
    const btnS = document.getElementById('btn-conf-salida');
    if (btnE) { btnE.disabled = true; btnE.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>'; }
    if (btnS) { btnS.disabled = true; btnS.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>'; }

    try {
        const fd = new FormData();
        fd.append('usuario_id', u.id);
        fd.append('tipo_evento', tipoEvento);
        fd.append('dedo', u.dedo || 'Huella');

        const resp = await fetch('api/fingerprint_controller.php?action=confirm_access', { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.status === 'exito') {
            reproducirSonidoBiometrico('exito');
            mostrarAccesoConfirmado(data, u, tipoEvento);
        } else {
            reproducirSonidoBiometrico('rechazado');
            document.getElementById('panel-resultado').classList.add('d-none');
            const alertBox = document.getElementById('live-alert');
            alertBox.classList.remove('d-none');
            alertBox.innerHTML = '<div class="alert alert-danger mb-0"><i class="fa-solid fa-ban me-2"></i>' + (data.mensaje || 'Error al registrar acceso.') + '</div>';
        }
    } catch(e) {
        reproducirSonidoBiometrico('rechazado');
    }

    personaIdentificada = null;
    recargarDatosHistorial();
}

// ─── Mostrar acceso confirmado en UI ─────────────────────────────────────────
function mostrarAccesoConfirmado(data, u, tipoEvento) {
    const esEntrada = tipoEvento === 'ENTRADA';
    const ahora = new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    const partes = (u.nombre || 'ST').trim().split(' ');
    const iniciales = ((partes[0]?.[0] || '') + (partes[1]?.[0] || '')).toUpperCase();

    // Actualizar panel de resultado con éxito
    const panelRes = document.getElementById('panel-resultado');
    panelRes.classList.remove('d-none');
    panelRes.innerHTML = `
        <div class="p-3 rounded-3 border ${esEntrada ? 'border-success' : 'border-warning'} shadow-sm d-flex align-items-center gap-3"
             style="background:${esEntrada ? 'rgba(16,185,129,0.07)' : 'rgba(245,158,11,0.07)'};">
            <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0 shadow"
                 style="width:52px;height:52px;font-size:1.1rem;background:linear-gradient(135deg,${esEntrada ? '#059669,#34d399' : '#b45309,#fbbf24'});">
                ${iniciales}
            </div>
            <div class="flex-grow-1" style="min-width:0;">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
                    <span class="badge ${esEntrada ? 'bg-success' : 'bg-warning text-dark'} px-3 py-1 rounded-pill fw-bold">
                        <i class="fa-solid ${esEntrada ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket'} me-1"></i>
                        ${tipoEvento} REGISTRADA
                    </span>
                    <span class="small text-muted fw-semibold">${ahora}</span>
                </div>
                <h6 class="fw-bold text-dark mb-0 text-truncate">${u.nombre || '—'}</h6>
                <div class="small text-muted">${u.grado || ''} &bull; Doc: ${u.documento || '—'}</div>
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 flex-shrink-0" onclick="cancelarConfirmacion()" title="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    `;

    // Sensor UI: listo para próxima lectura
    const icon      = document.getElementById('scanner-icon-img');
    const titulo    = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const box       = document.getElementById('scanner-box');
    const btnEsc    = document.getElementById('btn-escanear-directo');

    if (icon)    { icon.className = 'fa-solid fa-circle-check'; icon.style.color = esEntrada ? '#34d399' : '#fbbf24'; }
    if (box)     { box.style.borderColor = esEntrada ? '#10b981' : '#f59e0b'; box.style.boxShadow = '0 0 20px ' + (esEntrada ? 'rgba(16,185,129,0.4)' : 'rgba(245,158,11,0.4)'); }
    if (titulo)  titulo.innerHTML = '<span class="fw-bold" style="color:' + (esEntrada ? '#34d399' : '#fbbf24') + '"><i class="fa-solid fa-circle-check me-2"></i>' + (esEntrada ? '¡ENTRADA REGISTRADA!' : '¡SALIDA REGISTRADA!') + '</span>';
    if (subtitulo) subtitulo.textContent = (u.nombre || 'Estudiante') + ' — Acceso confirmado por portero.';
    if (btnEsc)  btnEsc.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Nueva Lectura';

    // Actualizar panel lateral "último acceso"
    const ultimoTxt = document.getElementById('ultimo-acceso-txt');
    if (ultimoTxt) {
        ultimoTxt.innerHTML = '<strong class="' + (esEntrada ? 'text-success' : 'text-warning') + '">' + tipoEvento + '</strong> — ' +
            '<strong>' + (u.nombre || '—') + '</strong> (' + (u.grado || '—') + ')<br>' +
            '<small class="text-muted">' + ahora + '</small>';
    }
}

// ─── Cancelar / resetear ──────────────────────────────────────────────────────
function cancelarConfirmacion() {
    personaIdentificada = null;
    document.getElementById('panel-resultado').classList.add('d-none');
    document.getElementById('live-alert').classList.add('d-none');

    const icon      = document.getElementById('scanner-icon-img');
    const titulo    = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const box       = document.getElementById('scanner-box');
    const btnEsc    = document.getElementById('btn-escanear-directo');

    if (icon)    { icon.className = 'fa-solid fa-fingerprint'; icon.style.color = '#38bdf8'; }
    if (box)     { box.style.borderColor = 'rgba(56,189,248,0.25)'; box.style.boxShadow = 'none'; }
    if (titulo)  titulo.textContent = 'Sensor Biométrico Digital';
    if (subtitulo) subtitulo.textContent = 'Haz clic en el botón o sobre el sensor para activar';
    if (btnEsc)  { btnEsc.classList.replace('btn-info','btn-primary'); btnEsc.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Escanear Huella'; }
}

function cancelarEscaneoPorteria(mensaje = null) {
    escanerActivo = false;
    if (scannerPollingInterval) clearInterval(scannerPollingInterval);

    const beam  = document.getElementById('scanner-beam');
    const btnCan = document.getElementById('btn-cancelar-escaneo');
    if (beam)   beam.classList.add('d-none');
    if (btnCan) btnCan.classList.add('d-none');

    cancelarConfirmacion();
    if (mensaje) {
        const subtitulo = document.getElementById('scanner-subtitulo');
        if (subtitulo) subtitulo.textContent = mensaje;
    }
}

function mostrarHuellaNoEncontrada() {
    reproducirSonidoBiometrico('rechazado');
    escanerActivo = false;

    const icon      = document.getElementById('scanner-icon-img');
    const titulo    = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const box       = document.getElementById('scanner-box');
    const beam      = document.getElementById('scanner-beam');
    const btnEsc    = document.getElementById('btn-escanear-directo');
    const btnCan    = document.getElementById('btn-cancelar-escaneo');
    const alertBox  = document.getElementById('live-alert');

    if (beam)  beam.classList.add('d-none');
    if (btnCan) btnCan.classList.add('d-none');
    if (icon)  { icon.className = 'fa-solid fa-triangle-exclamation'; icon.style.color = '#f87171'; }
    if (box)   { box.style.borderColor = '#ef4444'; box.style.boxShadow = '0 0 25px rgba(239,68,68,0.4)'; }
    if (titulo) titulo.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-ban me-2"></i>HUELLA NO ENCONTRADA</span>';
    if (subtitulo) subtitulo.textContent = 'No se encontró ningún estudiante con esa huella dactilar';
    if (btnEsc) { btnEsc.classList.replace('btn-info','btn-primary'); btnEsc.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Intentar de Nuevo'; }

    if (alertBox) {
        alertBox.classList.remove('d-none');
        alertBox.innerHTML = `
            <div class="p-3 rounded-3 border border-danger bg-white shadow-sm d-flex align-items-center gap-3">
                <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:48px;height:48px;font-size:1.2rem;background:linear-gradient(135deg,#dc2626,#f87171);">
                    <i class="fa-solid fa-xmark"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-danger mb-1">Huella no reconocida</h6>
                    <p class="small text-muted mb-1">No coincide con ningún estudiante registrado.</p>
                    <a href="huellas.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold" style="font-size:0.75rem;">
                        <i class="fa-solid fa-fingerprint me-1"></i> Enrolar Huellas
                    </a>
                </div>
            </div>
        `;
    }
}

// ─── Registro manual por documento ───────────────────────────────────────────
async function enviarSimulacion(event) {
    event.preventDefault();
    const btn = document.getElementById('btn-simular');
    const alertBox = document.getElementById('live-alert');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Validando...';

    const formData = new FormData(document.getElementById('form-simulador-biometrico'));
    try {
        const response = await fetch('index.php?c=acceso&a=simularManual', { method: 'POST', body: formData });
        const data = await response.json();

        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
        if (data.status === 'exito') {
            alertBox.innerHTML = '<div class="alert alert-success mb-0"><strong><i class="fa-solid fa-circle-check me-1"></i>' +
                data.mensaje + '</strong><br><small>' + (data.usuario ? data.usuario.nombre + ' (' + data.usuario.grado + ') — ' + data.tipo_evento : '') + '</small></div>';
            document.getElementById('input-documento').value = '';
            reproducirSonidoBiometrico('exito');
        } else {
            alertBox.innerHTML = '<div class="alert alert-danger mb-0"><strong><i class="fa-solid fa-triangle-exclamation me-1"></i>' + data.mensaje + '</strong></div>';
            reproducirSonidoBiometrico('rechazado');
        }
        recargarDatosHistorial();
    } catch (error) { console.error('Error:', error); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Registrar';
    }
}

// ─── Historial / tabla ────────────────────────────────────────────────────────
async function recargarDatosHistorial() {
    try {
        const response = await fetch('index.php?c=acceso&a=obtenerHistorialJson');
        const data = await response.json();
        if (data.status === 'ok') {
            const kd = document.getElementById('kpi-estudiantes-dentro');
            if (kd) kd.textContent = data.estadisticas.estudiantes_dentro;
            const ke = document.getElementById('kpi-entradas');
            if (ke) ke.textContent = data.estadisticas.total_entradas;
            const ks = document.getElementById('kpi-salidas');
            if (ks) ks.textContent = data.estadisticas.total_salidas;
            const kr = document.getElementById('kpi-rechazados');
            if (kr) kr.textContent = data.estadisticas.rechazados;

            if (data.accesos && data.accesos.length > 0) {
                const tbody = document.getElementById('tabla-accesos-body');
                tbody.innerHTML = data.accesos.map(acc => {
                    const hora = new Date(acc.fecha_hora).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                    const isEntrada   = acc.tipo_evento === 'ENTRADA';
                    const isPermitido = acc.resultado === 'PERMITIDO';
                    return `
                        <tr>
                            <td class="small fw-semibold text-muted">\${hora}</td>
                            <td>
                                <strong class="d-block text-dark">\${acc.nombre || 'Persona no identificada'}</strong>
                                <small class="text-muted">Doc: \${acc.documento || '—'}</small>
                            </td>
                            <td><span class="badge bg-light text-dark border">\${acc.grado || 'PERSONAL'}</span></td>
                            <td>
                                <span class="badge \${isEntrada ? 'bg-success bg-opacity-15 text-success' : 'bg-warning bg-opacity-15 text-warning'}">
                                    <i class="fa-solid \${isEntrada ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket'} me-1"></i> \${acc.tipo_evento}
                                </span>
                            </td>
                            <td>
                                <span class="badge \${isPermitido ? 'bg-success' : 'bg-danger'} text-white">
                                    <i class="fa-solid \${isPermitido ? 'fa-check' : 'fa-xmark'}"></i> \${acc.resultado}
                                </span>
                            </td>
                            <td class="small text-muted">\${acc.observaciones || 'Lectura biométrica'}</td>
                        </tr>
                    `;
                }).join('');
                filtrarTablaPorteria();
            }
        }
    } catch (err) {}
}

function normalizarTextoPorteria(str) {
    return (str || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[°º\-]/g, ' ').trim();
}

function filtrarTablaPorteria() {
    const input = document.getElementById('buscador-porteria');
    if (!input) return;
    const q = normalizarTextoPorteria(input.value);
    const terminos = q.split(/\s+/).filter(t => t.length > 0);
    document.querySelectorAll('#tabla-accesos-body tr').forEach(fila => {
        const txt = normalizarTextoPorteria(fila.textContent);
        const match = terminos.length === 0 || terminos.every(term => txt.includes(term));
        fila.style.display = match ? '' : 'none';
    });
}

JS;

require __DIR__ . '/../layout/app_layout.php';

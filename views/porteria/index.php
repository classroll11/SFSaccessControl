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
                        <h5 class="fw-bold mb-0">Lector Biométrico Directo</h5>
                        <small class="text-muted">Primero lee la huella y busca al estudiante automáticamente</small>
                    </div>
                    <span class="badge bg-success bg-opacity-15 text-success px-3 py-2">
                        <span class="live-dot" style="width:6px;height:6px;"></span> Sensor Activo
                    </span>
                </div>

                <!-- Visor Interactivo del Lector Biométrico -->
                <div class="biometric-scanner-box mb-3 text-center position-relative" id="scanner-box" style="cursor:pointer;" onclick="activarHuelleroPorteria()">
                    <div class="scanner-beam d-none" id="scanner-beam"></div>
                    
                    <div class="biometric-pulse mx-auto mb-3" id="scanner-pulse-icon" style="width:90px;height:90px;" title="Haz clic para activar sensor">
                        <i class="fa-solid fa-fingerprint" id="scanner-icon-img" style="font-size:3.2rem; color:#38bdf8; transition: all 0.3s ease;"></i>
                    </div>

                    <h5 class="text-white fw-bold mb-1" id="scanner-titulo">Sensor Biométrico Digital</h5>
                    <p class="text-white-50 small mb-3" id="scanner-subtitulo">Haz clic en el botón o sobre el sensor para iniciar la lectura</p>
                    
                    <div class="d-flex justify-content-center gap-2 flex-wrap" onclick="event.stopPropagation()">
                        <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" id="btn-escanear-directo" onclick="activarHuelleroPorteria()">
                            <i class="fa-solid fa-fingerprint me-2"></i> Escanear Huella Ahora
                        </button>
                        <button class="btn btn-outline-danger rounded-pill px-3 fw-semibold btn-sm d-none" id="btn-cancelar-escaneo" onclick="cancelarEscaneoPorteria()">
                            <i class="fa-solid fa-xmark me-1"></i> Cancelar
                        </button>
                    </div>

                </div>

                <!-- Alerta y tarjeta de resultado en vivo -->
                <div id="live-alert" class="d-none mb-3"></div>

                <!-- Formulario manual / simulador de contingencia -->
                <div class="p-3 bg-light rounded-3 border">
                    <span class="d-block fw-bold small text-muted mb-2 text-uppercase">
                        <i class="fa-solid fa-keyboard me-1"></i> Registro Manual por Documento o Huella Específica
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

        <!-- Panel de Estado del Sensor e Instrucciones -->
        <div class="col-12 col-lg-5">
            <div class="card-surface h-100">
                <h5 class="fw-bold mb-3">Protocolo de Portería</h5>
                <ul class="list-unstyled d-flex flex-column gap-3 mb-4">
                    <li class="d-flex align-items-start gap-3">
                        <span class="badge bg-primary rounded-circle p-2" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
                        <div>
                            <strong class="d-block text-dark small">Paso del Estudiante</strong>
                            <span class="text-muted small">El estudiante debe presentar el dedo registrado en el lector óptico sin presionar con fuerza excesiva.</span>
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-3">
                        <span class="badge bg-primary rounded-circle p-2" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
                        <div>
                            <strong class="d-block text-dark small">Identificación Automática</strong>
                            <span class="text-muted small">El algoritmo biométrico busca en los registros dactilares y valida el estado de matrícula activa.</span>
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-3">
                        <span class="badge bg-primary rounded-circle p-2" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
                        <div>
                            <strong class="d-block text-dark small">Desbloqueo de Torniquete</strong>
                            <span class="text-muted small">Luz verde en pantalla confirma ingreso y registra hora exacta en el log institucional.</span>
                        </div>
                    </li>
                </ul>

                <div class="p-3 rounded-3 bg-light border text-center">
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

$scriptExtra = <<<JS
let scannerPollingInterval = null;
let escanerActivo = false;

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
            osc.start();
            osc.stop(audioCtx.currentTime + 0.35);
        } else if (tipo === 'rechazado') {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, audioCtx.currentTime);
            osc.frequency.setValueAtTime(164.81, audioCtx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.4);
        } else if (tipo === 'beep') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(700, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.1);
        }
    } catch(e) {}
}

async function activarHuelleroPorteria() {
    if (escanerActivo) {
        cancelarEscaneoPorteria();
        return;
    }
    
    escanerActivo = true;
    reproducirSonidoBiometrico('beep');

    const box = document.getElementById('scanner-box');
    const beam = document.getElementById('scanner-beam');
    const icon = document.getElementById('scanner-icon-img');
    const titulo = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const btnEscanear = document.getElementById('btn-escanear-directo');
    const btnCancelar = document.getElementById('btn-cancelar-escaneo');
    const quickTest = document.getElementById('scanner-quick-test');
    const alertBox = document.getElementById('live-alert');

    if (alertBox) alertBox.classList.add('d-none');

    // UI de Huellero Activo
    if (beam) beam.classList.remove('d-none');
    if (icon) {
        icon.className = 'fa-solid fa-fingerprint';
        icon.style.color = '#38bdf8';
        icon.classList.add('fa-beat-fade');
    }
    if (box) {
        box.style.borderColor = '#38bdf8';
        box.style.boxShadow = '0 0 25px rgba(56, 189, 248, 0.35)';
    }
    if (titulo) titulo.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2 text-info"></i> Huellero Activo';
    if (subtitulo) subtitulo.innerHTML = 'Coloca el dedo sobre el lector biométrico para verificar...';
    if (btnEscanear) {
        btnEscanear.classList.replace('btn-primary', 'btn-info');
        btnEscanear.innerHTML = '<i class="fa-solid fa-fingerprint fa-bounce me-2"></i> Esperando huella...';
    }
    if (btnCancelar) btnCancelar.classList.remove('d-none');
    if (quickTest) quickTest.classList.remove('d-none');

    // Iniciar el listener de hardware físico en background
    try {
        await fetch('api/fingerprint_controller.php?action=launch_identify');
    } catch(e) {}

    // Polling del estado del lector físico
    let intentos = 0;
    scannerPollingInterval = setInterval(async () => {
        intentos++;
        if (intentos > 40) {
            cancelarEscaneoPorteria('Tiempo de espera finalizado. Presiona Escanear para reactivar.');
            return;
        }

        try {
            const resp = await fetch('api/fingerprint_controller.php?action=poll');
            const data = await resp.json();

            if (data.status === 'identificado' && data.identity_hex) {
                clearInterval(scannerPollingInterval);
                await procesarHuellaDetectada(data.identity_hex, data.dedo);
            } else if (data.status === 'no_registrado') {
                clearInterval(scannerPollingInterval);
                mostrarResultadoLectura({
                    status: 'rechazado',
                    codigo: 'NO_ENCONTRADO',
                    mensaje: 'Huella no reconocida: No se encontró ningún estudiante con esa huella.'
                });
            } else if (data.status === 'error') {
                clearInterval(scannerPollingInterval);
                cancelarEscaneoPorteria('Error del sensor: ' + (data.mensaje || 'Inténtalo de nuevo'));
            }
        } catch(e) {}
    }, 600);
}

function cancelarEscaneoPorteria(mensaje = null) {
    escanerActivo = false;
    if (scannerPollingInterval) clearInterval(scannerPollingInterval);

    const box = document.getElementById('scanner-box');
    const beam = document.getElementById('scanner-beam');
    const icon = document.getElementById('scanner-icon-img');
    const titulo = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const btnEscanear = document.getElementById('btn-escanear-directo');
    const btnCancelar = document.getElementById('btn-cancelar-escaneo');
    const quickTest = document.getElementById('scanner-quick-test');

    if (beam) beam.classList.add('d-none');
    if (icon) {
        icon.className = 'fa-solid fa-fingerprint';
        icon.style.color = '#38bdf8';
        icon.classList.remove('fa-beat-fade');
    }
    if (box) {
        box.style.borderColor = 'rgba(56,189,248,0.25)';
        box.style.boxShadow = 'none';
    }
    if (titulo) titulo.textContent = 'Sensor Biométrico Digital';
    if (subtitulo) subtitulo.textContent = mensaje || 'Haz clic en el botón o sobre el sensor para iniciar la lectura';
    if (btnEscanear) {
        btnEscanear.classList.replace('btn-info', 'btn-primary');
        btnEscanear.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Escanear Huella Ahora';
    }
    if (btnCancelar) btnCancelar.classList.add('d-none');
    if (quickTest) quickTest.classList.add('d-none');
}

async function procesarHuellaDetectada(huellaTemplate, dedo) {
    if (scannerPollingInterval) clearInterval(scannerPollingInterval);
    escanerActivo = false;

    const beam = document.getElementById('scanner-beam');
    const titulo = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const btnEscanear = document.getElementById('btn-escanear-directo');
    const btnCancelar = document.getElementById('btn-cancelar-escaneo');

    if (beam) beam.classList.add('d-none');
    if (btnCancelar) btnCancelar.classList.add('d-none');

    if (titulo) titulo.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2 text-info"></i> Verificando huella...';
    if (subtitulo) subtitulo.textContent = 'Consultando base de datos institucional...';

    try {
        const fd = new FormData();
        fd.append('identity_hex', huellaTemplate);
        if (dedo) fd.append('dedo', dedo);

        const resp = await fetch('api/fingerprint_controller.php?action=process_identity', { method: 'POST', body: fd });
        const data = await resp.json();
        mostrarResultadoLectura(data);
    } catch(err) {
        mostrarResultadoLectura({
            status: 'rechazado',
            codigo: 'ERROR_CONEXION',
            mensaje: 'Error de comunicación con el servidor al procesar la huella.'
        });
    }
}

function mostrarResultadoLectura(data) {
    const box = document.getElementById('scanner-box');
    const icon = document.getElementById('scanner-icon-img');
    const titulo = document.getElementById('scanner-titulo');
    const subtitulo = document.getElementById('scanner-subtitulo');
    const btnEscanear = document.getElementById('btn-escanear-directo');
    const alertBox = document.getElementById('live-alert');

    const esExito = (data.status === 'exito' || data.acceso_permitido === true);

    if (esExito) {
        reproducirSonidoBiometrico('exito');

        if (box) {
            box.style.borderColor = '#10b981';
            box.style.boxShadow = '0 0 30px rgba(16, 185, 129, 0.45)';
        }
        if (icon) {
            icon.className = 'fa-solid fa-circle-check';
            icon.style.color = '#34d399';
            icon.classList.remove('fa-beat-fade');
        }
        if (titulo) titulo.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-2"></i> \${data.tipo_evento === 'ENTRADA' ? '¡ENTRADA REGISTRADA!' : '¡SALIDA REGISTRADA!'}</span>`;
        if (subtitulo) {
            const nom = data.usuario ? data.usuario.nombre : 'Estudiante';
            const gr = data.usuario ? data.usuario.grado : '';
            subtitulo.innerHTML = `<strong>\${nom}</strong> (\${gr}) &bull; Huella reconocida correctamente`;
        }

        if (alertBox) {
            alertBox.classList.remove('d-none');
            const u = data.usuario || {};
            const partes = (u.nombre || 'ST').trim().split(' ');
            const iniciales = ((partes[0]?.[0] || '') + (partes[1]?.[0] || '')).toUpperCase();
            const ahora = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            
            alertBox.innerHTML = `
                <div class="p-3 rounded-3 border border-success bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm"
                         style="width:52px;height:52px;font-size:1.15rem;background:linear-gradient(135deg,#059669,#34d399);">
                        \${iniciales}
                    </div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <span class="badge bg-success text-white px-3 py-1 rounded-pill fw-bold">
                                <i class="fa-solid fa-door-open me-1"></i> ACCESO CONCEDIDO &bull; \${data.tipo_evento}
                            </span>
                            <span class="small text-muted fw-semibold">\${ahora}</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-0 mt-1 text-truncate">\${u.nombre || 'Estudiante'}</h5>
                        <div class="small text-muted d-flex gap-3 flex-wrap mt-1">
                            <span><i class="fa-solid fa-graduation-cap me-1 text-primary"></i> Grado: <strong class="text-dark">\${u.grado || '—'}</strong></span>
                            <span><i class="fa-solid fa-id-card me-1 text-primary"></i> Documento: <strong class="text-dark">\${u.documento || '—'}</strong></span>
                            <span><i class="fa-solid fa-fingerprint me-1 text-success"></i> Dedo: <strong class="text-dark">\${u.dedo || 'Huella principal'}</strong></span>
                        </div>
                    </div>
                </div>
            `;
        }
    } else {
        reproducirSonidoBiometrico('rechazado');

        if (box) {
            box.style.borderColor = '#ef4444';
            box.style.boxShadow = '0 0 30px rgba(239, 68, 68, 0.45)';
        }
        if (icon) {
            icon.className = 'fa-solid fa-triangle-exclamation';
            icon.style.color = '#f87171';
            icon.classList.remove('fa-beat-fade');
        }
        if (titulo) titulo.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-ban me-2"></i> HUELLA NO ENCONTRADA</span>';
        if (subtitulo) subtitulo.textContent = 'No se encontró ningún estudiante con esa huella dactilar';

        if (alertBox) {
            alertBox.classList.remove('d-none');
            const ahora = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            
            alertBox.innerHTML = `
                <div class="p-3 rounded-3 border border-danger bg-white shadow-sm d-flex align-items-center gap-3">
                    <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm"
                         style="width:52px;height:52px;font-size:1.3rem;background:linear-gradient(135deg,#dc2626,#f87171);">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span class="badge bg-danger text-white px-3 py-1 rounded-pill fw-bold">
                                <i class="fa-solid fa-ban me-1"></i> ACCESO DENEGADO
                            </span>
                            <span class="small text-muted fw-semibold">\${ahora}</span>
                        </div>
                        <h6 class="fw-bold text-danger mb-1 mt-1">Huella dactilar no reconocida</h6>
                        <p class="small text-muted mb-2">La huella escaneada no coincide con ningún estudiante registrado en la institución.</p>
                        <a href="huellas.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold" style="font-size:0.75rem;">
                            <i class="fa-solid fa-fingerprint me-1"></i> Ir a Enrolar Huellas
                        </a>
                    </div>
                </div>
            `;
        }
    }

    if (btnEscanear) {
        btnEscanear.classList.replace('btn-info', 'btn-primary');
        btnEscanear.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Escanear Otra Huella';
    }

    recargarDatosHistorial();
}

async function enviarSimulacion(event) {
    event.preventDefault();
    const btn = document.getElementById('btn-simular');
    const alertBox = document.getElementById('live-alert');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Validando...';

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
            alertBox.innerHTML = `<strong><i class="fa-solid fa-circle-check me-1"></i> \${data.mensaje}</strong><br><small>Estudiante: \${data.usuario ? data.usuario.nombre : ''} (\${data.usuario ? data.usuario.grado : ''}) - Movimiento: \${data.tipo_evento}</small>`;
            document.getElementById('input-documento').value = '';
        } else {
            alertBox.classList.add('alert-danger');
            alertBox.innerHTML = `<strong><i class="fa-solid fa-triangle-exclamation me-1"></i> \${data.mensaje}</strong>`;
        }

        recargarDatosHistorial();
    } catch (error) {
        console.error('Error:', error);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Registrar';
    }
}

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
                    const isEntrada = acc.tipo_evento === 'ENTRADA';
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
    return (str || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[°º\-]/g, ' ')
        .trim();
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

setInterval(recargarDatosHistorial, 5000);
JS;

require __DIR__ . '/../layout/app_layout.php';

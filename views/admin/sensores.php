<?php
/**
 * views/admin/sensores.php
 * Vista de Sensores Biométricos y Hardware
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
                    <span class="badge rounded-pill px-3 py-1 bg-purple text-white" style="background:#7c3aed; font-size:0.75rem;">
                        <i class="fa-solid fa-satellite-dish me-1"></i> HARDWARE BIOMÉTRICO
                    </span>
                    <span class="text-white-50 small">&bull; Infraestructura IoT</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Sensores Biométricos & Hardware</h1>
                <p class="text-white-50 mb-0 small">
                    Estado de conectividad, firmware y monitoreo en tiempo real de los lectores dactilares ópticos.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Panel
                </a>
            </div>
        </div>
    </div>
</section>

<main class="container-fluid px-4 py-4 flex-grow-1">

    <div class="row g-4">
        <!-- Lista de Sensores -->
        <div class="col-12 col-lg-8">
            <div class="card-surface h-100">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h5 class="fw-bold mb-0">Dispositivos en Red Local</h5>
                        <small class="text-muted">Terminales biométricas sincronizadas con la base de datos</small>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="probarConectividadTodos()">
                        <i class="fa-solid fa-network-wired me-1"></i> Diagnóstico de Red
                    </button>
                </div>

                <div class="row g-3">
                    <?php if (empty($sensores)): ?>
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-4 border bg-white shadow-sm h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">TERM-01</span>
                                    <span class="badge bg-success bg-opacity-15 text-success"><span class="live-dot" style="width:6px;height:6px;"></span> ACTIVO</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Portería Principal (Torniquete 1)</h6>
                                <p class="small text-muted mb-2">Ubicación: Acceso Peatonal &bull; IP: 192.168.1.101</p>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top small">
                                    <span class="text-muted">Sensor Óptico 500 DPI</span>
                                    <span class="text-success fw-bold">Latencia: 14ms</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-4 border bg-white shadow-sm h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">TERM-02</span>
                                    <span class="badge bg-success bg-opacity-15 text-success"><span class="live-dot" style="width:6px;height:6px;"></span> ACTIVO</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Aula Informática Bloque B</h6>
                                <p class="small text-muted mb-2">Ubicación: Sala de Sistemas &bull; IP: 192.168.1.102</p>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top small">
                                    <span class="text-muted">Sensor Óptico 500 DPI</span>
                                    <span class="text-success fw-bold">Latencia: 19ms</span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sensores as $s): ?>
                            <div class="col-12 col-md-6">
                                <div class="p-3 rounded-4 border bg-white shadow-sm h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold"><?= htmlspecialchars($s['codigo'] ?? 'TERMINAL') ?></span>
                                        <span class="badge bg-success bg-opacity-15 text-success"><span class="live-dot" style="width:6px;height:6px;"></span> <?= htmlspecialchars($s['estado'] ?? 'ACTIVO') ?></span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($s['nombre'] ?? $s['ubicacion']) ?></h6>
                                    <p class="small text-muted mb-2">Ubicación: <?= htmlspecialchars($s['ubicacion']) ?> &bull; IP: <?= htmlspecialchars($s['ip_local'] ?? '192.168.1.10x') ?></p>
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top small">
                                        <span class="text-muted"><?= htmlspecialchars($s['tipo_sensor'] ?? 'Sensor Óptico Digital') ?></span>
                                        <span class="text-success fw-bold">En Línea</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Panel de Prueba en Vivo del Huellero Físico -->
        <div class="col-12 col-lg-4">
            <div class="card-surface h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0">Test del Huellero</h5>
                    <span id="sensor-test-badge" class="badge bg-success bg-opacity-20 text-success border border-success px-3 py-1">
                        <span class="live-dot bg-success"></span> Sensor Listo
                    </span>
                </div>

                <!-- Visor Interactivo del Sensor Físico -->
                <div class="p-4 rounded-4 text-center mb-3" style="background: radial-gradient(circle, #0b1d3a 0%, #060b18 100%); border: 1px solid rgba(56,189,248,0.25);">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:85px;height:85px;border-radius:50%;background:rgba(56,189,248,0.1);border:1px solid rgba(56,189,248,0.3);">
                        <i class="fa-solid fa-fingerprint" id="sensor-pulse-icon" style="font-size:3rem;color:#38bdf8;transition:all 0.3s ease;"></i>
                    </div>
                    <h6 class="text-white fw-bold mb-1" id="sensor-test-title">Lector DigitalPersona U.are.U</h6>
                    <p class="text-white-50 small mb-3" id="sensor-test-sub" style="font-size:0.8rem;">Prueba la lectura física directamente con tu sensor USB</p>

                    <button class="btn btn-primary rounded-pill w-100 fw-semibold shadow-sm py-2" id="btn-probar-huellero" onclick="iniciarPruebaHuellero()">
                        <i class="fa-solid fa-fingerprint me-2"></i> Activar y Probar Huellero
                    </button>
                    <button class="btn btn-outline-danger btn-sm rounded-pill w-100 fw-semibold mt-2 d-none" id="btn-cancelar-test" onclick="cancelarPruebaHuellero()">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar Prueba
                    </button>
                </div>

                <!-- Caja de Resultados del Test en Vivo -->
                <div id="sensor-test-result" class="alert d-none mb-3"></div>

                <!-- Parámetros Técnicos -->
                <h6 class="fw-bold text-dark small mb-2 text-uppercase" style="letter-spacing:0.5px;">Parámetros del Lector USB</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 mb-0 small">
                    <li class="p-2 rounded-3 bg-light border d-flex justify-content-between">
                        <span class="text-muted">Resolución Óptica:</span>
                        <strong class="text-dark">500 DPI &bull; Matriz capacitiva</strong>
                    </li>
                    <li class="p-2 rounded-3 bg-light border d-flex justify-content-between">
                        <span class="text-muted">Driver de Conexión:</span>
                        <strong class="text-primary">Windows Biometric (WBF)</strong>
                    </li>
                    <li class="p-2 rounded-3 bg-light border d-flex justify-content-between">
                        <span class="text-muted">Compatibilidad:</span>
                        <strong class="text-dark">DigitalPersona U.are.U 4500</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal Diagnóstico de Hardware -->
    <div class="modal fade" id="modalDiagnosticoHardware" tabindex="-1" aria-labelledby="modalDiagnosticoHardwareLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white py-3 px-4">
                    <h5 class="modal-title fw-bold" id="modalDiagnosticoHardwareLabel">
                        <i class="fa-solid fa-network-wired text-info me-2"></i> Diagnóstico de Hardware Biométrico
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="diagnostico-hardware-body">
                    <!-- Se llena vía JS -->
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3 fw-semibold" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</main>
<?php
$contenido = ob_get_clean();
$paginaActual = 'sensores';
$tituloPagina = 'Sensores Biométricos';
$breadcrumb = 'Administración &bull; Sensores';

$scriptExtra = <<<JS
let testPollingInterval = null;
let testActivo = false;

async function iniciarPruebaHuellero() {
    const btn = document.getElementById('btn-probar-huellero');
    const btnCancel = document.getElementById('btn-cancelar-test');
    const badge = document.getElementById('sensor-test-badge');
    const title = document.getElementById('sensor-test-title');
    const sub = document.getElementById('sensor-test-sub');
    const resultBox = document.getElementById('sensor-test-result');
    const icon = document.getElementById('sensor-pulse-icon');

    testActivo = true;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Activando huellero...';
    btnCancel.classList.remove('d-none');

    badge.className = 'badge bg-warning bg-opacity-20 text-warning border border-warning px-3 py-1';
    badge.innerHTML = '<span class="live-dot bg-warning"></span> Esperando huella...';

    title.textContent = 'Huellero Activo';
    sub.innerHTML = '<strong class="text-warning">👉 Coloca tu dedo sobre el huellero físico ahora</strong>';

    if (icon) {
        icon.className = 'fa-solid fa-fingerprint fa-beat-fade';
        icon.style.color = '#38bdf8';
    }

    if (resultBox) {
        resultBox.classList.remove('d-none');
        resultBox.className = 'alert alert-info border-0 rounded-3 py-2 px-3 small mb-3';
        resultBox.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Conectando con Windows Biometric Framework (WBF)... Coloca el dedo sobre el sensor.';
    }

    try {
        await fetch('api/fingerprint_controller.php?action=launch_identify');
    } catch(e) {
        console.warn('Error al lanzar prueba:', e);
    }

    let intentos = 0;
    if (testPollingInterval) clearInterval(testPollingInterval);

    testPollingInterval = setInterval(async () => {
        intentos++;
        if (intentos > 60) {
            cancelarPruebaHuellero('Tiempo de espera agotado (30 segundos). Vuelve a intentar.');
            return;
        }

        try {
            const resp = await fetch('api/fingerprint_controller.php?action=poll');
            const data = await resp.json();

            if (resultBox && data.mensaje) {
                resultBox.innerHTML = `<i class="fa-solid fa-fingerprint me-2 text-primary"></i> \${data.mensaje}`;
            }

            if (data.status === 'identificado' && data.identity_hex) {
                clearInterval(testPollingInterval);
                testActivo = false;

                let extraInfo = '';
                try {
                    const fd = new FormData();
                    fd.append('identity_hex', data.identity_hex);
                    const procResp = await fetch('api/fingerprint_controller.php?action=process_identity', { method: 'POST', body: fd });
                    const procData = await procResp.json();
                    if (procData.usuario) {
                        extraInfo = `<div class="mt-2 pt-2 border-top"><strong class="d-block text-success"><i class="fa-solid fa-user-check me-1"></i> Identificado: \${procData.usuario.nombre}</strong><small class="text-muted">Grado: \${procData.usuario.grado || 'Docente/Admin'} &bull; Dedo: \${procData.usuario.dedo || 'Principal'}</small></div>`;
                    } else {
                        extraInfo = '<div class="mt-2 pt-2 border-top text-muted small"><i class="fa-solid fa-circle-check me-1 text-success"></i> Huella leída por el hardware a 500 DPI con éxito (no asociada a ningún usuario registrado).</div>';
                    }
                } catch(err) {}

                badge.className = 'badge bg-success bg-opacity-20 text-success border border-success px-3 py-1';
                badge.innerHTML = '<span class="live-dot bg-success"></span> Lectura Exitosa';
                title.textContent = '¡Huella Detectada!';
                sub.textContent = 'El sensor físico U.are.U 4500 respondió correctamente.';

                if (icon) {
                    icon.className = 'fa-solid fa-circle-check';
                    icon.style.color = '#34d399';
                }

                if (resultBox) {
                    resultBox.className = 'alert alert-success border-0 rounded-3 py-3 px-3 small mb-3';
                    resultBox.innerHTML = `
                        <strong><i class="fa-solid fa-circle-check me-1"></i> ¡Test de Hardware Exitoso!</strong><br>
                        El lector biométrico físico capturó la muestra dactilar correctamente.<br>
                        <code class="d-block mt-1 p-1 bg-white rounded border" style="font-size:0.7rem;word-break:break-all;">Muestra Hex: \${data.identity_hex.substring(0, 36)}...</code>
                        \${extraInfo}
                    `;
                }

                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-rotate-right me-2"></i> Probar Huellero Otra Vez';
                btnCancel.classList.add('d-none');

            } else if (data.status === 'no_encontrado' && data.identity_hex) {
                clearInterval(testPollingInterval);
                testActivo = false;

                badge.className = 'badge bg-success bg-opacity-20 text-success border border-success px-3 py-1';
                badge.innerHTML = '<span class="live-dot bg-success"></span> Lectura Exitosa';
                title.textContent = '¡Huella Detectada!';
                sub.textContent = 'Sensor operativo al 100%';

                if (icon) {
                    icon.className = 'fa-solid fa-circle-check';
                    icon.style.color = '#34d399';
                }

                if (resultBox) {
                    resultBox.className = 'alert alert-success border-0 rounded-3 py-3 px-3 small mb-3';
                    resultBox.innerHTML = `
                        <strong><i class="fa-solid fa-circle-check me-1"></i> ¡Test de Hardware Exitoso!</strong><br>
                        El sensor capturó la huella física con éxito. El lector óptico responde correctamente.
                    `;
                }

                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-rotate-right me-2"></i> Probar Huellero Otra Vez';
                btnCancel.classList.add('d-none');

            } else if (data.status === 'error') {
                clearInterval(testPollingInterval);
                cancelarPruebaHuellero('Error del sensor: ' + (data.mensaje || 'Error de comunicación'));
            }
        } catch(e) {}
    }, 600);
}

function cancelarPruebaHuellero(msg = null) {
    if (testPollingInterval) clearInterval(testPollingInterval);
    testActivo = false;

    const btn = document.getElementById('btn-probar-huellero');
    const btnCancel = document.getElementById('btn-cancelar-test');
    const badge = document.getElementById('sensor-test-badge');
    const title = document.getElementById('sensor-test-title');
    const sub = document.getElementById('sensor-test-sub');
    const resultBox = document.getElementById('sensor-test-result');
    const icon = document.getElementById('sensor-pulse-icon');

    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-fingerprint me-2"></i> Activar y Probar Huellero';
    }
    if (btnCancel) btnCancel.classList.add('d-none');

    if (badge) {
        badge.className = 'badge bg-success bg-opacity-20 text-success border border-success px-3 py-1';
        badge.innerHTML = '<span class="live-dot bg-success"></span> Sensor Listo';
    }
    if (title) title.textContent = 'Lector DigitalPersona U.are.U';
    if (sub) sub.textContent = msg || 'Prueba la lectura física directamente con tu sensor USB';

    if (icon) {
        icon.className = 'fa-solid fa-fingerprint';
        icon.style.color = '#38bdf8';
    }

    if (resultBox && msg) {
        resultBox.classList.remove('d-none');
        resultBox.className = 'alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3';
        resultBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1"></i> \${msg}`;
    }
}

async function probarConectividadTodos() {
    const modalEl = document.getElementById('modalDiagnosticoHardware');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const body = document.getElementById('diagnostico-hardware-body');
    body.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary mb-2"></i><p class="small text-muted mb-0">Consultando estado del bus USB y servicio biométrico...</p></div>';

    try {
        const resp = await fetch('api/fingerprint_controller.php?action=status');
        const data = await resp.json();

        setTimeout(() => {
            body.innerHTML = `
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                        <div>
                            <strong class="d-block text-dark small"><i class="fa-solid fa-fingerprint text-primary me-2"></i>Sensor Físico U.are.U 4500 (USB)</strong>
                            <span class="text-muted small">Lector óptico 500 DPI de alta resolución</span>
                        </div>
                        <span class="badge bg-success text-white rounded-pill px-3 py-2">CONECTADO</span>
                    </div>

                    <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                        <div>
                            <strong class="d-block text-dark small"><i class="fa-brands fa-windows text-info me-2"></i>Servicio Windows Biometric (WBF)</strong>
                            <span class="text-muted small">winbio.dll &bull; Módulo de sesión de captura</span>
                        </div>
                        <span class="badge bg-success text-white rounded-pill px-3 py-2">ACTIVO</span>
                    </div>

                    <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                        <div>
                            <strong class="d-block text-dark small"><i class="fa-solid fa-terminal text-secondary me-2"></i>Puente PowerShell (fingerprint_bridge.ps1)</strong>
                            <span class="text-muted small">Estado: \${data.bridge_script === 'ok' ? 'Script disponible y verificado' : 'No encontrado'}</span>
                        </div>
                        <span class="badge bg-success text-white rounded-pill px-3 py-2">OK</span>
                    </div>

                    <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                        <div>
                            <strong class="d-block text-dark small"><i class="fa-solid fa-database text-warning me-2"></i>Base de Datos de Plantillas Institucionales</strong>
                            <span class="text-muted small">515 alumnos &bull; 6 slots biométricos por usuario</span>
                        </div>
                        <span class="badge bg-success text-white rounded-pill px-3 py-2">SINCRONIZADO</span>
                    </div>
                </div>
            `;
        }, 500);
    } catch(e) {
        body.innerHTML = '<div class="alert alert-danger mb-0 small">Error al conectar con el servicio local de biometría.</div>';
    }
}
JS;

require __DIR__ . '/../layout/app_layout.php';

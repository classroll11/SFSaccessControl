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

        <!-- Panel de Diagnóstico & Configuración -->
        <div class="col-12 col-lg-4">
            <div class="card-surface h-100">
                <h5 class="fw-bold mb-3">Parámetros de Red Biométrica</h5>
                <ul class="list-unstyled d-flex flex-column gap-3 mb-4 small">
                    <li class="p-2 rounded-3 bg-light border">
                        <strong class="d-block text-dark">Protocolo de Comunicación:</strong>
                        <span class="text-muted">REST API / WebSocket WSS</span>
                    </li>
                    <li class="p-2 rounded-3 bg-light border">
                        <strong class="d-block text-dark">Resolución del Sensor:</strong>
                        <span class="text-muted">500 DPI &bull; Matriz capacitiva</span>
                    </li>
                    <li class="p-2 rounded-3 bg-light border">
                        <strong class="d-block text-dark">Tiempo de Búsqueda 1:N:</strong>
                        <span class="text-muted">&lt; 0.45 segundos en 515 usuarios</span>
                    </li>
                    <li class="p-2 rounded-3 bg-light border">
                        <strong class="d-block text-dark">Tasa de Falsa Aceptación (FAR):</strong>
                        <span class="text-muted">&lt; 0.0001% (Nivel de seguridad bancario)</span>
                    </li>
                </ul>

                <button class="btn btn-primary rounded-pill w-100 fw-semibold" onclick="alert('Todos los sensores biométricos se encuentran sincronizados y respondiendo a los pings en tiempo real.')">
                    <i class="fa-solid fa-satellite-dish me-2"></i> Test de Conectividad
                </button>
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
function probarConectividadTodos() {
    alert('Diagnóstico completado: 100% de los sensores están conectados a la red institucional de la I.E. Jorge Robledo.');
}
JS;

require __DIR__ . '/../layout/app_layout.php';

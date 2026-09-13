<?php
/**
 * views/historial/index.php
 * Vista de Historial Completo de Accesos y Auditoría
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
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> AUDITORÍA GENERAL
                    </span>
                    <span class="text-white-50 small">&bull; Registro Histórico</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Historial de Accesos Institucionales</h1>
                <p class="text-white-50 mb-0 small">
                    Trazabilidad completa de entradas, salidas y novedades registradas por los sensores biométricos.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="index.php?c=reporte&a=exportarCsv" class="btn btn-success btn-sm rounded-pill px-3 fw-semibold">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar a CSV
                </a>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Panel Principal
                </a>
            </div>
        </div>
    </div>
</section>

<main class="container-fluid px-4 py-4 flex-grow-1">

    <div class="card-surface">
        <!-- Barra de filtros -->
        <div class="row g-2 align-items-center mb-4 pb-3 border-bottom">
            <div class="col-12 col-md-5">
                <div class="position-relative">
                    <input type="text" class="form-control form-control-sm ps-4 rounded-pill" id="buscador-historial" placeholder="Buscar por nombre, documento o grado..." onkeyup="filtrarHistorial()">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left:12px; top:50%; transform:translateY(-50%); font-size:0.75rem;"></i>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm rounded-pill" id="filtro-evento" onchange="filtrarHistorial()">
                    <option value="">Todos los movimientos</option>
                    <option value="ENTRADA">Solo Entradas</option>
                    <option value="SALIDA">Solo Salidas</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm rounded-pill" id="filtro-resultado" onchange="filtrarHistorial()">
                    <option value="">Todos los resultados</option>
                    <option value="PERMITIDO">Permitido</option>
                    <option value="RECHAZADO">Rechazado</option>
                </select>
            </div>

            <div class="col-12 col-md-2 text-md-end">
                <span class="badge bg-light text-dark border px-3 py-2 small" id="contador-filas">
                    <?= count($accesos) ?> Registros
                </span>
            </div>
        </div>

        <!-- Tabla de eventos -->
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="tabla-historial">
                <thead class="table-light">
                    <tr>
                        <th style="width:140px;">Fecha y Hora</th>
                        <th>Persona / Estudiante</th>
                        <th>Documento</th>
                        <th>Grado / Rol</th>
                        <th>Movimiento</th>
                        <th>Resultado</th>
                        <th>Observaciones / Sensor</th>
                    </tr>
                </thead>
                <tbody id="tabla-historial-body">
                    <?php if (empty($accesos)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fs-2 d-block mb-2"></i>
                                No hay eventos de acceso registrados en el sistema.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accesos as $acc): 
                            $nombre = $acc['nombre_usuario'] ?? $acc['nombre'] ?? 'Persona no identificada';
                            $documento = $acc['documento_usuario'] ?? $acc['documento'] ?? '—';
                            $grado = $acc['grado_usuario'] ?? $acc['grado'] ?? 'PERSONAL';
                            $estadoAcceso = $acc['estado_acceso'] ?? $acc['resultado'] ?? 'APROBADO';
                            $esPermitido = in_array(strtoupper($estadoAcceso), ['APROBADO', 'PERMITIDO']);
                            $esEntrada = ($acc['tipo_evento'] === 'ENTRADA');
                            $txt = strtolower(htmlspecialchars($nombre . ' ' . $documento . ' ' . $grado));
                        ?>
                            <tr class="fila-historial" data-texto="<?= $txt ?>" data-evento="<?= $acc['tipo_evento'] ?>" data-resultado="<?= $esPermitido ? 'PERMITIDO' : 'RECHAZADO' ?>">
                                <td class="small fw-semibold text-muted">
                                    <span class="d-block"><?= date('d/m/Y', strtotime($acc['fecha_hora'])) ?></span>
                                    <span class="text-dark"><?= date('h:i:s A', strtotime($acc['fecha_hora'])) ?></span>
                                </td>
                                <td>
                                    <strong class="d-block text-dark"><?= htmlspecialchars($nombre) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($acc['matricula'] ?? '') ?></small>
                                </td>
                                <td class="small"><?= htmlspecialchars($documento) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($grado) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $esEntrada ? 'bg-success bg-opacity-15 text-success' : 'bg-warning bg-opacity-15 text-warning' ?>">
                                        <i class="fa-solid <?= $esEntrada ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket' ?> me-1"></i>
                                        <?= htmlspecialchars($acc['tipo_evento']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $esPermitido ? 'bg-success' : 'bg-danger' ?> text-white">
                                        <i class="fa-solid <?= $esPermitido ? 'fa-check' : 'fa-xmark' ?> me-1"></i>
                                        <?= $esPermitido ? 'Permitido' : 'Denegado' ?>
                                    </span>
                                </td>
                                <td class="small text-muted" style="max-width:240px;">
                                    <?= htmlspecialchars($acc['observaciones'] ?? 'Lectura biométrica regular') ?>
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
$paginaActual = 'historial';
$tituloPagina = 'Historial de Accesos';
$breadcrumb = 'General &bull; Historial de Accesos';

$scriptExtra = <<<JS
function filtrarHistorial() {
    const q = document.getElementById('buscador-historial').value.toLowerCase().trim();
    const evento = document.getElementById('filtro-evento').value;
    const resultado = document.getElementById('filtro-resultado').value;

    let visibles = 0;
    document.querySelectorAll('.fila-historial').forEach(fila => {
        const texto = fila.dataset.texto;
        const filaEvento = fila.dataset.evento;
        const filaResultado = fila.dataset.resultado;

        const matchQ = !q || texto.includes(q);
        const matchEvento = !evento || filaEvento === evento;
        const matchResultado = !resultado || filaResultado === resultado;

        if (matchQ && matchEvento && matchResultado) {
            fila.style.display = '';
            visibles++;
        } else {
            fila.style.display = 'none';
        }
    });

    const contador = document.getElementById('contador-filas');
    if (contador) contador.textContent = `\${visibles} Registros`;
}
JS;

require __DIR__ . '/../layout/app_layout.php';

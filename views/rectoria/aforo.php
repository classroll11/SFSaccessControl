<?php
/**
 * views/aforo/index.php
 * Vista de Aforo Institucional y Distribución por Grados
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

$mapAforo = [];
if (!empty($estudiantesPorGrado)) {
    foreach ($estudiantesPorGrado as $g) {
        $mapAforo[$g['grado']] = (int)($g['total_dentro'] ?? 0);
    }
}

$capacidadTotal = 515;
$porcTotal = min(100, round(($estudiantesDentro / $capacidadTotal) * 100));

ob_start();
?>
<!-- Hero Banner -->
<section class="page-hero">
    <div class="container-fluid px-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill px-3 py-1 bg-warning text-dark fw-bold" style="font-size:0.75rem;">
                        <i class="fa-solid fa-chart-pie me-1"></i> SUPERVISIÓN & RECTORÍA
                    </span>
                    <span class="text-white-50 small">&bull; Ocupación Institucional</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Aforo Institucional por Grados</h1>
                <p class="text-white-50 mb-0 small">
                    Monitoreo en tiempo real de los estudiantes que se encuentran dentro de las instalaciones escolares.
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

    <!-- RESUMEN GLOBAL DE AFORO -->
    <div class="card-surface mb-4">
        <div class="row align-items-center g-4">
            <div class="col-12 col-md-4 text-center text-md-start border-end">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Aforo Actual en Plantel</span>
                <div class="d-flex align-items-baseline justify-content-center justify-content-md-start gap-2">
                    <h1 class="display-5 fw-bold text-success mb-0"><?= $estudiantesDentro ?></h1>
                    <span class="text-muted fs-5">/ <?= $capacidadTotal ?> est.</span>
                </div>
                <small class="text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i> Estudiantes presentes hoy</small>
            </div>

            <div class="col-12 col-md-5">
                <div class="d-flex justify-content-between small fw-bold mb-1">
                    <span>Ocupación Global de las Instalaciones</span>
                    <span class="text-primary"><?= $porcTotal ?>%</span>
                </div>
                <div class="progress" style="height: 12px; border-radius: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $porcTotal ?>%"></div>
                </div>
                <small class="text-muted mt-2 d-block">Basado en la lectura de entradas y salidas de la portería principal.</small>
            </div>

            <div class="col-12 col-md-3 text-center">
                <div class="p-3 bg-light rounded-3 border">
                    <span class="small text-muted d-block mb-1">Total de Grupos Escolares</span>
                    <strong class="fs-4 text-dark">16 Grados</strong>
                    <span class="text-muted small d-block">Desde 6°01 hasta 11°02</span>
                </div>
            </div>
        </div>
    </div>

    <!-- TARJETAS DE AFORO POR CADA GRADO -->
    <div class="card-surface">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h5 class="fw-bold mb-0">Distribución por Grados y Salones</h5>
                <small class="text-muted">Aforo desagregado por cada grupo institucional</small>
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="location.reload()">
                <i class="fa-solid fa-rotate me-1"></i> Actualizar Aforo
            </button>
        </div>

        <div class="row g-3">
            <?php 
            foreach ($gradosLista as $grado):
                $dentro = $mapAforo[$grado] ?? 0;
                $capacidadGrado = 35; // promedio por grupo
                $porc = min(100, round(($dentro / $capacidadGrado) * 100));

                $colorBg = 'bg-primary';
                if ($porc >= 80) $colorBg = 'bg-success';
                elseif ($porc >= 40) $colorBg = 'bg-primary';
                elseif ($porc > 0) $colorBg = 'bg-warning';
                else $colorBg = 'bg-secondary';
            ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="p-3 rounded-3 border bg-light h-100 hover-shadow transition">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-dark text-white fw-bold px-2 py-1">Grado <?= $grado ?></span>
                            <span class="fw-bold fs-6 text-dark"><?= $dentro ?> <small class="text-muted fw-normal">/ ~<?= $capacidadGrado ?></small></span>
                        </div>

                        <div class="progress mb-2" style="height: 7px; border-radius: 5px;">
                            <div class="progress-bar <?= $colorBg ?>" role="progressbar" style="width: <?= $porc ?>%"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                            <span class="text-muted">Presencia</span>
                            <strong class="text-dark"><?= $porc ?>%</strong>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</main>
<?php
$contenido = ob_get_clean();
$paginaActual = 'aforo';
$tituloPagina = 'Aforo por Grados';
$breadcrumb = 'Supervisión &bull; Aforo por Grados';

$scriptExtra = <<<JS
// Recargar automáticamente cada 20 segundos
setInterval(() => {
    // refresco silencioso si es necesario
}, 20000);
JS;

require __DIR__ . '/../layout/app_layout.php';

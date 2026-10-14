<?php
/**
 * views/admin/usuarios.php
 * Vista de Administración de Personal, Cuentas y Contraseñas
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
                        <i class="fa-solid fa-crown me-1"></i> ADMINISTRACIÓN
                    </span>
                    <span class="text-white-50 small">&bull; Control Total de Acceso</span>
                </div>
                <h1 class="mb-1 fw-bold text-white">Gestión de Usuarios y Claves</h1>
                <p class="text-white-50 mb-0 small">
                    Añade nuevos profesores, celadores o coordinadores y gestiona sus credenciales de acceso institucional.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 fw-semibold shadow-sm" onclick="abrirModalReiniciarAsistencias()">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reiniciar Asistencias
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" onclick="abrirModalCrearUsuario()">
                    <i class="fa-solid fa-user-plus me-2"></i> + Nuevo Usuario
                </button>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Panel
                </a>
            </div>
        </div>
    </div>
</section>

<main class="container-fluid px-4 py-4 flex-grow-1">

    <!-- Tarjeta informativa de política de contraseñas -->
    <div class="alert alert-info border-0 rounded-4 p-3 mb-4 shadow-sm d-flex align-items-start gap-3" style="background: rgba(37,99,235,0.08); border-left: 4px solid #2563eb !important;">
        <div class="fs-4 text-primary mt-1"><i class="fa-solid fa-shield-keyhole"></i></div>
        <div class="small">
            <strong class="text-dark d-block">Política de Contraseñas Predeterminadas (123456):</strong>
            <span>
                Al crear un nuevo usuario o utilizar la opción <strong>Reiniciar Contraseña</strong>, la clave se restablecerá a <code>123456</code>. El sistema obligará al usuario a definir una nueva clave privada en su primer inicio de sesión.
            </span>
        </div>
    </div>

    <!-- Tabla de Personal -->
    <div class="card-surface">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h5 class="fw-bold mb-0">Cuentas de Personal Institucional</h5>
                <small class="text-muted">Directivos, docentes, celadores y administradores autorizados</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control form-control-sm rounded-pill" id="buscador-personal" placeholder="Buscar por nombre, correo o documento..." oninput="filtrarPersonal()" onkeyup="filtrarPersonal()" style="max-width:280px;">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="tabla-personal">
                <thead class="table-light">
                    <tr>
                        <th>Usuario</th>
                        <th>Documento</th>
                        <th>Correo Institucional</th>
                        <th>Rol Asignado</th>
                        <th>Cargo / Grado</th>
                        <th>Estado</th>
                        <th>Contraseña</th>
                        <th class="text-end" style="min-width:180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-personal-body">
                    <?php if (empty($personalUsuarios)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No hay usuarios de personal registrados en el sistema.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($personalUsuarios as $u): ?>
                            <?php 
                            $uRol = $u['rol'];
                            $badgeColor = 'bg-secondary';
                            if ($uRol === 'ADMINISTRADOR') $badgeColor = 'bg-purple text-white';
                            elseif ($uRol === 'DOCENTE')   $badgeColor = 'bg-primary text-white';
                            elseif ($uRol === 'CELADOR')   $badgeColor = 'bg-success text-white';
                            elseif ($uRol === 'COORDINADOR')$badgeColor = 'bg-warning text-dark';
                            elseif ($uRol === 'RECTOR')    $badgeColor = 'bg-dark text-white';

                            $inicialesU = getUsuarioIniciales($u['nombre']);
                            $txt = strtolower(htmlspecialchars($u['nombre'] . ' ' . $u['documento'] . ' ' . $u['correo'] . ' ' . $u['rol']));
                            ?>
                            <tr class="fila-personal" data-texto="<?= $txt ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white small flex-shrink-0"
                                             style="width:34px;height:34px; background: #334155; font-size:0.75rem;">
                                            <?= $inicialesU ?>
                                        </div>
                                        <div>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($u['nombre']) ?></strong>
                                            <?php if ((int)$u['id'] === (int)$usuarioSesion['id']): ?>
                                                <span class="badge bg-light text-primary border" style="font-size:0.65rem;">Tu cuenta</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="small fw-semibold"><?= htmlspecialchars($u['documento']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($u['correo'] ?? '—') ?></td>
                                <td>
                                    <span class="badge <?= $badgeColor ?> px-2 py-1" style="<?= ($uRol === 'ADMINISTRADOR') ? 'background:#7c3aed !important;' : '' ?>">
                                        <?= htmlspecialchars($u['rol']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($u['grado'] ?: ($u['cargo'] ?? 'Personal')) ?></td>
                                <td>
                                    <?php if ($u['estado'] === 'ACTIVO'): ?>
                                        <span class="badge bg-success bg-opacity-15 text-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-15 text-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($u['debe_cambiar_password'])): ?>
                                        <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 small" title="El usuario debe cambiarla">
                                            <i class="fa-solid fa-key me-1"></i> Por defecto (123456)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success small">
                                            <i class="fa-solid fa-lock me-1"></i> Personalizada
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-outline-warning btn-sm rounded-pill px-2"
                                                title="Restablecer clave a 123456"
                                                onclick="reiniciarPasswordUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>')">
                                            <i class="fa-solid fa-rotate-left me-1"></i> Clave
                                        </button>

                                        <button class="btn btn-outline-primary btn-sm rounded-pill px-2"
                                                title="Editar datos"
                                                onclick="editarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>', '<?= htmlspecialchars($u['documento']) ?>', '<?= htmlspecialchars($u['correo']) ?>', '<?= htmlspecialchars($u['rol']) ?>', '<?= htmlspecialchars($u['grado'] ?: ($u['cargo'] ?? '')) ?>', '<?= htmlspecialchars($u['estado']) ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <?php if ((int)$u['id'] !== (int)$usuarioSesion['id']): ?>
                                            <button class="btn btn-outline-danger btn-sm rounded-pill px-2"
                                                    title="Eliminar del sistema"
                                                    onclick="eliminarUsuarioSistema(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL CREAR / EDITAR USUARIO -->
    <div class="modal fade" id="modalFormUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #07111f, #1e293b);">
                    <h5 class="modal-title fw-bold" id="formUsuarioTitulo">
                        <i class="fa-solid fa-user-plus me-2 text-primary"></i>+ Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-guardar-usuario" onsubmit="guardarUsuarioSubmit(event)">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id" id="user_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre Completo:</label>
                            <input type="text" class="form-control" name="nombre" id="user_nombre" required placeholder="Ej: Profe Alexander Arango">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">N° Documento (Cédula):</label>
                                <input type="text" class="form-control" name="documento" id="user_documento" required placeholder="Ej: 71234567">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Rol Institucional:</label>
                                <select class="form-select fw-semibold" name="rol" id="user_rol">
                                    <option value="DOCENTE">DOCENTE</option>
                                    <option value="CELADOR">CELADOR / PORTERÍA</option>
                                    <option value="COORDINADOR">COORDINADOR</option>
                                    <option value="RECTOR">RECTOR</option>
                                    <option value="ADMINISTRADOR">ADMINISTRADOR</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Correo Institucional:</label>
                            <input type="email" class="form-control" name="correo" id="user_correo" required placeholder="usuario@jorgerobledo.edu.co">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Grado o Asignatura Asignada:</label>
                                <input type="text" class="form-control" name="cargo" id="user_cargo" placeholder="Ej: 6°01 / Matemáticas">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Estado de Cuenta:</label>
                                <select class="form-select" name="estado" id="user_estado">
                                    <option value="ACTIVO">ACTIVO</option>
                                    <option value="INACTIVO">INACTIVO</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 border small text-muted">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                            Si es un nuevo usuario, se le asignará la clave <strong>123456</strong> y deberá cambiarla al iniciar sesión.
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btn-submit-usuario">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>
<?php
$contenido = ob_get_clean();
$paginaActual = 'usuarios';
$tituloPagina = 'Gestión de Usuarios y Claves';
$breadcrumb = 'Administración &bull; Usuarios y Claves';

$scriptExtra = <<<JS
function normalizarTextoPersonal(str) {
    return (str || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[°º\-]/g, ' ')
        .trim();
}

function filtrarPersonal() {
    const input = document.getElementById('buscador-personal');
    if (!input) return;
    const q = normalizarTextoPersonal(input.value);
    const terminos = q.split(/\s+/).filter(t => t.length > 0);

    document.querySelectorAll('.fila-personal').forEach(fila => {
        const rawTexto = fila.dataset.texto || fila.textContent;
        const texto = normalizarTextoPersonal(rawTexto);
        const match = terminos.length === 0 || terminos.every(term => texto.includes(term));
        fila.style.display = match ? '' : 'none';
    });
}

function abrirModalCrearUsuario() {
    document.getElementById('user_id').value = '';
    document.getElementById('user_nombre').value = '';
    document.getElementById('user_documento').value = '';
    document.getElementById('user_correo').value = '';
    document.getElementById('user_cargo').value = '';
    document.getElementById('user_rol').value = 'DOCENTE';
    document.getElementById('user_estado').value = 'ACTIVO';
    document.getElementById('formUsuarioTitulo').innerHTML = '<i class="fa-solid fa-user-plus me-2 text-primary"></i>+ Nuevo Usuario del Sistema';
    new bootstrap.Modal(document.getElementById('modalFormUsuario')).show();
}

function editarUsuario(id, nombre, doc, correo, rol, cargo, estado) {
    document.getElementById('user_id').value = id;
    document.getElementById('user_nombre').value = nombre;
    document.getElementById('user_documento').value = doc;
    document.getElementById('user_correo').value = correo;
    document.getElementById('user_rol').value = rol;
    document.getElementById('user_cargo').value = cargo;
    document.getElementById('user_estado').value = estado;
    document.getElementById('formUsuarioTitulo').innerHTML = '<i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Editar Usuario';
    new bootstrap.Modal(document.getElementById('modalFormUsuario')).show();
}

async function guardarUsuarioSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-usuario');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...';

    const fd = new FormData(document.getElementById('form-guardar-usuario'));
    try {
        const resp = await fetch('index.php?c=acceso&a=guardarUsuarioPersonal', { method: 'POST', body: fd });
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
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Usuario';
    }
}

async function reiniciarPasswordUsuario(id, nombre) {
    if (!confirm(`¿Deseas restablecer la contraseña de "\${nombre}" a la contraseña por defecto (123456)?\\n\\nEl usuario tendrá que cambiarla obligatoriamente en su próximo inicio de sesión.`)) {
        return;
    }

    const fd = new FormData();
    fd.append('usuario_id', id);

    try {
        const resp = await fetch('index.php?c=acceso&a=reiniciarPassword', { method: 'POST', body: fd });
        const data = await resp.json();
        alert(data.mensaje);
        location.reload();
    } catch (e) {
        alert('Error al restablecer la contraseña.');
    }
}

async function eliminarUsuarioSistema(id, nombre) {
    if (!confirm(`¿Estás seguro de eliminar al usuario "\${nombre}" del sistema? Esta acción no se puede deshacer.`)) return;

    const fd = new FormData();
    fd.append('usuario_id', id);

    try {
        const resp = await fetch('index.php?c=acceso&a=eliminarUsuario', { method: 'POST', body: fd });
        const data = await resp.json();
        alert(data.mensaje);
        location.reload();
    } catch (e) {
        alert('Error al eliminar usuario.');
    }
}
JS;

require __DIR__ . '/../layout/app_layout.php';

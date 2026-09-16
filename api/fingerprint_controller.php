<?php
/**
 * ============================================================================
 * SFS ACCESS CONTROL - API: fingerprint_controller.php
 * Endpoints para comunicar el dashboard con el lector U.are.U 4500
 * a través del puente PowerShell (fingerprint_bridge.ps1).
 *
 * Acciones disponibles (GET ?action=...):
 *   status          → Verificar que el bridge y el lector están activos
 *   poll            → Leer el último resultado del bridge (fingerprint_status.json)
 *   launch_enroll   → Lanzar enrolamiento en background
 *   launch_identify → Lanzar identificación en background
 *   save_enrolled   → Guardar huella enrolada en la BD
 *   process_identity→ Identificar huella WBF contra la BD y registrar acceso
 * ============================================================================
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();
require_once __DIR__ . '/../config/database.php';

define('BRIDGE_PS1',   __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_bridge.ps1');
define('STATUS_FILE',  __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_status.json');
define('BRIDGE_LOG',   __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_bridge.log');
define('BRIDGE_PID',   __DIR__ . DIRECTORY_SEPARATOR . 'bridge.pid');

$action = $_REQUEST['action'] ?? 'poll';

switch ($action) {

    // ── Verificar estado del lector ─────────────────────────────────────────
    case 'status':
        $data = [
            'bridge_script'  => file_exists(BRIDGE_PS1)  ? 'ok' : 'no encontrado',
            'status_file'    => file_exists(STATUS_FILE) ? 'ok' : 'pendiente',
            'ultimo_estado'  => file_exists(STATUS_FILE) ? readStatusFile() : null,
            'timestamp'      => date('Y-m-d H:i:s'),
        ];
        echo json_encode(['status' => 'info'] + $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        break;

    // ── Polling: leer resultado actual del bridge ───────────────────────────
    case 'poll':
        echo json_encode(readStatusFile(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        break;

    // ── Lanzar enrolamiento en background ───────────────────────────────────
    case 'launch_enroll':
        if (!isset($_SESSION['usuario_id'])) { respondError('NO_AUTH', 'Sesión requerida.', 401); break; }
        $input      = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $usuario_id = (int)($input['usuario_id'] ?? 0);
        $slot       = (int)($input['slot'] ?? 1);
        $dedo       = trim($input['dedo'] ?? '');

        if ($usuario_id <= 0 || $slot < 1 || $slot > 6 || empty($dedo)) {
            respondError('PARAM_INVALIDO', 'Se requiere usuario_id, slot (1-6) y dedo.', 400); break;
        }

        // Escribir estado "iniciando" inmediatamente
        writeStatus([
            'status'     => 'iniciando',
            'codigo'     => 'ENROLL_STARTING',
            'mensaje'    => "Iniciando enrolamiento de '$dedo' para slot $slot...",
            'usuario_id' => $usuario_id,
            'slot'       => $slot,
            'dedo'       => $dedo,
        ]);

        // Lanzar bridge en background (no bloqueante)
        $dedoEscaped = str_replace('"', '', $dedo); // sanear
        $cmd = 'powershell -ExecutionPolicy Bypass -NonInteractive -NoProfile -WindowStyle Hidden'
             . ' -File "' . BRIDGE_PS1 . '"'
             . ' -modo enroll'
             . ' -usuario_id ' . $usuario_id
             . ' -slot ' . $slot
             . ' -dedo "' . $dedoEscaped . '"'
             . ' -output_file "' . STATUS_FILE . '"';

        launchBackground($cmd);

        echo json_encode([
            'status'  => 'ok',
            'codigo'  => 'ENROLL_LANZADO',
            'mensaje' => "Enrolamiento iniciado. Coloque el dedo '$dedo' sobre el lector.",
        ], JSON_UNESCAPED_UNICODE);
        break;

    // ── Lanzar identificación en background ─────────────────────────────────
    case 'launch_identify':
        writeStatus([
            'status'  => 'esperando',
            'codigo'  => 'IDENTIFY_STARTING',
            'mensaje' => 'Coloque el dedo sobre el lector para identificar...',
        ]);

        $cmd = 'powershell -ExecutionPolicy Bypass -NonInteractive -NoProfile -WindowStyle Hidden'
             . ' -File "' . BRIDGE_PS1 . '"'
             . ' -modo identify'
             . ' -output_file "' . STATUS_FILE . '"';

        launchBackground($cmd);

        echo json_encode([
            'status'  => 'ok',
            'codigo'  => 'IDENTIFY_LANZADO',
            'mensaje' => 'Coloque el dedo sobre el lector para identificar.',
        ], JSON_UNESCAPED_UNICODE);
        break;

    // ── Guardar huella enrolada en BD ────────────────────────────────────────
    case 'save_enrolled':
        if (!isset($_SESSION['usuario_id'])) { respondError('NO_AUTH', 'Sesión requerida.', 401); break; }
        $input       = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $usuario_id  = (int)($input['usuario_id']  ?? 0);
        $slot        = (int)($input['slot']         ?? 0);
        $dedo        = trim($input['dedo']          ?? '');
        $identity_hex = trim($input['identity_hex'] ?? '');

        if ($usuario_id <= 0 || $slot < 1 || $slot > 6 || empty($dedo) || empty($identity_hex)) {
            respondError('PARAM_INVALIDO', 'Faltan campos requeridos.', 400); break;
        }

        try {
            $db  = Database::getConnection();
            // Verificar que no hay otra huella igual registrada para otro usuario
            $dup = $db->prepare("SELECT usuario_id FROM huellas_dactilares WHERE huella_template = :t AND usuario_id != :u LIMIT 1");
            $dup->execute([':t' => $identity_hex, ':u' => $usuario_id]);
            if ($dup->fetch()) {
                respondError('HUELLA_DUPLICADA', 'Esta huella ya está registrada para otro estudiante.', 409); break;
            }

            $stmt = $db->prepare("
                INSERT INTO huellas_dactilares (usuario_id, slot_numero, dedo, huella_template, creado_en, actualizado_en)
                VALUES (:uid, :slot, :dedo, :tmpl, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    dedo = VALUES(dedo),
                    huella_template = VALUES(huella_template),
                    actualizado_en = NOW()
            ");
            $ok = $stmt->execute([
                ':uid'  => $usuario_id,
                ':slot' => $slot,
                ':dedo' => $dedo,
                ':tmpl' => $identity_hex,
            ]);

            if ($ok) {
                // Si es el slot 1 o 2, actualizar también en la tabla usuarios como respaldo
                if ($slot === 1 || $slot === 2) {
                    $updUser = $db->prepare("UPDATE usuarios SET huella_template = :t WHERE id = :uid");
                    $updUser->execute([':t' => $identity_hex, ':uid' => $usuario_id]);
                }

                // Contar huellas actuales
                $cnt = $db->prepare("SELECT COUNT(*) FROM huellas_dactilares WHERE usuario_id = :uid");
                $cnt->execute([':uid' => $usuario_id]);
                $total = (int)$cnt->fetchColumn();

                echo json_encode([
                    'status'         => 'ok',
                    'codigo'         => 'GUARDADA',
                    'mensaje'        => "Huella '$dedo' (Slot $slot) guardada correctamente.",
                    'total_huellas'  => $total,
                ], JSON_UNESCAPED_UNICODE);
            } else {
                respondError('DB_ERROR', 'No se pudo guardar en la base de datos.', 500);
            }
        } catch (Exception $e) {
            respondError('DB_EXCEPTION', $e->getMessage(), 500);
        }
        break;

    // ── Procesar identity_hex e identificar persona ──────────────────────────
    case 'process_identity':
        $input        = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $identity_hex = trim($input['identity_hex'] ?? '');
        $dedo         = trim($input['dedo']         ?? '');

        if (empty($identity_hex)) { respondError('PARAM_INVALIDO', 'identity_hex es requerido.', 400); break; }

        try {
            $db = Database::getConnection();

            $stmt = $db->prepare("
                SELECT h.usuario_id, h.slot_numero, h.dedo,
                       u.id, u.nombre, u.documento, u.matricula, u.grado, u.rol, u.estado
                FROM huellas_dactilares h
                JOIN usuarios u ON u.id = h.usuario_id
                WHERE h.huella_template = :tmpl
                LIMIT 1
            ");
            $stmt->execute([':tmpl' => $identity_hex]);
            $reg = $stmt->fetch(PDO::FETCH_ASSOC);

            // Respaldo: si no está en huellas_dactilares, buscar en usuarios.huella_template
            if (!$reg) {
                $stmtU = $db->prepare("SELECT id, nombre, documento, matricula, grado, rol, estado, 1 AS slot_numero, 'Huella Principal' AS dedo FROM usuarios WHERE huella_template = :tmpl LIMIT 1");
                $stmtU->execute([':tmpl' => $identity_hex]);
                $reg = $stmtU->fetch(PDO::FETCH_ASSOC);
            }

            if (!$reg) {
                // Registrar intento no reconocido
                $db->prepare("INSERT INTO registros_acceso (usuario_id, tipo_evento, estado_acceso, observaciones, fecha_hora) VALUES (NULL,'ENTRADA','RECHAZADO','Huella WBF no encontrada en BD.',NOW())")->execute();
                echo json_encode(['status'=>'rechazado','codigo'=>'NO_REGISTRADO','mensaje'=>'Huella no encontrada en el sistema. Registre al estudiante primero.','acceso_permitido'=>false], JSON_UNESCAPED_UNICODE);
                break;
            }

            if ($reg['estado'] !== 'ACTIVO') {
                $db->prepare("INSERT INTO registros_acceso (usuario_id, tipo_evento, estado_acceso, observaciones, fecha_hora) VALUES (:uid,'ENTRADA','RECHAZADO',:obs,NOW())")
                   ->execute([':uid'=>$reg['id'], ':obs'=>"Usuario inactivo ({$reg['estado']})"]);
                echo json_encode(['status'=>'rechazado','codigo'=>'ESTADO_INACTIVO','mensaje'=>"Acceso denegado: {$reg['nombre']} ({$reg['estado']}).","acceso_permitido"=>false,'usuario'=>$reg], JSON_UNESCAPED_UNICODE);
                break;
            }

            $rolSesion = $_SESSION['usuario_rol'] ?? 'CELADOR';
            $dedoNombre = !empty($dedo) ? $dedo : ($reg['dedo'] ?? 'Huella');

            if ($rolSesion === 'DOCENTE') {
                // Registro de asistencia en clase
                $docenteId = (int)($_SESSION['usuario_id'] ?? 1);
                $gradoEst = $reg['grado'] ?: 'GENERAL';
                $fechaHoy = date('Y-m-d');

                $stmtAsist = $db->prepare("
                    INSERT INTO asistencias_clase (estudiante_id, docente_id, grado, fecha, estado_asistencia, materia, observaciones)
                    VALUES (:eid, :did, :grado, :fecha, 'PRESENTE', 'GENERAL', :obs)
                    ON DUPLICATE KEY UPDATE estado_asistencia='PRESENTE', observaciones=:obs2, actualizado_en=NOW()
                ");
                $stmtAsist->execute([
                    ':eid' => $reg['id'],
                    ':did' => $docenteId,
                    ':grado' => $gradoEst,
                    ':fecha' => $fechaHoy,
                    ':obs' => "Lectura biométrica en aula (Dedo: {$dedoNombre})",
                    ':obs2' => "Lectura biométrica en aula (Dedo: {$dedoNombre})"
                ]);

                // Registrar evento de log
                $db->prepare("INSERT INTO registros_acceso (usuario_id, tipo_evento, estado_acceso, observaciones, fecha_hora) VALUES (:uid,'ENTRADA','APROBADO',:obs,NOW())")
                   ->execute([':uid'=>$reg['id'], ':obs'=>"Asistencia a clase tomada por Docente. Dedo: {$dedoNombre} (Slot {$reg['slot_numero']})"]);

                echo json_encode([
                    'status'          => 'exito',
                    'codigo'          => 'ASISTENCIA_CLASE_REGISTRADA',
                    'tipo_registro'   => 'CLASE',
                    'mensaje'         => "¡Asistencia a clase registrada! {$reg['nombre']} ({$reg['grado']}) marcado como PRESENTE.",
                    'acceso_permitido' => true,
                    'tipo_evento'     => 'PRESENTE EN CLASE',
                    'usuario'         => [
                        'id'       => $reg['id'],
                        'nombre'   => $reg['nombre'],
                        'grado'    => $reg['grado'],
                        'rol'      => $reg['rol'],
                        'matricula'=> $reg['matricula'],
                        'dedo'     => $dedoNombre,
                        'slot'     => $reg['slot_numero'],
                    ],
                ], JSON_UNESCAPED_UNICODE);

            } else {
                // Autodetectar ENTRADA / SALIDA en Portería
                $last = $db->prepare("SELECT tipo_evento FROM registros_acceso WHERE usuario_id=:uid AND estado_acceso='APROBADO' AND DATE(fecha_hora)=CURDATE() ORDER BY fecha_hora DESC, id DESC LIMIT 1");
                $last->execute([':uid' => $reg['id']]);
                $lastRow    = $last->fetch(PDO::FETCH_ASSOC);
                $tipoEvento = ($lastRow && $lastRow['tipo_evento'] === 'ENTRADA') ? 'SALIDA' : 'ENTRADA';

                $obs = "Portería - $tipoEvento - Dedo: {$dedoNombre} (Slot {$reg['slot_numero']})";
                $db->prepare("INSERT INTO registros_acceso (usuario_id, tipo_evento, estado_acceso, observaciones, fecha_hora) VALUES (:uid,:tipo,'APROBADO',:obs,NOW())")
                   ->execute([':uid'=>$reg['id'],':tipo'=>$tipoEvento,':obs'=>$obs]);

                echo json_encode([
                    'status'          => 'exito',
                    'codigo'          => 'ACCESO_PORTERIA_REGISTRADO',
                    'tipo_registro'   => 'PORTERIA',
                    'mensaje'         => ($tipoEvento === 'ENTRADA' ? "¡Ingreso registrado! {$reg['nombre']} ({$reg['grado']}) — ENTRADA concedida." : "¡Salida registrada! {$reg['nombre']} ({$reg['grado']}) — SALIDA concedida."),
                    'acceso_permitido' => true,
                    'tipo_evento'     => $tipoEvento,
                    'usuario'         => [
                        'id'       => $reg['id'],
                        'nombre'   => $reg['nombre'],
                        'grado'    => $reg['grado'],
                        'rol'      => $reg['rol'],
                        'matricula'=> $reg['matricula'],
                        'dedo'     => $dedoNombre,
                        'slot'     => $reg['slot_numero'],
                    ],
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            respondError('DB_EXCEPTION', $e->getMessage(), 500);
        }
        break;

    default:
        respondError('ACCION_INVALIDA', "Acción '$action' no reconocida.", 400);
}

// ─── Helpers ────────────────────────────────────────────────────────────────

function readStatusFile(): array {
    if (!file_exists(STATUS_FILE)) {
        return ['status' => 'sin_datos', 'codigo' => 'ESPERANDO', 'mensaje' => 'El lector aún no ha enviado datos.'];
    }
    $raw  = file_get_contents(STATUS_FILE);
    // Eliminar BOM UTF-8 si existe
    $raw  = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $raw  = trim($raw);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : ['status' => 'error', 'codigo' => 'JSON_INVALIDO', 'raw' => substr($raw, 0, 200)];
}

function writeStatus(array $data): void {
    $data['timestamp'] = date('Y-m-d H:i:s');
    file_put_contents(STATUS_FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function launchBackground(string $cmd): void {
    // Windows: usar wmic para lanzar el proceso en la sesión interactiva del usuario.
    // Esto es necesario porque Apache corre como servicio y WBF requiere sesión activa.
    $wmicCmd = 'wmic process call create "' . addslashes($cmd) . '" > NUL 2>&1';
    $handle = popen($wmicCmd, 'r');
    if ($handle) pclose($handle);
    // Fallback con start /B si wmic falla
    // $handle = popen('start /B ' . $cmd . ' > NUL 2>&1', 'r');
    // if ($handle) pclose($handle);
}

function respondError(string $code, string $msg, int $http = 400): void {
    http_response_code($http);
    echo json_encode(['status' => 'error', 'codigo' => $code, 'mensaje' => $msg], JSON_UNESCAPED_UNICODE);
}

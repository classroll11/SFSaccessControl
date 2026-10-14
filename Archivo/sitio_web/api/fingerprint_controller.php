<?php
/**
 * ============================================================================
 * SFS ACCESS CONTROL - API: fingerprint_controller.php
 * Endpoints para comunicar el dashboard con el lector SecuGen Hamster Pro
 * a través del agente local .NET.
 *
 * Acciones disponibles (GET ?action=...):
 *   status          → Verificar que el bridge y el lector están activos
 *   poll            → Leer el último resultado del bridge (fingerprint_status.json)
 *   launch_enroll   → Lanzar enrolamiento en background
 *   launch_identify → Lanzar identificación en background
 *   save_enrolled   → Guardar huella enrolada en la BD
 *   process_identity→ Resolver una plantilla contra la BD y registrar acceso
 * ============================================================================
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();
require_once __DIR__ . '/../config/database.php';

define('BRIDGE_PS1',   __DIR__ . DIRECTORY_SEPARATOR . 'digitalpersona_bridge.ps1');
define('STATUS_FILE',  __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_status.json');
define('BRIDGE_LOG',   __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_bridge.log');
define('BRIDGE_PID',   __DIR__ . DIRECTORY_SEPARATOR . 'bridge.pid');
define('AGENT_JOB',    __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_agent_job.json');
define('AGENT_ACTIVE', __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_agent.active');
define('AGENT_QUEUE_LOCK', __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_agent_queue.lock');
define('AGENT_HEARTBEAT', __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_agent_heartbeat.json');

$action = $_REQUEST['action'] ?? 'poll';

switch ($action) {

    // ── Verificar estado del lector ─────────────────────────────────────────
    case 'status':
        if (!isset($_SESSION['usuario_id'])) { respondError('NO_AUTH', 'Sesión requerida.', 401); break; }
        $data = [
            'bridge_script'  => file_exists(BRIDGE_PS1)  ? 'ok' : 'no encontrado',
            'status_file'    => file_exists(STATUS_FILE) ? 'ok' : 'pendiente',
            'agent_online'   => isAgentOnline(),
            'agent_heartbeat'=> readAgentHeartbeat(),
            'ultimo_estado'  => file_exists(STATUS_FILE) ? readStatusFile() : null,
            'timestamp'      => date('Y-m-d H:i:s'),
        ];
        echo json_encode(['status' => 'info'] + $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        break;

    // ── Cancelar operación biométrica en curso ──────────────────────────────
    case 'cancel':
        if (!isset($_SESSION['usuario_id'])) { respondError('NO_AUTH', 'Sesión requerida.', 401); break; }
        cancelAgentJob();
        writeStatus([
            'status'  => 'cancelado',
            'codigo'  => 'CANCELADO',
            'mensaje' => 'Operación cancelada por el usuario.',
        ]);
        echo json_encode(['status' => 'ok', 'mensaje' => 'Solicitud de cancelación enviada al agente biométrico.'], JSON_UNESCAPED_UNICODE);
        break;

    // ── Encender o apagar el lector SecuGen ──────────────────────────────────
    case 'reader_on':
    case 'reader_off':
        if (!isset($_SESSION['usuario_id'])) { respondError('NO_AUTH', 'Sesión requerida.', 401); break; }
        $turningOn = $action === 'reader_on';
        try {
            queueAgentJob([
                'action' => $action,
                'userId' => 0,
                'slot' => 0,
                'finger' => '',
            ], null, [
                'status' => $turningOn ? 'encendiendo_lector' : 'apagando_lector',
                'codigo' => $turningOn ? 'READER_STARTING' : 'READER_STOPPING',
                'mensaje' => $turningOn ? 'Encendiendo el huellero SecuGen...' : 'Apagando el huellero SecuGen...',
            ]);
        } catch (RuntimeException $e) {
            respondError($e->getMessage(), agentQueueErrorMessage($e->getMessage()), 503);
            break;
        }

        echo json_encode([
            'status' => 'ok',
            'codigo' => $turningOn ? 'READER_ON_REQUESTED' : 'READER_OFF_REQUESTED',
            'mensaje' => $turningOn ? 'Solicitud para encender el huellero enviada.' : 'Solicitud para apagar el huellero enviada.',
        ], JSON_UNESCAPED_UNICODE);
        break;

    // ── Polling: leer resultado actual del bridge ───────────────────────────
    case 'poll':
        if (!isset($_SESSION['usuario_id'])) { respondError('NO_AUTH', 'Sesión requerida.', 401); break; }
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

        try {
            $db = Database::getConnection();
            $stmtUser = $db->prepare("SELECT rol FROM usuarios WHERE id = :uid");
            $stmtUser->execute([':uid' => $usuario_id]);
            $rol = $stmtUser->fetchColumn();
            if ($rol === false) {
                respondError('USUARIO_NO_ENCONTRADO', 'No se encontró el usuario indicado.', 404); break;
            }
            if ($rol === 'ESTUDIANTE') {
                $slot = 1;
            }
            $candidates = loadFingerprintCandidates(Database::getConnection());
        } catch (Exception $e) {
            respondError('DB_EXCEPTION', $e->getMessage(), 500);
            break;
        }

        try {
            queueAgentJob([
                'action' => 'enroll',
                'userId' => $usuario_id,
                'slot' => $slot,
                'finger' => $dedo,
            ], $candidates, [
                'status' => 'iniciando',
                'codigo' => 'ENROLL_STARTING',
                'mensaje' => "Solicitud de enrolamiento para '$dedo' (slot $slot). El huellero se activará automáticamente.",
                'usuario_id' => $usuario_id,
                'slot' => $slot,
                'dedo' => $dedo,
            ]);
        } catch (RuntimeException $e) {
            respondError($e->getMessage(), agentQueueErrorMessage($e->getMessage()), 503);
            break;
        }

        echo json_encode([
            'status'  => 'ok',
            'codigo'  => 'ENROLL_LANZADO',
            'mensaje' => "Enrolamiento iniciado. Coloque el dedo '$dedo' sobre el lector.",
        ], JSON_UNESCAPED_UNICODE);
        break;

    // ── Lanzar identificación en background ─────────────────────────────────
    case 'launch_identify':
        if (!isset($_SESSION['usuario_id'])) { respondError('NO_AUTH', 'Sesión requerida.', 401); break; }
        try {
            $candidates = loadFingerprintCandidates(Database::getConnection());
        } catch (Exception $e) {
            respondError('DB_EXCEPTION', $e->getMessage(), 500);
            break;
        }

        try {
            queueAgentJob([
                'action' => 'identify',
                'userId' => 0,
                'slot' => 0,
                'finger' => '',
            ], $candidates, [
                'status' => 'iniciando',
                'codigo' => 'IDENTIFY_STARTING',
                'mensaje' => 'Solicitud de identificación. El huellero se activará automáticamente.',
            ]);
        } catch (RuntimeException $e) {
            respondError($e->getMessage(), agentQueueErrorMessage($e->getMessage()), 503);
            break;
        }

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
        $slot        = (int)($input['slot']         ?? ($input['slot_numero'] ?? 0));
        $dedo        = trim($input['dedo']          ?? '');
        $identity_hex = trim($input['identity_hex'] ?? ($input['huella_template'] ?? ''));

        if ($usuario_id <= 0 || $slot < 1 || $slot > 6 || empty($dedo) || empty($identity_hex)) {
            respondError('PARAM_INVALIDO', 'Faltan campos requeridos.', 400); break;
        }

        $db = null;
        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            $stmtUser = $db->prepare("SELECT rol FROM usuarios WHERE id = :uid");
            $stmtUser->execute([':uid' => $usuario_id]);
            $rol = $stmtUser->fetchColumn();
            if ($rol === false) {
                $db->rollBack();
                respondError('USUARIO_NO_ENCONTRADO', 'No se encontró el usuario indicado.', 404); break;
            }
            if ($rol === 'ESTUDIANTE') {
                $slot = 1;
            }

            // Verificar que no hay otra huella igual registrada para otro usuario
            $dup = $db->prepare("SELECT usuario_id FROM huellas_dactilares WHERE huella_template = :t AND usuario_id != :u LIMIT 1");
            $dup->execute([':t' => $identity_hex, ':u' => $usuario_id]);
            if ($dup->fetch()) {
                $db->rollBack();
                respondError('HUELLA_DUPLICADA', 'Esta huella ya está registrada para otro estudiante.', 409); break;
            }

            if ($rol === 'ESTUDIANTE') {
                $deleteOtherSlots = $db->prepare(
                    "DELETE FROM huellas_dactilares WHERE usuario_id = :uid AND slot_numero <> 1"
                );
                $deleteOtherSlots->execute([':uid' => $usuario_id]);
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
                // Conservar el respaldo legacy sincronizado con la única plantilla del estudiante.
                if ($rol === 'ESTUDIANTE' || $slot === 1 || $slot === 2) {
                    $updUser = $db->prepare("UPDATE usuarios SET huella_template = :t WHERE id = :uid");
                    $updUser->execute([':t' => $identity_hex, ':uid' => $usuario_id]);
                }

                // Contar huellas actuales
                $cnt = $db->prepare("SELECT COUNT(*) FROM huellas_dactilares WHERE usuario_id = :uid");
                $cnt->execute([':uid' => $usuario_id]);
                $total = (int)$cnt->fetchColumn();
                $db->commit();

                $captureStatus = readStatusFile();
                if (($captureStatus['status'] ?? null) === 'enrolado'
                    && hash_equals((string)($captureStatus['identity_hex'] ?? ''), $identity_hex)) {
                    unset($captureStatus['identity_hex']);
                    writeStatus($captureStatus);
                }

                echo json_encode([
                    'status'         => 'ok',
                    'codigo'         => 'GUARDADA',
                    'mensaje'        => "Huella '$dedo' (Slot $slot) guardada correctamente.",
                    'total_huellas'  => $total,
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $db->rollBack();
                respondError('DB_ERROR', 'No se pudo guardar en la base de datos.', 500);
            }
        } catch (Exception $e) {
            if ($db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }
            respondError('DB_EXCEPTION', $e->getMessage(), 500);
        }
        break;

    // ── Solo identificar (buscar) sin registrar acceso ───────────────────────
    case 'identify_only':
        $input        = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $identity_hex = trim($input['identity_hex'] ?? '');
        $dedo         = trim($input['dedo']         ?? '');
        $matchedUserId = (int)($input['usuario_id'] ?? 0);
        $matchedSlot   = (int)($input['slot'] ?? 0);
        $matchedSource = (string)($input['origen'] ?? 'huellas');

            if (empty($identity_hex) && $matchedUserId <= 0) {
                respondError('PARAM_INVALIDO', 'Se requiere identity_hex o usuario_id.', 400); break;
            }

            try {
                $db = Database::getConnection();

                if ($matchedUserId > 0) {
                    if ($matchedSource === 'usuarios') {
                        $stmt = $db->prepare("
                            SELECT id, nombre, documento, matricula, grado, rol, estado,
                                   1 AS slot_numero, 'Huella Principal' AS dedo
                            FROM usuarios WHERE id = :uid LIMIT 1
                        ");
                        $stmt->execute([':uid' => $matchedUserId]);
                    } else {
                        if ($matchedSlot < 1 || $matchedSlot > 6) {
                            respondError('PARAM_INVALIDO', 'El slot de la coincidencia no es válido.', 400); break;
                        }
                        $stmt = $db->prepare("
                            SELECT h.usuario_id, h.slot_numero, h.dedo,
                                   u.id, u.nombre, u.documento, u.matricula, u.grado, u.rol, u.estado
                            FROM huellas_dactilares h
                            JOIN usuarios u ON u.id = h.usuario_id
                            WHERE h.usuario_id = :uid AND h.slot_numero = :slot
                            LIMIT 1
                        ");
                        $stmt->execute([':uid' => $matchedUserId, ':slot' => $matchedSlot]);
                    }
                    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
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

                    if (!$reg) {
                        $stmtU = $db->prepare("SELECT id, nombre, documento, matricula, grado, rol, estado, 1 AS slot_numero, 'Huella Principal' AS dedo FROM usuarios WHERE huella_template = :tmpl LIMIT 1");
                        $stmtU->execute([':tmpl' => $identity_hex]);
                        $reg = $stmtU->fetch(PDO::FETCH_ASSOC);
                    }
                }

            if (!$reg) {
                echo json_encode(['status' => 'no_encontrado', 'codigo' => 'NO_REGISTRADO', 'mensaje' => 'Huella no encontrada en el sistema.'], JSON_UNESCAPED_UNICODE);
                break;
            }

            // Determinar el próximo tipo de evento probable (para sugerir al portero)
            $last = $db->prepare("SELECT tipo_evento FROM registros_acceso WHERE usuario_id=:uid AND estado_acceso='APROBADO' AND DATE(fecha_hora)=CURDATE() ORDER BY fecha_hora DESC, id DESC LIMIT 1");
            $last->execute([':uid' => $reg['id']]);
            $lastRow     = $last->fetch(PDO::FETCH_ASSOC);
            $tipoSugerid = ($lastRow && $lastRow['tipo_evento'] === 'ENTRADA') ? 'SALIDA' : 'ENTRADA';

            echo json_encode([
                'status'          => 'encontrado',
                'codigo'          => 'PERSONA_IDENTIFICADA',
                'dedo'            => !empty($dedo) ? $dedo : ($reg['dedo'] ?? ''),
                'tipo_sugerido'   => $tipoSugerid,
                'usuario'         => [
                    'id'        => $reg['id'],
                    'nombre'    => $reg['nombre'],
                    'documento' => $reg['documento'],
                    'matricula' => $reg['matricula'],
                    'grado'     => $reg['grado'],
                    'rol'       => $reg['rol'],
                    'estado'    => $reg['estado'],
                    'dedo'      => !empty($dedo) ? $dedo : ($reg['dedo'] ?? 'Huella'),
                    'slot'      => $reg['slot_numero'],
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            respondError('DB_EXCEPTION', $e->getMessage(), 500);
        }
        break;

    // ── Confirmar y registrar acceso (portero confirmó manualmente) ──────────
    case 'confirm_access':
        $input       = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $usuario_id  = (int)($input['usuario_id']  ?? 0);
        $tipo_evento = strtoupper(trim($input['tipo_evento'] ?? ''));
        $dedo        = trim($input['dedo'] ?? 'Huella');

        if ($usuario_id <= 0 || !in_array($tipo_evento, ['ENTRADA', 'SALIDA'])) {
            respondError('PARAM_INVALIDO', 'usuario_id y tipo_evento (ENTRADA|SALIDA) son requeridos.', 400); break;
        }

        try {
            $db  = Database::getConnection();
            $stmtU = $db->prepare("SELECT id, nombre, documento, grado, rol, estado FROM usuarios WHERE id = :uid LIMIT 1");
            $stmtU->execute([':uid' => $usuario_id]);
            $reg = $stmtU->fetch(PDO::FETCH_ASSOC);

            if (!$reg) { respondError('NO_ENCONTRADO', 'Usuario no encontrado.', 404); break; }

            if ($reg['estado'] !== 'ACTIVO') {
                echo json_encode(['status' => 'rechazado', 'codigo' => 'ESTADO_INACTIVO', 'mensaje' => "Acceso denegado: {$reg['nombre']} ({$reg['estado']}).", 'acceso_permitido' => false], JSON_UNESCAPED_UNICODE);
                break;
            }

            $obs = "Portería confirmada - $tipo_evento - Dedo: $dedo (confirmado por portero)";
            $db->prepare("INSERT INTO registros_acceso (usuario_id, tipo_evento, estado_acceso, observaciones, fecha_hora) VALUES (:uid,:tipo,'APROBADO',:obs,NOW())")
               ->execute([':uid' => $usuario_id, ':tipo' => $tipo_evento, ':obs' => $obs]);

            $msg = $tipo_evento === 'ENTRADA'
                ? "¡Entrada confirmada! {$reg['nombre']} ({$reg['grado']}) — ENTRADA registrada."
                : "¡Salida confirmada! {$reg['nombre']} ({$reg['grado']}) — SALIDA registrada.";

            echo json_encode([
                'status'           => 'exito',
                'codigo'           => 'ACCESO_CONFIRMADO',
                'tipo_registro'    => 'PORTERIA',
                'tipo_evento'      => $tipo_evento,
                'acceso_permitido' => true,
                'mensaje'          => $msg,
                'usuario'          => $reg,
            ], JSON_UNESCAPED_UNICODE);
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
                $db->prepare("INSERT INTO registros_acceso (usuario_id, tipo_evento, estado_acceso, observaciones, fecha_hora) VALUES (NULL,'ENTRADA','RECHAZADO','Huella no encontrada en BD.',NOW())")->execute();
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

function readAgentHeartbeat(): ?array {
    if (!file_exists(AGENT_HEARTBEAT)) {
        return null;
    }
    $heartbeat = json_decode((string)file_get_contents(AGENT_HEARTBEAT), true);
    return is_array($heartbeat) ? $heartbeat : null;
}

function isAgentOnline(): bool {
    $heartbeat = readAgentHeartbeat();
    return is_array($heartbeat)
        && isset($heartbeat['updated_unix'])
        && (int)$heartbeat['updated_unix'] >= time() - 15;
}

function agentQueueErrorMessage(string $code): string {
    return match ($code) {
        'AGENT_BUSY' => 'El agente está ocupado. Espera a que termine la operación actual.',
        'AGENT_QUEUE_ERROR' => 'No se pudo crear la solicitud biométrica. Revisa los permisos de escritura de la carpeta api.',
        default => 'Abre el agente biométrico antes de iniciar una captura.',
    };
}

function loadFingerprintCandidates(PDO $db): array {
    $stmt = $db->query("
        SELECT usuario_id AS userId, slot_numero AS slot, dedo AS finger, huella_template AS template
               , 'huellas' AS source
        FROM huellas_dactilares
        WHERE huella_template IS NOT NULL AND huella_template <> ''
        UNION ALL
        SELECT u.id AS userId, 1 AS slot, 'Huella Principal' AS finger, u.huella_template AS template, 'usuarios' AS source
        FROM usuarios u
        WHERE u.huella_template IS NOT NULL AND u.huella_template <> ''
          AND NOT EXISTS (
              SELECT 1 FROM huellas_dactilares h
              WHERE h.usuario_id = u.id AND h.huella_template = u.huella_template
          )
    ");

    $candidates = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $candidate) {
        $template = trim((string)$candidate['template']);
        if ((int)$candidate['userId'] > 0 && $template !== '') {
            $candidates[] = [
                'userId' => (int)$candidate['userId'],
                'slot' => (int)$candidate['slot'],
                'finger' => (string)$candidate['finger'],
                'template' => $template,
                'source' => (string)$candidate['source'],
            ];
        }
    }
    return $candidates;
}

function queueAgentJob(array $job, ?array $candidates = null, array $initialStatus = []): void {
    if (!isAgentOnline()) {
        throw new RuntimeException('AGENT_OFFLINE');
    }

    $lock = fopen(AGENT_QUEUE_LOCK, 'c');
    if ($lock === false) {
        throw new RuntimeException('AGENT_QUEUE_ERROR');
    }
    if (!flock($lock, LOCK_EX)) {
        fclose($lock);
        throw new RuntimeException('AGENT_QUEUE_ERROR');
    }

    $candidatePath = null;
    $temporaryPath = null;
    try {
        if (file_exists(AGENT_JOB) || file_exists(AGENT_ACTIVE)) {
            throw new RuntimeException('AGENT_BUSY');
        }

        $job['jobId'] = bin2hex(random_bytes(16));
        if ($candidates !== null) {
            $candidateName = 'fingerprint_agent_candidates_' . $job['jobId'] . '.json';
            $candidatePath = __DIR__ . DIRECTORY_SEPARATOR . $candidateName;
            $candidateJson = json_encode($candidates, JSON_UNESCAPED_UNICODE);
            if ($candidateJson === false || file_put_contents($candidatePath, $candidateJson, LOCK_EX) === false) {
                throw new RuntimeException('AGENT_QUEUE_ERROR');
            }
            $job['candidatesFile'] = $candidateName;
        }

        $jobJson = json_encode($job, JSON_UNESCAPED_UNICODE);
        if ($jobJson === false) {
            throw new RuntimeException('AGENT_QUEUE_ERROR');
        }
        $temporaryPath = tempnam(__DIR__, 'fingerprint_job_');
        if ($temporaryPath === false || file_put_contents($temporaryPath, $jobJson, LOCK_EX) === false) {
            throw new RuntimeException('AGENT_QUEUE_ERROR');
        }
        if ($initialStatus !== []) {
            writeStatus($initialStatus);
        }
        if (!rename($temporaryPath, AGENT_JOB)) {
            throw new RuntimeException('AGENT_QUEUE_ERROR');
        }
        $temporaryPath = null;
    } finally {
        if ($temporaryPath !== null && file_exists($temporaryPath)) {
            unlink($temporaryPath);
        }
        if ($candidatePath !== null && file_exists(AGENT_JOB) === false && file_exists($candidatePath)) {
            unlink($candidatePath);
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function cancelAgentJob(): void {
    if (file_exists(AGENT_JOB)) {
        $job = json_decode((string)file_get_contents(AGENT_JOB), true);
        if (is_array($job) && !empty($job['candidatesFile'])) {
            $candidatePath = __DIR__ . DIRECTORY_SEPARATOR . basename((string)$job['candidatesFile']);
            if (file_exists($candidatePath)) {
                unlink($candidatePath);
            }
        }
        unlink(AGENT_JOB);
        return;
    }

    if (file_exists(AGENT_ACTIVE)) {
        $active = json_decode((string)file_get_contents(AGENT_ACTIVE), true);
        if (is_array($active) && !empty($active['job_id'])) {
            $cancelPath = __DIR__ . DIRECTORY_SEPARATOR . 'fingerprint_cancel_' . basename((string)$active['job_id']) . '.flag';
            file_put_contents($cancelPath, 'cancel', LOCK_EX);
        }
    }
}

function writeStatus(array $data): void {
    $data['timestamp'] = date('Y-m-d H:i:s');
    file_put_contents(STATUS_FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function launchBackground(string $cmd): void {
    // 1. Terminar procesos previos huérfanos de fingerprint_bridge para liberar el lector
    killPreviousBridge();

    // 2. Ejecutar proceso desacoplado en segundo plano en Windows
    $fullCmd = 'start "" /B ' . $cmd;
    $handle = popen($fullCmd, 'r');
    if ($handle) {
        pclose($handle);
    }
}

function killPreviousBridge(): void {
    // Leer PID guardado por el script de PowerShell
    if (file_exists(BRIDGE_PID)) {
        $pid = (int)trim(file_get_contents(BRIDGE_PID));
        if ($pid > 0) {
            @exec("taskkill /F /PID $pid 2>NUL");
        }
        @unlink(BRIDGE_PID);
    }
    // Respaldo: buscar por nombre de proceso
    @exec('taskkill /F /FI "IMAGENAME eq powershell.exe" /FI "WINDOWTITLE eq fingerprint_bridge*" 2>NUL');
}

function respondError(string $code, string $msg, int $http = 400): void {
    http_response_code($http);
    echo json_encode(['status' => 'error', 'codigo' => $code, 'mensaje' => $msg], JSON_UNESCAPED_UNICODE);
}

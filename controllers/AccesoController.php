<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: controllers/AccesoController.php
 * DESCRIPCIÓN: Controlador para la lógica de validación biométrica, registro de
 *              eventos de entrada/salida, toma de asistencias en aula,
 *              administración de usuarios, reinicio de claves y directivos.
 * ============================================================================
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/AccesoModel.php';

class AccesoController {
    private UsuarioModel $usuarioModel;
    private AccesoModel $accesoModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
        $this->accesoModel = new AccesoModel();
    }

    /**
     * Muestra la vista principal del Dashboard adaptada según el rol del usuario conectado.
     */
    public function index(): void {
        requireLogin();

        $rolActual = getRolActual();
        $estadisticas = $this->accesoModel->obtenerEstadisticasHoy();
        $accesosRecientes = $this->accesoModel->obtenerAccesosRecientes(15);
        $estudiantesDentro = $this->accesoModel->contarEstudiantesDentro();
        $estudiantesPorGrado = $this->accesoModel->obtenerEstudiantesDentroPorGrado();
        $estudiantesHuellas = $this->usuarioModel->obtenerEstudiantesConConteoHuellas();
        $sensores = $this->accesoModel->obtenerSensores();

        // Datos específicos para Administrador
        $personalUsuarios = esAdmin() ? $this->usuarioModel->obtenerPersonal() : [];

        // Datos específicos para Coordinador y Rector
        $faltasSupervision = ($rolActual === 'COORDINADOR' || $rolActual === 'RECTOR' || esAdmin()) 
            ? $this->accesoModel->obtenerFaltasSupervision() 
            : [];

        // Resumen institucional para Rector y Coordinación
        $resumenConsolidado = ($rolActual === 'RECTOR' || $rolActual === 'COORDINADOR' || esAdmin())
            ? $this->accesoModel->obtenerResumenConsolidado()
            : [];

        // Lista de grados disponibles para docentes y directivos
        $gradosDisponibles = [
            '6°01', '6°02', '6°03',
            '7°01', '7°02', '7°03',
            '8°01', '8°02', '8°03',
            '9°01', '9°02', '9°03',
            '10°01', '10°02',
            '11°01', '11°02'
        ];

        // Renderizar la vista pasando todas las variables
        require_once __DIR__ . '/../views/dashboard.php';
    }

    /**
     * Procesa la lectura biométrica (huella o documento), valida el permiso y registra el acceso.
     */
    public function procesarLectura(?string $huella = null, ?string $documento = null, ?string $tipoForzado = null): array {
        $huella = !empty($huella) ? trim($huella) : null;
        $documento = !empty($documento) ? trim($documento) : null;

        if (empty($huella) && empty($documento)) {
            return [
                'status' => 'error',
                'codigo' => 'DATOS_INSUFICIENTES',
                'mensaje' => 'Debe proporcionar la huella dactilar o el documento de identidad.',
                'acceso_permitido' => false
            ];
        }

        $usuario = null;
        if (!empty($huella)) {
            $usuario = $this->usuarioModel->obtenerPorHuella($huella);
        }

        if (!$usuario && !empty($documento)) {
            $usuario = $this->usuarioModel->obtenerPorDocumento($documento);
        }

        if (!$usuario) {
            $tipoEvento = $tipoForzado ? strtoupper($tipoForzado) : 'ENTRADA';
            $obs = 'Huella o documento no registrado en el sistema SFS.';
            $registroId = $this->accesoModel->registrarAcceso(null, $tipoEvento, 'RECHAZADO', $obs);

            return [
                'status' => 'rechazado',
                'codigo' => 'NO_ENCONTRADO',
                'mensaje' => 'Acceso denegado: Huella no reconocida. Persona no registrada en la institución.',
                'acceso_permitido' => false,
                'registro_id' => $registroId,
                'fecha_hora' => date('Y-m-d H:i:s')
            ];
        }

        $dedoIdentificado = $usuario['dedo_identificado'] ?? 'Huella Registrada';
        $slotIdentificado = $usuario['slot_identificado'] ?? null;

        if ($usuario['estado'] !== 'ACTIVO') {
            $tipoEvento = $tipoForzado ? strtoupper($tipoForzado) : 'ENTRADA';
            $obs = "Acceso denegado: Usuario en estado '" . $usuario['estado'] . "'. Dedo: {$dedoIdentificado}";
            $registroId = $this->accesoModel->registrarAcceso($usuario['id'], $tipoEvento, 'RECHAZADO', $obs);

            return [
                'status' => 'rechazado',
                'codigo' => 'ESTADO_INACTIVO',
                'mensaje' => "Acceso denegado: El usuario '{$usuario['nombre']}' se encuentra en estado '{$usuario['estado']}'.",
                'acceso_permitido' => false,
                'usuario' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'grado' => $usuario['grado'],
                    'estado' => $usuario['estado']
                ],
                'registro_id' => $registroId,
                'fecha_hora' => date('Y-m-d H:i:s')
            ];
        }

        $rolSesion = $_SESSION['usuario_rol'] ?? 'CELADOR';
        $esDocente = ($rolSesion === 'DOCENTE');
        $docenteId = (int)($_SESSION['usuario_id'] ?? 1);
        $fechaHoy = date('Y-m-d');
        $gradoEstudiante = $usuario['grado'] ?? 'GENERAL';

        // Determinar si es toma de asistencia en aula (Docente) o registro de portería (Celador / Admin)
        if ($esDocente) {
            // 1. REGISTRAR ASISTENCIA A CLASE
            $materiaDocente = $_POST['materia'] ?? ($_SESSION['usuario_grado'] ?? 'GENERAL');
            $asistenciasMap = [
                $usuario['id'] => 'PRESENTE'
            ];
            $this->accesoModel->guardarTomaAsistencia(
                $docenteId,
                $gradoEstudiante,
                $fechaHoy,
                $materiaDocente,
                $asistenciasMap,
                "Registro biométrico directo en aula (Dedo: {$dedoIdentificado})"
            );

            // Registrar también en el log de auditoría
            $obs = "Asistencia en aula validada por Docente. Dedo: {$dedoIdentificado}" . ($slotIdentificado ? " (Slot {$slotIdentificado})" : '');
            $registroId = $this->accesoModel->registrarAcceso($usuario['id'], 'ENTRADA', 'APROBADO', $obs);

            return [
                'status' => 'exito',
                'codigo' => 'ASISTENCIA_CLASE_REGISTRADA',
                'tipo_registro' => 'CLASE',
                'tipo_evento' => 'PRESENTE EN CLASE',
                'mensaje' => "¡Asistencia a clase registrada! {$usuario['nombre']} ({$gradoEstudiante}) marcado como PRESENTE.",
                'acceso_permitido' => true,
                'registro_id' => $registroId,
                'usuario' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'documento' => $usuario['documento'],
                    'matricula' => $usuario['matricula'] ?? $usuario['documento'],
                    'grado' => $usuario['grado'],
                    'rol' => $usuario['rol'],
                    'dedo' => $dedoIdentificado,
                    'slot' => $slotIdentificado
                ],
                'fecha_hora' => date('Y-m-d H:i:s')
            ];
        } else {
            // 2. REGISTRAR ACCESO EN PORTERÍA (CELADOR / ADMIN / RECTOR)
            if ($tipoForzado) {
                $tipoEvento = strtoupper($tipoForzado);
            } else {
                $ultimoEvento = $this->accesoModel->obtenerUltimoEventoUsuario($usuario['id']);
                $tipoEvento = ($ultimoEvento && $ultimoEvento['tipo_evento'] === 'ENTRADA') ? 'SALIDA' : 'ENTRADA';
            }

            $obs = "Acceso en portería validado por {$rolSesion}. Dedo: {$dedoIdentificado}" . ($slotIdentificado ? " (Slot {$slotIdentificado})" : '');
            $registroId = $this->accesoModel->registrarAcceso($usuario['id'], $tipoEvento, 'APROBADO', $obs);

            return [
                'status' => 'exito',
                'codigo' => 'ACCESO_PORTERIA_REGISTRADO',
                'tipo_registro' => 'PORTERIA',
                'tipo_evento' => $tipoEvento,
                'mensaje' => ($tipoEvento === 'ENTRADA') 
                    ? "¡Ingreso al colegio registrado! {$usuario['nombre']} ({$gradoEstudiante}) - ENTRADA concedida." 
                    : "¡Salida del colegio registrada! {$usuario['nombre']} ({$gradoEstudiante}) - SALIDA concedida.",
                'acceso_permitido' => true,
                'registro_id' => $registroId,
                'usuario' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'documento' => $usuario['documento'],
                    'matricula' => $usuario['matricula'] ?? $usuario['documento'],
                    'grado' => $usuario['grado'],
                    'rol' => $usuario['rol'],
                    'dedo' => $dedoIdentificado,
                    'slot' => $slotIdentificado
                ],
                'fecha_hora' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Endpoint AJAX para consultar las huellas registradas de un estudiante.
     */
    public function obtenerHuellasEstudiante(): void {
        requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $usuarioId = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
        if ($usuarioId <= 0) {
            echo json_encode(['status' => 'error', 'mensaje' => 'ID de usuario inválido.']);
            exit;
        }

        $usuario = $this->usuarioModel->obtenerPorId($usuarioId);
        if (!$usuario) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Estudiante no encontrado.']);
            exit;
        }

        $huellas = $this->usuarioModel->obtenerHuellasPorUsuario($usuarioId);

        echo json_encode([
            'status' => 'ok',
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'documento' => $usuario['documento'],
                'matricula' => $usuario['matricula'] ?? $usuario['documento'],
                'grado' => $usuario['grado']
            ],
            'huellas' => $huellas,
            'total_registradas' => count($huellas)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Endpoint AJAX para guardar una huella dactilar en un slot específico.
     */
    public function guardarHuellaEstudiante(): void {
        requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $usuarioId = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        $slotNumero = isset($_POST['slot_numero']) ? (int)$_POST['slot_numero'] : 0;
        $dedo = trim($_POST['dedo'] ?? '');
        $huellaTemplate = trim($_POST['huella_template'] ?? '');

        if ($usuarioId <= 0 || $slotNumero < 1 || $slotNumero > 6 || empty($dedo) || empty($huellaTemplate)) {
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Todos los campos son obligatorios y el slot debe estar entre 1 y 6.'
            ]);
            exit;
        }

        $exito = $this->usuarioModel->registrarHuellaSlot($usuarioId, $slotNumero, $dedo, $huellaTemplate);

        if ($exito) {
            echo json_encode([
                'status' => 'ok',
                'mensaje' => "Huella para {$dedo} (Slot {$slotNumero}) guardada exitosamente."
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'No se pudo guardar la huella dactilar. Verifique que no esté duplicada.'
            ]);
        }
        exit;
    }

    /**
     * Endpoint AJAX para eliminar una huella dactilar de un slot específico.
     */
    public function eliminarHuellaEstudiante(): void {
        requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $usuarioId = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        $slotNumero = isset($_POST['slot_numero']) ? (int)$_POST['slot_numero'] : 0;

        if ($usuarioId <= 0 || $slotNumero < 1 || $slotNumero > 6) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros inválidos.']);
            exit;
        }

        $exito = $this->usuarioModel->eliminarHuellaSlot($usuarioId, $slotNumero);

        if ($exito) {
            echo json_encode([
                'status' => 'ok',
                'mensaje' => "Huella del Slot {$slotNumero} eliminada correctamente."
            ]);
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo eliminar la huella.']);
        }
        exit;
    }

    /* =========================================================================
       MÓDULO: GESTIÓN DE USUARIOS Y CLAVES (ADMINISTRADOR)
       ========================================================================= */

    /**
     * Endpoint AJAX para crear o editar un usuario del personal.
     */
    public function guardarUsuarioPersonal(): void {
        requireLogin();
        if (!esAdmin()) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Acceso no autorizado. Solo el Administrador puede gestionar usuarios.']);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $documento = trim($_POST['documento'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $rol = trim($_POST['rol'] ?? 'DOCENTE');
        $cargo = trim($_POST['cargo'] ?? $rol);
        $estado = trim($_POST['estado'] ?? 'ACTIVO');

        if (empty($documento) || empty($nombre) || empty($correo)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Documento, Nombre y Correo son obligatorios.']);
            exit;
        }

        $rolesPermitidos = ['ADMINISTRADOR', 'DOCENTE', 'CELADOR', 'COORDINADOR', 'RECTOR'];
        if (!in_array($rol, $rolesPermitidos)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'El rol seleccionado no es válido.']);
            exit;
        }

        try {
            if ($id > 0) {
                // Actualizar usuario existente
                $exito = $this->usuarioModel->actualizarUsuarioPersonal($id, [
                    'documento' => $documento,
                    'nombre' => $nombre,
                    'correo' => $correo,
                    'rol' => $rol,
                    'cargo' => $cargo,
                    'estado' => $estado
                ]);
                echo json_encode([
                    'status' => $exito ? 'ok' : 'error',
                    'mensaje' => $exito ? 'Usuario actualizado correctamente.' : 'No se pudo actualizar el usuario.'
                ]);
            } else {
                // Crear nuevo usuario (Contraseña por defecto 123456 y debe cambiarla al ingresar)
                $nuevoId = $this->usuarioModel->crearUsuarioPersonal([
                    'documento' => $documento,
                    'nombre' => $nombre,
                    'correo' => $correo,
                    'rol' => $rol,
                    'cargo' => $cargo,
                    'password' => '123456',
                    'debe_cambiar_password' => 1,
                    'estado' => $estado
                ]);
                echo json_encode([
                    'status' => 'ok',
                    'mensaje' => "Usuario creado con éxito. Contraseña inicial por defecto: 123456 (Deberá cambiarla al ingresar).",
                    'id' => $nuevoId
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Endpoint AJAX para reiniciar la contraseña de un usuario a '123456'.
     */
    public function reiniciarPassword(): void {
        requireLogin();
        if (!esAdmin()) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado.']);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');

        $usuarioId = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        if ($usuarioId <= 0) {
            echo json_encode(['status' => 'error', 'mensaje' => 'ID de usuario inválido.']);
            exit;
        }

        $exito = $this->usuarioModel->reiniciarPasswordDefecto($usuarioId, '123456');

        if ($exito) {
            echo json_encode([
                'status' => 'ok',
                'mensaje' => 'Contraseña restablecida exitosamente a "123456". El usuario deberá cambiarla obligatoriamente en su próximo inicio de sesión.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo restablecer la contraseña.']);
        }
        exit;
    }

    /**
     * Endpoint AJAX para eliminar un usuario del sistema.
     */
    public function eliminarUsuario(): void {
        requireLogin();
        if (!esAdmin()) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado.']);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');

        $usuarioId = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        if ($usuarioId <= 0 || $usuarioId === (int)$_SESSION['usuario_id']) {
            echo json_encode(['status' => 'error', 'mensaje' => 'No puedes eliminar tu propia cuenta en sesión o el ID es inválido.']);
            exit;
        }

        $exito = $this->usuarioModel->eliminarUsuario($usuarioId);
        echo json_encode([
            'status' => $exito ? 'ok' : 'error',
            'mensaje' => $exito ? 'Usuario eliminado del sistema.' : 'Error al eliminar el usuario.'
        ]);
        exit;
    }

    /* =========================================================================
       MÓDULO: GESTIÓN DE ESTUDIANTES (ADMINISTRADOR)
       ========================================================================= */

    /**
     * Endpoint AJAX para crear o editar un estudiante.
     */
    public function guardarEstudiante(): void {
        requireLogin();
        if (!esAdmin()) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado.']);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $documento = trim($_POST['documento'] ?? '');
        $matricula = trim($_POST['matricula'] ?? $documento);
        $nombre = trim($_POST['nombre'] ?? '');
        $grado = trim($_POST['grado'] ?? '');
        $estado = trim($_POST['estado'] ?? 'ACTIVO');

        if (empty($documento) || empty($nombre) || empty($grado)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Documento, Nombre y Grado son obligatorios.']);
            exit;
        }

        try {
            if ($id > 0) {
                $exito = $this->usuarioModel->actualizarEstudiante($id, [
                    'documento' => $documento,
                    'matricula' => $matricula,
                    'nombre' => $nombre,
                    'grado' => $grado,
                    'estado' => $estado
                ]);
                echo json_encode([
                    'status' => $exito ? 'ok' : 'error',
                    'mensaje' => $exito ? 'Estudiante actualizado con éxito.' : 'No se pudo actualizar el estudiante.'
                ]);
            } else {
                $nuevoId = $this->usuarioModel->crearEstudiante([
                    'documento' => $documento,
                    'matricula' => $matricula,
                    'nombre' => $nombre,
                    'grado' => $grado,
                    'estado' => $estado
                ]);
                echo json_encode([
                    'status' => 'ok',
                    'mensaje' => 'Estudiante registrado con éxito en la base de datos institucional.',
                    'id' => $nuevoId
                ]);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /* =========================================================================
       MÓDULO: ASISTENCIA EN AULA (DOCENTE / PROFESOR)
       ========================================================================= */

    /**
     * Endpoint AJAX para consultar la lista de estudiantes de un grado y su asistencia de hoy.
     */
    public function obtenerAsistenciaClase(): void {
        requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $grado = trim($_GET['grado'] ?? '6°01');
        $fecha = trim($_GET['fecha'] ?? date('Y-m-d'));
        $materia = trim($_GET['materia'] ?? 'GENERAL');

        $alumnos = $this->accesoModel->obtenerAsistenciaClase($grado, $fecha, $materia);

        echo json_encode([
            'status' => 'ok',
            'grado' => $grado,
            'fecha' => $fecha,
            'materia' => $materia,
            'alumnos' => $alumnos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Endpoint AJAX para guardar la toma de asistencia en el aula por parte del Docente.
     */
    public function guardarTomaAsistencia(): void {
        requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $docenteId = (int)$_SESSION['usuario_id'];
        $grado = trim($_POST['grado'] ?? '');
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
        $materia = trim($_POST['materia'] ?? 'GENERAL');
        $asistenciasRaw = $_POST['asistencias'] ?? []; // JSON string o array

        if (is_string($asistenciasRaw)) {
            $asistencias = json_decode($asistenciasRaw, true) ?: [];
        } else {
            $asistencias = (array)$asistenciasRaw;
        }

        if (empty($grado) || empty($asistencias)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Debe seleccionar un grado y enviar las asistencias.']);
            exit;
        }

        $exito = $this->accesoModel->guardarTomaAsistencia($docenteId, $grado, $fecha, $materia, $asistencias);

        if ($exito) {
            echo json_encode([
                'status' => 'ok',
                'mensaje' => "¡Asistencia para el grado {$grado} ({$materia}) guardada exitosamente!"
            ]);
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'Error al guardar la planilla de asistencia.']);
        }
        exit;
    }

    /* =========================================================================
       MÓDULO: SUPERVISIÓN Y JUSTIFICACIÓN DE FALTAS (COORDINADOR / RECTOR)
       ========================================================================= */

    /**
     * Endpoint AJAX para justificar una falta de un estudiante.
     */
    public function justificarFalta(): void {
        requireLogin();
        if (!esCoordinador() && !esAdmin() && !esRector()) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado.']);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');

        $asistenciaId = isset($_POST['asistencia_id']) ? (int)$_POST['asistencia_id'] : 0;
        $motivo = trim($_POST['motivo'] ?? 'Justificado por Coordinación');

        if ($asistenciaId <= 0) {
            echo json_encode(['status' => 'error', 'mensaje' => 'ID de registro de asistencia inválido.']);
            exit;
        }

        $exito = $this->accesoModel->justificarFalta($asistenciaId, $motivo);

        if ($exito) {
            echo json_encode([
                'status' => 'ok',
                'mensaje' => 'Falta justificada exitosamente con registro en el historial institucional.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo registrar la justificación.']);
        }
        exit;
    }

    /**
     * Endpoint AJAX para retornar los accesos recientes y estadísticas en JSON (actualización en tiempo real).
     */
    public function obtenerHistorialJson(): void {
        header('Content-Type: application/json; charset=utf-8');
        
        $historial = $this->accesoModel->obtenerAccesosRecientes(20);
        $stats = $this->accesoModel->obtenerEstadisticasHoy();
        $porGrado = $this->accesoModel->obtenerEstudiantesDentroPorGrado();

        echo json_encode([
            'status' => 'ok',
            'estadisticas' => $stats,
            'por_grado' => $porGrado,
            'historial' => $historial
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Maneja el formulario de prueba manual desde la interfaz web.
     */
    public function simularManual(): void {
        header('Content-Type: application/json; charset=utf-8');
        
        $huella = $_POST['huella'] ?? null;
        $documento = $_POST['documento'] ?? null;
        $tipoForzado = $_POST['tipo_evento'] ?? null;

        $resultado = $this->procesarLectura($huella, $documento, $tipoForzado);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

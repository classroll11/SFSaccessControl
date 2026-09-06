<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: controllers/AccesoController.php
 * DESCRIPCIÓN: Controlador para la lógica de validación biométrica, registro de
 *              eventos de entrada/salida y renderizado del panel principal.
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
     * Muestra la vista principal del Dashboard con estadísticas e historial.
     */
    public function index(): void {
        requireLogin();
        $estadisticas = $this->accesoModel->obtenerEstadisticasHoy();
        $accesosRecientes = $this->accesoModel->obtenerAccesosRecientes(15);
        $estudiantesDentro = $this->accesoModel->contarEstudiantesDentro();
        $estudiantesPorGrado = $this->accesoModel->obtenerEstudiantesDentroPorGrado();
        $usuariosDisponibles = $this->usuarioModel->obtenerTodos();
        $estudiantesHuellas = $this->usuarioModel->obtenerEstudiantesConConteoHuellas();
        $sensores = $this->accesoModel->obtenerSensores();

        // Renderizar la vista pasando las variables
        require_once __DIR__ . '/../views/dashboard.php';
    }

    /**
     * Procesa la lectura biométrica (huella o documento), valida el permiso y registra el acceso.
     * Identifica automáticamente a qué estudiante y a cuál de sus 6 dedos pertenece la huella.
     *
     * @param string|null $huella Hash/template de la huella dactilar capturada.
     * @param string|null $documento Número de documento (opcional o de respaldo).
     * @param string|null $tipoForzado 'ENTRADA', 'SALIDA' o null para autodetección.
     * @return array Resultado estructurado del procesamiento.
     */
    public function procesarLectura(?string $huella = null, ?string $documento = null, ?string $tipoForzado = null): array {
        $huella = !empty($huella) ? trim($huella) : null;
        $documento = !empty($documento) ? trim($documento) : null;

        // 1. Validar que al menos uno de los identificadores fue provisto
        if (empty($huella) && empty($documento)) {
            return [
                'status' => 'error',
                'codigo' => 'DATOS_INSUFICIENTES',
                'mensaje' => 'Debe proporcionar la huella dactilar o el documento de identidad.',
                'acceso_permitido' => false
            ];
        }

        // 2. Buscar al usuario en la base de datos (detecta automáticamente quién es y qué dedo usó)
        $usuario = null;
        if (!empty($huella)) {
            $usuario = $this->usuarioModel->obtenerPorHuella($huella);
        }

        // Si no se encontró por huella y se envió documento, intentar por documento
        if (!$usuario && !empty($documento)) {
            $usuario = $this->usuarioModel->obtenerPorDocumento($documento);
        }

        // 3. Caso: Usuario no encontrado en el sistema
        if (!$usuario) {
            $tipoEvento = $tipoForzado ? strtoupper($tipoForzado) : 'ENTRADA';
            $obs = 'Huella o documento no registrado en el sistema SFS.';

            // Registrar intento fallido para auditoría de seguridad
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

        // Extraer dedo y slot detectados (si aplica)
        $dedoIdentificado = $usuario['dedo_identificado'] ?? 'Huella Registrada';
        $slotIdentificado = $usuario['slot_identificado'] ?? null;

        // 4. Caso: Usuario encontrado pero inactivo o suspendido
        if ($usuario['estado'] !== 'ACTIVO') {
            $tipoEvento = $tipoForzado ? strtoupper($tipoForzado) : 'ENTRADA';
            $obs = "Acceso denegado: Usuario en estado '" . $usuario['estado'] . "'. Dedo: {$dedoIdentificado}";

            $registroId = $this->accesoModel->registrarAcceso($usuario['id'], $tipoEvento, 'RECHAZADO', $obs);

            return [
                'status' => 'rechazado',
                'codigo' => 'ESTADO_INACTIVO',
                'mensaje' => "Acceso denegado: El usuario {$usuario['nombre']} se encuentra {$usuario['estado']}.",
                'acceso_permitido' => false,
                'usuario' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'documento' => $usuario['documento'],
                    'grado' => $usuario['grado'],
                    'rol' => $usuario['rol'],
                    'estado' => $usuario['estado'],
                    'dedo_identificado' => $dedoIdentificado,
                    'slot_identificado' => $slotIdentificado
                ],
                'registro_id' => $registroId,
                'fecha_hora' => date('Y-m-d H:i:s')
            ];
        }

        // 5. Caso: Usuario ACTIVO - Determinar tipo de evento (ENTRADA vs SALIDA)
        if (!empty($tipoForzado) && in_array(strtoupper($tipoForzado), ['ENTRADA', 'SALIDA'])) {
            $tipoEvento = strtoupper($tipoForzado);
        } else {
            // Autodetección: Consultar el último evento aprobado de hoy
            $ultimoEvento = $this->accesoModel->obtenerUltimoEventoUsuario($usuario['id']);
            if ($ultimoEvento && $ultimoEvento['tipo_evento'] === 'ENTRADA') {
                $tipoEvento = 'SALIDA';
            } else {
                $tipoEvento = 'ENTRADA';
            }
        }

        $obs = "Acceso concedido ({$tipoEvento}) - Dedo: {$dedoIdentificado}";
        if ($slotIdentificado) {
            $obs .= " (Slot {$slotIdentificado})";
        }

        // 6. Registrar acceso exitoso
        $registroId = $this->accesoModel->registrarAcceso($usuario['id'], $tipoEvento, 'APROBADO', $obs);

        return [
            'status' => 'exito',
            'codigo' => 'ACCESO_CONCEDIDO',
            'mensaje' => "¡Identificado! {$usuario['nombre']} ({$usuario['grado']}) - {$dedoIdentificado}. {$tipoEvento} registrada correctamente.",
            'acceso_permitido' => true,
            'tipo_evento' => $tipoEvento,
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'documento' => $usuario['documento'],
                'grado' => $usuario['grado'],
                'rol' => $usuario['rol'],
                'dedo_identificado' => $dedoIdentificado,
                'slot_identificado' => $slotIdentificado
            ],
            'registro_id' => $registroId,
            'fecha_hora' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Endpoint AJAX para consultar las huellas registradas de un estudiante (hasta 6 slots).
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
            'usuario' => $usuario,
            'huellas' => $huellas,
            'total_registradas' => count($huellas),
            'max_huellas' => 6
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Endpoint AJAX para guardar o actualizar una huella dactilar en un slot (1 al 6).
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
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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

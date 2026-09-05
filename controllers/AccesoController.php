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

        // Renderizar la vista pasando las variables
        require_once __DIR__ . '/../views/dashboard.php';
    }

    /**
     * Procesa la lectura biométrica (huella o documento), valida el permiso y registra el acceso.
     * Método central consumido tanto por la API REST como por la interfaz web.
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

        // 2. Buscar al usuario en la base de datos
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
                'mensaje' => 'Acceso denegado: Persona no registrada en la institución.',
                'acceso_permitido' => false,
                'registro_id' => $registroId,
                'fecha_hora' => date('Y-m-d H:i:s')
            ];
        }

        // 4. Caso: Usuario encontrado pero inactivo o suspendido
        if ($usuario['estado'] !== 'ACTIVO') {
            $tipoEvento = $tipoForzado ? strtoupper($tipoForzado) : 'ENTRADA';
            $obs = "Acceso denegado: Usuario en estado '" . $usuario['estado'] . "'.";

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
                    'estado' => $usuario['estado']
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

        $obs = "Acceso biométrico concedido ({$tipoEvento})";

        // 6. Registrar acceso exitoso
        $registroId = $this->accesoModel->registrarAcceso($usuario['id'], $tipoEvento, 'APROBADO', $obs);

        return [
            'status' => 'exito',
            'codigo' => 'ACCESO_CONCEDIDO',
            'mensaje' => "¡Bienvenido(a)! {$tipoEvento} registrada correctamente.",
            'acceso_permitido' => true,
            'tipo_evento' => $tipoEvento,
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'documento' => $usuario['documento'],
                'grado' => $usuario['grado'],
                'rol' => $usuario['rol']
            ],
            'registro_id' => $registroId,
            'fecha_hora' => date('Y-m-d H:i:s')
        ];
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

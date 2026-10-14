<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: api/registrar_huella.php
 * DESCRIPCIÓN: Endpoint API REST (JSON) para recibir lecturas de huella dactilar
 *              desde sensores biométricos físicos (Arduino, ESP32, Python, etc.)
 * ============================================================================
 */

// 1. Cabeceras HTTP y configuración CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Manejo de petición preliminar CORS OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Cargar dependencias requeridas
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AccesoController.php';

// 3. Capturar y decodificar el cuerpo de la petición (JSON o Form-Data)
$datos = [];
$inputRaw = file_get_contents('php://input');

if (!empty($inputRaw)) {
    $jsonDecoded = json_decode($inputRaw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
        $datos = $jsonDecoded;
    }
}

// Si no vino como JSON en el body, intentar con variables $_POST tradicionales
if (empty($datos)) {
    $datos = $_POST;
}

// 4. Extraer los campos enviados por el lector biométrico
$huella = $datos['huella'] ?? $datos['fingerprint'] ?? $datos['template'] ?? null;
$documento = $datos['documento'] ?? $datos['doc'] ?? null;
$tipoEvento = $datos['tipo_evento'] ?? $datos['tipo'] ?? null;

// Validación mínima de entrada
if (empty($huella) && empty($documento)) {
    http_response_code(400); // 400 Bad Request
    echo json_encode([
        'status' => 'error',
        'codigo' => 'PARAMETROS_FALTANTES',
        'mensaje' => 'Solicitud incompleta. Debe enviar el campo "huella" o "documento" vía JSON o POST.',
        'ejemplo_payload' => [
            'huella' => 'HUELLA_HEX_SAMPLE_001',
            'tipo_evento' => 'ENTRADA'
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 5. Procesar la validación y registro mediante el controlador
try {
    $controller = new AccesoController();
    $resultado = $controller->procesarLectura($huella, $documento, $tipoEvento);

    // Asignar el código de respuesta HTTP acorde al resultado de seguridad
    if ($resultado['status'] === 'exito') {
        http_response_code(200); // 200 OK - Acceso Aprobado
    } elseif ($resultado['codigo'] === 'ESTADO_INACTIVO') {
        http_response_code(403); // 403 Forbidden - Usuario no habilitado
    } elseif ($resultado['codigo'] === 'NO_ENCONTRADO') {
        http_response_code(404); // 404 Not Found - Huella no existe
    } else {
        http_response_code(400);
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500); // 500 Internal Server Error
    echo json_encode([
        'status' => 'error',
        'codigo' => 'ERROR_INTERNO_SERVIDOR',
        'mensaje' => 'Ocurrió un error inesperado al procesar la huella dactilar.',
        'error_detalle' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

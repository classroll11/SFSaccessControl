<?php
/**
 * ============================================================================
 * PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
 * ARCHIVO: config/database.php
 * DESCRIPCIÓN: Clase Singleton para la conexión segura a MySQL utilizando PDO.
 * ============================================================================
 */

class Database {
    // Parámetros de conexión predeterminados para entorno local (XAMPP / WampServer)
    private static string $host = 'localhost';
    private static string $dbName = 'sfs_access_control';
    private static string $user = 'root';
    private static string $password = '';
    private static string $charset = 'utf8mb4';
    private static int $port = 3306;

    // Instancia única de la conexión PDO (Patrón Singleton)
    private static ?PDO $conexion = null;

    /**
     * Constructor privado para evitar la instanciación directa.
     */
    private function __construct() {}

    /**
     * Obtiene la instancia activa de la conexión PDO.
     * 
     * @return PDO Instancia de la conexión a la base de datos.
     * @throws PDOException Si ocurre un error al conectar.
     */
    public static function getConnection(): PDO {
        if (self::$conexion === null) {
            try {
                // Configuración de la cadena DSN (Data Source Name)
                $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$dbName . ";charset=" . self::$charset;
                
                // Opciones avanzadas de PDO para seguridad y rendimiento
                $opciones = [
                    // Lanzar excepciones en caso de error para capturarlas en bloques try-catch
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // Retornar los resultados como arrays asociativos por defecto
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Desactivar la emulación de sentencias preparadas (máxima protección contra SQL Injection)
                    PDO::ATTR_EMULATE_PREPARES => false,
                    // Mantener la conexión persistente si es requerido (opcional)
                    PDO::ATTR_PERSISTENT => false
                ];

                self::$conexion = new PDO($dsn, self::$user, self::$password, $opciones);
            } catch (PDOException $e) {
                // Registro y manejo del error de conexión
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                die(json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error de conexión con la base de datos SFS Access. Verifique que MySQL esté iniciado en XAMPP y que la BD "sfs_access_control" exista.',
                    'detalle_tecnico' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE));
            }
        }

        return self::$conexion;
    }
}

<?php
/**
 * src/DB.php
 * Clase de conexión a base de datos - CORREGIDA
 * 
 * Cambios:
 * - Usa variables de .env en lugar de hardcoding
 * - Singleton seguro
 * - Manejo de errores mejorado
 */

require_once __DIR__ . '/../config/loader.php';

class DB {
    private static $conn = null;
    private static $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Obtener conexión singleton a la base de datos
     * 
     * @return PDO
     * @throws Exception Si falla la conexión
     */
    public static function conn(): PDO {
        if (self::$conn === null) {
            self::connect();
        }
        return self::$conn;
    }

    /**
     * Establecer conexión a BD
     * 
     * @return void
     * @throws Exception
     */
    private static function connect(): void {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            self::$conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ]);

            // Configurar zona horaria
            self::$conn->exec("SET SESSION time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw new Exception("Error de conexión BD: " . $e->getMessage());
            } else {
                throw new Exception("Error de conexión a base de datos");
            }
        }
    }

    /**
     * Desconectar BD (usa destructor normalmente)
     * 
     * @return void
     */
    public static function disconnect(): void {
        self::$conn = null;
    }

    /**
     * Verificar si hay conexión activa
     * 
     * @return bool
     */
    public static function isConnected(): bool {
        return self::$conn !== null;
    }

    /**
     * Obtener última inserción
     * 
     * @return string
     */
    public static function lastInsertId(): string {
        return self::$conn->lastInsertId();
    }

    /**
     * Destructor
     */
    public function __destruct() {
        self::disconnect();
    }
}
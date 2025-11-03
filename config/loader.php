<?php
/**
 * config/loader.php
 * Carga variables de entorno desde .env
 * 
 * USO: require_once __DIR__ . '/loader.php';
 */

if (file_exists(__DIR__ . '/../.env')) {
    $env_file = __DIR__ . '/../.env';
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parsear KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover comillas si existen
            if (preg_match('/^"(.*)"$/', $value, $m)) {
                $value = $m[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $m)) {
                $value = $m[1];
            }
            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Función helper para obtener variables de entorno
 * 
 * @param string $key Nombre de la variable
 * @param mixed $default Valor por defecto si no existe
 * @return mixed
 */
function env(string $key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

/**
 * Definir constantes globales desde .env
 */
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', 3306));
define('DB_NAME', env('DB_NAME', 'vestige_es'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

define('BASE_URL', env('BASE_URL', 'http://localhost/vestige_es/public'));
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/public/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('UPLOAD_MAX_SIZE', (int)env('UPLOAD_MAX_SIZE', 5242880));

define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');
define('APP_KEY', env('APP_KEY', 'change-me-in-production'));

define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'admin@vestige.local'));
define('CSRF_TOKEN_LENGTH', (int)env('CSRF_TOKEN_LENGTH', 32));
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 3600));

// Estados de publicación
define('ESTADO_ACTIVA', 0);
define('ESTADO_BORRADOR', 1);
define('ESTADO_PUBLICADA', 2);
define('ESTADO_PAUSADA', 3);
define('ESTADO_VENDIDA', 4);

// Roles
define('ROLE_ADMIN', 1);
define('ROLE_VENDEDOR', 2);
define('ROLE_COMPRADOR', 3);

// Tipos de alerta válidos
define('ALERT_TYPES', ['success', 'error', 'info', 'warning', 'danger']);

<?php
// admin/_common.php – Bootstrap del panel admin Vestige
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rutas base
$__ADMIN  = __DIR__;
$__PUBLIC = dirname($__ADMIN);
$__BASE   = dirname($__PUBLIC);

// Cargar configuración principal (esto ya carga loader.php que define todas las constantes)
$cfgPath = $__BASE . '/config/config.php';
if (is_file($cfgPath)) { 
    require_once $cfgPath; 
}

// Cargar loader.php directamente si config.php no existe
if (!defined('ADMIN_EMAIL')) {
    $loaderPath = $__BASE . '/config/loader.php';
    if (is_file($loaderPath)) {
        require_once $loaderPath;
    }
}

// Cargar clase DB
$dbCandidates = [
    $__BASE . '/src/DB.php',
    $__BASE . '/src/Db.php',
    $__BASE . '/src/db.php',
    $__BASE . '/src/DB.class.php',
];
foreach ($dbCandidates as $f) {
    if (is_file($f)) { 
        require_once $f; 
        break; 
    }
}

if (!class_exists('DB')) {
    http_response_code(500);
    die("Error: No se pudo cargar la clase DB desde /src/DB.php");
}

/**
 * Obtener conexión PDO
 */
function pdo(): PDO { 
    return DB::conn(); 
}

/**
 * Verificar si es admin por rol
 */
function is_admin_by_role(): bool {
    $adminRoleId = defined('ROLE_ADMIN') ? ROLE_ADMIN : 1;
    return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === $adminRoleId;
}

/**
 * Verificar si es admin por correo (opcional)
 */
function is_admin_by_email(): bool {
    if (!defined('ADMIN_EMAIL') || ADMIN_EMAIL === '') {
        return false;
    }
    return isset($_SESSION['usuario_correo']) && $_SESSION['usuario_correo'] === ADMIN_EMAIL;
}

/**
 * Verificar si puede acceder al panel admin
 */
function can_access_admin(): bool {
    return is_admin_by_email() || is_admin_by_role();
}

/**
 * Asegurar que existe token CSRF
 */
function ensure_csrf_token(): void {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
}

/**
 * Generar campo CSRF para formularios
 */
function csrf_field(): string {
    ensure_csrf_token();
    $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="'.$token.'">';
}

/**
 * Verificar token CSRF en peticiones POST
 */
function check_csrf(): void {
    ensure_csrf_token();
    
    $tokenValid = isset($_POST['csrf_token']) && 
                  hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    
    if (!$tokenValid) { 
        http_response_code(403); 
        die("Error: Token CSRF inválido o expirado."); 
    }
}

/**
 * Escapar HTML (helper)
 */
function h($str): string { 
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); 
}

/**
 * Formatear fecha para admin
 */
function format_date($date, $format = 'd/m/Y H:i'): string {
    if (empty($date)) return '-';
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}
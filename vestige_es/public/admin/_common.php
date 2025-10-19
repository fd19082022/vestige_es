<?php
// admin/_common.php — Bootstrap del panel admin Vestige
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rutas base: /public/admin -> /public -> / (raíz del proyecto)
$__ADMIN  = __DIR__;
$__PUBLIC = dirname($__ADMIN);
$__BASE   = dirname($__PUBLIC);

// === CONFIGURACIÓN DE ACCESO ===
// Modo por defecto: por rol (rol_id = 1 es admin).
// Opcional: bloquear por correo exacto. Si no quieres usar correo, deja cadena vacía.
define('ADMIN_EMAIL', ''); // e.g. 'tu_correo@vestige.com' para bloquear por correo
define('ADMIN_ROLE_ID', 1);

// Carga config si existe (no obligatorio, pero común en tu proyecto)
$cfgPath = $__BASE . '/config/config.php';
if (is_file($cfgPath)) { require_once $cfgPath; }

// Cargar clase DB (tu conexión está en /src/DB.php según nos indicaste)
$dbCandidates = [
    $__BASE . '/src/DB.php',
    $__BASE . '/src/Db.php',
    $__BASE . '/src/db.php',
    $__BASE . '/src/DB.class.php',
];
foreach ($dbCandidates as $f) {
    if (is_file($f)) { require_once $f; break; }
}
if (!class_exists('DB')) {
    http_response_code(500);
    die("No se pudo cargar la clase DB desde /src/DB.php");
}

function pdo(): PDO { return DB::conn(); }

function is_admin_by_role(): bool {
    return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === ADMIN_ROLE_ID;
}
function is_admin_by_email(): bool {
    if (ADMIN_EMAIL === '') return false;
    return isset($_SESSION['usuario_correo']) && $_SESSION['usuario_correo'] === ADMIN_EMAIL;
}
function can_access_admin(): bool {
    return is_admin_by_email() || is_admin_by_role();
}

function ensure_csrf_token(): void {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
}
function csrf_field(): string {
    ensure_csrf_token();
    $t = htmlspecialchars($_SESSION['csrf_token']);
    return '<input type="hidden" name="csrf_token" value="'.$t.'">';
}
function check_csrf(): void {
    ensure_csrf_token();
    $ok = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    if (!$ok) { http_response_code(400); echo "CSRF inválido."; exit; }
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

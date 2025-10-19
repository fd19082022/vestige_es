<?php
session_start();
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Usuario.php';
require_once __DIR__ . '/../config/database.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'danger');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$pass_actual = $_POST['password_actual'] ?? '';
$pass_nueva = $_POST['password_nueva'] ?? '';
$pass_conf = $_POST['password_confirmar'] ?? '';

$errores = [];

if ($nombre === '') {
    $errores[] = 'El nombre no puede estar vacío.';
}

$cambiar_pass = ($pass_actual !== '' || $pass_nueva !== '' || $pass_conf !== '');

if ($cambiar_pass) {
    if ($pass_nueva === '' || $pass_conf === '') {
        $errores[] = 'Debes escribir y confirmar la nueva contraseña.';
    } elseif ($pass_nueva !== $pass_conf) {
        $errores[] = 'La confirmación de contraseña no coincide.';
    } elseif (strlen($pass_nueva) < 6) {
        $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        // Verificar contraseña actual 
        $pdo = db_conectar();
        $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($pass_actual, $row['password_hash'])) {
            $errores[] = 'La contraseña actual es incorrecta.';
        }
    }
}

if ($errores) {
    foreach ($errores as $e) {
        Helper::flash_mensaje($e, 'danger');
    }
    header('Location: ' . BASE_URL . '/perfil.php');
    exit;
}

// Construir datos para actualizar
$data = ['nombre' => $nombre];
if ($cambiar_pass) {
    $data['password'] = $pass_nueva;
}

// Ejecutar actualización
$ok = Usuario::actualizar($usuarioId, $data);

if ($ok) {
    $_SESSION['usuario_nombre'] = $nombre;
    Helper::flash_mensaje('Perfil actualizado correctamente.', 'success');
} else {
    Helper::flash_mensaje('No se realizaron cambios.', 'warning');
}

header('Location: ' . BASE_URL . '/perfil.php');
exit;

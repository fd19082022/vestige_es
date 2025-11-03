<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Favorito.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Helper::flash_mensaje('Solicitud inválida.', 'error');
    Helper::redir(BASE_URL . '/favoritos.php');
}

// CSRF bÃ¡sico: verificar token
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    Helper::flash_mensaje('Token inválido.', 'error');
    Helper::redir($_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/favoritos.php'));
}

$publicacion_id = isset($_POST['publicacion_id']) ? (int)$_POST['publicacion_id'] : 0;
if ($publicacion_id <= 0) {
    Helper::flash_mensaje('Publicación inválida.', 'error');
    Helper::redir(BASE_URL . '/favoritos.php');
}

try {
    $ok = Favorito::alternar((int)$_SESSION['usuario_id'], $publicacion_id);
    Helper::flash_mensaje($ok ? 'Favorito actualizado.' : 'No se pudo actualizar favorito.', $ok ? 'success' : 'error');
} catch (Throwable $e) {
    error_log('Favorito toggle error: ' . $e->getMessage());
    Helper::flash_mensaje('Ocurrió un error al actualizar favorito.', 'error');
}

$ref = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/favoritos.php');
header('Location: ' . $ref);
exit;
<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión para chatear.', 'error');
    Helper::redir(BASE_URL . '/login.php');
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$yo = (int)($_SESSION['usuario_id'] ?? 0);

if ($method === 'POST') {
    // Validación CSRF usando Helper
    $token_enviado = $_POST['csrf'] ?? '';
    
    if (!Helper::validateCsrf($token_enviado)) {
        Helper::flash_mensaje('Token inválido. Recarga la página e intenta de nuevo.', 'error');
        $publicacion_id = (int)($_POST['publicacion_id'] ?? 0);
        if ($publicacion_id > 0) {
            Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
        } else {
            Helper::redir(BASE_URL . '/explorar.php');
        }
        exit;
    }
    
    $publicacion_id = (int)($_POST['publicacion_id'] ?? 0);
    $mensaje        = trim($_POST['mensaje'] ?? '');
} else {
    $publicacion_id = (int)($_GET['publicacion_id'] ?? 0);
    $mensaje        = '';
}

if ($publicacion_id <= 0) {
    Helper::flash_mensaje('Publicación inválida.', 'error');
    Helper::redir(BASE_URL . '/explorar.php');
    exit;
}

try {
    $pdo = DB::conn();

    // Obtener vendedor de la publicación
    $st = $pdo->prepare("SELECT vendedor_id FROM publicaciones WHERE id = ? LIMIT 1");
    $st->execute([$publicacion_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        Helper::flash_mensaje('No se encontró la publicación.', 'error');
        Helper::redir(BASE_URL . '/explorar.php');
        exit;
    }
    
    $vendedor_id = (int)$row['vendedor_id'];
    
    if ($yo === $vendedor_id) {
        Helper::flash_mensaje('No puedes iniciar conversación contigo mismo.', 'error');
        Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
        exit;
    }

    $comprador_id = $yo;

    $pdo->beginTransaction();

    // Verificar si ya existe conversación
    $sel = $pdo->prepare("
        SELECT id FROM conversaciones
        WHERE comprador_id = ? AND vendedor_id = ? AND publicacion_id = ?
        LIMIT 1
    ");
    $sel->execute([$comprador_id, $vendedor_id, $publicacion_id]);
    $conv_id = (int)($sel->fetchColumn() ?: 0);

    // Crear conversación si no existe
    if ($conv_id === 0) {
        $ins = $pdo->prepare("
            INSERT INTO conversaciones (comprador_id, vendedor_id, publicacion_id)
            VALUES (?, ?, ?)
        ");
        $ins->execute([$comprador_id, $vendedor_id, $publicacion_id]);
        $conv_id = (int)$pdo->lastInsertId();
    }

    // Insertar mensaje si viene del POST
    if ($method === 'POST' && $mensaje !== '') {
        $destinatario_id = $vendedor_id; // El comprador siempre envía al vendedor
        $insMsg = $pdo->prepare("
            INSERT INTO mensajes
                (conversacion_id, emisor_id, destinatario_id, publicacion_id, contenido, leido)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $insMsg->execute([$conv_id, $yo, $destinatario_id, $publicacion_id, $mensaje]);
    }

    $pdo->commit();

    Helper::redir(BASE_URL . '/chat.php?id=' . $conv_id);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error al iniciar chat: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    Helper::flash_mensaje('No se pudo iniciar el chat. Error: ' . $e->getMessage(), 'error');
    Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
    exit;
}
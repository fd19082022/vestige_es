<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Helper::flash_mensaje('Solicitud inválida.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

// Validación CSRF usando Helper
$token_enviado = $_POST['csrf'] ?? '';

if (!Helper::validateCsrf($token_enviado)) {
    Helper::flash_mensaje('Token inválido. Recarga la página e intenta de nuevo.', 'error');
    $conversacion_id = (int)($_POST['conversacion_id'] ?? 0);
    if ($conversacion_id > 0) {
        Helper::redir(BASE_URL . '/chat.php?id=' . $conversacion_id);
    } else {
        Helper::redir(BASE_URL . '/index.php');
    }
    exit;
}

$yo = (int)($_SESSION['usuario_id'] ?? 0);
$conversacion_id = (int)($_POST['conversacion_id'] ?? 0);
$mensaje = trim($_POST['mensaje'] ?? '');

if ($conversacion_id <= 0) {
    Helper::flash_mensaje('Conversación inválida.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

if ($mensaje === '') {
    Helper::flash_mensaje('El mensaje no puede estar vacío.', 'error');
    Helper::redir(BASE_URL . '/chat.php?id=' . $conversacion_id);
    exit;
}

try {
    $pdo = DB::conn();

    $st = $pdo->prepare("SELECT comprador_id, vendedor_id, publicacion_id FROM conversaciones WHERE id = ?");
    $st->execute([$conversacion_id]);
    $conv = $st->fetch(PDO::FETCH_ASSOC);
    
    if (!$conv) {
        Helper::flash_mensaje('Conversación no válida.', 'error');
        Helper::redir(BASE_URL . '/index.php');
        exit;
    }

    $comprador_id = (int)$conv['comprador_id'];
    $vendedor_id  = (int)$conv['vendedor_id'];
    
    if ($yo !== $comprador_id && $yo !== $vendedor_id) {
        Helper::flash_mensaje('Acceso denegado a esta conversación.', 'error');
        Helper::redir(BASE_URL . '/index.php');
        exit;
    }

    $destinatario_id = ($yo === $comprador_id) ? $vendedor_id : $comprador_id;
    $publicacion_id  = (int)$conv['publicacion_id'];

    $st = $pdo->prepare("
        INSERT INTO mensajes
            (conversacion_id, emisor_id, destinatario_id, publicacion_id, contenido, leido)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $st->execute([$conversacion_id, $yo, $destinatario_id, $publicacion_id, $mensaje]);

    // Redirigir de vuelta al chat
    Helper::redir(BASE_URL . '/chat.php?id=' . $conversacion_id);
    exit;

} catch (Throwable $e) {
    error_log('Error al enviar mensaje: ' . $e->getMessage());
    Helper::flash_mensaje('Error al enviar el mensaje. Por favor, intenta de nuevo.', 'error');
    Helper::redir(BASE_URL . '/chat.php?id=' . $conversacion_id);
    exit;
}
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
    if (!Helper::csrf_validar($_POST['csrf'] ?? '')) {
        Helper::flash_mensaje('CSRF inválido.', 'error');
        Helper::redir(BASE_URL . '/index.php');
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
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

try {
    $pdo = DB::conn();

    $st = $pdo->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
    $st->execute([$publicacion_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        Helper::flash_mensaje('No se encontró la publicación.', 'error');
        Helper::redir(BASE_URL . '/index.php');
        exit;
    }
    $vendedor_id = (int)$row['usuario_id'];
    if ($yo === $vendedor_id) {
        Helper::flash_mensaje('No puedes iniciar conversación contigo mismo.', 'error');
        Helper::redir(BASE_URL . '/detalle.php?id=' . $publicacion_id);
        exit;
    }

    $comprador_id = $yo;

    $pdo->beginTransaction();

    $sel = $pdo->prepare("
        SELECT id FROM conversaciones
        WHERE comprador_id = ? AND vendedor_id = ? AND publicacion_id = ?
        LIMIT 1
    ");
    $sel->execute([$comprador_id, $vendedor_id, $publicacion_id]);
    $conv_id = (int)($sel->fetchColumn() ?: 0);

    if ($conv_id === 0) {
        $ins = $pdo->prepare("
            INSERT INTO conversaciones (comprador_id, vendedor_id, publicacion_id)
            VALUES (?, ?, ?)
        ");
        $ins->execute([$comprador_id, $vendedor_id, $publicacion_id]);
        $conv_id = (int)$pdo->lastInsertId();
    }

    if ($method === 'POST' && $mensaje !== '') {
        $destinatario_id = ($yo === $comprador_id) ? $vendedor_id : $comprador_id;
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
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    Helper::flash_mensaje('No se pudo iniciar el chat. ' . Helper::limpiar($e->getMessage()), 'error');
    Helper::redir(BASE_URL . '/detalle.php?id=' . $publicacion_id);
    exit;
}

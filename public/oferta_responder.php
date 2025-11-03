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
    Helper::redir(BASE_URL . '/ofertas_recibidas.php');
    exit;
}

// Validación CSRF
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    Helper::flash_mensaje('Token inválido.', 'error');
    Helper::redir(BASE_URL . '/ofertas_recibidas.php');
    exit;
}

$yo = (int)$_SESSION['usuario_id'];
$oferta_id = (int)($_POST['oferta_id'] ?? 0);
$accion = trim($_POST['accion'] ?? '');
$respuesta = trim($_POST['respuesta'] ?? '');

if ($oferta_id <= 0 || !in_array($accion, ['aceptar', 'rechazar'])) {
    Helper::flash_mensaje('Datos inválidos.', 'error');
    Helper::redir(BASE_URL . '/ofertas_recibidas.php');
    exit;
}

try {
    $pdo = DB::conn();
    
    // Verificar que la oferta existe y pertenece al vendedor
    $stmt = $pdo->prepare("
        SELECT o.*, p.titulo AS publicacion_titulo, u.nombre AS comprador_nombre
        FROM ofertas o
        JOIN publicaciones p ON p.id = o.publicacion_id
        JOIN usuarios u ON u.id = o.comprador_id
        WHERE o.id = ? AND o.vendedor_id = ?
    ");
    $stmt->execute([$oferta_id, $yo]);
    $oferta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oferta) {
        Helper::flash_mensaje('Oferta no encontrada o no tienes permiso.', 'error');
        Helper::redir(BASE_URL . '/ofertas_recibidas.php');
        exit;
    }
    
    if ($oferta['estado'] !== 'pendiente') {
        Helper::flash_mensaje('Esta oferta ya fue respondida anteriormente.', 'error');
        Helper::redir(BASE_URL . '/ofertas_recibidas.php');
        exit;
    }
    
    // Actualizar el estado de la oferta
    $nuevo_estado = ($accion === 'aceptar') ? 'aceptada' : 'rechazada';
    
    $stmt = $pdo->prepare("
        UPDATE ofertas 
        SET estado = ?, respuesta_vendedor = ?, actualizado_en = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$nuevo_estado, $respuesta, $oferta_id]);
    
    // Mensaje de éxito
    $accion_texto = ($accion === 'aceptar') ? 'aceptada' : 'rechazada';
    Helper::flash_mensaje("Oferta {$accion_texto} exitosamente.", 'success');
    
    Helper::redir(BASE_URL . '/ofertas_recibidas.php');
    exit;
    
} catch (Throwable $e) {
    error_log('Error al responder oferta: ' . $e->getMessage());
    Helper::flash_mensaje('Error al procesar la respuesta. Intenta de nuevo.', 'error');
    Helper::redir(BASE_URL . '/ofertas_recibidas.php');
    exit;
}
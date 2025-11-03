<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión para hacer ofertas.', 'error');
    Helper::redir(BASE_URL . '/login.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Helper::flash_mensaje('Solicitud inválida.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

// Validación CSRF
$token_enviado = $_POST['csrf'] ?? '';
if (!Helper::validateCsrf($token_enviado)) {
    Helper::flash_mensaje('Token inválido. Recarga la página e intenta de nuevo.', 'error');
    $publicacion_id = (int)($_POST['publicacion_id'] ?? 0);
    if ($publicacion_id > 0) {
        Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
    } else {
        Helper::redir(BASE_URL . '/index.php');
    }
    exit;
}

$yo = (int)$_SESSION['usuario_id'];
$publicacion_id = (int)($_POST['publicacion_id'] ?? 0);
$precio_oferta = (float)($_POST['precio_oferta'] ?? 0);
$mensaje = trim($_POST['mensaje'] ?? '');

if ($publicacion_id <= 0) {
    Helper::flash_mensaje('Publicación inválida.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

if ($precio_oferta <= 0) {
    Helper::flash_mensaje('El precio de la oferta debe ser mayor a 0.', 'error');
    Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
    exit;
}

try {
    $pdo = DB::conn();
    
    // ✅ CORREGIDO: Usar usuario_id si vendedor_id está NULL
$stmt = $pdo->prepare("
    SELECT id, titulo, precio_bs, 
           COALESCE(vendedor_id, usuario_id) AS vendedor_id
    FROM publicaciones
    WHERE id = ?
    LIMIT 1
");
    $stmt->execute([$publicacion_id]);
    $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$publicacion) {
        Helper::flash_mensaje('Publicación no encontrada.', 'error');
        Helper::redir(BASE_URL . '/index.php');
        exit;
    }
    
    $vendedor_id = (int)$publicacion['vendedor_id'];
    
    // No permitir ofertar en tu propia publicación
    if ($yo === $vendedor_id) {
        Helper::flash_mensaje('No puedes hacer ofertas en tus propias publicaciones.', 'error');
        Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
        exit;
    }
    
    // Verificar si ya existe una oferta pendiente
    $stmt = $pdo->prepare("
        SELECT id FROM ofertas
        WHERE publicacion_id = ? AND comprador_id = ? AND estado = 'pendiente'
        LIMIT 1
    ");
    $stmt->execute([$publicacion_id, $yo]);
    $oferta_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($oferta_existente) {
        Helper::flash_mensaje('Ya tienes una oferta pendiente para esta publicación. Espera la respuesta del vendedor.', 'error');
        Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
        exit;
    }
    
    // Crear la oferta
    $stmt = $pdo->prepare("
        INSERT INTO ofertas (publicacion_id, comprador_id, vendedor_id, precio_oferta, mensaje, estado)
        VALUES (?, ?, ?, ?, ?, 'pendiente')
    ");
    $stmt->execute([$publicacion_id, $yo, $vendedor_id, $precio_oferta, $mensaje]);
    
    Helper::flash_mensaje('¡Oferta enviada exitosamente! El vendedor recibirá una notificación.', 'success');
    Helper::redir(BASE_URL . '/mis_ofertas.php');
    exit;
    
} catch (Throwable $e) {
    error_log('Error al crear oferta: ' . $e->getMessage());
    Helper::flash_mensaje('Error al enviar la oferta. Por favor, intenta de nuevo.', 'error');
    Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
    exit;
}
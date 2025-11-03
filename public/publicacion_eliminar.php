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
    Helper::redir(BASE_URL . '/mis_publicaciones.php');
    exit;
}

// Validación CSRF
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    Helper::flash_mensaje('Token inválido.', 'error');
    Helper::redir(BASE_URL . '/mis_publicaciones.php');
    exit;
}

$yo = (int)$_SESSION['usuario_id'];
$publicacion_id = (int)($_POST['publicacion_id'] ?? 0);

if ($publicacion_id <= 0) {
    Helper::flash_mensaje('Publicación inválida.', 'error');
    Helper::redir(BASE_URL . '/mis_publicaciones.php');
    exit;
}

try {
    $pdo = DB::conn();
    
    // Verificar que la publicación existe y pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT id, titulo, usuario_id 
        FROM publicaciones 
        WHERE id = ?
    ");
    $stmt->execute([$publicacion_id]);
    $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$publicacion) {
        Helper::flash_mensaje('Publicación no encontrada.', 'error');
        Helper::redir(BASE_URL . '/mis_publicaciones.php');
        exit;
    }
    
    if ((int)$publicacion['usuario_id'] !== $yo) {
        Helper::flash_mensaje('No tienes permiso para eliminar esta publicación.', 'error');
        Helper::redir(BASE_URL . '/mis_publicaciones.php');
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Obtener imágenes para eliminarlas del servidor
    $stmt = $pdo->prepare("SELECT ruta FROM publicaciones_imagenes WHERE publicacion_id = ?");
    $stmt->execute([$publicacion_id]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Eliminar archivos de imágenes del servidor
    foreach ($imagenes as $ruta) {
        $archivo = __DIR__ . '/' . ltrim($ruta, '/');
        if (file_exists($archivo) && is_file($archivo)) {
            @unlink($archivo);
        }
    }
    
    // Eliminar imágenes de la base de datos (si no hay CASCADE)
    $stmt = $pdo->prepare("DELETE FROM publicaciones_imagenes WHERE publicacion_id = ?");
    $stmt->execute([$publicacion_id]);
    
    // Eliminar ofertas relacionadas (si no hay CASCADE)
    $stmt = $pdo->prepare("DELETE FROM ofertas WHERE publicacion_id = ?");
    $stmt->execute([$publicacion_id]);
    
    // Eliminar favoritos relacionados (si no hay CASCADE)
    $stmt = $pdo->prepare("DELETE FROM favoritos WHERE publicacion_id = ?");
    $stmt->execute([$publicacion_id]);
    
    // Eliminar mensajes relacionados (si no hay CASCADE)
    $stmt = $pdo->prepare("DELETE FROM mensajes WHERE publicacion_id = ?");
    $stmt->execute([$publicacion_id]);
    
    // Eliminar conversaciones relacionadas (si no hay CASCADE)
    $stmt = $pdo->prepare("DELETE FROM conversaciones WHERE publicacion_id = ?");
    $stmt->execute([$publicacion_id]);
    
    // Finalmente, eliminar la publicación
    $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
    $stmt->execute([$publicacion_id]);
    
    $pdo->commit();
    
    Helper::flash_mensaje('Publicación eliminada exitosamente.', 'success');
    Helper::redir(BASE_URL . '/mis_publicaciones.php');
    exit;
    
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al eliminar publicación: ' . $e->getMessage());
    Helper::flash_mensaje('Error al eliminar la publicación. Por favor, intenta de nuevo.', 'error');
    Helper::redir(BASE_URL . '/mis_publicaciones.php');
    exit;
}
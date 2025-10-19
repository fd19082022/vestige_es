<?php
require_once __DIR__ . '/_auth.php';
check_csrf();
$pdo = pdo();
$action = $_POST['action'] ?? '';
header('Content-Type: application/json; charset=utf-8');

try {
    switch ($action) {
        case 'usuario_estado':
            $id = (int)($_POST['id'] ?? 0);
            $estado = $_POST['estado'] ?? '';
            if (!in_array($estado, ['activo','suspendido','eliminado'], true)) throw new RuntimeException("Estado inválido");
            $st = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
            $st->execute([$estado, $id]);
            echo json_encode(['ok'=>true, 'msg'=>"Estado actualizado"]); break;

        case 'usuario_rol':
            $id = (int)($_POST['id'] ?? 0);
            $rol_id = (int)($_POST['rol_id'] ?? 0);
            $ex = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE id = ?");
            $ex->execute([$rol_id]);
            if (!$ex->fetchColumn()) throw new RuntimeException("Rol inexistente");
            $st = $pdo->prepare("UPDATE usuarios SET rol_id = ? WHERE id = ?");
            $st->execute([$rol_id, $id]);
            echo json_encode(['ok'=>true, 'msg'=>"Rol actualizado"]); break;

        case 'pub_estado':
            $id = (int)($_POST['id'] ?? 0);
            $estado_id = (int)($_POST['estado_id'] ?? 0);
            $ex = $pdo->prepare("SELECT COUNT(*) FROM estados_publicacion WHERE id = ?");
            $ex->execute([$estado_id]);
            if (!$ex->fetchColumn()) throw new RuntimeException("Estado no válido");
            $st = $pdo->prepare("UPDATE publicaciones SET estado_id = ? WHERE id = ?");
            $st->execute([$estado_id, $id]);
            echo json_encode(['ok'=>true, 'msg'=>"Estado de publicación actualizado"]); break;

        case 'pub_eliminar':
            $id = (int)($_POST['id'] ?? 0);
            $st = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
            $st->execute([$id]);
            echo json_encode(['ok'=>true, 'msg'=>"Publicación eliminada"]); break;

        case 'oferta_estado':
            $id = (int)($_POST['id'] ?? 0);
            $estado = $_POST['estado'] ?? '';
            if (!in_array($estado, ['pendiente','aceptada','rechazada'], true)) throw new RuntimeException("Estado inválido");
            $st = $pdo->prepare("UPDATE ofertas SET estado = ? WHERE id = ?");
            $st->execute([$estado, $id]);
            echo json_encode(['ok'=>true, 'msg'=>"Oferta actualizada"]); break;

        default: throw new RuntimeException("Acción no soportada");
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]); exit;
}

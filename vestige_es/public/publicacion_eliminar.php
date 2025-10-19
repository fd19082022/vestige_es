<?php
// public/publicacion_eliminar.php
session_start();
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Helper.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php'); exit;
}
$usuario = $_SESSION['usuario'];
$uid = (int)($usuario['id'] ?? 0);
$es_admin = ((int)($usuario['rol_id'] ?? 0) === 1); // roles.id=1 => admin

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { Helper::set_flash('Publicación inválida', 'error'); header('Location: panel.php'); exit; }

// (opcional) CSRF simple
if (!isset($_GET['csrf']) || !isset($_SESSION['csrf']) || $_GET['csrf'] !== $_SESSION['csrf']) {
    Helper::set_flash('Acción no autorizada (CSRF)', 'error'); header('Location: panel.php'); exit;
}

$pdo = DB::conn();

// Verificar propiedad o admin
$stmt = $pdo->prepare("SELECT id, vendedor_id FROM publicaciones WHERE id = ?");
$stmt->execute([$id]);
$pub = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pub) { Helper::set_flash('Publicación no encontrada', 'error'); header('Location: panel.php'); exit; }

if (!$es_admin && (int)$pub['vendedor_id'] !== $uid) {
    Helper::set_flash('No puedes eliminar esta publicación', 'error'); header('Location: panel.php'); exit;
}

// Borrar (cascadas FK ya están definidas en tu BD)
$pdo->beginTransaction();
try {
    $del = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
    $del->execute([$id]);
    $pdo->commit();
    Helper::set_flash('Publicación eliminada', 'ok');
} catch (Throwable $e) {
    $pdo->rollBack();
    Helper::set_flash('No se pudo eliminar: ' . $e->getMessage(), 'error');
}
header('Location: panel.php'); exit;

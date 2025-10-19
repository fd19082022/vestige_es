<?php
// public/resena_guardar.php
session_start();
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Helper.php';

if (!isset($_SESSION['usuario'])) { header('Location: login.php'); exit; }
$yo = $_SESSION['usuario'];
$autor_id = (int)$yo['id'];

$vendedor_id    = (int)($_POST['vendedor_id'] ?? 0);
$publicacion_id = (int)($_POST['publicacion_id'] ?? 0);
$calificacion   = (int)($_POST['calificacion'] ?? 0);
$csrf           = $_POST['csrf'] ?? '';

if ($calificacion < 1 || $calificacion > 5) { Helper::set_flash('Calificación inválida', 'error'); header('Location: publicacion_ver.php?id='.$publicacion_id); exit; }
if (!isset($_SESSION['csrf']) || $csrf !== $_SESSION['csrf']) { Helper::set_flash('CSRF inválido', 'error'); header('Location: publicacion_ver.php?id='.$publicacion_id); exit; }

$pdo = DB::conn();

// (Opcional) validar que el autor haya interactuado con el vendedor (pedido/oferta/puja/mensaje). Por simplicidad lo omitimos ahora.
try {
    $stmt = $pdo->prepare("INSERT INTO resenas (publicacion_id, autor_id, vendedor_id, calificacion, comentario) VALUES (?,?,?,?,NULL)
                           ON DUPLICATE KEY UPDATE calificacion=VALUES(calificacion)");
    $stmt->execute([$publicacion_id, $autor_id, $vendedor_id, $calificacion]);
    Helper::set_flash('¡Gracias por tu calificación!', 'ok');
} catch (Throwable $e) {
    Helper::set_flash('No se pudo guardar la calificación: ' . $e->getMessage(), 'error');
}
header('Location: publicacion_ver.php?id='.$publicacion_id); exit;

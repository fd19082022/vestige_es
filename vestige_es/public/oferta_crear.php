<?php
// public/oferta_crear.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión para ofertar.', 'error');
    Helper::redir(BASE_URL . '/login.php');
    exit;
}
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    Helper::flash_mensaje('Token CSRF inválido.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

$publicacion_id = (int)($_POST['publicacion_id'] ?? 0);
$precio_oferta  = (float)($_POST['precio_oferta'] ?? 0);
$mensaje        = trim($_POST['mensaje'] ?? '');
$comprador_id   = (int)($_SESSION['usuario_id']);

if ($publicacion_id <= 0 || $precio_oferta <= 0) {
    Helper::flash_mensaje('Datos de oferta inválidos.', 'error');
    Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $publicacion_id);
    exit;
}

$pdo = DB::conn();

// Traer vendedor de la publicación
$st = $pdo->prepare("SELECT vendedor_id FROM publicaciones WHERE id = ?");
$st->execute([$publicacion_id]);
$pub = $st->fetch(PDO::FETCH_ASSOC);
if (!$pub) {
    Helper::flash_mensaje('Publicación no encontrada.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}
$vendedor_id = (int)$pub['vendedor_id'];

// Determinar tabla de ofertas existente
$tabla = null;
$posibles = ['ofertas', 'publicaciones_ofertas', 'ofertas_publicaciones'];
foreach ($posibles as $t) {
    $chk = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = " . $pdo->quote($t))->fetchColumn();
    if ($chk) { $tabla = $t; break; }
}
if (!$tabla) {
    // Crea una tabla simple si no existe ninguna (último recurso)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ofertas (
          id INT AUTO_INCREMENT PRIMARY KEY,
          publicacion_id INT NOT NULL,
          comprador_id INT NOT NULL,
          vendedor_id INT NOT NULL,
          monto_bs DECIMAL(12,2) NOT NULL,
          mensaje TEXT NULL,
          estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
          creado_en DATETIME NOT NULL,
          actualizado_en DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $tabla = 'ofertas';
}

// Insertar oferta
$ahora = date('Y-m-d H:i:s');
$cols = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
$montoCol = in_array('monto_bs', $cols) ? 'monto_bs' : (in_array('monto', $cols) ? 'monto' : 'precio');

$sql = "INSERT INTO `$tabla`
        (publicacion_id, comprador_id, vendedor_id, $montoCol, mensaje, estado, creado_en, actualizado_en)
        VALUES (:publicacion_id, :comprador_id, :vendedor_id, :monto, :mensaje, :estado, :creado, :actualizado)";
$st = $pdo->prepare($sql);
$st->execute([
    ':publicacion_id' => $publicacion_id,
    ':comprador_id'   => $comprador_id,
    ':vendedor_id'    => $vendedor_id,
    ':monto'          => $precio_oferta,
    ':mensaje'        => ($mensaje !== '' ? $mensaje : null),
    ':estado'         => 'pendiente',
    ':creado'         => $ahora,
    ':actualizado'    => $ahora,
]);

Helper::flash_mensaje('¡Oferta enviada! El vendedor ha sido notificado.', 'ok');

// 🔧 Ajusta esta URL al sistema de chat real si tu proyecto lo llama diferente
Helper::redir(BASE_URL . '/chat.php?vendedor_id=' . $vendedor_id);

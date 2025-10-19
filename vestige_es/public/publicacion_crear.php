<?php
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Helper.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1;
}

$pdo = DB::conn();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: publicacion_nueva.php');
    exit;
}


$titulo       = trim($_POST['titulo'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$precio_bs    = isset($_POST['precio_bs']) ? (float)$_POST['precio_bs'] : 0;
$condicion    = $_POST['condicion'] ?? 'buen_estado';
$categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
$estado_id    = isset($_POST['estado_id']) ? (int)$_POST['estado_id'] : 1;

$usuario_id  = (int)($_SESSION['usuario_id'] ?? 0);
$vendedor_id = $usuario_id;

$errores = [];
if ($usuario_id <= 0) $errores[] = 'Debes iniciar sesión.';
if ($titulo === '')  $errores[] = 'El título es obligatorio.';
if ($categoria_id <= 0) $errores[] = 'Selecciona una categoría válida.';
if ($precio_bs <= 0) $errores[] = 'Ingresa un precio válido.';

if ($errores) {
    echo "<h1>Error al crear publicación</h1>";
    foreach ($errores as $e) echo "<p>".htmlspecialchars($e)."</p>";
    echo '<p><a href="publicacion_nueva.php">Volver</a></p>';
    exit;
}

try {
    $sql = "INSERT INTO publicaciones
        (usuario_id, vendedor_id, categoria_id, titulo, descripcion, condicion, estado, precio_bs, es_subasta, estado_id, creado_en)
        VALUES (?, ?, ?, ?, ?, ?, 'Usado - Bueno', ?, 0, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, $vendedor_id, $categoria_id, $titulo, $descripcion, $condicion, $precio_bs, $estado_id]);

    $pub_id = (int)$pdo->lastInsertId();

    
    if (!empty($_FILES['fotos']) && is_array($_FILES['fotos']['name'])) {
        $maxFiles = 6;
        $saved = 0;
        for ($i=0; $i < count($_FILES['fotos']['name']) && $saved < $maxFiles; $i++) {
            $name = $_FILES['fotos']['name'][$i] ?? '';
            $tmp  = $_FILES['fotos']['tmp_name'][$i] ?? '';
            $err  = $_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $size = $_FILES['fotos']['size'][$i] ?? 0;
            if ($err !== UPLOAD_ERR_OK || !$tmp) continue;
            if ($size > 5 * 1024 * 1024) continue;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            if (!in_array($mime, ['image/jpeg','image/png','image/webp'])) continue;

            $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
            $newName = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $newName;
            if (!move_uploaded_file($tmp, $dest)) continue;

            $es_principal = ($saved === 0) ? 1 : 0;
            $insImg = $pdo->prepare("INSERT INTO publicaciones_imagenes (publicacion_id, ruta, es_principal) VALUES (?, ?, ?)");
            $insImg->execute([$pub_id, 'uploads/' . $newName, $es_principal]);
            $saved++;
        }
    }

    header('Location: publicacion_ver.php?id=' . $pub_id);
    exit;
} catch (Throwable $e) {
    echo "<h1>Error al crear publicación</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo '<p><a href="publicacion_nueva.php">Volver</a></p>';
}

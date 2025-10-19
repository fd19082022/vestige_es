<?php
// public/publicacion_guardar.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
    exit;
}

// CSRF
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    Helper::flash_mensaje('Token CSRF inválido.', 'error');
    Helper::redir(BASE_URL . '/publicacion_nueva.php');
    exit;
}

$pdo = DB::conn();

$titulo        = trim($_POST['titulo'] ?? '');
$descripcion   = trim($_POST['descripcion'] ?? '');
$categoria_id  = (int)($_POST['categoria_id'] ?? 0);
$subcat_raw    = trim($_POST['subcategoria_id'] ?? '');
$subcategoria_id = ($subcat_raw === '' ? null : (int)$subcat_raw);

$condicion     = trim($_POST['condicion'] ?? '');
$talla_raw     = trim($_POST['talla_id'] ?? '');
$color_raw     = trim($_POST['color_id'] ?? '');
$talla_id      = ($talla_raw === '' ? null : (int)$talla_raw);
$color_id      = ($color_raw === '' ? null : (int)$color_raw);

$precio_bs     = (float)($_POST['precio_bs'] ?? 0);
$vendedor_id   = (int)$_SESSION['usuario_id'];

// Validaciones mínimas
$errores = [];
if ($titulo === '')        $errores[] = 'El título es obligatorio.';
if ($categoria_id <= 0)    $errores[] = 'Selecciona una categoría.';
if ($condicion === '')     $errores[] = 'Selecciona la condición.';
if ($precio_bs <= 0)       $errores[] = 'El precio debe ser mayor a 0.';
if ($errores) {
    Helper::flash_mensaje(implode(' ', $errores), 'error');
    Helper::redir(BASE_URL . '/publicacion_nueva.php');
    exit;
}

// 👉 estado_id válido para cumplir la FK
try {
    $q = $pdo->query("
        SELECT id
        FROM estados_publicacion
        WHERE LOWER(nombre) IN ('activa','publicada','pendiente','borrador')
        ORDER BY FIELD(LOWER(nombre),'activa','publicada','pendiente','borrador')
        LIMIT 1
    ");
    $estado_id = (int)$q->fetchColumn();
    if (!$estado_id) {
        $q = $pdo->query("SELECT id FROM estados_publicacion ORDER BY id LIMIT 1");
        $estado_id = (int)$q->fetchColumn();
    }
} catch (Throwable $e) {
    $estado_id = 1; // fallback
}

$pdo->beginTransaction();
try {
    // Insert principal
    $stmt = $pdo->prepare("
        INSERT INTO publicaciones
        (vendedor_id, categoria_id, subcategoria_id, condicion, talla_id, color_id, precio_bs, titulo, descripcion, estado_id, creado_en, actualizado_en)
        VALUES
        (:vendedor_id, :categoria_id, :subcategoria_id, :condicion, :talla_id, :color_id, :precio_bs, :titulo, :descripcion, :estado_id, NOW(), NOW())
    ");
    $stmt->execute([
        ':vendedor_id'     => $vendedor_id,
        ':categoria_id'    => $categoria_id,
        ':subcategoria_id' => $subcategoria_id, // NULL si vacío
        ':condicion'       => $condicion,
        ':talla_id'        => $talla_id,        // NULL si vacío
        ':color_id'        => $color_id,        // NULL si vacío
        ':precio_bs'       => $precio_bs,
        ':titulo'          => $titulo,
        ':descripcion'     => $descripcion,
        ':estado_id'       => $estado_id,       // ✅ FK cumplida
    ]);

    $pub_id = (int)$pdo->lastInsertId();

    // Imagen principal (opcional)
    if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
            throw new RuntimeException('Formato de imagen no permitido.');
        }
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

        $nombre  = bin2hex(random_bytes(16)) . '.' . $ext;
        $destAbs = $uploadsDir . '/' . $nombre;
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destAbs)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        $rutaRel = 'uploads/' . $nombre; // relativo a /public
        $pi = $pdo->prepare("INSERT INTO publicaciones_imagenes (publicacion_id, ruta, es_principal) VALUES (?, ?, 1)");
        $pi->execute([$pub_id, $rutaRel]);
    }

    $pdo->commit();
    Helper::flash_mensaje('¡Publicación creada con éxito!', 'ok');
    Helper::redir(BASE_URL . '/publicacion_ver.php?id=' . $pub_id);
} catch (Throwable $e) {
    $pdo->rollBack();
    Helper::flash_mensaje('Error al guardar la publicación: ' . $e->getMessage(), 'error');
    Helper::redir(BASE_URL . '/publicacion_nueva.php');
}

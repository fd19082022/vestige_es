<?php
// Script de prueba independiente para verificar "Mis publicaciones".
// Úsalo temporalmente en http://localhost/vestige_es/public/tools_check_mis_pub.php

require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Publicacion.php';

session_start();
if (empty($_SESSION['usuario']['id'])) {
    echo "<p>Inicia sesión y vuelve a cargar.</p>";
    exit;
}
$yo = (int)$_SESSION['usuario']['id'];

$lista = Publicacion::listarPorVendedor($yo, ESTADO_PUBLICADA);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Check — Mis publicaciones</title>
  <style>
    body{font-family:Arial, sans-serif; margin:20px;}
    .grid{display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px;}
    .card{border:1px solid #ddd; border-radius:10px; overflow:hidden}
    .card img{width:100%; height:180px; object-fit:cover; display:block; background:#f7f7f7}
    .card .c{padding:10px}
    .precio{font-weight:bold}
    .muted{color:#666; font-size:12px}
  </style>
</head>
<body>
  <h1>Mis publicaciones (check)</h1>
  <p class="muted">Se muestran con <code>vendedor_id = yo</code> (o <code>usuario_id = yo</code>) y <code>estado_id = 2</code>. Imagen: <code>COALESCE(publicaciones_imagenes.ruta, publicaciones.imagen_principal)</code>.</p>
  <div class="grid">
  <?php foreach ($lista as $p): 
      $img = $p['principal_img'] ?: 'assets/img/demo.jpg';
      $src = (strpos($img, 'http') === 0) ? $img : ('/' . ltrim($img,'/'));
  ?>
    <article class="card">
      <img src="<?= htmlspecialchars($src) ?>" alt="">
      <div class="c">
        <div class="muted">#<?= (int)$p['id'] ?></div>
        <h3><?= Helper::limpiar($p['titulo']) ?></h3>
        <div class="precio"><?= number_format((float)$p['precio_bs'], 2) ?> Bs</div>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
</body>
</html>

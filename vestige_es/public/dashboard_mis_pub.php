<?php
// Dashboard mínimo: "Mis publicaciones" (tolerante con sesión y con ?uid=)
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Publicacion.php';

// Asegurar sesión
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

// Detección robusta del ID de usuario
$yo = null;
if (!empty($_SESSION['usuario']['id']))        $yo = (int)$_SESSION['usuario']['id'];
elseif (!empty($_SESSION['user']['id']))       $yo = (int)$_SESSION['user']['id'];
elseif (!empty($_SESSION['auth']['id']))       $yo = (int)$_SESSION['auth']['id'];
elseif (!empty($_SESSION['uid']))              $yo = (int)$_SESSION['uid'];

// Fallback por query param para pruebas rápidas: /dashboard_mis_pub.php?uid=123
if (!$yo && !empty($_GET['uid'])) {
    $yo = (int)$_GET['uid'];
}

// Si aún no hay usuario, mostramos ayuda rápida
if (!$yo) {
    echo "<p><strong>No encontré una sesión con el ID de usuario.</strong></p>";
    echo "<p>Opciones:</p>";
    echo "<ol>";
    echo "<li>Inicia sesión en tu app y recarga esta página.</li>";
    echo "<li>O prueba agregando <code>?uid=TU_ID</code> en la URL. Ejemplo: <code>dashboard_mis_pub.php?uid=1</code></li>";
    echo "<li>O temporalmente define <code>\$_SESSION['usuario']['id']</code> en tu login.</li>";
    echo "</ol>";
    if (!empty($_SESSION)) {
        echo '<details><summary>Debug de claves de $_SESSION</summary><pre>';
        echo htmlspecialchars(print_r($_SESSION, true));
        echo '</pre></details>';
    }
    exit;
}

// IMPORTANTE: traemos TODAS (sin filtrar por estado) para evitar que se escondan si no están 'publicadas'.
$lista = Publicacion::listarPorVendedor($yo, null);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis publicaciones — Dashboard</title>
  <style>
    body{font-family:Arial, sans-serif; margin:20px;}
    h1{margin-top:0}
    .grid{display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px;}
    .card{border:1px solid #ddd; border-radius:10px; overflow:hidden}
    .card img{width:100%; height:180px; object-fit:cover; display:block; background:#f7f7f7}
    .c{padding:10px}
    .muted{color:#666; font-size:12px}
    .precio{font-weight:bold}
    .estado{font-size:12px; padding:2px 6px; border-radius:6px; display:inline-block; background:#eee}
  </style>
</head>
<body>
  <h1>Mis publicaciones</h1>
  <p class="muted">Mostrando por <code>vendedor_id = <?= $yo ?></code> (o <code>usuario_id = <?= $yo ?></code>), sin filtro de estado.</p>
  <div class="grid">
  <?php foreach ($lista as $p): 
      $img = $p['principal_img'] ?? $p['imagen_principal'] ?? '';
      $src = '/assets/img/demo.jpg';
      if ($img) {
        if (strpos($img, 'http') === 0) {
            $src = $img;
        } else {
            $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            if ($base) {
                $src = $base . '/' . ltrim($img,'/');
            } else {
                // intento relativo: asume que el proyecto está en /vestige_es/
                $src = '/vestige_es/' . ltrim($img,'/');
            }
        }
      }
      $estado_txt = isset($p['estado_id']) ? ('estado_id='.(int)$p['estado_id']) : 's/estado_id';
  ?>
    <article class="card">
      <img src="<?= htmlspecialchars($src) ?>" alt="">
      <div class="c">
        <div class="muted">#<?= (int)$p['id'] ?> — <?= htmlspecialchars($estado_txt) ?></div>
        <h3><?= Helper::limpiar($p['titulo']) ?></h3>
        <div class="precio"><?= number_format((float)($p['precio_bs'] ?? 0), 2) ?> Bs</div>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
</body>
</html>

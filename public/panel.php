<?php
// public/panel.php — versión autocontenida
session_start();
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Helper.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php'); exit;
}
$yo       = $_SESSION['usuario'];
$uid      = (int)($yo['id'] ?? 0);
$es_admin = ((int)($yo['rol_id'] ?? 0) === 1);

// CSRF
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf'];

$pdo = DB::conn();

// Traer publicaciones: admin => todas; vendedor => propias
if ($es_admin) {
    $sql = "SELECT p.*,
            (SELECT ruta FROM publicaciones_imagenes pi 
             WHERE pi.publicacion_id = p.id AND pi.es_principal = 1 
             ORDER BY pi.id ASC LIMIT 1) AS principal_img
            FROM publicaciones p
            ORDER BY p.creado_en DESC
            LIMIT 300";
    $st = $pdo->query($sql);
} else {
    $sql = "SELECT p.*,
            (SELECT ruta FROM publicaciones_imagenes pi 
             WHERE pi.publicacion_id = p.id AND pi.es_principal = 1 
             ORDER BY pi.id ASC LIMIT 1) AS principal_img
            FROM publicaciones p
            WHERE p.vendedor_id = ?
            ORDER BY p.creado_en DESC
            LIMIT 300";
    $st = $pdo->prepare($sql);
    $st->execute([$uid]);
}
$lista = $st->fetchAll(PDO::FETCH_ASSOC);

// Helper: formatear imagen
function imagen_src($p) {
    $img = $p['principal_img'] ?? $p['imagen_principal'] ?? '';
    if (!$img || trim($img)==='') {
        return '/assets/img/demo.jpg'; // pon aquí tu placeholder si usas otro
    }
    // Si es URL absoluta
    if (strpos($img, 'http') === 0) return $img;
    // relativa al docroot
    return '/' . ltrim($img, '/');
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel | Vestige</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root{
      --bg:#faf7fb; --card:#fff; --text:#333; --muted:#777;
      --primary:#8E3D56; --primary-2:#AB4E6A; --danger:#c0392b;
      --border:#e9e9ee;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
    header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:var(--card);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:10}
    h1{font-size:18px;margin:0}
    .tag{font-size:12px;padding:4px 8px;border-radius:999px;background:#f1eef3;color:var(--primary)}
    .wrap{max-width:1100px;margin:22px auto;padding:0 16px}
    .flash{padding:10px 12px;border-radius:10px;margin-bottom:16px;background:#eef9f0;color:#1b5e20;border:1px solid #c8e6c9}
    .flash.error{background:#ffefef;color:#b00020;border-color:#ffcdd2}
    .tools{display:flex;gap:8px;margin-bottom:16px}
    .btn{display:inline-block;padding:8px 12px;border-radius:10px;text-decoration:none;border:1px solid var(--border);background:var(--card);color:var(--text)}
    .btn-primario{background:linear-gradient(135deg,var(--primary),var(--primary-2));color:#fff;border:none}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;display:flex;flex-direction:column}
    .thumb{width:100%;height:180px;object-fit:cover;background:#eee}
    .cnt{padding:12px}
    .ttl{font-size:16px;margin:0 0 6px;line-height:1.25}
    .row{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:8px}
    .precio{font-weight:700}
    .pill{font-size:11px;padding:4px 8px;border-radius:999px;background:#f3f3f7;color:var(--muted);border:1px dashed #ddd}
    .acciones{display:flex;gap:8px;margin-top:10px}
    .btn-peligro{background:var(--danger);color:#fff;border:none}
    .muted{color:var(--muted);font-size:12px}
    footer{padding:24px 20px;color:var(--muted);text-align:center}
    @media (hover:hover){ .card:hover{transform:translateY(-2px);transition:transform .15s ease} }
  </style>
</head>
<body>
  <header>
    <h1>Panel de <?= htmlspecialchars($yo['nombre'] ?? 'Usuario') ?> <?= $es_admin ? '<span class="tag">Admin</span>' : '' ?></h1>
    <nav class="tools">
      <a class="btn" href="index.php">Inicio</a>
      <a class="btn btn-primario" href="publicacion_nueva.php">Subir publicación</a>
    </nav>
  </header>

  <main class="wrap">
    <?php if ($flash = Helper::obtener_flash()): ?>
      <?php
        // Simple detección de tipo en el mensaje (ok/error) si usas set_flash(msg,tipo)
        $clase = 'flash';
        if (is_array($flash)) {
          $clase .= isset($flash['tipo']) && $flash['tipo']==='error' ? ' error' : '';
          $msg = $flash['msg'] ?? '';
        } else {
          $msg = $flash;
        }
      ?>
      <div class="<?= $clase ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="grid">
      <?php if (!$lista): ?>
        <div class="card">
          <div class="cnt">
            <p class="muted">No tienes publicaciones aún.</p>
            <a class="btn btn-primario" href="publicacion_nueva.php">Crear la primera</a>
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($lista as $p): ?>
        <?php
          $src = imagen_src($p);
          $soy_dueno = ((int)($p['vendedor_id'] ?? 0) === $uid);
          $puede_eliminar = $es_admin || $soy_dueno;
          $precio = number_format((float)($p['precio_bs'] ?? 0), 2);
          $cond  = $p['condicion'] ? ucwords(str_replace('_',' ', $p['condicion'])) : '—';
          $estado_pub = (int)($p['estado_id'] ?? 0);
          $pill = $estado_pub===2 ? 'Publicada' : ($estado_pub===1?'Borrador':($estado_pub===3?'Pausada':($estado_pub===4?'Vendida':'Estado')));
        ?>
        <article class="card">
          <img class="thumb" src="<?= htmlspecialchars($src) ?>" alt="Imagen">
          <div class="cnt">
            <h3 class="ttl"><?= htmlspecialchars($p['titulo'] ?? 'Sin título') ?></h3>
            <div class="row">
              <span class="precio"><?= $precio ?> Bs</span>
              <span class="pill"><?= htmlspecialchars($pill) ?></span>
            </div>
            <div class="row">
              <span class="muted">Condición: <?= htmlspecialchars($cond) ?></span>
              <?php if (!empty($p['visitas'])): ?>
                <span class="muted"><?= (int)$p['visitas'] ?> visitas</span>
              <?php endif; ?>
            </div>

            <div class="acciones">
              <a class="btn" href="publicacion_ver.php?id=<?= (int)$p['id'] ?>">Ver</a>
              <?php if ($puede_eliminar): ?>
                <a class="btn btn-peligro"
                   href="publicacion_eliminar.php?id=<?= (int)$p['id'] ?>&csrf=<?= $csrf ?>"
                   onclick="return confirm('¿Eliminar \"<?= htmlspecialchars($p['titulo']) ?>\"? Esta acción no se puede deshacer.');">
                   Eliminar
                </a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </main>

  <footer>
    © <?= date('Y') ?> Vestige — Panel
  </footer>
</body>
</html>

<?php
// public/index.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

/**
 * Retorna la URL pública de un asset dentro de /public o una URL absoluta si ya lo es.
 * Si no existe, devuelve un placeholder.
 */
function asset_url(string $relative): string {
    $r = trim($relative);
    if ($r === '') return BASE_URL . '/assets/imgs/no-image.png';
    if (preg_match('~^(https?:)?//|^data:image/~i', $r)) return $r;
    $r = ltrim($r, '/');
    if (str_starts_with($r, 'public/')) $r = substr($r, 7);

    $cands = [
        __DIR__ . '/' . $r,
        __DIR__ . '/public/' . $r,
    ];
    foreach ($cands as $abs) {
        if (is_file($abs)) {
            $rel = str_replace('\\','/', str_replace(__DIR__ . '/', '', $abs));
            return BASE_URL . '/' . $rel;
        }
    }
    return BASE_URL . '/assets/imgs/no-image.png';
}

$pdo = DB::conn();

// Cargar categorías
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Últimas publicaciones + primera imagen
$publicaciones = $pdo->query("
  SELECT p.id, p.titulo, p.precio_bs,
         (SELECT ruta FROM publicaciones_imagenes i 
          WHERE i.publicacion_id = p.id 
          ORDER BY es_principal DESC, id ASC LIMIT 1) AS img
  FROM publicaciones p
  ORDER BY p.id DESC
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// Mapa de imágenes por nombre de categoría (ajústalo a tus nombres reales)
$catImageMap = [
    'Mujeres'     => 'assets/imgs/cat_mujer.jpg',
    'Hombres'     => 'assets/imgs/cat_hombre.jpg',
    'Niños'       => 'assets/imgs/cat_nino.jpg',
    'Accesorios'  => 'assets/imgs/cat_accesorios.jpg',
];

require_once __DIR__ . '/../templates/header.php';
?>

<style>
  .hero {
    position: relative; width: 100%; min-height: 420px;
    background: center/cover no-repeat; display: flex; align-items: center; justify-content: center;
    text-align: center; color: #fff; border-radius: 0 0 20px 20px; overflow: hidden;
  }
  .hero::after { content:""; position:absolute; inset:0; background: rgba(0,0,0,0.35); }
  .hero-content { position:relative; z-index:2; max-width: 600px; padding: 20px; }
  .hero h1 { font-size: 2.3rem; font-weight: 800; margin-bottom: 12px; }
  .hero p { font-size: 1.1rem; opacity: .9; margin-bottom: 20px; }

  .categorias, .ultimas { margin: 60px auto; max-width: 1100px; padding: 0 14px; }
  .categorias h2, .ultimas h2 { color: #8E3D56; font-weight: 700; margin-bottom: 20px; }

  .cat-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(230px,1fr)); gap: 18px;
  }
  .cat-item {
    position: relative; border-radius: 12px; overflow: hidden;
    height: 220px; background: #eee; cursor: pointer; transition: transform .25s; display:block; text-decoration:none;
  }
  .cat-item:hover { transform: scale(1.02); }
  .cat-item img { width: 100%; height: 100%; object-fit: cover; filter: brightness(0.72); transition: filter .25s; display:block; }
  .cat-item:hover img { filter: brightness(0.86); }
  .cat-item span {
    position: absolute; bottom: 12px; left: 14px; right: 14px;
    color: #fff; font-weight: 700; font-size: 1.1rem; text-shadow: 1px 2px 6px rgba(0,0,0,.5);
  }

  .pub-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap: 18px;
  }
  .pub {
    border-radius: 12px; overflow: hidden; background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,.08); transition: transform .25s; display:block; text-decoration:none;
  }
  .pub:hover { transform: translateY(-3px); }
  .pub img { width: 100%; height: 220px; object-fit: cover; display:block; }
  .pub-info { padding: 12px; }
  .pub-info h3 { margin: 0 0 6px; font-size: 1rem; color: #222; }
  .pub-info p { color: #8E3D56; font-weight: 600; }
</style>

<!-- HERO -->
<section class="hero" style="background-image:url('<?= asset_url('assets/imgs/hero_ropa.jpg') ?>');">
  <div class="hero-content">
    <h1>Descubre tu estilo con Vestige</h1>
    <p>Explora, sube y encuentra prendas únicas para ti. Moda sostenible, joven y diferente.</p>
    <a href="<?= BASE_URL ?>/explorar.php" class="btn-primario">Explorar publicaciones</a>
  </div>
</section>

<!-- CATEGORÍAS -->
<section class="categorias">
  <h2>Categorías populares</h2>
  <div class="cat-grid">
    <?php foreach ($categorias as $cat): 
      $nombre = $cat['nombre'];
      $imgRel = $catImageMap[$nombre] ?? 'assets/imgs/no-image.png';
      ?>
      <a href="<?= BASE_URL ?>/explorar.php?categoria=<?= urlencode($nombre) ?>" class="cat-item">
        <img src="<?= asset_url($imgRel) ?>" alt="<?= htmlspecialchars($nombre) ?>">
        <span><?= htmlspecialchars($nombre) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ÚLTIMAS PUBLICACIONES -->
<section class="ultimas">
  <h2>Últimas publicaciones</h2>
  <div class="pub-grid">
    <?php if ($publicaciones): ?>
      <?php foreach($publicaciones as $p): ?>
        <!-- 🔧 FIX: enlace correcto al detalle -->
        <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$p['id'] ?>" class="pub">
          <img src="<?= asset_url($p['img'] ?: 'assets/imgs/no-image.png') ?>" alt="">
          <div class="pub-info">
            <h3><?= htmlspecialchars($p['titulo']) ?></h3>
            <p><?= number_format((float)$p['precio_bs'], 2) ?> Bs</p>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No hay publicaciones aún.</p>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

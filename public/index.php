<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

/**
 * Retorna la URL pública de un asset
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
  WHERE p.estado_id = 2
  ORDER BY p.id DESC
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// Mapa de imágenes por categoría
$catImageMap = [
    'Mujeres'     => 'assets/imgs/cat_mujer.jpg',
    'Hombres'     => 'assets/imgs/cat_hombre.jpg',
    'Niños'       => 'assets/imgs/cat_nino.jpg',
    'Accesorios'  => 'assets/imgs/cat_accesorios.jpg',
];

$flash = Helper::getFlash();
require_once __DIR__ . '/../templates/header.php';
?>

<!-- HERO -->
<section class="hero" style="background-image:url('<?= asset_url('assets/imgs/hero_ropa.jpg') ?>'); min-height:420px; display:flex; align-items:center; justify-content:center; text-align:center; position:relative; border-radius:var(--radius); overflow:hidden; margin-bottom:2rem;">
  <div style="position:absolute; inset:0; background:rgba(3,7,18,0.45);"></div>
  <div style="position:relative; z-index:2; max-width:900px; padding:2rem;">
    <h1 style="font-size:clamp(2rem, 4vw, 3rem); margin:0 0 1rem; color:var(--text);">
      Descubre tu estilo con Vestige
    </h1>
    <p style="font-size:1.1rem; color:var(--muted); margin:0 0 1.5rem; line-height:1.6;">
      Explora, sube y encuentra prendas únicas para ti. Moda sostenible, joven y diferente.
    </p>
    <a href="<?= BASE_URL ?>/explorar.php" class="btn-primario" style="display:inline-block;">
      Explorar publicaciones
    </a>
  </div>
</section>

<main class="contenedor principal">
  
  <?php if (!empty($flash)): ?>
    <?php foreach ($flash as $f): ?>
      <div class="alerta alerta--<?php echo $f['tipo']; ?>">
        <?php echo htmlspecialchars($f['mensaje']); ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- CATEGORÍAS -->
  <section class="seccion">
    <h2 style="margin:0 0 1rem; font-size:1.8rem;">Categorías populares</h2>
    
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px,1fr)); gap:1rem;">
      <?php foreach ($categorias as $cat): 
        $nombre = $cat['nombre'];
        $imgRel = $catImageMap[$nombre] ?? 'assets/imgs/no-image.png';
      ?>
        <a href="<?= BASE_URL ?>/explorar.php?cat=<?= (int)$cat['id'] ?>" 
           style="position:relative; border-radius:14px; overflow:hidden; height:200px; display:block; text-decoration:none; color:var(--text); background:var(--card); border:1px solid rgba(255,255,255,.06); transition:transform .2s ease;">
          <img src="<?= asset_url($imgRel) ?>" 
               alt="<?= htmlspecialchars($nombre) ?>"
               style="width:100%; height:100%; object-fit:cover; filter:brightness(.75);">
          <span style="position:absolute; bottom:12px; left:14px; right:14px; color:var(--text); font-weight:700; font-size:1.1rem; text-shadow:0 4px 12px rgba(0,0,0,.8);">
            <?= htmlspecialchars($nombre) ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ÚLTIMAS PUBLICACIONES -->
  <section class="seccion" style="margin-top:2.5rem;">
    <h2 style="margin:0 0 1rem; font-size:1.8rem;">Últimas publicaciones</h2>
    
    <?php if ($publicaciones): ?>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:1rem;">
        <?php foreach($publicaciones as $p): ?>
          <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$p['id'] ?>" 
             class="card"
             style="display:block; text-decoration:none; color:var(--text); background:var(--card); border:1px solid rgba(255,255,255,.06); border-radius:var(--radius); overflow:hidden; transition:transform .2s ease, border-color .2s ease;">
            
            <div style="aspect-ratio:4/3; background:#0b1226; overflow:hidden;">
              <img src="<?= asset_url($p['img'] ?: 'assets/imgs/no-image.png') ?>" 
                   alt="<?= htmlspecialchars($p['titulo']) ?>"
                   style="width:100%; height:100%; object-fit:cover; display:block;">
            </div>
            
            <div style="padding:.9rem 1rem 1.1rem;">
              <h3 style="margin:.3rem 0 .5rem; font-size:1rem; line-height:1.3; color:var(--text);">
                <?= htmlspecialchars($p['titulo']) ?>
              </h3>
              <p style="margin:0; font-weight:700; color:var(--primary); font-size:1.05rem;">
                <?= number_format((float)$p['precio_bs'], 2) ?> Bs
              </p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty">
        <h3>No hay publicaciones aún</h3>
        <p>Sé el primero en publicar una prenda</p>
        <?php if (Helper::esta_logueado()): ?>
          <div class="actions">
            <a href="<?= BASE_URL ?>/publicacion_nueva.php" class="btn-primario">Crear publicación</a>
          </div>
        <?php else: ?>
          <div class="actions">
            <a href="<?= BASE_URL ?>/login.php" class="btn-primario">Inicia sesión para publicar</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- LLAMADA A LA ACCIÓN -->
  <?php if (!Helper::esta_logueado()): ?>
    <section class="seccion" style="margin-top:2.5rem;">
      <div style="background:linear-gradient(135deg, rgba(167,139,250,.15), rgba(34,211,238,.12)); border:1px solid rgba(255,255,255,.08); border-radius:var(--radius); padding:2rem; text-align:center;">
        <h2 style="margin:0 0 .5rem; font-size:1.6rem;">¿Tienes ropa que ya no usas?</h2>
        <p class="texto-suave" style="margin:0 0 1.5rem; font-size:1.05rem;">
          Únete a Vestige y convierte tu armario en efectivo
        </p>
        <div style="display:flex; gap:.8rem; justify-content:center; flex-wrap:wrap;">
          <a href="<?= BASE_URL ?>/registro.php" class="btn-primario">Crear cuenta</a>
          <a href="<?= BASE_URL ?>/login.php" class="btn">Iniciar sesión</a>
        </div>
      </div>
    </section>
  <?php endif; ?>

</main>

<style>
  .card:hover {
    transform: translateY(-4px);
    border-color: rgba(167,139,250,.3);
  }
  
  a[href*="explorar.php?cat"]:hover {
    transform: translateY(-4px);
    border-color: rgba(167,139,250,.3);
  }
  
  @media (max-width: 768px) {
    .hero {
      min-height: 320px !important;
    }
    .hero h1 {
      font-size: 1.8rem !important;
    }
    .hero p {
      font-size: 1rem !important;
    }
  }
</style>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
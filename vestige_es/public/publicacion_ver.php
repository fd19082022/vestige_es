<?php
// public/publicacion_ver.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

$pdo = DB::conn();
$id  = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

// Publicación + joins (LEFT para campos opcionales)
$sql = "
SELECT p.id, p.titulo, p.descripcion, p.precio_bs, p.condicion,
       p.categoria_id, c.nombre AS categoria,
       p.subcategoria_id, sc.nombre AS subcategoria,
       p.talla_id, t.nombre AS talla,
       p.color_id, co.nombre AS color,
       p.vendedor_id, u.nombre AS vendedor, u.id AS vendedor_id
FROM publicaciones p
JOIN categorias c ON c.id = p.categoria_id
LEFT JOIN subcategorias sc ON sc.id = p.subcategoria_id
LEFT JOIN tallas t ON t.id = p.talla_id
LEFT JOIN colores co ON co.id = p.color_id
JOIN usuarios u ON u.id = p.vendedor_id
WHERE p.id = ?
LIMIT 1";
$st = $pdo->prepare($sql);
$st->execute([$id]);
$pub = $st->fetch(PDO::FETCH_ASSOC);
if (!$pub) {
    Helper::flash_mensaje('Publicación no encontrada.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

// Imágenes
$imgs = $pdo->prepare("
    SELECT ruta, es_principal
    FROM publicaciones_imagenes
    WHERE publicacion_id = ?
    ORDER BY es_principal DESC, id ASC
");
$imgs->execute([$id]);
$imagenes = $imgs->fetchAll(PDO::FETCH_ASSOC);

// CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

// util imagen
function asset_url(string $relative): string {
    $r = trim($relative);
    if ($r === '') return BASE_URL . '/assets/imgs/no-image.png';
    if (preg_match('~^(https?:)?//|^data:image/~i', $r)) return $r;
    $r = ltrim($r, '/');
    if (str_starts_with($r, 'public/')) $r = substr($r, 7);
    $abs = __DIR__ . '/' . $r;
    return is_file($abs) ? (BASE_URL . '/' . $r) : (BASE_URL . '/assets/imgs/no-image.png');
}

require_once __DIR__ . '/../templates/header.php';
?>
<style>
  :root{
    --accent:#3A80BA; --accent-2:#4F9BD9; --brand:#8E3D56;
    --text:#222; --muted:#6b6b6b; --line:#e6dfe4; --bg:#faf8fa;
  }
  .pv-wrap{max-width:1120px;margin:18px auto;padding:0 14px}
  .pv-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:22px}

  /* Galería */
  .pv-gallery{display:grid;gap:12px}
  .pv-main{border-radius:14px;overflow:hidden;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,.07)}
  .pv-main img{width:100%;height:520px;object-fit:cover;display:block}
  .pv-thumbs{display:grid;grid-template-columns:repeat(auto-fit,minmax(80px,1fr));gap:8px}
  .pv-thumb{border:2px solid transparent;border-radius:10px;overflow:hidden;cursor:pointer;background:#fff;transition:.15s}
  .pv-thumb:hover{transform:translateY(-1px)}
  .pv-thumb.active{border-color:var(--accent)}
  .pv-thumb img{width:100%;height:80px;object-fit:cover;display:block}

  /* Info */
  .pv-card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 8px 18px rgba(0,0,0,.05)}
  .pv-head{padding:14px 16px;border-bottom:1px solid #eee;background:var(--bg)}
  .pv-title{margin:0;color:var(--accent);font-weight:800;letter-spacing:.2px}
  .pv-price{color:var(--brand);font-size:1.6rem;font-weight:800;margin-top:6px}

  .pv-body{padding:16px}
  .pv-badges{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 8px}
  .badge{padding:6px 10px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:.93rem}
  .badge.muted{color:var(--muted)}

  .pv-warning{margin-top:10px;padding:10px 12px;border:1px solid #f0d6d6;background:#fff7f7;color:#8a2c2c;border-radius:10px}

  .pv-meta{margin:12px 0;color:var(--text)}
  .pv-meta dt{font-weight:700;margin-top:8px}
  .pv-meta dd{margin:0 0 8px 0;color:var(--muted)}

  /* Oferta */
  .pv-offer{margin-top:16px;padding:14px;border:1px solid var(--line);border-radius:12px;background:#fff}
  .pv-offer h3{margin:0 0 10px;color:var(--accent)}
  .pv-offer .row{display:grid;grid-template-columns:1fr;gap:10px}
  .pv-offer input[type="number"], .pv-offer textarea{
    width:100%;padding:10px 12px;border:1px solid #d9c8cf;border-radius:10px;background:#fff;outline:none
  }
  .pv-offer input:focus,.pv-offer textarea:focus{border-color:var(--accent-2);box-shadow:0 0 0 3px rgba(79,155,217,.15)}

  .pv-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
  .btn-primario{background:var(--accent);color:#fff;padding:10px 16px;border:none;border-radius:10px;cursor:pointer;font-weight:700}
  .btn-primario:hover{background:var(--accent-2)}
  .btn{border:1px solid #d9c8cf;border-radius:10px;padding:10px 16px;text-decoration:none;color:#333;background:#fff}
  .btn:hover{background:#f2e9ed}

  @media(max-width:900px){
    .pv-grid{grid-template-columns:1fr}
    .pv-main img{height:360px}
  }
</style>

<main class="pv-wrap">
  <div class="pv-grid">
    <!-- Galería -->
    <section class="pv-gallery">
      <div class="pv-main">
        <?php $imgPrincipal = $imagenes[0]['ruta'] ?? ''; ?>
        <img id="pvMainImg" src="<?= asset_url($imgPrincipal) ?>" alt="">
      </div>
      <?php if ($imagenes): ?>
        <div class="pv-thumbs" id="pvThumbs">
          <?php foreach ($imagenes as $i => $im): $src = asset_url($im['ruta']); ?>
            <div class="pv-thumb<?= $i===0?' active':'' ?>" data-src="<?= htmlspecialchars($src) ?>">
              <img src="<?= $src ?>" alt="">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Info + Oferta -->
    <section class="pv-card">
      <div class="pv-head">
        <h1 class="pv-title"><?= htmlspecialchars($pub['titulo']) ?></h1>
        <div class="pv-price"><?= number_format((float)$pub['precio_bs'], 2) ?> Bs</div>
      </div>

      <div class="pv-body">
        <div class="pv-badges">
          <span class="badge"><?= htmlspecialchars($pub['categoria']) ?></span>
          <?php if (!empty($pub['subcategoria'])): ?>
            <span class="badge"><?= htmlspecialchars($pub['subcategoria']) ?></span>
          <?php endif; ?>
          <span class="badge"><?= htmlspecialchars(str_replace('_',' ', $pub['condicion'])) ?></span>
          <?php if (!empty($pub['talla'])): ?><span class="badge">Talla: <?= htmlspecialchars($pub['talla']) ?></span><?php endif; ?>
          <?php if (!empty($pub['color'])): ?><span class="badge">Color: <?= htmlspecialchars($pub['color']) ?></span><?php endif; ?>
        </div>

        <?php $sinTalla=empty($pub['talla']); $sinColor=empty($pub['color']); if ($sinTalla||$sinColor): ?>
          <div class="pv-warning">
            <?php if ($sinTalla && $sinColor): ?>
              El vendedor no especificó <strong>talla</strong> ni <strong>color</strong> para esta prenda.
            <?php elseif ($sinTalla): ?>
              El vendedor no especificó <strong>talla</strong>.
            <?php else: ?>
              El vendedor no especificó <strong>color</strong>.
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <dl class="pv-meta">
          <dt>Descripción</dt>
          <dd><?= nl2br(htmlspecialchars($pub['descripcion'] ?: 'Sin descripción.')) ?></dd>
          <dt>Vendedor</dt>
          <dd><?= htmlspecialchars($pub['vendedor']) ?></dd>
        </dl>

        <!-- === OFERTAR === -->
        <div class="pv-offer">
          <h3>Ofertar</h3>
          <form action="<?= BASE_URL ?>/oferta_crear.php" method="post" class="row" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="publicacion_id" value="<?= (int)$pub['id'] ?>">

            <label>
              Monto de tu oferta (Bs)
              <input type="number" name="precio_oferta" step="0.01" min="0.01" placeholder="Ej: 95.00" required>
            </label>

            <label>
              Mensaje para el vendedor (opcional)
              <textarea name="mensaje" rows="3" placeholder="Ej: ¿Podrías enviarme más fotos?"></textarea>
            </label>

            <div class="pv-actions">
              <button type="submit" class="btn-primario">Enviar oferta</button>

              <!-- ✅ Chat que inicia/reusa conversación y manda saludo con el título -->
           <a class="btn"
   href="<?= BASE_URL ?>/chat_iniciar.php?publicacion_id=<?= (int)$pub['id'] ?>&mensaje=Hola,%20me%20interesa%20tu%20publicaci%C3%B3n%20'<?= urlencode($pub['titulo']) ?>'">
  Abrir chat con el vendedor
</a>

            </div>
          </form>
        </div>

        <div class="pv-actions" style="margin-top:10px;">
          <a class="btn" href="<?= BASE_URL ?>/index.php">Volver al inicio</a>
        </div>
      </div>
    </section>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded',function(){
  var main=document.getElementById('pvMainImg'); var thumbs=document.getElementById('pvThumbs');
  if(!main||!thumbs) return;
  thumbs.addEventListener('click',function(e){
    var t=e.target.closest('.pv-thumb'); if(!t) return;
    var src=t.getAttribute('data-src'); if(!src) return;
    main.src=src; thumbs.querySelectorAll('.pv-thumb').forEach(function(x){x.classList.remove('active')}); t.classList.add('active');
  });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

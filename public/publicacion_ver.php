<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Favorito.php';

$pdo = DB::conn();
$id  = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

// Publicación + joins
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

// CSRF - usando csrf (sin _token) para compatibilidad con favorito_toggle
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// Función para imágenes
function asset_url(string $relative): string {
    $r = trim($relative);
    if ($r === '') return BASE_URL . '/assets/imgs/no-image.png';
    if (preg_match('~^(https?:)?//|^data:image/~i', $r)) return $r;
    $r = ltrim($r, '/');
    if (str_starts_with($r, 'public/')) $r = substr($r, 7);
    $abs = __DIR__ . '/' . $r;
    return is_file($abs) ? (BASE_URL . '/' . $r) : (BASE_URL . '/assets/imgs/no-image.png');
}

$flash = Helper::getFlash();
require_once __DIR__ . '/../templates/header.php';
?>

<main class="contenedor principal">
  
  <?php if (!empty($flash)): ?>
    <?php foreach ($flash as $f): ?>
      <div class="alerta alerta--<?php echo $f['tipo']; ?>">
        <?php echo htmlspecialchars($f['mensaje']); ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:1.5rem; margin-top:1rem;">
    
    <!-- Galería de imágenes -->
    <section>
      <div style="border-radius:14px; overflow:hidden; background:var(--card); border:1px solid rgba(255,255,255,.06); margin-bottom:1rem;">
        <?php $imgPrincipal = $imagenes[0]['ruta'] ?? ''; ?>
        <img id="mainImg" 
             src="<?= asset_url($imgPrincipal) ?>" 
             alt="<?= htmlspecialchars($pub['titulo']) ?>"
             style="width:100%; height:500px; object-fit:cover; display:block;">
      </div>
      
      <?php if (count($imagenes) > 1): ?>
        <div id="thumbs" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(100px,1fr)); gap:.8rem;">
          <?php foreach ($imagenes as $i => $im): $src = asset_url($im['ruta']); ?>
            <div class="thumb<?= $i===0?' active':'' ?>" 
                 data-src="<?= htmlspecialchars($src) ?>"
                 style="border:2px solid <?= $i===0?'var(--primary)':'rgba(255,255,255,.06)' ?>; border-radius:10px; overflow:hidden; cursor:pointer; transition:.2s;">
              <img src="<?= $src ?>" alt="" style="width:100%; height:100px; object-fit:cover; display:block;">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Info de la publicación -->
    <section>
      <div class="tarjeta" style="padding:1.2rem;">
        <h1 style="margin:0 0 .5rem; font-size:1.8rem; color:var(--text);">
          <?= htmlspecialchars($pub['titulo']) ?>
        </h1>
        <div style="font-size:2rem; font-weight:800; color:var(--primary); margin:.5rem 0 1rem;">
          <?= number_format((float)$pub['precio_bs'], 2) ?> Bs
        </div>

        <!-- Badges -->
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin:1rem 0;">
          <span class="badge"><?= htmlspecialchars($pub['categoria']) ?></span>
          <?php if (!empty($pub['subcategoria'])): ?>
            <span class="badge"><?= htmlspecialchars($pub['subcategoria']) ?></span>
          <?php endif; ?>
          <span class="badge"><?= htmlspecialchars(str_replace('_',' ', $pub['condicion'])) ?></span>
          <?php if (!empty($pub['talla'])): ?>
            <span class="badge">Talla: <?= htmlspecialchars($pub['talla']) ?></span>
          <?php endif; ?>
          <?php if (!empty($pub['color'])): ?>
            <span class="badge">Color: <?= htmlspecialchars($pub['color']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Advertencia si falta talla o color -->
        <?php $sinTalla=empty($pub['talla']); $sinColor=empty($pub['color']); if ($sinTalla||$sinColor): ?>
          <div class="alerta alerta--info" style="margin:.8rem 0;">
            <?php if ($sinTalla && $sinColor): ?>
              El vendedor no especificó <strong>talla</strong> ni <strong>color</strong>.
            <?php elseif ($sinTalla): ?>
              El vendedor no especificó <strong>talla</strong>.
            <?php else: ?>
              El vendedor no especificó <strong>color</strong>.
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Descripción -->
        <div style="margin:1.2rem 0;">
          <h3 style="margin:0 0 .5rem; font-size:1.1rem;">Descripción</h3>
          <p class="texto-suave" style="line-height:1.6;">
            <?= nl2br(htmlspecialchars($pub['descripcion'] ?: 'Sin descripción.')) ?>
          </p>
        </div>

        <!-- Vendedor -->
        <div style="margin:1rem 0; padding:1rem; background:rgba(255,255,255,.02); border-radius:10px; border:1px solid rgba(255,255,255,.06);">
          <strong>Vendedor:</strong> <?= htmlspecialchars($pub['vendedor']) ?>
        </div>
      </div>

      <!-- Ofertar -->
      <div class="tarjeta" style="padding:1.2rem; margin-top:1rem;">
        <h3 style="margin:0 0 1rem; font-size:1.2rem;">Ofertar</h3>
        <form action="<?= BASE_URL ?>/oferta_crear.php" method="post" class="formulario">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="publicacion_id" value="<?= (int)$pub['id'] ?>">

          <label>
            <span style="display:block; margin-bottom:.3rem;">Monto de tu oferta (Bs)</span>
            <input type="number" name="precio_oferta" step="0.01" min="0.01" placeholder="Ej: 95.00" required>
          </label>

          <label>
            <span style="display:block; margin-bottom:.3rem;">Mensaje para el vendedor (opcional)</span>
            <textarea name="mensaje" rows="3" placeholder="Ej: ¿Podrías enviarme más fotos?"></textarea>
          </label>

          <div style="display:flex; gap:.6rem; flex-wrap:wrap; margin-top:1rem;">
            <button type="submit" class="btn-primario">Enviar oferta</button>
          </div>
        </form>

       <!-- Chat separado -->
<form action="<?= BASE_URL ?>/chat_iniciar.php" method="post" style="margin-top:.8rem;">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="publicacion_id" value="<?= (int)$pub['id'] ?>">
  <input type="hidden" name="mensaje" value="Hola, me interesa tu publicación '<?= htmlspecialchars($pub['titulo']) ?>'">
  <button type="submit" class="btn-secundario" style="width:100%; background:var(--secondary); color:#fff; font-weight:700;">
    💬 Abrir chat con el vendedor
  </button>
</form>
        </form>

        <!-- Favoritos -->
        <?php if (Helper::esta_logueado()): ?>
          <?php $es_favorito = Favorito::esFavorito((int)$_SESSION['usuario_id'], (int)$pub['id']); ?>
          <form action="<?= BASE_URL ?>/favorito_toggle.php" method="post" style="margin-top:1rem;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="publicacion_id" value="<?= (int)$pub['id'] ?>">
            <button type="submit" class="<?= $es_favorito ? 'btn-favorito-quitar' : 'btn-secundario' ?>" style="width:100%; font-weight:700; <?= !$es_favorito ? 'background:var(--accent); color:#fff;' : '' ?>">
              <?= $es_favorito ? '❤️ Quitar de Favoritos' : '🤍 Agregar a Favoritos' ?>
            </button>
          </form>
        <?php endif; ?>
      </div>

      <div style="margin-top:1rem;">
        <a class="btn" href="<?= BASE_URL ?>/index.php" style="width:100%; text-align:center; display:block;">
          Volver al inicio
        </a>
      </div>
    </section>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var main = document.getElementById('mainImg');
  var thumbs = document.getElementById('thumbs');
  if (!main || !thumbs) return;
  
  thumbs.addEventListener('click', function(e) {
    var thumb = e.target.closest('.thumb');
    if (!thumb) return;
    
    var src = thumb.getAttribute('data-src');
    if (!src) return;
    
    main.src = src;
    
    // Actualizar estilos de thumbnails
    thumbs.querySelectorAll('.thumb').forEach(function(t) {
      t.style.borderColor = 'rgba(255,255,255,.06)';
    });
    thumb.style.borderColor = 'var(--primary)';
  });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
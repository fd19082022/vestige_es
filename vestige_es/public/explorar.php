<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat     = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$cond    = isset($_GET['condicion']) ? trim($_GET['condicion']) : '';
$min     = (isset($_GET['min']) && $_GET['min'] !== '') ? (float)$_GET['min'] : null;
$max     = (isset($_GET['max']) && $_GET['max'] !== '') ? (float)$_GET['max'] : null;
$subasta = isset($_GET['subasta']) ? 1 : null; 

$where = [];
$params = [];


if ($q !== '') {
    $where[] = "(p.titulo LIKE ? OR p.descripcion LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

// Categoría
if ($cat > 0) {
    $where[] = "p.categoria_id = ?";
    $params[] = $cat;
}

// Condición
if ($cond !== '') {
    $where[] = "p.condicion = ?";
    $params[] = $cond;
}

// Rango de precio
if ($min !== null && $max !== null) {
    $where[] = "(p.precio_bs BETWEEN ? AND ?)";
    $params[] = $min;
    $params[] = $max;
} else if ($min !== null) {
    $where[] = "p.precio_bs >= ?";
    $params[] = $min;
} else if ($max !== null) {
    $where[] = "p.precio_bs <= ?";
    $params[] = $max;
}

// Solo subastas
if ($subasta === 1) {
    $where[] = "p.es_subasta = 1";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';


$sql = "
SELECT
  p.*,
  c.nombre  AS categoria,
  sc.nombre AS subcategoria,
  t.nombre  AS talla,
  co.nombre AS color,
  ep.nombre AS estado,
  img.ruta  AS imagen
FROM publicaciones p
LEFT JOIN (
  SELECT i.publicacion_id, i.ruta
  FROM publicaciones_imagenes i
  INNER JOIN (
    SELECT publicacion_id, MIN(id) AS min_id
    FROM publicaciones_imagenes
    GROUP BY publicacion_id
  ) x ON x.publicacion_id = i.publicacion_id AND x.min_id = i.id
) img ON img.publicacion_id = p.id
JOIN categorias c           ON c.id = p.categoria_id
LEFT JOIN subcategorias sc  ON sc.id = p.subcategoria_id
LEFT JOIN tallas t          ON t.id = p.talla_id
LEFT JOIN colores co        ON co.id = p.color_id
JOIN estados_publicacion ep ON ep.id = p.estado_id
{$whereSql}
ORDER BY p.creado_en DESC, p.id DESC
LIMIT 60
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$publicaciones = $stmt->fetchAll();


$cats = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll();

$flash = Helper::obtener_flash();
require_once __DIR__ . '/../templates/header.php';
?>

<main class="contenedor principal">
  <h1>Explorar</h1>

  <form method="get" class="filtros" style="display:grid; gap:.6rem; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); margin-bottom:1rem;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar..." />
    <select name="cat">
      <option value="0">Todas las categorías</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="condicion">
      <option value="">Cualquier condición</option>
      <?php foreach (['Nuevo','Usado - Excelente','Usado - Bueno','Usado - Aceptable'] as $op): ?>
        <option value="<?= htmlspecialchars($op) ?>" <?= $cond === $op ? 'selected' : '' ?>><?= htmlspecialchars($op) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" step="0.01" name="min" value="<?= $min !== null ? htmlspecialchars($min) : '' ?>" placeholder="Precio mín. Bs">
    <input type="number" step="0.01" name="max" value="<?= $max !== null ? htmlspecialchars($max) : '' ?>" placeholder="Precio máx. Bs">
    <label style="display:flex; align-items:center; gap:.4rem;">
      <input type="checkbox" name="subasta" value="1" <?= $subasta === 1 ? 'checked' : '' ?>>
      Solo subastas
    </label>
    <button class="btn-primario" type="submit">Filtrar</button>
  </form>

  <?php if (!empty($publicaciones)): ?>
  <div class="grid" style="display:grid; gap:1rem; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
    <?php foreach ($publicaciones as $p): ?>
      <article class="card" style="border:1px solid #eee; border-radius:12px; overflow:hidden;">
        <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$p['id'] ?>" style="color:inherit; text-decoration:none; display:block;">
          <?php if (!empty($p['imagen'])): ?>
          <div class="card__media" style="aspect-ratio: 4/3; background:#fafafa; display:flex; align-items:center; justify-content:center; overflow:hidden;">
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['titulo']) ?>" style="width:100%; height:100%; object-fit:cover;">
          </div>
          <?php endif; ?>
          <div class="card__body" style="padding:.8rem;">
            <h3 class="card__title" style="margin:.2rem 0 .4rem; font-size:1rem; line-height:1.3;">
              <?= htmlspecialchars($p['titulo']) ?>
            </h3>
            <p class="card__precio" style="margin:0; font-weight:600;">Bs <?= number_format((float)$p['precio_bs'], 2, '.', ',') ?></p>
            <p class="card__categoria" style="margin:.2rem 0; font-size:.9rem; opacity:.8;">
              <?= htmlspecialchars($p['categoria'] ?? '') ?>
              <?php if (!empty($p['condicion'])): ?>
                · <?= htmlspecialchars($p['condicion']) ?>
              <?php endif; ?>
              <?= !empty($p['es_subasta']) ? ' · Subasta' : '' ?>
            </p>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p class="texto-suave">No se encontraron productos con los filtros elegidos.</p>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

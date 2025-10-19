<?php
require_once __DIR__ . '/_auth.php';
ensure_csrf_token();
$pdo = pdo();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$estado_id = isset($_GET['estado_id']) && $_GET['estado_id'] !== '' ? (int)$_GET['estado_id'] : null;

$sql = "SELECT p.id, p.titulo, p.precio_bs, p.estado_id, p.es_subasta, p.fecha_fin_subasta,
               u.nombre AS vendedor, c.nombre AS categoria,
               (SELECT ruta FROM publicaciones_imagenes i WHERE i.publicacion_id = p.id AND i.es_principal=1 ORDER BY i.id ASC LIMIT 1) AS img
        FROM publicaciones p
        JOIN usuarios u ON u.id = p.vendedor_id
        JOIN categorias c ON c.id = p.categoria_id
        WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (p.titulo LIKE ? OR u.nombre LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like;
}
if ($estado_id !== null) {
    $sql .= " AND p.estado_id = ?";
    $params[] = $estado_id;
}
$sql .= " ORDER BY p.id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estados = $pdo->query("SELECT id, nombre FROM estados_publicacion ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Publicaciones | Admin Vestige</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="header">
    <h1>Publicaciones</h1>
    <nav class="nav">
      <a href="index.php">Inicio</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="publicaciones.php" class="active">Publicaciones</a>
      <a href="ofertas.php">Ofertas</a>
    </nav>
  </header>

  <section class="card">
    <form class="searchbar" method="get">
      <input type="text" name="q" placeholder="Buscar título/vendedor" value="<?= h($q) ?>">
      <select name="estado_id">
        <option value="">Todos los estados</option>
        <?php foreach ($estados as $id=>$nom): ?>
          <option value="<?= (int)$id ?>" <?= ($estado_id===(int)$id)?'selected':'' ?>>
            <?= (int)$id ?> — <?= h($nom) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="btn">Filtrar</button>
      <a class="btn" href="publicaciones.php">Limpiar</a>
    </form>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th><th>Imagen</th><th>Título</th><th>Vendedor</th><th>Categoría</th><th>Precio (Bs)</th><th>Estado</th><th>Subasta</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($pubs as $p): ?>
          <tr>
            <td><?= (int)$p['id'] ?></td>
            <td><?php if ($p['img']): ?><img src="../<?= h($p['img']) ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:.5rem;border:1px solid #1f2937"><?php endif; ?></td>
            <td><?= h($p['titulo']) ?></td>
            <td><?= h($p['vendedor']) ?></td>
            <td><?= h($p['categoria']) ?></td>
            <td><?= number_format((float)$p['precio_bs'], 2, '.', ',') ?></td>
            <td><span class="badge"><?= (int)$p['estado_id'] ?> — <?= h($estados[(int)$p['estado_id']] ?? 'N/A') ?></span></td>
            <td><?= (int)$p['es_subasta'] ? '<span class="badge warn">Sí</span>' : '<span class="badge">No</span>' ?></td>
            <td class="row-actions">
              <form class="inline" method="post" action="actions.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="pub_estado">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <select name="estado_id">
                  <?php foreach ($estados as $id=>$nom): ?>
                    <option value="<?= (int)$id ?>" <?= ((int)$p['estado_id']===(int)$id)?'selected':'' ?>><?= (int)$id ?> — <?= h($nom) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn small">Aplicar</button>
              </form>

              <form class="inline" method="post" action="actions.php" onsubmit="return confirm('¿Eliminar publicación #<?= (int)$p['id'] ?>? Esta acción es irreversible.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="pub_eliminar">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="btn small err">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <footer class="footer"><p>Total: <?= count($pubs) ?> publicaciones.</p></footer>
</body>
</html>

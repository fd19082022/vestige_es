<?php
require_once __DIR__ . '/_auth.php';
ensure_csrf_token();
$pdo = pdo();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

$sql = "SELECT o.id, o.publicacion_id, o.comprador_id, o.precio_ofrecido, o.estado, o.creado_en,
               p.titulo, u.nombre AS comprador
        FROM ofertas o
        JOIN publicaciones p ON p.id = o.publicacion_id
        JOIN usuarios u ON u.id = o.comprador_id
        WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (p.titulo LIKE ? OR u.nombre LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like;
}
if ($estado !== '') {
    $sql .= " AND o.estado = ?";
    $params[] = $estado;
}
$sql .= " ORDER BY o.id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$estados = ['pendiente','aceptada','rechazada'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ofertas | Admin Vestige</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="header">
    <h1>Ofertas</h1>
    <nav class="nav">
      <a href="index.php">Inicio</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="publicaciones.php">Publicaciones</a>
      <a href="ofertas.php" class="active">Ofertas</a>
    </nav>
  </header>

  <section class="card">
    <form class="searchbar" method="get">
      <input type="text" name="q" placeholder="Buscar por título/comprador" value="<?= h($q) ?>">
      <select name="estado">
        <option value="">Todos los estados</option>
        <?php foreach ($estados as $e): ?>
          <option value="<?= $e ?>" <?= $estado===$e?'selected':'' ?>><?= $e ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn">Filtrar</button>
      <a class="btn" href="ofertas.php">Limpiar</a>
    </form>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th><th>Publicación</th><th>Comprador</th><th>Precio Ofrecido</th><th>Estado</th><th>Creado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td>#<?= (int)$r['publicacion_id'] ?> — <?= h($r['titulo']) ?></td>
            <td>#<?= (int)$r['comprador_id'] ?> — <?= h($r['comprador']) ?></td>
            <td><?= number_format((float)$r['precio_ofrecido'], 2, '.', ',') ?></td>
            <td><span class="badge"><?= h($r['estado']) ?></span></td>
            <td><?= h($r['creado_en']) ?></td>
            <td class="row-actions">
              <form class="inline" method="post" action="actions.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="oferta_estado">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <select name="estado">
                  <?php foreach ($estados as $e): ?>
                    <option value="<?= $e ?>" <?= $r['estado']===$e?'selected':'' ?>><?= $e ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn small">Aplicar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <footer class="footer"><p>Total: <?= count($rows) ?> ofertas.</p></footer>
</body>
</html>

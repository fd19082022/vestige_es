<?php
require_once __DIR__ . '/_auth.php';
ensure_csrf_token();
$pdo = pdo();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

$sql = "SELECT u.id, u.nombre, u.apellido, u.correo, u.telefono, u.estado, u.rol_id, r.nombre AS rol
        FROM usuarios u
        LEFT JOIN roles r ON r.id = u.rol_id
        WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($estado !== '') {
    $sql .= " AND u.estado = ?";
    $params[] = $estado;
}
$sql .= " ORDER BY u.id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estados_validos = ['activo','suspendido','eliminado'];
$roles = $pdo->query("SELECT id, nombre FROM roles ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Usuarios | Admin Vestige</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="header">
    <h1>Usuarios</h1>
    <nav class="nav">
      <a href="index.php">Inicio</a>
      <a href="usuarios.php" class="active">Usuarios</a>
      <a href="publicaciones.php">Publicaciones</a>
      <a href="ofertas.php">Ofertas</a>
    </nav>
  </header>

  <section class="card">
    <form class="searchbar" method="get">
      <input type="text" name="q" placeholder="Buscar nombre/correo" value="<?= h($q) ?>">
      <select name="estado">
        <option value="">Todos los estados</option>
        <?php foreach (['activo','suspendido','eliminado'] as $e): ?>
          <option value="<?= $e ?>" <?= $estado===$e?'selected':'' ?>><?= $e ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn">Filtrar</button>
      <a class="btn" href="usuarios.php">Limpiar</a>
    </form>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Rol</th><th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= h($u['nombre'] . ' ' . $u['apellido']) ?></td>
            <td><?= h($u['correo']) ?></td>
            <td><?= h($u['telefono']) ?></td>
            <td><span class="badge"><?= (int)$u['rol_id'] ?> — <?= h($u['rol'] ?? 'N/A') ?></span></td>
            <td>
              <?php $cls = $u['estado']==='activo'?'ok':($u['estado']==='suspendido'?'warn':'err'); ?>
              <span class="badge <?= $cls ?>"><?= h($u['estado']) ?></span>
            </td>
            <td class="row-actions">
              <form class="inline" method="post" action="actions.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="usuario_estado">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <select name="estado">
                  <?php foreach ($estados_validos as $e): ?>
                    <option value="<?= $e ?>" <?= $u['estado']===$e?'selected':'' ?>><?= $e ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn small warn">Guardar</button>
              </form>

              <form class="inline" method="post" action="actions.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="usuario_rol">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <select name="rol_id">
                  <?php foreach ($roles as $rid=>$rnom): ?>
                    <option value="<?= (int)$rid ?>" <?= ((int)$u['rol_id']===(int)$rid)?'selected':'' ?>><?= (int)$rid ?> — <?= h($rnom) ?></option>
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

  <footer class="footer"><p>Total: <?= count($users) ?> usuarios.</p></footer>
</body>
</html>

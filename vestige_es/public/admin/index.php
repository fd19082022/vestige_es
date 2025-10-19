<?php
require_once __DIR__ . '/_auth.php';
ensure_csrf_token();
$pdo = pdo();
$stats = [
  'usuarios' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
  'publicaciones' => (int)$pdo->query("SELECT COUNT(*) FROM publicaciones")->fetchColumn(),
  'ofertas' => (int)$pdo->query("SELECT COUNT(*) FROM ofertas")->fetchColumn(),
  'mensajes' => (int)$pdo->query("SELECT COUNT(*) FROM mensajes")->fetchColumn(),
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Vestige</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="header">
    <h1>Panel de Administración — Vestige</h1>
    <nav class="nav">
      <a href="index.php" class="active">Inicio</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="publicaciones.php">Publicaciones</a>
      <a href="ofertas.php">Ofertas</a>
    </nav>
  </header>

  <section class="card">
    <h2>Resumen</h2>
    <div class="table-wrap">
      <table class="table">
        <tr><th>Usuarios</th><td><?= (int)$stats['usuarios'] ?></td></tr>
        <tr><th>Publicaciones</th><td><?= (int)$stats['publicaciones'] ?></td></tr>
        <tr><th>Ofertas</th><td><?= (int)$stats['ofertas'] ?></td></tr>
        <tr><th>Mensajes</th><td><?= (int)$stats['mensajes'] ?></td></tr>
      </table>
    </div>
  </section>

  <footer class="footer">
    <p>Acceso: <?= is_admin_by_email() ? 'Admin por correo' : (is_admin_by_role() ? 'Admin por rol' : 'Usuario') ?> · <a class="btn small" href="../index.php">Volver</a></p>
  </footer>
</body>
</html>

<?php
require_once __DIR__ . '/_auth.php';
$pdo = pdo();

// Estadísticas
$stats = [
    'usuarios' => $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'usuarios_activos' => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado='activo'")->fetchColumn(),
    'publicaciones' => $pdo->query("SELECT COUNT(*) FROM publicaciones")->fetchColumn(),
    'publicaciones_activas' => $pdo->query("SELECT COUNT(*) FROM publicaciones WHERE estado_id=2")->fetchColumn(),
    'ofertas' => $pdo->query("SELECT COUNT(*) FROM ofertas")->fetchColumn(),
    'ofertas_pendientes' => $pdo->query("SELECT COUNT(*) FROM ofertas WHERE estado='pendiente'")->fetchColumn(),
    'conversaciones' => $pdo->query("SELECT COUNT(*) FROM conversaciones")->fetchColumn(),
    'favoritos' => $pdo->query("SELECT COUNT(*) FROM favoritos")->fetchColumn(),
];

// Últimas actividades
$ultimos_usuarios = $pdo->query("
    SELECT id, nombre, apellido, correo, estado, creado_en 
    FROM usuarios 
    ORDER BY id DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$ultimas_publicaciones = $pdo->query("
    SELECT p.id, p.titulo, p.precio_bs, p.estado_id, u.nombre AS vendedor, p.creado_en
    FROM publicaciones p
    JOIN usuarios u ON u.id = p.vendedor_id
    ORDER BY p.id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel Admin | Vestige</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="header">
    <h1>🛡️ Panel de Administración - Vestige</h1>
    <nav class="nav">
      <a href="index.php" class="active">Inicio</a>
      <a href="usuarios.php">Usuarios</a>
      <a href="publicaciones.php">Publicaciones</a>
      <a href="ofertas.php">Ofertas</a>
      <a href="../index.php" style="margin-left: auto;">← Volver al sitio</a>
    </nav>
  </header>

  <section class="card">
    <h2>📊 Estadísticas Generales</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
      <div style="background: #0b1222; padding: 1.5rem; border-radius: 0.8rem; border: 1px solid #1f2937;">
        <h3 style="margin: 0 0 0.5rem 0; color: #9ca3af; font-size: 0.9rem;">Usuarios</h3>
        <p style="margin: 0; font-size: 2rem; font-weight: bold; color: #8E3D56;"><?= number_format($stats['usuarios']) ?></p>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #9ca3af;">
          <?= number_format($stats['usuarios_activos']) ?> activos
        </p>
      </div>

      <div style="background: #0b1222; padding: 1.5rem; border-radius: 0.8rem; border: 1px solid #1f2937;">
        <h3 style="margin: 0 0 0.5rem 0; color: #9ca3af; font-size: 0.9rem;">Publicaciones</h3>
        <p style="margin: 0; font-size: 2rem; font-weight: bold; color: #AB4E6A;"><?= number_format($stats['publicaciones']) ?></p>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #9ca3af;">
          <?= number_format($stats['publicaciones_activas']) ?> activas
        </p>
      </div>

      <div style="background: #0b1222; padding: 1.5rem; border-radius: 0.8rem; border: 1px solid #1f2937;">
        <h3 style="margin: 0 0 0.5rem 0; color: #9ca3af; font-size: 0.9rem;">Ofertas</h3>
        <p style="margin: 0; font-size: 2rem; font-weight: bold; color: #f59e0b;"><?= number_format($stats['ofertas']) ?></p>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #9ca3af;">
          <?= number_format($stats['ofertas_pendientes']) ?> pendientes
        </p>
      </div>

      <div style="background: #0b1222; padding: 1.5rem; border-radius: 0.8rem; border: 1px solid #1f2937;">
        <h3 style="margin: 0 0 0.5rem 0; color: #9ca3af; font-size: 0.9rem;">Actividad</h3>
        <p style="margin: 0; font-size: 2rem; font-weight: bold; color: #16a34a;"><?= number_format($stats['conversaciones']) ?></p>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #9ca3af;">
          <?= number_format($stats['favoritos']) ?> favoritos
        </p>
      </div>
    </div>
  </section>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1rem;">
    <section class="card">
      <h2>👥 Últimos Usuarios Registrados</h2>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Estado</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ultimos_usuarios as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td>
                <?= h($u['nombre'] . ' ' . $u['apellido']) ?><br>
                <small style="color: var(--muted);"><?= h($u['correo']) ?></small>
              </td>
              <td>
                <?php $cls = $u['estado']==='activo'?'ok':($u['estado']==='suspendido'?'warn':'err'); ?>
                <span class="badge <?= $cls ?>"><?= h($u['estado']) ?></span>
              </td>
              <td><small><?= date('d/m/Y H:i', strtotime($u['creado_en'])) ?></small></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="margin-top: 1rem; text-align: center;">
        <a href="usuarios.php" class="btn">Ver todos los usuarios →</a>
      </div>
    </section>

    <section class="card">
      <h2>📦 Últimas Publicaciones</h2>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Título</th>
              <th>Precio</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ultimas_publicaciones as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td>
                <?= h($p['titulo']) ?><br>
                <small style="color: var(--muted);">por <?= h($p['vendedor']) ?></small>
              </td>
              <td><?= number_format((float)$p['precio_bs'], 2) ?> Bs</td>
              <td><small><?= date('d/m/Y H:i', strtotime($p['creado_en'])) ?></small></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="margin-top: 1rem; text-align: center;">
        <a href="publicaciones.php" class="btn">Ver todas las publicaciones →</a>
      </div>
    </section>
  </div>

  <footer class="footer">
    <p>Panel de Administración Vestige | Usuario: <?= h($_SESSION['usuario_nombre'] ?? 'Admin') ?> (<?= h($_SESSION['usuario_correo'] ?? '') ?>)</p>
  </footer>
</body>
</html>
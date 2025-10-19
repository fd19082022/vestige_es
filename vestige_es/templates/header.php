<?php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
$usuario = [
    'id' => $_SESSION['usuario_id'] ?? null,
    'nombre' => $_SESSION['usuario_nombre'] ?? null,
    'correo' => $_SESSION['usuario_correo'] ?? null,
];
$flash = Helper::obtener_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vestige</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="encabezado">
        <div class="contenedor encabezado__contenido">
            <a class="logo" href="<?= BASE_URL ?>/index.php">
                <img src="<?= BASE_URL ?>/assets/img/logo.svg" alt="Vestige Logo" />
                <span>Vestige</span>
            </a>
            <nav class="navegacion">
                <a href="<?= BASE_URL ?>/explorar.php">Explorar</a>
                <?php if ($usuario['id']): ?>
                    <a href="<?= BASE_URL ?>/publicacion_crear.php">Vender</a>
                    <a href="<?= BASE_URL ?>/favoritos.php">Favoritos</a>
                    <a href="<?= BASE_URL ?>/dashboard.php">Mi cuenta</a>
                    <a href="<?= BASE_URL ?>/perfil.php">Perfil</a>
                    <a class="btn-salir" href="<?= BASE_URL ?>/logout.php">Salir</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php">Entrar</a>
                    <a class="btn-primario" href="<?= BASE_URL ?>/registro.php">Crear cuenta</a>
                <?php endif; ?>
            <?php if (isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 1): ?>
  <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-admin" style="margin-left:8px;">
    Panel Admin
  </a>
<?php endif; ?>

            <?php if (!empty($_SESSION['usuario_id'])): ?>
                <a class="btn-secundario" href="<?= BASE_URL ?>/vendedor_panel.php">Panel vendedor</a>
            <?php endif; ?>
        </nav>
        </div>
    </header>

    <?php if (!empty($flash)): ?>
    <div class="contenedor">
        <?php foreach ($flash as $f): ?>
            <div class="alerta alerta--<?= $f['tipo'] ?>"><?= $f['mensaje'] ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <main class="contenedor principal">
<?php
session_start();
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Publicacion.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesiÃ³n.', 'error');
    Helper::redir(BASE_URL . '/login.php');
}

$usuario_id = Helper::usuario_actual_id();
if (!$usuario_id) {
    Helper::flash_mensaje('Debes iniciar sesiÃ³n.', 'error');
    Helper::redir(BASE_URL . '/login.php');
}

// âœ… Trae TODAS las publicaciones del usuario (sin filtrar por estado)
// Si solo quieres publicadas, usa: Publicacion::listarPorVendedor($usuario_id, ESTADO_PUBLICADA);
$mis = Publicacion::listarPorVendedor((int)$usuario_id, null);

require_once __DIR__ . '/../templates/header.php';
?>
<h2>Mi cuenta</h2>
<section class="panel">
    <div class="panel__item">
        <h3>Hola, <?= Helper::limpiar($_SESSION['usuario_nombre'] ?? '') ?> </h3>
        <p>Desde aquí puedes gestionar tus publicaciones.</p>
        <a class="btn-primario" href="<?= BASE_URL ?>/publicacion_crear.php">Crear publicación</a>
    </div>
</section>

<h3>Mis publicaciones</h3>

<?php if (empty($mis)): ?>
    <p>No tienes publicaciones todaví­a. ¡Crea la primera!</p>
<?php endif; ?>

<div class="grid tarjetas">
<?php foreach ($mis as $p): ?>
    <?php
        // Imagen: principal de publicaciones_imagenes (si existe), si no imagen_principal, si no ruta, si no demo
        $img = $p['principal_img'] ?? $p['imagen_principal'] ?? ($p['ruta'] ?? '');
        $src = rtrim(BASE_URL, '/') . '/assets/img/demo.jpg';
        if ($img) {
            if (strpos($img, 'http') === 0) {
                $src = $img;
            } else {
                $src = rtrim(BASE_URL, '/') . '/' . ltrim($img, '/');
            }
        }
        $precio = isset($p['precio_bs']) ? (float)$p['precio_bs'] : 0;
    ?>
    <article class="tarjeta">
        <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$p['id'] ?>">
            <img src="<?= Helper::limpiar($src) ?>" alt="PublicaciÃ³n">
            <div class="tarjeta__contenido">
                <h3><?= Helper::limpiar($p['titulo'] ?? '') ?></h3>
                <p class="precio"><?= number_format($precio, 2) ?> Bs</p>
                <p class="detalle">
                    <?= Helper::limpiar($p['categoria'] ?? '') ?>
                    <?php if (!empty($p['talla'])): ?> Â· <?= Helper::limpiar($p['talla']) ?><?php endif; ?>
                    <?php if (!empty($p['color'])): ?> Â· <?= Helper::limpiar($p['color']) ?><?php endif; ?>
                </p>
            </div>
        </a>
    </article>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
<?php

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Favorito.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
}
$lista = Favorito::listar_por_usuario($_SESSION['usuario_id']);

require_once __DIR__ . '/../templates/header.php';
?>
<h2>Mis favoritos</h2>
<div class="grid tarjetas">
<?php foreach ($lista as $p): ?>
    <article class="tarjeta">
        <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= $p['id'] ?>">
            <?php $img = $p['principal_img'] ?? $p['imagen_principal'] ?? ''; $src = $img ? ( (strpos($img,'http')===0 ? $img : (BASE_URL . '/' . ltrim($img,'/')) ) ) : (BASE_URL . '/assets/img/demo.jpg'); ?><img src="<?= $src ?>" alt="Publicación">
            <div class="tarjeta__contenido">
                <h3><?= Helper::limpiar($p['titulo']) ?></h3>
                <p class="precio"><?= number_format($p['precio_bs'], 2) ?> Bs</p>
                <p class="detalle"><?= Helper::limpiar($p['categoria'] ?? '') ?> · <?= Helper::limpiar($p['talla'] ?? '') ?> · <?= Helper::limpiar($p['color'] ?? '') ?></p>
            <div class="acciones" style="display:flex;gap:.5rem;margin-top:.5rem;">
  <form action="favorito_toggle.php" method="post">
    <input type="hidden" name="publicacion_id" value="<?= (int)$p['id'] ?>">
    <button type="submit" class="btn btn-favorito-quitar">Quitar de favoritos</button>
  </form>
</div>
</article>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
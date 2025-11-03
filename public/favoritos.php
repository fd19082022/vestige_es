<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Favorito.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
}

$lista = Favorito::listarPorUsuario($_SESSION['usuario_id']);

// Generar token CSRF si no existe
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

require_once __DIR__ . '/../templates/header.php';
?>
<main class="contenedor principal">
  <h2>Mis favoritos</h2>
  
  <?php if (empty($lista)): ?>
    <div class="empty">
      <h3>No tienes favoritos aún</h3>
      <p>Explora publicaciones y guarda tus prendas favoritas</p>
      <div class="actions">
        <a href="<?= BASE_URL ?>/explorar.php" class="btn-primario">Explorar publicaciones</a>
      </div>
    </div>
  <?php else: ?>
    <div class="grid tarjetas">
      <?php foreach ($lista as $p): ?>
        <article class="tarjeta">
          <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$p['id'] ?>">
            <?php 
              $img = $p['principal_img'] ?? $p['imagen_principal'] ?? ''; 
              $src = $img ? ((strpos($img,'http') === 0 ? $img : (BASE_URL . '/' . ltrim($img,'/')))) : (BASE_URL . '/assets/img/demo.jpg'); 
            ?>
            <img src="<?= htmlspecialchars($src) ?>" alt="Publicación">
            <div class="tarjeta__contenido">
              <h3><?= Helper::limpiar($p['titulo']) ?></h3>
              <p class="precio"><?= number_format($p['precio_bs'], 2) ?> Bs</p>
              <p class="detalle">
                <?= Helper::limpiar($p['categoria'] ?? '') ?> · 
                <?= Helper::limpiar($p['talla'] ?? '') ?> · 
                <?= Helper::limpiar($p['color'] ?? '') ?>
              </p>
            </div>
          </a>
          
          <div class="acciones" style="display:flex; gap:.5rem; margin-top:.5rem; padding:0 1rem 1rem;">
            <form action="<?= BASE_URL ?>/favorito_toggle.php" method="post" style="width:100%;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="publicacion_id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="btn-favorito-quitar" style="width:100%;">
                ❤️ Quitar de favoritos
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
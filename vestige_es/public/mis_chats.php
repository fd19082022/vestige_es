<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Conversacion.php';

if (!isset($_SESSION['usuario_id'])) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$convs = Conversacion::listarDelUsuario($usuario_id);

require_once __DIR__ . '/../templates/header.php';
$flash = Helper::obtener_flash();
?>
<main class="contenedor principal">
    <h1>Mis conversaciones</h1>
    <div class="grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1rem;">
        <?php foreach ($convs as $c): ?>
        <article class="card" style="padding:1rem; border:1px solid #eee; border-radius:12px;">
            <h3 style="margin:0 0 .5rem 0;"><?= Helper::limpiar($c['publicacion_titulo']) ?></h3>
            <p><strong>Con:</strong> <?= Helper::limpiar($c['otro_nombre']) ?></p>
            <a class="btn-primario" href="<?= BASE_URL ?>/chat.php?id=<?= (int)$c['id'] ?>">Abrir chat</a>
        </article>
        <?php endforeach; ?>
        <?php if (empty($convs)): ?>
            <p>No tienes conversaciones aún.</p>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
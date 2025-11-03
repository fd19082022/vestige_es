<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Oferta.php';
require_once __DIR__ . '/../src/Conversacion.php';

if (!isset($_SESSION['usuario_id'])) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
}
$usuario_id = (int)$_SESSION['usuario_id'];

$ofertas = Oferta::listarPorVendedor($usuario_id);
$convs   = Conversacion::listarDelUsuario($usuario_id);

require_once __DIR__ . '/../templates/header.php';
$flash = Helper::obtener_flash();
?>
<main class="contenedor principal">
    <h1>Panel de vendedor</h1>

    <section style="margin-top:1rem;">
        <h2>Ofertas recibidas</h2>
        <?php if (empty($ofertas)): ?>
            <p>No tienes ofertas por ahora.</p>
        <?php else: ?>
        <div class="tabla-responsiva" style="overflow-x:auto;">
            <table class="tabla" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom:1px solid #eee;">
                        <th style="padding:.6rem;">Fecha</th>
                        <th style="padding:.6rem;">Publicación</th>
                        <th style="padding:.6rem;">Comprador</th>
                        <th style="padding:.6rem;">Precio ofrecido (Bs)</th>
                        <th style="padding:.6rem;">Estado</th>
                        <th style="padding:.6rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ofertas as $o): ?>
                        <tr style="border-bottom:1px solid #f2f2f2;">
                            <td style="padding:.6rem;"><?= Helper::limpiar($o['creado_en']) ?></td>
                            <td style="padding:.6rem;"><?= Helper::limpiar($o['publicacion_titulo']) ?></td>
                            <td style="padding:.6rem;"><?= Helper::limpiar($o['comprador_nombre']) ?></td>
                            <td style="padding:.6rem;"><?= number_format((float)$o['precio_ofrecido'], 2, '.', '') ?></td>
                            <td style="padding:.6rem;">
                                <span class="badge" style="padding:.2rem .5rem; border-radius:999px; background:#eee;">
                                    <?= Helper::limpiar($o['estado']) ?>
                                </span>
                            </td>
                            <td style="padding:.6rem;">
                                <?php if ($o['estado'] === 'pendiente'): ?>
                                    <form action="<?= BASE_URL ?>/oferta_estado.php" method="post" style="display:inline;">
                                        <input type="hidden" name="oferta_id" value="<?= (int)$o['id'] ?>">
                                        <input type="hidden" name="accion" value="aceptar">
                                        <button class="btn-primario" type="submit">Aceptar</button>
                                    </form>
                                    <form action="<?= BASE_URL ?>/oferta_estado.php" method="post" style="display:inline; margin-left:.3rem;">
                                        <input type="hidden" name="oferta_id" value="<?= (int)$o['id'] ?>">
                                        <input type="hidden" name="accion" value="rechazar">
                                        <button class="btn-salir" type="submit">Rechazar</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <section style="margin-top:2rem;">
        <h2>Conversaciones</h2>
        <?php if (empty($convs)): ?>
            <p>No tienes conversaciones aún.</p>
        <?php else: ?>
            <div class="grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1rem;">
                <?php foreach ($convs as $c): ?>
                    <article class="card" style="padding:1rem; border:1px solid #eee; border-radius:12px;">
                        <h3 style="margin:0 0 .5rem 0;"><?= Helper::limpiar($c['publicacion_titulo']) ?></h3>
                        <p><strong>Con:</strong> <?= Helper::limpiar($c['otro_nombre']) ?></p>
                        <a class="btn-primario" href="<?= BASE_URL ?>/chat.php?id=<?= (int)$c['id'] ?>">Abrir chat</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>

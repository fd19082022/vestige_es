<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
    exit;
}

$yo = (int)$_SESSION['usuario_id'];
$pdo = DB::conn();

$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.precio_oferta,
        o.mensaje,
        o.estado,
        o.respuesta_vendedor,
        o.creado_en,
        o.actualizado_en,
        p.id AS publicacion_id,
        p.titulo AS publicacion_titulo,
        p.precio_bs AS precio_original,
        u.nombre AS vendedor_nombre,
        (SELECT ruta FROM publicaciones_imagenes WHERE publicacion_id = p.id ORDER BY es_principal DESC, id ASC LIMIT 1) AS imagen
    FROM ofertas o
    INNER JOIN publicaciones p ON p.id = o.publicacion_id
    INNER JOIN usuarios u ON u.id = COALESCE(p.vendedor_id, p.usuario_id)
    WHERE o.comprador_id = ?
    ORDER BY o.creado_en DESC
");
$stmt->execute([$yo]);
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: ver cuántas ofertas encontró
error_log("Mis ofertas - Usuario: {$yo}, Ofertas encontradas: " . count($ofertas));

// Función para asset_url
function asset_url(string $relative): string {
    $r = trim($relative);
    if ($r === '') return BASE_URL . '/assets/imgs/no-image.png';
    if (preg_match('~^(https?:)?//|^data:image/~i', $r)) return $r;
    $r = ltrim($r, '/');
    if (str_starts_with($r, 'public/')) $r = substr($r, 7);
    $abs = __DIR__ . '/' . $r;
    return is_file($abs) ? (BASE_URL . '/' . $r) : (BASE_URL . '/assets/imgs/no-image.png');
}

$flash = Helper::getFlash();
require_once __DIR__ . '/../templates/header.php';
?>

<main class="contenedor principal">
    <h1>Mis Ofertas Enviadas</h1>
    <p class="texto-suave">Aquí puedes ver el estado de todas las ofertas que has realizado</p>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $f): ?>
            <div class="alerta alerta--<?= $f['tipo'] ?>">
                <?= htmlspecialchars($f['mensaje']) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($ofertas)): ?>
        <div class="empty">
            <h3>No has enviado ofertas aún</h3>
            <p>Explora publicaciones y haz ofertas en las prendas que te gusten</p>
            <div class="actions">
                <a href="<?= BASE_URL ?>/explorar.php" class="btn-primario">Explorar publicaciones</a>
            </div>
        </div>
    <?php else: ?>
        <div style="display:grid; gap:1rem;">
            <?php foreach ($ofertas as $oferta): ?>
                <article class="tarjeta" style="display:grid; grid-template-columns: 120px 1fr; gap:1rem;">
                    <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$oferta['publicacion_id'] ?>">
                        <img src="<?= asset_url($oferta['imagen']) ?>" 
                             alt="<?= htmlspecialchars($oferta['publicacion_titulo']) ?>"
                             style="width:100%; height:120px; object-fit:cover; border-radius:10px;">
                    </a>
                    
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:start; gap:1rem; margin-bottom:.5rem;">
                            <div>
                                <h3 style="margin:0 0 .3rem;">
                                    <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$oferta['publicacion_id'] ?>" 
                                       style="color:var(--text);">
                                        <?= htmlspecialchars($oferta['publicacion_titulo']) ?>
                                    </a>
                                </h3>
                                <p style="margin:0; color:var(--muted); font-size:.9rem;">
                                    Vendedor: <?= htmlspecialchars($oferta['vendedor_nombre']) ?>
                                </p>
                            </div>
                            
                            <?php
                                $badge_class = match($oferta['estado']) {
                                    'aceptada' => 'badge-success',
                                    'rechazada' => 'badge-danger',
                                    default => 'badge-warning'
                                };
                                $badge_text = match($oferta['estado']) {
                                    'aceptada' => '✓ Aceptada',
                                    'rechazada' => '✗ Rechazada',
                                    default => '⏱ Pendiente'
                                };
                            ?>
                            <span class="badge <?= $badge_class ?>" style="white-space:nowrap;">
                                <?= $badge_text ?>
                            </span>
                        </div>
                        
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:.8rem; margin:.8rem 0;">
                            <div>
                                <small style="color:var(--muted); display:block;">Precio original</small>
                                <strong style="color:var(--text);"><?= number_format($oferta['precio_original'], 2) ?> Bs</strong>
                            </div>
                            <div>
                                <small style="color:var(--muted); display:block;">Tu oferta</small>
                                <strong style="color:var(--primary); font-size:1.1rem;"><?= number_format($oferta['precio_oferta'], 2) ?> Bs</strong>
                            </div>
                        </div>
                        
                        <?php if (!empty($oferta['mensaje'])): ?>
                            <div style="margin:.8rem 0; padding:.8rem; background:rgba(255,255,255,.02); border-radius:8px; border:1px solid rgba(255,255,255,.06);">
                                <small style="color:var(--muted); display:block; margin-bottom:.3rem;">Tu mensaje:</small>
                                <p style="margin:0; font-size:.95rem; color:var(--text);">
                                    <?= nl2br(htmlspecialchars($oferta['mensaje'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($oferta['respuesta_vendedor'])): ?>
                            <div style="margin:.8rem 0; padding:.8rem; background:rgba(167,139,250,.08); border-radius:8px; border:1px solid rgba(167,139,250,.2);">
                                <small style="color:var(--secondary); display:block; margin-bottom:.3rem; font-weight:700;">
                                    Respuesta del vendedor:
                                </small>
                                <p style="margin:0; font-size:.95rem; color:var(--text);">
                                    <?= nl2br(htmlspecialchars($oferta['respuesta_vendedor'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.8rem;">
                            <small style="color:var(--muted);">
                                Enviada: <?= date('d/m/Y H:i', strtotime($oferta['creado_en'])) ?>
                            </small>
                            
                            <?php if ($oferta['estado'] === 'aceptada'): ?>
                                <form action="<?= BASE_URL ?>/chat_iniciar.php" method="post" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(Helper::csrf_token()) ?>">
                                    <input type="hidden" name="publicacion_id" value="<?= (int)$oferta['publicacion_id'] ?>">
                                    <input type="hidden" name="mensaje" value="Hola, mi oferta fue aceptada. Hablemos sobre '<?= htmlspecialchars($oferta['publicacion_titulo']) ?>'">
                                    <button type="submit" class="btn-primario" style="font-size:.9rem; padding:.5rem 1rem;">
                                        💬 Contactar vendedor
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<style>
.badge-success {
    background: rgba(34,197,94,.18);
    border: 1px solid rgba(34,197,94,.35);
    color: #22c55e;
    padding: .4rem .8rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: .85rem;
}
.badge-danger {
    background: rgba(239,68,68,.18);
    border: 1px solid rgba(239,68,68,.35);
    color: #ef4444;
    padding: .4rem .8rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: .85rem;
}
.badge-warning {
    background: rgba(245,158,11,.18);
    border: 1px solid rgba(245,158,11,.35);
    color: #f59e0b;
    padding: .4rem .8rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: .85rem;
}
</style>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
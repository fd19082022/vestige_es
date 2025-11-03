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

// Línea ~16-33
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
        u.nombre AS comprador_nombre,
        (SELECT ruta FROM publicaciones_imagenes WHERE publicacion_id = p.id ORDER BY es_principal DESC, id ASC LIMIT 1) AS imagen
    FROM ofertas o
    JOIN publicaciones p ON p.id = o.publicacion_id
    JOIN usuarios u ON u.id = o.comprador_id
    WHERE COALESCE(p.vendedor_id, p.usuario_id) = ?
    ORDER BY 
        CASE o.estado 
            WHEN 'pendiente' THEN 1 
            WHEN 'aceptada' THEN 2 
            WHEN 'rechazada' THEN 3 
        END,
        o.creado_en DESC
");
$stmt->execute([$yo]);
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar token CSRF
$csrf = Helper::csrf_token();

$flash = Helper::getFlash();
require_once __DIR__ . '/../templates/header.php';
?>

<main class="contenedor principal">
    <h1>Ofertas Recibidas</h1>
    <p class="texto-suave">Gestiona las ofertas que han hecho en tus publicaciones</p>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $f): ?>
            <div class="alerta alerta--<?= $f['tipo'] ?>">
                <?= htmlspecialchars($f['mensaje']) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($ofertas)): ?>
        <div class="empty">
            <h3>No has recibido ofertas aún</h3>
            <p>Cuando alguien haga una oferta en tus publicaciones, aparecerá aquí</p>
            <div class="actions">
                <a href="<?= BASE_URL ?>/publicacion_nueva.php" class="btn-primario">Crear nueva publicación</a>
            </div>
        </div>
    <?php else: ?>
        <div style="display:grid; gap:1rem;">
            <?php foreach ($ofertas as $oferta): ?>
                <article class="tarjeta" style="display:grid; grid-template-columns: 120px 1fr; gap:1rem;">
                    <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$oferta['publicacion_id'] ?>">
                        <?php 
                            $img_src = $oferta['imagen'] 
                                ? (BASE_URL . '/' . ltrim($oferta['imagen'], '/'))
                                : (BASE_URL . '/assets/imgs/no-image.png');
                        ?>
                        <img src="<?= htmlspecialchars($img_src) ?>" 
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
                                    De: <?= htmlspecialchars($oferta['comprador_nombre']) ?>
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
                                <small style="color:var(--muted); display:block;">Tu precio</small>
                                <strong style="color:var(--text);"><?= number_format($oferta['precio_original'], 2) ?> Bs</strong>
                            </div>
                            <div>
                                <small style="color:var(--muted); display:block;">Oferta recibida</small>
                                <strong style="color:var(--primary); font-size:1.1rem;"><?= number_format($oferta['precio_oferta'], 2) ?> Bs</strong>
                            </div>
                            <div>
                                <small style="color:var(--muted); display:block;">Diferencia</small>
                                <?php 
                                    $diff = $oferta['precio_original'] - $oferta['precio_oferta'];
                                    $diff_color = $diff > 0 ? '#ef4444' : '#22c55e';
                                ?>
                                <strong style="color:<?= $diff_color ?>;">
                                    <?= $diff > 0 ? '-' : '+' ?><?= number_format(abs($diff), 2) ?> Bs
                                </strong>
                            </div>
                        </div>
                        
                        <?php if (!empty($oferta['mensaje'])): ?>
                            <div style="margin:.8rem 0; padding:.8rem; background:rgba(255,255,255,.02); border-radius:8px; border:1px solid rgba(255,255,255,.06);">
                                <small style="color:var(--muted); display:block; margin-bottom:.3rem;">Mensaje del comprador:</small>
                                <p style="margin:0; font-size:.95rem; color:var(--text);">
                                    <?= nl2br(htmlspecialchars($oferta['mensaje'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($oferta['respuesta_vendedor'])): ?>
                            <div style="margin:.8rem 0; padding:.8rem; background:rgba(167,139,250,.08); border-radius:8px; border:1px solid rgba(167,139,250,.2);">
                                <small style="color:var(--secondary); display:block; margin-bottom:.3rem; font-weight:700;">
                                    Tu respuesta:
                                </small>
                                <p style="margin:0; font-size:.95rem; color:var(--text);">
                                    <?= nl2br(htmlspecialchars($oferta['respuesta_vendedor'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.8rem; gap:.8rem; flex-wrap:wrap;">
                            <small style="color:var(--muted);">
                                Recibida: <?= date('d/m/Y H:i', strtotime($oferta['creado_en'])) ?>
                            </small>
                            
                            <?php if ($oferta['estado'] === 'pendiente'): ?>
                                <div style="display:flex; gap:.6rem;">
                                    <button type="button" 
                                            onclick="mostrarModalRespuesta(<?= (int)$oferta['id'] ?>, 'aceptar')"
                                            class="btn-primario" 
                                            style="font-size:.9rem; padding:.5rem 1rem; background:#22c55e;">
                                        ✓ Aceptar
                                    </button>
                                    <button type="button" 
                                            onclick="mostrarModalRespuesta(<?= (int)$oferta['id'] ?>, 'rechazar')"
                                            class="btn" 
                                            style="font-size:.9rem; padding:.5rem 1rem; background:#ef4444; color:#fff; border:none;">
                                        ✗ Rechazar
                                    </button>
                                </div>
                            <?php elseif ($oferta['estado'] === 'aceptada'): ?>
                                <a href="<?= BASE_URL ?>/chat_iniciar.php?publicacion_id=<?= (int)$oferta['publicacion_id'] ?>" 
                                   class="btn-primario" style="font-size:.9rem; padding:.5rem 1rem;">
                                    💬 Contactar comprador
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modal para responder oferta -->
<div id="modalRespuesta" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--card); border-radius:16px; padding:1.5rem; max-width:500px; width:90%; border:1px solid rgba(255,255,255,.1);">
        <h3 id="modalTitulo" style="margin:0 0 1rem;">Responder oferta</h3>
        
        <form id="formRespuesta" action="<?= BASE_URL ?>/oferta_responder.php" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="oferta_id" id="modalOfertaId">
            <input type="hidden" name="accion" id="modalAccion">
            
            <label style="display:block; margin-bottom:1rem;">
                <span style="display:block; margin-bottom:.5rem; font-weight:700;">
                    Mensaje para el comprador (opcional)
                </span>
                <textarea name="respuesta" 
                          rows="4" 
                          style="width:100%; padding:.8rem; background:var(--bg); border:1px solid rgba(255,255,255,.1); border-radius:10px; color:var(--text);"
                          placeholder="Ej: Acepto tu oferta, contactame por chat para coordinar..."></textarea>
            </label>
            
            <div style="display:flex; gap:.8rem; justify-content:flex-end;">
                <button type="button" onclick="cerrarModal()" class="btn">Cancelar</button>
                <button type="submit" id="btnEnviar" class="btn-primario">Confirmar</button>
            </div>
        </form>
    </div>
</div>

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

<script>
function mostrarModalRespuesta(ofertaId, accion) {
    const modal = document.getElementById('modalRespuesta');
    const titulo = document.getElementById('modalTitulo');
    const btnEnviar = document.getElementById('btnEnviar');
    
    document.getElementById('modalOfertaId').value = ofertaId;
    document.getElementById('modalAccion').value = accion;
    
    if (accion === 'aceptar') {
        titulo.textContent = '✓ Aceptar oferta';
        btnEnviar.textContent = 'Aceptar oferta';
        btnEnviar.style.background = '#22c55e';
    } else {
        titulo.textContent = '✗ Rechazar oferta';
        btnEnviar.textContent = 'Rechazar oferta';
        btnEnviar.style.background = '#ef4444';
    }
    
    modal.style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalRespuesta').style.display = 'none';
    document.getElementById('formRespuesta').reset();
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalRespuesta')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
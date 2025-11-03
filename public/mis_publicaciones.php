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

// Generar token CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// Obtener publicaciones del usuario
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.titulo,
        p.descripcion,
        p.precio_bs,
        p.condicion,
        p.estado_id,
        p.creado_en,
        c.nombre AS categoria,
        t.nombre AS talla,
        co.nombre AS color,
        ep.nombre AS estado,
        (SELECT ruta FROM publicaciones_imagenes WHERE publicacion_id = p.id ORDER BY es_principal DESC, id ASC LIMIT 1) AS imagen,
        (SELECT COUNT(*) FROM ofertas WHERE publicacion_id = p.id) AS total_ofertas,
        (SELECT COUNT(*) FROM ofertas WHERE publicacion_id = p.id AND estado = 'pendiente') AS ofertas_pendientes
    FROM publicaciones p
    JOIN categorias c ON c.id = p.categoria_id
    LEFT JOIN tallas t ON t.id = p.talla_id
    LEFT JOIN colores co ON co.id = p.color_id
    JOIN estados_publicacion ep ON ep.id = p.estado_id
    WHERE p.usuario_id = ?
    ORDER BY p.creado_en DESC
");
$stmt->execute([$yo]);
$publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = Helper::getFlash();
require_once __DIR__ . '/../templates/header.php';
?>

<main class="contenedor principal">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
            <h1 style="margin:0 0 .3rem;">Mis Publicaciones</h1>
            <p class="texto-suave" style="margin:0;">Gestiona todas tus prendas publicadas</p>
        </div>
        <a href="<?= BASE_URL ?>/publicacion_nueva.php" class="btn-primario">
            + Nueva publicación
        </a>
    </div>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $f): ?>
            <div class="alerta alerta--<?= $f['tipo'] ?>">
                <?= htmlspecialchars($f['mensaje']) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($publicaciones)): ?>
        <div class="empty">
            <h3>No tienes publicaciones aún</h3>
            <p>Comienza a vender tus prendas creando tu primera publicación</p>
            <div class="actions">
                <a href="<?= BASE_URL ?>/publicacion_nueva.php" class="btn-primario">Crear primera publicación</a>
            </div>
        </div>
    <?php else: ?>
        <div style="display:grid; gap:1rem;">
            <?php foreach ($publicaciones as $pub): ?>
                <article class="tarjeta" style="display:grid; grid-template-columns: 120px 1fr auto; gap:1rem; align-items:center;">
                    <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$pub['id'] ?>">
                        <?php 
                            $img_src = $pub['imagen'] 
                                ? (BASE_URL . '/' . ltrim($pub['imagen'], '/'))
                                : (BASE_URL . '/assets/imgs/no-image.png');
                        ?>
                        <img src="<?= htmlspecialchars($img_src) ?>" 
                             alt="<?= htmlspecialchars($pub['titulo']) ?>"
                             style="width:100%; height:120px; object-fit:cover; border-radius:10px;">
                    </a>
                    
                    <div>
                        <div style="display:flex; align-items:start; gap:1rem; margin-bottom:.5rem;">
                            <div style="flex:1;">
                                <h3 style="margin:0 0 .3rem;">
                                    <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$pub['id'] ?>" 
                                       style="color:var(--text);">
                                        <?= htmlspecialchars($pub['titulo']) ?>
                                    </a>
                                </h3>
                                <p style="margin:0; color:var(--muted); font-size:.9rem;">
                                    <?= htmlspecialchars($pub['categoria']) ?>
                                    <?php if (!empty($pub['talla'])): ?>
                                        · Talla <?= htmlspecialchars($pub['talla']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($pub['color'])): ?>
                                        · <?= htmlspecialchars($pub['color']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <?php
                                $estado_class = match((int)$pub['estado_id']) {
                                    2 => 'badge-success',
                                    3 => 'badge-danger',
                                    default => 'badge-warning'
                                };
                                $estado_text = htmlspecialchars($pub['estado']);
                            ?>
                            <span class="badge <?= $estado_class ?>" style="white-space:nowrap;">
                                <?= $estado_text ?>
                            </span>
                        </div>
                        
                        <div style="display:flex; gap:1.5rem; margin:.8rem 0;">
                            <div>
                                <small style="color:var(--muted); display:block;">Precio</small>
                                <strong style="color:var(--primary); font-size:1.1rem;">
                                    <?= number_format($pub['precio_bs'], 2) ?> Bs
                                </strong>
                            </div>
                            <div>
                                <small style="color:var(--muted); display:block;">Condición</small>
                                <strong style="color:var(--text);">
                                    <?= htmlspecialchars($pub['condicion']) ?>
                                </strong>
                            </div>
                            <?php if ($pub['total_ofertas'] > 0): ?>
                                <div>
                                    <small style="color:var(--muted); display:block;">Ofertas</small>
                                    <strong style="color:var(--accent);">
                                        <?= (int)$pub['total_ofertas'] ?>
                                        <?php if ($pub['ofertas_pendientes'] > 0): ?>
                                            <span style="color:var(--warning); font-size:.85rem;">
                                                (<?= (int)$pub['ofertas_pendientes'] ?> pendientes)
                                            </span>
                                        <?php endif; ?>
                                    </strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display:flex; gap:.6rem; margin-top:.8rem; flex-wrap:wrap;">
                            <a href="<?= BASE_URL ?>/publicacion_ver.php?id=<?= (int)$pub['id'] ?>" 
                               class="btn" style="font-size:.85rem; padding:.4rem .8rem;">
                                👁️ Ver
                            </a>
                            <a href="<?= BASE_URL ?>/publicacion_editar.php?id=<?= (int)$pub['id'] ?>" 
                               class="btn" style="font-size:.85rem; padding:.4rem .8rem;">
                                ✏️ Editar
                            </a>
                            <?php if ($pub['total_ofertas'] > 0): ?>
                                <a href="<?= BASE_URL ?>/ofertas_recibidas.php" 
                                   class="btn-primario" style="font-size:.85rem; padding:.4rem .8rem;">
                                    💰 Ver ofertas (<?= (int)$pub['total_ofertas'] ?>)
                                </a>
                            <?php endif; ?>
                            <button type="button" 
                                    onclick="confirmarEliminar(<?= (int)$pub['id'] ?>, '<?= htmlspecialchars(addslashes($pub['titulo'])) ?>')"
                                    class="btn-eliminar" 
                                    style="font-size:.85rem; padding:.4rem .8rem; background:#ef4444; color:#fff; border:none; border-radius:10px; cursor:pointer;">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                    
                    <div style="text-align:right;">
                        <small style="color:var(--muted); display:block;">
                            Publicado
                        </small>
                        <small style="color:var(--muted);">
                            <?= date('d/m/Y', strtotime($pub['creado_en'])) ?>
                        </small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modal de confirmación -->
<div id="modalEliminar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.8); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--card); border-radius:16px; padding:1.5rem; max-width:450px; width:90%; border:1px solid rgba(255,255,255,.1);">
        <h3 style="margin:0 0 .5rem; color:#ef4444;">⚠️ Confirmar eliminación</h3>
        <p id="mensajeEliminar" style="margin:.5rem 0 1.5rem; color:var(--muted); line-height:1.6;"></p>
        
        <form id="formEliminar" action="<?= BASE_URL ?>/publicacion_eliminar.php" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="publicacion_id" id="publicacionEliminarId">
            
            <div style="display:flex; gap:.8rem; justify-content:flex-end;">
                <button type="button" onclick="cerrarModalEliminar()" class="btn">Cancelar</button>
                <button type="submit" class="btn" style="background:#ef4444; color:#fff; font-weight:700;">
                    Sí, eliminar
                </button>
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

.btn-eliminar:hover {
    filter: brightness(1.1);
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    article.tarjeta {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function confirmarEliminar(id, titulo) {
    const modal = document.getElementById('modalEliminar');
    const mensaje = document.getElementById('mensajeEliminar');
    const idInput = document.getElementById('publicacionEliminarId');
    
    mensaje.innerHTML = `¿Estás seguro de que deseas eliminar la publicación <strong>"${titulo}"</strong>?<br><br>Esta acción no se puede deshacer y se eliminarán también todas las ofertas y mensajes relacionados.`;
    idInput.value = id;
    
    modal.style.display = 'flex';
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminar').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalEliminar')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalEliminar();
    }
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
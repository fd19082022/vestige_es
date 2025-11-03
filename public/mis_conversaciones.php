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

// Obtener todas las conversaciones del usuario con info del último mensaje
$sql = "
SELECT 
    c.id AS conversacion_id,
    c.publicacion_id,
    p.titulo AS publicacion_titulo,
    CASE 
        WHEN c.comprador_id = ? THEN c.vendedor_id
        ELSE c.comprador_id
    END AS otro_usuario_id,
    CASE 
        WHEN c.comprador_id = ? THEN u_vendedor.nombre
        ELSE u_comprador.nombre
    END AS otro_usuario_nombre,
    (SELECT contenido FROM mensajes WHERE conversacion_id = c.id ORDER BY id DESC LIMIT 1) AS ultimo_mensaje,
    (SELECT creado_en FROM mensajes WHERE conversacion_id = c.id ORDER BY id DESC LIMIT 1) AS ultimo_mensaje_fecha,
    (SELECT COUNT(*) FROM mensajes WHERE conversacion_id = c.id AND destinatario_id = ? AND leido = 0) AS mensajes_no_leidos,
    (SELECT ruta FROM publicaciones_imagenes WHERE publicacion_id = c.publicacion_id ORDER BY es_principal DESC, id ASC LIMIT 1) AS publicacion_imagen
FROM conversaciones c
LEFT JOIN publicaciones p ON p.id = c.publicacion_id
LEFT JOIN usuarios u_comprador ON u_comprador.id = c.comprador_id
LEFT JOIN usuarios u_vendedor ON u_vendedor.id = c.vendedor_id
WHERE c.comprador_id = ? OR c.vendedor_id = ?
ORDER BY ultimo_mensaje_fecha DESC
";

$st = $pdo->prepare($sql);
$st->execute([$yo, $yo, $yo, $yo, $yo]);
$conversaciones = $st->fetchAll(PDO::FETCH_ASSOC);

// Función para imágenes
function asset_url(string $relative): string {
    $r = trim($relative);
    if ($r === '') return BASE_URL . '/assets/imgs/no-image.png';
    if (preg_match('~^(https?:)?//|^data:image/~i', $r)) return $r;
    $r = ltrim($r, '/');
    if (str_starts_with($r, 'public/')) $r = substr($r, 7);
    $abs = __DIR__ . '/' . $r;
    return is_file($abs) ? (BASE_URL . '/' . $r) : (BASE_URL . '/assets/imgs/no-image.png');
}

// Función para tiempo transcurrido
function tiempo_transcurrido($fecha) {
    if (!$fecha) return '';
    try {
        $dt = new DateTime($fecha);
        $now = new DateTime();
        $diff = $now->diff($dt);
        
        if ($diff->d == 0 && $diff->h == 0 && $diff->i < 1) return 'Ahora';
        if ($diff->d == 0 && $diff->h == 0) return $diff->i . 'm';
        if ($diff->d == 0) return $diff->h . 'h';
        if ($diff->d < 7) return $diff->d . 'd';
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return '';
    }
}

$flash = Helper::getFlash();
require_once __DIR__ . '/../templates/header.php';
?>

<style>
.conversaciones-wrap {
    max-width: 1000px;
    margin: 24px auto;
    padding: 0 16px;
}

.conversaciones-header {
    background: linear-gradient(180deg, rgba(16,24,48,.9), rgba(13,20,38,.95));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: var(--radius);
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
}

.conversaciones-header h1 {
    margin: 0;
    font-size: 1.8rem;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 12px;
}

.conversacion-item {
    background: linear-gradient(180deg, rgba(16,24,48,.9), rgba(13,20,38,.95));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: var(--radius);
    padding: 16px 20px;
    margin-bottom: 12px;
    display: flex;
    gap: 16px;
    align-items: center;
    text-decoration: none;
    color: var(--text);
    transition: all .2s ease;
    position: relative;
    overflow: hidden;
}

.conversacion-item:hover {
    border-color: rgba(167,139,250,.3);
    transform: translateX(4px);
    box-shadow: 0 4px 16px rgba(0,0,0,.3);
}

.conversacion-item.no-leido {
    border-left: 4px solid var(--primary);
    background: linear-gradient(180deg, rgba(167,139,250,.08), rgba(16,24,48,.9));
}

.conversacion-img {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid rgba(255,255,255,.1);
}

.conversacion-info {
    flex: 1;
    min-width: 0;
}

.conversacion-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 6px;
}

.conversacion-usuario {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text);
    margin: 0;
}

.conversacion-fecha {
    font-size: 12px;
    color: var(--muted);
    white-space: nowrap;
}

.conversacion-publicacion {
    font-size: 13px;
    color: var(--muted);
    margin: 4px 0;
}

.conversacion-ultimo {
    font-size: 14px;
    color: var(--muted);
    margin: 6px 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversacion-item.no-leido .conversacion-ultimo {
    color: var(--text);
    font-weight: 600;
}

.badge-no-leido {
    background: var(--primary);
    color: #0b1020;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    display: inline-block;
    margin-left: auto;
    flex-shrink: 0;
}

.conversaciones-empty {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(180deg, rgba(16,24,48,.9), rgba(13,20,38,.95));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: var(--radius);
}

.conversaciones-empty h3 {
    color: var(--text);
    margin: 0 0 12px;
    font-size: 1.4rem;
}

.conversaciones-empty p {
    color: var(--muted);
    margin: 0 0 24px;
}

@media (max-width: 768px) {
    .conversaciones-wrap {
        padding: 0 12px;
    }
    
    .conversacion-item {
        padding: 12px 14px;
        gap: 12px;
    }
    
    .conversacion-img {
        width: 60px;
        height: 60px;
    }
    
    .conversacion-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .conversacion-fecha {
        font-size: 11px;
    }
}
</style>

<main class="contenedor principal">
    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $f): ?>
            <div class="alerta alerta--<?php echo $f['tipo']; ?>">
                <?php echo htmlspecialchars($f['mensaje']); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="conversaciones-wrap">
        <div class="conversaciones-header">
            <h1>
                💬 Mis Conversaciones
                <?php 
                $total_no_leidos = array_sum(array_column($conversaciones, 'mensajes_no_leidos'));
                if ($total_no_leidos > 0): 
                ?>
                    <span class="badge-no-leido"><?= $total_no_leidos ?></span>
                <?php endif; ?>
            </h1>
        </div>

        <?php if (empty($conversaciones)): ?>
            <div class="conversaciones-empty">
                <h3>No tienes conversaciones</h3>
                <p>Cuando inicies una conversación con un vendedor, aparecerá aquí.</p>
                <a href="<?= BASE_URL ?>/explorar.php" class="btn-primario">
                    Explorar publicaciones
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($conversaciones as $conv): ?>
                <a href="<?= BASE_URL ?>/chat.php?id=<?= (int)$conv['conversacion_id'] ?>" 
                   class="conversacion-item <?= (int)$conv['mensajes_no_leidos'] > 0 ? 'no-leido' : '' ?>">
                    
                    <img src="<?= asset_url($conv['publicacion_imagen']) ?>" 
                         alt="<?= htmlspecialchars($conv['publicacion_titulo']) ?>"
                         class="conversacion-img">
                    
                    <div class="conversacion-info">
                        <div class="conversacion-top">
                            <h3 class="conversacion-usuario">
                                <?= htmlspecialchars($conv['otro_usuario_nombre']) ?>
                            </h3>
                            <span class="conversacion-fecha">
                                <?= tiempo_transcurrido($conv['ultimo_mensaje_fecha']) ?>
                            </span>
                        </div>
                        
                        <div class="conversacion-publicacion">
                            📦 <?= htmlspecialchars($conv['publicacion_titulo']) ?>
                        </div>
                        
                        <div class="conversacion-ultimo">
                            <?= htmlspecialchars($conv['ultimo_mensaje'] ?: 'Sin mensajes') ?>
                        </div>
                    </div>
                    
                    <?php if ((int)$conv['mensajes_no_leidos'] > 0): ?>
                        <span class="badge-no-leido">
                            <?= (int)$conv['mensajes_no_leidos'] ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 24px; text-align: center;">
            <a href="<?= BASE_URL ?>/index.php" class="btn">← Volver al inicio</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
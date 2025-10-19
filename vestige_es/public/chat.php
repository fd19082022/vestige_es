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

$yo = (int)($_SESSION['usuario_id'] ?? 0);
$conv_id = (int)($_GET['id'] ?? 0);
if ($conv_id <= 0) {
    Helper::flash_mensaje('Conversación inválida.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

$pdo = DB::conn();

$st = $pdo->prepare("SELECT comprador_id, vendedor_id FROM conversaciones WHERE id = ?");
$st->execute([$conv_id]);
$conv = $st->fetch(PDO::FETCH_ASSOC);
if (!$conv || ($yo !== (int)$conv['comprador_id'] && $yo !== (int)$conv['vendedor_id'])) {
    Helper::flash_mensaje('Conversación no válida.', 'error');
    Helper::redir(BASE_URL . '/index.php');
    exit;
}

$otro_id = ($yo === (int)$conv['comprador_id']) ? (int)$conv['vendedor_id'] : (int)$conv['comprador_id'];

$st = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
$st->execute([$otro_id]);
$otro_nombre = (string)$st->fetchColumn();

$st = $pdo->prepare("
    SELECT emisor_id, contenido, creado_en
    FROM mensajes
    WHERE conversacion_id = ?
    ORDER BY id ASC
");
$st->execute([$conv_id]);
$mensajes = $st->fetchAll(PDO::FETCH_ASSOC);

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

require_once __DIR__ . '/../templates/header.php';
?>
<style>
  .chat-wrap{max-width:900px;margin:18px auto;padding:0 14px}
  .chat-card{border:1px solid #ddd;border-radius:10px;overflow:hidden;background:#fff}
  .chat-head{padding:12px 16px;font-weight:600;background:#f7f7f7;border-bottom:1px solid #eee}
  .chat-body{padding:16px;max-height:480px;overflow-y:auto;background:#fafafa}
  .msg{display:flex;margin-bottom:10px}
  .msg.me{justify-content:flex-end}
  .msg .b{max-width:70%;padding:10px 12px;border-radius:12px;background:#fff;border:1px solid #e6e6e6}
  .msg.me .b{background:#e8f0ff;border-color:#d4e3ff}
  .msg .t{font-size:12px;color:#777;margin-top:4px}
  .chat-form{display:flex;gap:8px;padding:12px;border-top:1px solid #eee;background:#fff}
  .chat-form textarea{flex:1;resize:vertical;min-height:42px;padding:10px}
</style>

<div class="chat-wrap">
  <div class="chat-card">
    <div class="chat-head">Chat con <?= htmlspecialchars($otro_nombre ?: 'Usuario') ?></div>
    <div class="chat-body" id="chatBody">
      <?php foreach ($mensajes as $m): ?>
        <div class="msg <?= ((int)$m['emisor_id'] === $yo ? 'me' : '') ?>">
          <div class="b">
            <div><?= nl2br(htmlspecialchars($m['contenido'])) ?></div>
            <div class="t"><?= htmlspecialchars($m['creado_en']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <form class="chat-form" action="<?= BASE_URL ?>/chat_enviar.php" method="post">
      <input type="hidden" name="conversacion_id" value="<?= (int)$conv_id ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <textarea name="mensaje" placeholder="Escribe tu mensaje..." required></textarea>
      <button type="submit" class="btn">Enviar</button>
    </form>
  </div>
</div>

<script>
  const body = document.getElementById('chatBody');
  if (body) body.scrollTop = body.scrollHeight;
</script>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>

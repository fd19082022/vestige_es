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

// ✅ NUEVO: Marcar mensajes como leídos cuando el usuario abre el chat
$st_leer = $pdo->prepare("UPDATE mensajes SET leido = 1 WHERE conversacion_id = ? AND destinatario_id = ? AND leido = 0");
$st_leer->execute([$conv_id, $yo]);

$st = $pdo->prepare("
    SELECT emisor_id, contenido, creado_en
    FROM mensajes
    WHERE conversacion_id = ?
    ORDER BY id ASC
");
$st->execute([$conv_id]);
$mensajes = $st->fetchAll(PDO::FETCH_ASSOC);

// Generar token CSRF usando Helper
$csrf = Helper::csrf_token();

require_once __DIR__ . '/../templates/header.php';
?>
<style>
  .chat-wrap {
    max-width: 920px;
    margin: 24px auto;
    padding: 0 16px;
  }
  
  .chat-card {
    background: linear-gradient(180deg, rgba(16,24,48,.9), rgba(13,20,38,.95));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
  }
  
  .chat-head {
    padding: 18px 22px;
    font-weight: 800;
    font-size: 1.15rem;
    background: linear-gradient(180deg, rgba(20,28,52,.85), rgba(16,24,48,.9));
    border-bottom: 1px solid rgba(255,255,255,.12);
    color: var(--text);
    letter-spacing: .3px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .chat-back {
    color: var(--muted);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: color .2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  
  .chat-back:hover {
    color: var(--primary);
  }
  
  .chat-body {
    padding: 20px;
    max-height: 520px;
    overflow-y: auto;
    background: var(--bg-soft);
    min-height: 420px;
  }
  
  .chat-body::-webkit-scrollbar {
    width: 8px;
  }
  
  .chat-body::-webkit-scrollbar-track {
    background: rgba(255,255,255,.02);
    border-radius: 4px;
  }
  
  .chat-body::-webkit-scrollbar-thumb {
    background: rgba(167,139,250,.25);
    border-radius: 4px;
  }
  
  .chat-body::-webkit-scrollbar-thumb:hover {
    background: rgba(167,139,250,.35);
  }
  
  .msg {
    display: flex;
    margin-bottom: 16px;
    animation: fadeIn 0.3s ease;
  }
  
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  .msg.me {
    justify-content: flex-end;
  }
  
  .msg .b {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 16px;
    word-wrap: break-word;
    line-height: 1.6;
    box-shadow: 0 4px 12px rgba(0,0,0,.25);
  }
  
  /* Mensajes del otro usuario */
  .msg:not(.me) .b {
    background: linear-gradient(135deg, rgba(167,139,250,.18), rgba(167,139,250,.12));
    border: 1px solid rgba(167,139,250,.25);
    color: var(--text);
  }
  
  /* Mis mensajes */
  .msg.me .b {
    background: linear-gradient(135deg, var(--primary), rgba(34,211,238,.85));
    border: 1px solid rgba(34,211,238,.4);
    color: #0b1020;
    font-weight: 600;
  }
  
  .msg .t {
    font-size: 11px;
    color: var(--muted);
    margin-top: 6px;
    opacity: .7;
  }
  
  .msg.me .t {
    color: rgba(11,16,32,.65);
    text-align: right;
  }
  
  .chat-form {
    display: flex;
    gap: 10px;
    padding: 16px;
    border-top: 1px solid rgba(255,255,255,.12);
    background: linear-gradient(180deg, rgba(16,24,48,.9), rgba(13,20,38,.95));
  }
  
  .chat-form textarea {
    flex: 1;
    resize: vertical;
    min-height: 48px;
    max-height: 120px;
    padding: 12px 14px;
    background: var(--bg);
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 14px;
    color: var(--text);
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
    transition: border-color .2s ease, box-shadow .2s ease;
  }
  
  .chat-form textarea:focus {
    outline: none;
    border-color: rgba(167,139,250,.4);
    box-shadow: 0 0 0 3px rgba(167,139,250,.1);
  }
  
  .chat-form textarea::placeholder {
    color: var(--muted);
    opacity: .6;
  }
  
  .chat-form .btn {
    align-self: flex-end;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--secondary), var(--primary));
    color: #0b1020;
    font-weight: 800;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    transition: transform .2s ease, filter .2s ease;
    box-shadow: var(--shadow);
  }
  
  .chat-form .btn:hover {
    transform: translateY(-2px);
    filter: brightness(1.1);
  }
  
  .chat-form .btn:active {
    transform: translateY(0);
  }
  
  .chat-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--muted);
  }
  
  .chat-empty p {
    margin: 0;
    font-size: 15px;
  }
  
  @media (max-width: 768px) {
    .chat-wrap {
      padding: 0 12px;
    }
    
    .chat-head {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }
    
    .chat-body {
      max-height: 400px;
      min-height: 320px;
      padding: 16px;
    }
    
    .msg .b {
      max-width: 85%;
      padding: 10px 14px;
    }
    
    .chat-form {
      padding: 12px;
      gap: 8px;
    }
    
    .chat-form .btn {
      padding: 10px 18px;
    }
  }
</style>

<main class="contenedor principal">
  <div class="chat-wrap">
    <div class="chat-card">
      <div class="chat-head">
        <span>💬 Chat con <?= htmlspecialchars($otro_nombre ?: 'Usuario') ?></span>
        <a href="<?= BASE_URL ?>/mis_conversaciones.php" class="chat-back">
          ← Todas las conversaciones
        </a>
      </div>
      
      <div class="chat-body" id="chatBody">
        <?php if (empty($mensajes)): ?>
          <div class="chat-empty">
            <p>No hay mensajes aún. ¡Inicia la conversación!</p>
          </div>
        <?php else: ?>
          <?php foreach ($mensajes as $m): ?>
            <div class="msg <?= ((int)$m['emisor_id'] === $yo ? 'me' : '') ?>">
              <div class="b">
                <div><?= nl2br(htmlspecialchars($m['contenido'])) ?></div>
                <div class="t"><?= htmlspecialchars($m['creado_en']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      
      <form class="chat-form" action="<?= BASE_URL ?>/chat_enviar.php" method="post">
        <input type="hidden" name="conversacion_id" value="<?= (int)$conv_id ?>">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <textarea name="mensaje" placeholder="Escribe tu mensaje..." required></textarea>
        <button type="submit" class="btn">Enviar</button>
      </form>
    </div>
    
    <div style="margin-top: 1rem; text-align: center;">
      <a href="<?= BASE_URL ?>/mis_conversaciones.php" class="btn">← Ver todas las conversaciones</a>
    </div>
  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const body = document.getElementById('chatBody');
    if (body) {
      body.scrollTop = body.scrollHeight;
    }
    
    // Auto-resize del textarea
    const textarea = document.querySelector('.chat-form textarea');
    if (textarea) {
      textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
      });
    }
  });
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
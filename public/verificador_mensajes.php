<?php
/**
 * verificador_mensajes.php
 * Diagnóstico no destructivo del módulo de chat/mensajes.
 * - NO cambia nada en tu BD.
 * - Te dice qué columnas/FKs/tablas faltan o sobran y te propone SQL de arreglo.
 */

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/DB.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function ok($m){ echo '<li style="color:#10b981;font-weight:700;">✔️ ' . h($m) . '</li>'; }
function warn($m){ echo '<li style="color:#f59e0b;font-weight:700;">⚠️ ' . h($m) . '</li>'; }
function err($m){ echo '<li style="color:#ef4444;font-weight:700;">❌ ' . h($m) . '</li>'; }

function tableExists(PDO $pdo, string $table): bool {
  $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
  $stmt->execute([$table]);
  return (bool)$stmt->fetchColumn();
}
function colExists(PDO $pdo, string $table, string $col): bool {
  $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
  $stmt->execute([$col]);
  return (bool)$stmt->fetch();
}
function fkList(PDO $pdo, string $table): array {
  $sql = "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
          FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL";
  $st = $pdo->prepare($sql);
  $st->execute([$table]);
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
function indexList(PDO $pdo, string $table): array {
  $st = $pdo->query("SHOW INDEX FROM `$table`");
  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$issues = [];
$fixes  = [];

echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Verificador de Mensajes</title>';
echo '<style>body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;background:#0b1020;color:#eef2ff;padding:24px}';
echo 'h1{margin:0 0 10px} section{background:#0e1426;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:16px;margin:16px 0}';
echo 'code,pre{background:#0b1220;padding:8px 10px;border-radius:8px;display:block;white-space:pre-wrap}';
echo 'a.btn{display:inline-block;margin-top:10px;padding:8px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.12);color:#eef2ff;text-decoration:none}';
echo '</style></head><body>';
echo '<h1>Verificador de Mensajes / Conversaciones</h1>';
echo '<p>Este verificador es solo lectura y te mostrará exactamente qué está fallando.</p>';

// 1) Tablas base
echo '<section><h2>Tablas base</h2><ol>';
foreach (['usuarios','publicaciones'] as $t) {
  if (tableExists($pdo,$t)) ok("Tabla '$t' existe.");
  else { err("Falta tabla '$t'."); $issues[]="Falta $t"; }
}
echo '</ol></section>';

// 2) Conversaciones
echo '<section><h2>Tabla: conversaciones</h2><ol>';
if (!tableExists($pdo,'conversaciones')) {
  err("No existe la tabla 'conversaciones'.");
  $issues[]="No existe conversaciones";
  $fixes[] = "CREATE TABLE conversaciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    comprador_id BIGINT UNSIGNED NOT NULL,
    vendedor_id BIGINT UNSIGNED NOT NULL,
    publicacion_id BIGINT UNSIGNED NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_conv_pub (publicacion_id),
    KEY idx_conv_users (comprador_id, vendedor_id),
    CONSTRAINT fk_conv_comprador FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_conv_vendedor  FOREIGN KEY (vendedor_id)  REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_conv_pub       FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
} else {
  ok("La tabla 'conversaciones' existe.");
  foreach (['comprador_id','vendedor_id','publicacion_id'] as $c) {
    if (colExists($pdo,'conversaciones',$c)) ok("Columna conversaciones.$c OK.");
    else { err("Falta columna conversaciones.$c"); $issues[]="Falta conversaciones.$c"; }
  }
  $fks = fkList($pdo,'conversaciones');
  $fkmap = [];
  foreach ($fks as $fk) $fkmap[$fk['COLUMN_NAME']] = $fk;
  foreach ([
    'comprador_id' => ['usuarios','id'],
    'vendedor_id'  => ['usuarios','id'],
    'publicacion_id' => ['publicaciones','id']
  ] as $col=>$ref){
    if (!isset($fkmap[$col])) { warn("Falta FK de conversaciones.$col → {$ref[0]}({$ref[1]})"); }
    else ok("FK conversaciones.{$col} → {$fkmap[$col]['REFERENCED_TABLE_NAME']}({$fkmap[$col]['REFERENCED_COLUMN_NAME']}) OK.");
  }
}
echo '</ol></section>';

// 3) Mensajes
echo '<section><h2>Tabla: mensajes</h2><ol>';
if (!tableExists($pdo,'mensajes')) {
  err("No existe la tabla 'mensajes'.");
  $issues[]="No existe mensajes";
  $fixes[] = "CREATE TABLE mensajes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversacion_id BIGINT UNSIGNED NOT NULL,
    remitente_id BIGINT UNSIGNED NOT NULL,
    destinatario_id BIGINT UNSIGNED NULL,
    contenido TEXT NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_conv (conversacion_id),
    KEY idx_msg_users (remitente_id, destinatario_id),
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_msg_remitente FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_msg_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
} else {
  ok("La tabla 'mensajes' existe.");
  $hasEmisor = colExists($pdo,'mensajes','emisor_id');
  $hasRem    = colExists($pdo,'mensajes','remitente_id');
  $hasDest   = colExists($pdo,'mensajes','destinatario_id');
  $senderCol = $hasRem ? 'remitente_id' : ($hasEmisor ? 'emisor_id' : null);

  if (!$senderCol){
    err("Falta columna de remitente: ni 'remitente_id' ni 'emisor_id' existen.");
    $issues[]="mensajes sin remitente";
    $fixes[]="ALTER TABLE mensajes ADD COLUMN remitente_id BIGINT UNSIGNED NOT NULL AFTER conversacion_id;";
  } else {
    ok("Columna de remitente detectada: $senderCol");
  }
  if (!$hasDest){
    warn("No existe 'destinatario_id' (recomendado para FK y consultas).");
    $fixes[]="ALTER TABLE mensajes ADD COLUMN destinatario_id BIGINT UNSIGNED NULL AFTER ".$senderCol.";";
  } else {
    ok("Columna 'destinatario_id' existe.");
  }

  foreach (['conversacion_id'] as $c) {
    if (colExists($pdo,'mensajes',$c)) ok("Columna mensajes.$c OK."); else { err("Falta mensajes.$c"); $issues[]="Falta mensajes.$c"; }
  }
  if (!colExists($pdo,'mensajes','contenido')) { err("Falta mensajes.contenido"); $issues[]="Falta mensajes.contenido"; }
  else ok("Columna mensajes.contenido OK.");

  // FKs
  $fks = fkList($pdo,'mensajes');
  $fkByCol = [];
  foreach($fks as $fk){ $fkByCol[$fk['COLUMN_NAME']] = $fk; }

  if (!isset($fkByCol['conversacion_id'])) warn("Falta FK mensajes.conversacion_id → conversaciones(id)");
  else ok("FK mensajes.conversacion_id → ".$fkByCol['conversacion_id']['REFERENCED_TABLE_NAME']."(".$fkByCol['conversacion_id']['REFERENCED_COLUMN_NAME'].") OK.");

  if ($senderCol){
    if (!isset($fkByCol[$senderCol])) {
      warn("Falta FK mensajes.$senderCol → usuarios(id)");
      $fixes[]="ALTER TABLE mensajes ADD CONSTRAINT fk_msg_".$senderCol." FOREIGN KEY ($senderCol) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE;";
    } else {
      ok("FK mensajes.$senderCol → usuarios(id) OK.");
    }
  }
  if ($hasDest){
    if (!isset($fkByCol['destinatario_id'])) {
      warn("Falta FK mensajes.destinatario_id → usuarios(id)");
      $fixes[]="ALTER TABLE mensajes ADD CONSTRAINT fk_msg_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE;";
    } else ok("FK mensajes.destinatario_id → usuarios(id) OK.");
  }
}
echo '</ol></section>';

// 4) Datos de ejemplo y pertenencia
echo '<section><h2>Chequeo de pertenencia y envío</h2><ol>';
$ses = (int)($_SESSION['usuario_id'] ?? 0);
if ($ses <= 0) { warn("No hay usuario logueado en la sesión. Algunos errores pueden venir de esto."); }
else ok("Usuario en sesión: id=$ses");

$conv_test = $pdo->query("SELECT id, comprador_id, vendedor_id FROM conversaciones ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$conv_test){ warn("No hay ninguna conversación para probar."); }
else {
  ok("Conversación de prueba: id=".$conv_test['id']." (comprador_id=".$conv_test['comprador_id'].", vendedor_id=".$conv_test['vendedor_id'].")");
  $senderOptions = [(int)$conv_test['comprador_id'], (int)$conv_test['vendedor_id']];
  $senderCol = colExists($pdo,'mensajes','remitente_id') ? 'remitente_id' : (colExists($pdo,'mensajes','emisor_id') ? 'emisor_id' : null);
  $hasDest   = colExists($pdo,'mensajes','destinatario_id');

  if (!$senderCol){
    err("No se puede simular inserción: no hay columna remitente/emisor en mensajes.");
  } else {
    $emisor = $senderOptions[0];
    $dest   = $senderOptions[1];
    $sqlSim = $hasDest
      ? "INSERT INTO mensajes (conversacion_id, $senderCol, destinatario_id, contenido) VALUES (?,?,?,?)"
      : "INSERT INTO mensajes (conversacion_id, $senderCol, contenido) VALUES (?,?,?)";
    echo '<li>Simulación de INSERT (no ejecutado): <code>'.h($sqlSim).'</code></li>';
    echo '<li>Parámetros ejemplo: <code>'.h(json_encode([$conv_test['id'], $emisor] . ($hasDest?[$dest]:[]) + ['Hola demo'], JSON_UNESCAPED_UNICODE)).'</code></li>';
  }
}
echo '</ol></section>';

// 5) Sugerencias SQL
echo '<section><h2>SQL sugerido (no ejecutado)</h2>';
if (empty($fixes)) { echo '<p>No se detectaron cambios obligatorios. El error podría ser por datos inexistentes (usuarios/conversación).</p>'; }
else { echo '<pre>'.h(implode("\n\n", $fixes)).'</pre>'; }
echo '<a class="btn" href="'.h(BASE_URL).'/dashboard.php">Volver al dashboard</a>';
echo '</section>';

echo '</body></html>';

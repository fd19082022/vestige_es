<?php
// public/verificador_vendedor.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/DB.php';

function ok($m){ echo "<li style='color:#0a0;'>✔ $m</li>"; }
function warn($m){ echo "<li style='color:#b58900;'>• $m</li>"; }
function errx($m){ echo "<li style='color:#c00;'>✖ $m</li>"; }

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Helpers de introspección
function tableExists(PDO $pdo, $table){
  $stmt=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
  $stmt->execute([$table]);
  return $stmt->fetchColumn() > 0;
}
function colExists(PDO $pdo, $table, $col){
  $stmt=$pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
  $stmt->execute([$table,$col]);
  return $stmt->fetchColumn() > 0;
}
function indexExists(PDO $pdo, $table, $index){
  $stmt=$pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
  $stmt->execute([$index]);
  return (bool)$stmt->fetch();
}
function fkExists(PDO $pdo, $table, $fkName){
  $stmt=$pdo->prepare("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?");
  $stmt->execute([$table, $fkName]);
  return (bool)$stmt->fetch();
}
function engineInnoDB(PDO $pdo, $table){
  $stmt=$pdo->prepare("SELECT ENGINE FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
  $stmt->execute([$table]);
  $eng = $stmt->fetchColumn();
  return strtoupper((string)$eng) === 'INNODB';
}

echo "<h1>Verificador vendedor + chat + ofertas</h1><ol>";

// === BASES REQUERIDAS ===
foreach (['usuarios','publicaciones'] as $t) {
  if (!tableExists($pdo, $t)) { errx("Falta tabla base '$t'."); echo "</ol>"; exit; }
  else ok("Tabla base '$t' OK.");
}

// === PUBLICACIONES.vendedor_id ===
if (!colExists($pdo, 'publicaciones', 'vendedor_id')) {
  // ¿existe usuario_id? si sí, creamos vendedor_id y migramos
  $tieneUsuarioId = colExists($pdo,'publicaciones','usuario_id');
  $pdo->exec("ALTER TABLE publicaciones ADD COLUMN vendedor_id BIGINT UNSIGNED NOT NULL AFTER id;");
  ok("Columna 'publicaciones.vendedor_id' agregada.");
  if ($tieneUsuarioId) {
    // Migra valores si hay usuario_id
    $pdo->exec("UPDATE publicaciones SET vendedor_id = usuario_id WHERE vendedor_id = 0 OR vendedor_id IS NULL;");
    ok("Migrado 'vendedor_id' desde 'usuario_id'.");
  } else {
    warn("No existe 'usuario_id' en publicaciones; asegúrate de poblar 'vendedor_id' con el id del vendedor.");
  }
  // FK vendedor_id -> usuarios(id)
  try {
    if (!fkExists($pdo,'publicaciones','fk_pub_vendedor')) {
      // Si hay alguna FK previa con nombre diferente podría fallar: atrapamos
      $pdo->exec("ALTER TABLE publicaciones ADD CONSTRAINT fk_pub_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE;");
      ok("FK 'fk_pub_vendedor' creada.");
    } else {
      ok("FK 'fk_pub_vendedor' OK.");
    }
  } catch (Throwable $e) {
    warn("No pude agregar FK 'fk_pub_vendedor' (puede tener otro nombre): ".$e->getMessage());
  }
} else {
  ok("Columna 'publicaciones.vendedor_id' OK.");
}

// === CONVERSACIONES ===
if (!tableExists($pdo,'conversaciones')) {
  $pdo->exec("
    CREATE TABLE conversaciones (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      comprador_id BIGINT UNSIGNED NOT NULL,
      vendedor_id BIGINT UNSIGNED NOT NULL,
      publicacion_id BIGINT UNSIGNED NOT NULL,
      creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_conv_pub (publicacion_id),
      KEY idx_conv_users (comprador_id, vendedor_id),
      CONSTRAINT fk_conv_comprador FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
      CONSTRAINT fk_conv_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
      CONSTRAINT fk_conv_pub FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  ok("Tabla 'conversaciones' creada.");
} else {
  ok("Tabla 'conversaciones' OK.");
  if (!engineInnoDB($pdo,'conversaciones')) {
    $pdo->exec("ALTER TABLE conversaciones ENGINE=InnoDB;");
    ok("Motor de 'conversaciones' convertido a InnoDB.");
  }
}

// === MENSAJES ===
if (!tableExists($pdo,'mensajes')) {
  $pdo->exec("
    CREATE TABLE mensajes (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      conversacion_id BIGINT UNSIGNED NOT NULL,
      emisor_id BIGINT UNSIGNED NOT NULL,
      contenido TEXT NOT NULL,
      leido TINYINT(1) NOT NULL DEFAULT 0,
      creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_msg_conv (conversacion_id),
      CONSTRAINT fk_msg_conv FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
      CONSTRAINT fk_msg_user FOREIGN KEY (emisor_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  ok("Tabla 'mensajes' creada.");
} else {
  ok("Tabla 'mensajes' OK.");
  if (!engineInnoDB($pdo,'mensajes')) {
    $pdo->exec("ALTER TABLE mensajes ENGINE=InnoDB;");
    ok("Motor de 'mensajes' convertido a InnoDB.");
  }
  if (!colExists($pdo,'mensajes','conversacion_id')) {
    $pdo->exec("ALTER TABLE mensajes ADD COLUMN conversacion_id BIGINT UNSIGNED NOT NULL AFTER id;");
    ok("Columna 'mensajes.conversacion_id' agregada.");
  } else ok("Columna 'mensajes.conversacion_id' OK.");
  if (!colExists($pdo,'mensajes','emisor_id')) {
    $pdo->exec("ALTER TABLE mensajes ADD COLUMN emisor_id BIGINT UNSIGNED NOT NULL AFTER conversacion_id;");
    ok("Columna 'mensajes.emisor_id' agregada.");
  } else ok("Columna 'mensajes.emisor_id' OK.");

  if (!indexExists($pdo,'mensajes','idx_msg_conv')) {
    $pdo->exec("ALTER TABLE mensajes ADD INDEX idx_msg_conv (conversacion_id);");
    ok("Índice 'idx_msg_conv' agregado.");
  } else ok("Índice 'idx_msg_conv' OK.");

  // FKs (con nombre estándar). Si ya existen con otro nombre: warning amistoso.
  try {
    if (!fkExists($pdo,'mensajes','fk_msg_conv')) {
      $pdo->exec("ALTER TABLE mensajes ADD CONSTRAINT fk_msg_conv FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE;");
      ok("FK 'fk_msg_conv' agregada.");
    } else ok("FK 'fk_msg_conv' OK.");
  } catch (Throwable $e) {
    warn("No pude agregar FK 'fk_msg_conv' (puede existir con otro nombre): ".$e->getMessage());
  }
  try {
    if (!fkExists($pdo,'mensajes','fk_msg_user')) {
      $pdo->exec("ALTER TABLE mensajes ADD CONSTRAINT fk_msg_user FOREIGN KEY (emisor_id) REFERENCES usuarios(id) ON DELETE CASCADE;");
      ok("FK 'fk_msg_user' agregada.");
    } else ok("FK 'fk_msg_user' OK.");
  } catch (Throwable $e) {
    warn("No pude agregar FK 'fk_msg_user' (puede existir con otro nombre): ".$e->getMessage());
  }
}

// === OFERTAS ===
if (!tableExists($pdo,'ofertas')) {
  $pdo->exec("
    CREATE TABLE ofertas (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      publicacion_id BIGINT UNSIGNED NOT NULL,
      comprador_id BIGINT UNSIGNED NOT NULL,
      precio_ofrecido DECIMAL(10,2) NOT NULL,
      estado ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
      creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_oferta_pub (publicacion_id),
      KEY idx_oferta_user (comprador_id),
      CONSTRAINT fk_oferta_pub FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
      CONSTRAINT fk_oferta_user FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  ok("Tabla 'ofertas' creada.");
} else {
  ok("Tabla 'ofertas' OK.");
  if (!engineInnoDB($pdo,'ofertas')) {
    $pdo->exec("ALTER TABLE ofertas ENGINE=InnoDB;");
    ok("Motor de 'ofertas' convertido a InnoDB.");
  }
  foreach (['publicacion_id','comprador_id','precio_ofrecido','estado'] as $c) {
    if (!colExists($pdo,'ofertas',$c)) {
      errx("Falta columna 'ofertas.$c'. Añádela manualmente (ver script original).");
    } else ok("Columna 'ofertas.$c' OK.");
  }
}

// === CHEQUEO DE ARCHIVOS (no repara, solo informa) ===
$root = realpath(__DIR__ . '/..'); // carpeta vestige_es
$filesPublic = [
  '/public/chat.php',
  '/public/chat_enviar.php',
  '/public/mis_chats.php',
  '/public/vendedor_panel.php',
  '/public/oferta_estado.php',
];
$filesSrc = [
  '/src/Conversacion.php',
  '/src/Mensaje.php',
  '/src/Oferta.php',
];

echo "<h2>Archivos requeridos</h2><ul>";
foreach ($filesPublic as $f){
  $p = $root . $f;
  if (file_exists($p)) ok("Existe $f");
  else errx("Falta $f");
}
foreach ($filesSrc as $f){
  $p = $root . $f;
  if (file_exists($p)) ok("Existe $f");
  else errx("Falta $f");
}
echo "</ul>";

// === CONSEJO FINAL ===
echo "<p style='margin-top:1rem'><a class='btn' href='".BASE_URL."/dashboard.php'>Volver al Dashboard</a></p>";

<?php
// public/verificador_chat.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/DB.php';

function ok($m){ echo "<li style='color:#0a0;'>✔ $m</li>"; }
function warn($m){ echo "<li style='color:#b58900;'>• $m</li>"; }
function err($m){ echo "<li style='color:#c00;'>✖ $m</li>"; }

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Helpers
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

echo "<h1>Verificador de Chat y Ofertas</h1>";
echo "<ol>";

// 1) Tablas base: usuarios, publicaciones (solo checamos presencia)
foreach (['usuarios','publicaciones'] as $base) {
  if (tableExists($pdo, $base)) ok("Tabla base '$base' OK.");
  else { err("Falta tabla base '$base'. Sin estas no se puede crear chat/ofertas."); echo "</ol>"; exit; }
}

// 2) Crear tablas si faltan
// conversaciones
if (!tableExists($pdo, 'conversaciones')) {
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
  ok("Tabla 'conversaciones' existe.");
}

// mensajes
if (!tableExists($pdo, 'mensajes')) {
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
  ok("Tabla 'mensajes' existe.");
  // asegurar columna conversacion_id
  if (!colExists($pdo,'mensajes','conversacion_id')) {
    $pdo->exec("ALTER TABLE mensajes ADD COLUMN conversacion_id BIGINT UNSIGNED NOT NULL AFTER id;");
    ok("Columna 'mensajes.conversacion_id' agregada.");
  } else {
    ok("Columna 'mensajes.conversacion_id' OK.");
  }
  // índice
  if (!indexExists($pdo,'mensajes','idx_msg_conv')) {
    $pdo->exec("ALTER TABLE mensajes ADD INDEX idx_msg_conv (conversacion_id);");
    ok("Índice 'idx_msg_conv' agregado.");
  } else {
    ok("Índice 'idx_msg_conv' OK.");
  }
  // FK conversacion
  if (!fkExists($pdo,'mensajes','fk_msg_conv')) {
    try {
      $pdo->exec("ALTER TABLE mensajes ADD CONSTRAINT fk_msg_conv FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE;");
      ok("FK 'fk_msg_conv' agregada.");
    } catch (Throwable $e) {
      warn("No pude agregar FK 'fk_msg_conv' (puede existir con otro nombre): ".$e->getMessage());
    }
  } else {
    ok("FK 'fk_msg_conv' OK.");
  }
  // FK emisor
  if (!fkExists($pdo,'mensajes','fk_msg_user')) {
    try {
      $pdo->exec("ALTER TABLE mensajes ADD CONSTRAINT fk_msg_user FOREIGN KEY (emisor_id) REFERENCES usuarios(id) ON DELETE CASCADE;");
      ok("FK 'fk_msg_user' agregada.");
    } catch (Throwable $e) {
      warn("No pude agregar FK 'fk_msg_user' (puede existir con otro nombre): ".$e->getMessage());
    }
  } else {
    ok("FK 'fk_msg_user' OK.");
  }
}

// ofertas
if (!tableExists($pdo, 'ofertas')) {
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
  ok("Tabla 'ofertas' existe.");
}

echo "</ol>";
echo "<p style='margin-top:1rem'><a href='".BASE_URL."/dashboard.php' class='btn'>Volver al Dashboard</a></p>";

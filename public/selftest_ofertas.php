<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/DB.php';

function out($m){ echo "<li>".$m."</li>"; }

echo "<h1>Selftest Ofertas</h1><ul>";

try {
    require_once __DIR__ . '/../src/Oferta.php';
    out("Cargado src/Oferta.php");
    if (!class_exists('Oferta')) { out("Clase Oferta NO existe"); die; }
    out("Clase Oferta existe");
    $ref = new ReflectionClass('Oferta');
    foreach (['crear','listarPorPublicacion','listarPorVendedor','cambiarEstado'] as $m) {
        out("Método $m: " . ($ref->hasMethod($m) ? "OK" : "FALTA"));
    }
    $pdo = DB::conn();
    $pdo->query("SELECT 1");
    out("Conexión DB OK");
} catch (Throwable $e) {
    out("ERROR: " . htmlspecialchars($e->getMessage()));
}
echo "</ul><p>Si todo está OK, vuelve a vendedor_panel.php</p>";

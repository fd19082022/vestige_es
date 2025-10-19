<?php
// public/dev/check_images.php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/DB.php';

$pdo = DB::conn();

function checkPath(string $rel): array {
    $rel = ltrim($rel, '/');
    $abs1 = __DIR__ . '/../' . $rel;          // public/<ruta>
    $abs2 = __DIR__ . '/../public/' . $rel;   // public/public/<ruta> (por si estuviera duplicado)
    $exists1 = is_file($abs1);
    $exists2 = is_file($abs2);
    return [
        'relative' => $rel,
        'abs_try_1' => $abs1,
        'exists_1' => $exists1 ? 'YES' : 'NO',
        'abs_try_2' => $abs2,
        'exists_2' => $exists2 ? 'YES' : 'NO',
    ];
}

$rows = $pdo->query("
  SELECT p.id, p.titulo,
         (SELECT ruta FROM publicaciones_imagenes i 
           WHERE i.publicacion_id = p.id 
           ORDER BY es_principal DESC, id ASC LIMIT 1) AS ruta
  FROM publicaciones p
  ORDER BY p.id DESC
  LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/plain; charset=utf-8');
echo "=== DIAGNÓSTICO DE IMÁGENES (publicaciones_imagenes.ruta) ===\n";
echo "Coloca tus imágenes en: " . realpath(__DIR__ . '/..') . "\n\n";

foreach ($rows as $r) {
    $ruta = $r['ruta'] ?: '(NULL)';
    echo "Publicación #{$r['id']} «{$r['titulo']}»\n";
    echo " - ruta BD: {$ruta}\n";
    if ($r['ruta']) {
        $info = checkPath($r['ruta']);
        echo " - intento 1: {$info['abs_try_1']} -> {$info['exists_1']}\n";
        echo " - intento 2: {$info['abs_try_2']} -> {$info['exists_2']}\n";
    } else {
        echo " - sin imagen asociada\n";
    }
    echo str_repeat('-', 70) . "\n";
}

// Extra: verifica imágenes del home (hero + categorías)
$assets = [
    'assets/imgs/hero_ropa.jpg',
    'assets/imgs/cat_mujer.jpg',
    'assets/imgs/cat_hombre.jpg',
    'assets/imgs/cat_nino.jpg',
    'assets/imgs/cat_accesorios.jpg',
    'assets/imgs/no-image.png',
];
echo "\n=== ASSETS DEL HOME ===\n";
foreach ($assets as $a) {
    $info = checkPath($a);
    echo " - {$a}\n";
    echo "   * {$info['abs_try_1']} -> {$info['exists_1']}\n";
}

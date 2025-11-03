<?php
// public/dev/check_images.php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/DB.php';

function asset_url(string $relative): string {
    $r = trim($relative);
    if ($r === '') return BASE_URL . '/assets/imgs/no-image.png';
    if (preg_match('~^(https?:)?//|^data:image/~i', $r)) return $r;
    $r = ltrim($r, '/');
    if (str_starts_with($r, 'public/')) $r = substr($r, 7);
    $cands = [ __DIR__ . '/../' . $r, __DIR__ . '/../public/' . $r ];
    foreach ($cands as $abs) {
        if (is_file($abs)) {
            $rel = str_replace('\\','/', str_replace(__DIR__ . '/../', '', $abs));
            return BASE_URL . '/' . $rel;
        }
    }
    return BASE_URL . '/assets/imgs/no-image.png';
}

function checkPath(string $rel): array {
    $rel = ltrim($rel, '/');
    if (str_starts_with($rel, 'public/')) $rel = substr($rel, 7);
    $abs1 = __DIR__ . '/../' . $rel;
    $abs2 = __DIR__ . '/../public/' . $rel;
    return [
        'rel' => $rel,
        'abs1' => $abs1, 'exists1' => is_file($abs1) ? 'YES' : 'NO',
        'abs2' => $abs2, 'exists2' => is_file($abs2) ? 'YES' : 'NO',
        'final_url' => asset_url($rel),
    ];
}

$pdo = DB::conn();
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

echo "=== PUBLICACIONES ===\n";
foreach ($rows as $r) {
    $ruta = $r['ruta'] ?: '(NULL)';
    echo "Pub #{$r['id']} «{$r['titulo']}»\n";
    echo "BD: {$ruta}\n";
    if ($r['ruta']) {
        $info = checkPath($r['ruta']);
        echo " try1: {$info['abs1']} -> {$info['exists1']}\n";
        echo " try2: {$info['abs2']} -> {$info['exists2']}\n";
        echo " URL : {$info['final_url']}\n";
    } else {
        echo " (sin imagen)\n";
    }
    echo str_repeat('-', 70) . "\n";
}

$assets = [
    'assets/imgs/hero_ropa.jpg',
    'assets/imgs/cat_mujer.jpg',
    'assets/imgs/cat_hombre.jpg',
    'assets/imgs/cat_nino.jpg',
    'assets/imgs/cat_accesorios.jpg',
    'assets/imgs/no-image.png',
];
echo "\n=== HOME ASSETS ===\n";
foreach ($assets as $a) {
    $info = checkPath($a);
    echo "{$a}\n";
    echo " try1: {$info['abs1']} -> {$info['exists1']}\n";
    echo " try2: {$info['abs2']} -> {$info['exists2']}\n";
    echo " URL : {$info['final_url']}\n";
    echo str_repeat('-', 70) . "\n";
}

echo "\nTIP: Coloca imágenes de publicaciones en /public/uploads/ y assets del home en /public/assets/imgs/\n";

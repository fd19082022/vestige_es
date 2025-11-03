<?php
require_once __DIR__ . '/DB.php';

class Oferta
{
    public static function crear(int $publicacion_id, int $comprador_id, float $precio)
    {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("INSERT INTO ofertas (publicacion_id, comprador_id, precio_ofrecido) VALUES (?,?,?)");
        return $stmt->execute([$publicacion_id, $comprador_id, $precio]);
    }

    public static function listarPorPublicacion(int $publicacion_id)
    {
        $pdo = DB::conn();
        $sql = "SELECT o.*, u.nombre AS comprador_nombre
                FROM ofertas o
                JOIN usuarios u ON u.id = o.comprador_id
                WHERE o.publicacion_id = ?
                ORDER BY o.creado_en DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$publicacion_id]);
        return $stmt->fetchAll();
    }

    public static function listarPorVendedor(int $vendedor_id)
    {
        $pdo = DB::conn();
        $sql = "SELECT o., 
                       p.titulo AS publicacion_titulo, p.vendedor_id,
                       u.nombre AS comprador_nombre
                FROM ofertas o
                JOIN publicaciones p ON p.id = o.publicacion_id
                JOIN usuarios u ON u.id = o.comprador_id
                WHERE p.vendedor_id = ?
                ORDER BY o.estado != 'pendiente' ASC, o.creado_en DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vendedor_id]);
        return $stmt->fetchAll();
    }

    public static function cambiarEstado(int $oferta_id, int $vendedor_id, string $estado): bool
    {
        $estado = trim(strtolower($estado));
        if (!in_array($estado, ['pendiente','aceptada','rechazada'], true)) {
            return false;
        }
        $pdo = DB::conn();
        $chk = $pdo->prepare("SELECT o.id
                              FROM ofertas o
                              JOIN publicaciones p ON p.id = o.publicacion_id
                              WHERE o.id = ? AND p.vendedor_id = ?
                              LIMIT 1");
        $chk->execute([$oferta_id, $vendedor_id]);
        if (!$chk->fetchColumn()) return false;

        $upd = $pdo->prepare("UPDATE ofertas SET estado = ? WHERE id = ?");
        return $upd->execute([$estado, $oferta_id]);
    }
}

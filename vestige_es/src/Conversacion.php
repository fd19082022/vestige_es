<?php
require_once __DIR__ . '/DB.php';

class Conversacion
{
    public static function obtenerOCrear(int $comprador_id, int $vendedor_id, int $publicacion_id)
    {
        $pdo = DB::conn();
        $sql = "SELECT id FROM conversaciones 
                WHERE comprador_id = ? AND vendedor_id = ? AND publicacion_id = ? 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$comprador_id, $vendedor_id, $publicacion_id]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;

        $ins = $pdo->prepare("INSERT INTO conversaciones (comprador_id, vendedor_id, publicacion_id) VALUES (?,?,?)");
        $ins->execute([$comprador_id, $vendedor_id, $publicacion_id]);
        return (int)$pdo->lastInsertId();
    }

    public static function obtener(int $id)
    {
        $pdo = DB::conn();
        $sql = "SELECT c.*, 
                       uc.nombre AS comprador_nombre, 
                       uv.nombre AS vendedor_nombre,
                       p.titulo AS publicacion_titulo
                FROM conversaciones c
                JOIN usuarios uc ON uc.id = c.comprador_id
                JOIN usuarios uv ON uv.id = c.vendedor_id
                JOIN publicaciones p ON p.id = c.publicacion_id
                WHERE c.id = ?
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function listarDelUsuario(int $usuario_id)
    {
        $pdo = DB::conn();
        $sql = "SELECT c.*, p.titulo AS publicacion_titulo,
                       CASE WHEN c.comprador_id = ? THEN uv.nombre ELSE uc.nombre END AS otro_nombre
                FROM conversaciones c
                JOIN publicaciones p ON p.id = c.publicacion_id
                JOIN usuarios uc ON uc.id = c.comprador_id
                JOIN usuarios uv ON uv.id = c.vendedor_id
                WHERE c.comprador_id = ? OR c.vendedor_id = ?
                ORDER BY c.creado_en DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $usuario_id, $usuario_id]);
        return $stmt->fetchAll();
    }
}
?>
<?php

require_once __DIR__ . '/../config/database.php';

class Favorito {
    public static function alternar(int $usuario_id, int $publicacion_id): bool {
        $pdo = db_conectar();

        $stmt = $pdo->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND publicacion_id = ?");
        $stmt->execute([$usuario_id, $publicacion_id]);
        if ($stmt->fetch()) {

            $del = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND publicacion_id = ?");
            return $del->execute([$usuario_id, $publicacion_id]);
        } else {

            $ins = $pdo->prepare("INSERT INTO favoritos (usuario_id, publicacion_id, creado_en) VALUES (?, ?, NOW())");
            return $ins->execute([$usuario_id, $publicacion_id]);
        }
    }

    public static function listar_por_usuario(int $usuario_id): array {
        $pdo = db_conectar();
        $sql = "SELECT 
                p.id, p.titulo, p.descripcion, p.precio_bs, p.estado_id, p.estado,
                p.es_subasta, p.fecha_inicio_subasta, p.fecha_fin_subasta,
                p.imagen_principal,
                p.categoria_id, c.nombre AS categoria, p.subcategoria_id,
                t.nombre AS talla, co.nombre AS color,
                p.vendedor_id, u.nombre AS vendedor,
                COALESCE(p.imagen_principal, (
                  SELECT ruta FROM publicaciones_imagenes pi
                  WHERE pi.publicacion_id = p.id AND pi.es_principal = 1
                  ORDER BY pi.id ASC LIMIT 1
                )) AS principal_img
                FROM favoritos f
                INNER JOIN publicaciones p ON p.id = f.publicacion_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN tallas t ON t.id = p.talla_id
                LEFT JOIN colores co ON co.id = p.color_id
                LEFT JOIN usuarios u ON u.id = p.vendedor_id
                WHERE f.usuario_id = ?
                ORDER BY f.creado_en DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll();
    }

    public static function es_favorito(int $usuario_id, int $publicacion_id): bool {
        $pdo = db_conectar();
        $stmt = $pdo->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND publicacion_id = ?");
        $stmt->execute([$usuario_id, $publicacion_id]);
        return (bool)$stmt->fetch();
    }
}
?>
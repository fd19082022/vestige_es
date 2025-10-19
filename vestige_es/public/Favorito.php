<?php
// src/Favorito.php
// Implementación mínima orientada a tu esquema vestige_es (8).sql

require_once __DIR__ . '/DB.php';

class Favorito
{
    /**
     * Verifica si una publicación ya está en favoritos de un usuario.
     */
    public static function es_favorito(int $usuario_id, int $publicacion_id): bool
    {
        if ($usuario_id <= 0 || $publicacion_id <= 0) return false;
        $pdo = DB::conn();
        $st = $pdo->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND publicacion_id = ? LIMIT 1");
        $st->execute([$usuario_id, $publicacion_id]);
        return (bool)$st->fetchColumn();
    }

    /**
     * Alterna favorito: si existe lo elimina, si no existe lo inserta.
     * Usa la UNIQUE (usuario_id, publicacion_id) definida en la BD.
     */
    public static function alternar(int $usuario_id, int $publicacion_id): bool
    {
        if ($usuario_id <= 0 || $publicacion_id <= 0) return false;
        $pdo = DB::conn();

        if (self::es_favorito($usuario_id, $publicacion_id)) {
            // Quitar de favoritos
            $del = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND publicacion_id = ?");
            return $del->execute([$usuario_id, $publicacion_id]);
        } else {
            // Agregar a favoritos
            $ins = $pdo->prepare("INSERT INTO favoritos (usuario_id, publicacion_id) VALUES (?, ?)");
            return $ins->execute([$usuario_id, $publicacion_id]);
        }
    }

    /**
     * Lista los favoritos de un usuario (puedes usarlo en tu página de “Mis favoritos”).
     */
    public static function listar_por_usuario(int $usuario_id, int $limit = 60): array
    {
        if ($usuario_id <= 0) return [];
        $pdo = DB::conn();
        $sql = "SELECT p.*,
                   (SELECT ruta FROM publicaciones_imagenes pi
                     WHERE pi.publicacion_id = p.id AND pi.es_principal = 1
                     ORDER BY pi.id ASC LIMIT 1) AS principal_img
                FROM favoritos f
                JOIN publicaciones p ON p.id = f.publicacion_id
                WHERE f.usuario_id = ?
                ORDER BY f.creado_en DESC
                LIMIT ?";
        $st = $pdo->prepare($sql);
        $st->execute([$usuario_id, $limit]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta cuántas veces fue marcado como favorito una publicación (opcional).
     */
    public static function contar_por_publicacion(int $publicacion_id): int
    {
        if ($publicacion_id <= 0) return 0;
        $pdo = DB::conn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE publicacion_id = ?");
        $st->execute([$publicacion_id]);
        return (int)$st->fetchColumn();
    }
}

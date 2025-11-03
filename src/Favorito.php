<?php
/**
 * src/Favorito.php
 * Gestión de favoritos - CORREGIDA
 * 
 * Cambios:
 * - Mejor manejo de errores
 * - Usa DB::conn()
 * - Paginación en listados
 */

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Helper.php';

class Favorito {

    /**
     * Alternar favorito (agregar/quitar)
     * 
     * @param int $usuario_id
     * @param int $publicacion_id
     * @return bool
     */
    public static function alternar(int $usuario_id, int $publicacion_id): bool {
        if ($usuario_id <= 0 || $publicacion_id <= 0) {
            return false;
        }

        try {
            $pdo = DB::conn();

            // Validar que publicación existe
            $stmt = $pdo->prepare("SELECT id FROM publicaciones WHERE id = ? LIMIT 1");
            $stmt->execute([$publicacion_id]);
            if (!$stmt->fetch()) {
                return false;
            }

            // Verificar si ya es favorito
            if (self::esFavorito($usuario_id, $publicacion_id)) {
                // Quitar de favoritos
                $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND publicacion_id = ?");
                return $stmt->execute([$usuario_id, $publicacion_id]);
            } else {
                // Agregar a favoritos
                $stmt = $pdo->prepare("
                    INSERT INTO favoritos (usuario_id, publicacion_id)
                    VALUES (?, ?)
                ");
                return $stmt->execute([$usuario_id, $publicacion_id]);
            }

        } catch (Exception $e) {
            // Si es por duplicado (UNIQUE constraint), es porque ya existe
            // Silenciar el error en este caso
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                return true;
            }
            return false;
        }
    }

    /**
     * Verificar si es favorito
     * 
     * @param int $usuario_id
     * @param int $publicacion_id
     * @return bool
     */
    public static function esFavorito(int $usuario_id, int $publicacion_id): bool {
        if ($usuario_id <= 0 || $publicacion_id <= 0) {
            return false;
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("
                SELECT 1 FROM favoritos 
                WHERE usuario_id = ? AND publicacion_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$usuario_id, $publicacion_id]);

            return (bool)$stmt->fetch();

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Listar favoritos de usuario con paginación
     * 
     * @param int $usuario_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function listarPorUsuario(int $usuario_id, int $limit = 60, int $offset = 0): array {
        if ($usuario_id <= 0) {
            return [];
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.titulo,
                    p.descripcion,
                    p.precio_bs,
                    p.estado_id,
                    p.categoria_id,
                    p.talla_id,
                    p.color_id,
                    p.vendedor_id,
                    p.creado_en,
                    COALESCE(pi.ruta, p.imagen_principal) AS principal_img,
                    c.nombre AS categoria,
                    t.nombre AS talla,
                    co.nombre AS color,
                    u.nombre AS vendedor,
                    f.creado_en AS favorito_desde
                FROM favoritos f
                INNER JOIN publicaciones p ON p.id = f.publicacion_id
                LEFT JOIN publicaciones_imagenes pi 
                    ON pi.publicacion_id = p.id AND pi.es_principal = 1
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN tallas t ON t.id = p.talla_id
                LEFT JOIN colores co ON co.id = p.color_id
                LEFT JOIN usuarios u ON u.id = p.vendedor_id
                WHERE f.usuario_id = ?
                ORDER BY f.creado_en DESC
                LIMIT ? OFFSET ?
            ");

            $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Contar favoritos de usuario
     * 
     * @param int $usuario_id
     * @return int
     */
    public static function contar(int $usuario_id): int {
        if ($usuario_id <= 0) {
            return 0;
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);

            return (int)$stmt->fetchColumn();

        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Contar favoritos de una publicación
     * 
     * @param int $publicacion_id
     * @return int
     */
    public static function contarPorPublicacion(int $publicacion_id): int {
        if ($publicacion_id <= 0) {
            return 0;
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE publicacion_id = ?");
            $stmt->execute([$publicacion_id]);

            return (int)$stmt->fetchColumn();

        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Eliminar todos los favoritos de un usuario
     * 
     * @param int $usuario_id
     * @return bool
     */
    public static function limpiar(int $usuario_id): bool {
        if ($usuario_id <= 0) {
            return false;
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ?");
            return $stmt->execute([$usuario_id]);

        } catch (Exception $e) {
            return false;
        }
    }
}
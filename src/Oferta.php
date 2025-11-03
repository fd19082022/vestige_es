<?php
/**
 * src/Oferta.php
 * Gestión de ofertas - CORREGIDA
 * 
 * Cambios:
 * - Validación de precio mínimo
 * - Mejor manejo de errores
 * - Validación de estado_id
 */

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Helper.php';

class Oferta {

    /**
     * Crear oferta
     * 
     * @param int $publicacion_id
     * @param int $comprador_id
     * @param float $precio
     * @return int ID de oferta
     * @throws Exception
     */
    public static function crear(int $publicacion_id, int $comprador_id, float $precio): int {
        if ($publicacion_id <= 0 || $comprador_id <= 0) {
            throw new Exception("Parámetros inválidos");
        }

        $precio = (float)$precio;

        if ($precio <= 0) {
            throw new Exception("El precio debe ser mayor a 0");
        }

        if ($precio > 999999.99) {
            throw new Exception("El precio es demasiado alto");
        }

        try {
            $pdo = DB::conn();

            // Validar que publicación existe
            $stmt = $pdo->prepare("SELECT id, vendedor_id FROM publicaciones WHERE id = ? LIMIT 1");
            $stmt->execute([$publicacion_id]);
            $pub = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pub) {
                throw new Exception("Publicación no existe");
            }

            // Prevenir: El vendedor no puede ofertar en su propia publicación
            if ((int)$pub['vendedor_id'] === $comprador_id) {
                throw new Exception("No puedes ofertar en tu propia publicación");
            }

            // Validar que usuario existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND estado = 'activo' LIMIT 1");
            $stmt->execute([$comprador_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Comprador no válido o inactivo");
            }

            // Insertar oferta
            $stmt = $pdo->prepare("
                INSERT INTO ofertas 
                (publicacion_id, comprador_id, precio_ofrecido, estado) 
                VALUES (?, ?, ?, 'pendiente')
            ");

            $stmt->execute([$publicacion_id, $comprador_id, $precio]);

            return (int)$pdo->lastInsertId();

        } catch (PDOException $e) {
            throw new Exception("Error al crear oferta: " . $e->getMessage());
        }
    }

    /**
     * Listar ofertas por publicación
     * 
     * @param int $publicacion_id
     * @return array
     */
    public static function listarPorPublicacion(int $publicacion_id): array {
        if ($publicacion_id <= 0) {
            return [];
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("
                SELECT o.*, u.nombre AS comprador_nombre, u.correo AS comprador_correo
                FROM ofertas o
                JOIN usuarios u ON u.id = o.comprador_id
                WHERE o.publicacion_id = ?
                ORDER BY o.estado != 'pendiente' ASC, o.creado_en DESC
            ");
            $stmt->execute([$publicacion_id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Listar ofertas recibidas por vendedor
     * 
     * @param int $vendedor_id
     * @param int $limit
     * @return array
     */
    public static function listarPorVendedor(int $vendedor_id, int $limit = 100): array {
        if ($vendedor_id <= 0) {
            return [];
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("
                SELECT 
                    o.id,
                    o.publicacion_id,
                    o.comprador_id,
                    o.precio_ofrecido,
                    o.estado,
                    o.creado_en,
                    p.titulo AS publicacion_titulo,
                    p.vendedor_id,
                    u.nombre AS comprador_nombre,
                    u.correo AS comprador_correo
                FROM ofertas o
                JOIN publicaciones p ON p.id = o.publicacion_id
                JOIN usuarios u ON u.id = o.comprador_id
                WHERE p.vendedor_id = ?
                ORDER BY o.estado != 'pendiente' ASC, o.creado_en DESC
                LIMIT ?
            ");

            $stmt->bindValue(1, $vendedor_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Cambiar estado de oferta
     * 
     * @param int $oferta_id
     * @param int $vendedor_id
     * @param string $estado
     * @return bool
     */
    public static function cambiarEstado(int $oferta_id, int $vendedor_id, string $estado): bool {
        if ($oferta_id <= 0 || $vendedor_id <= 0) {
            return false;
        }

        $estado = strtolower(trim($estado));

        // Validar estado
        if (!in_array($estado, ['pendiente', 'aceptada', 'rechazada'], true)) {
            return false;
        }

        try {
            $pdo = DB::conn();

            // Verificar que la oferta pertenece a una publicación del vendedor
            $stmt = $pdo->prepare("
                SELECT o.id
                FROM ofertas o
                JOIN publicaciones p ON p.id = o.publicacion_id
                WHERE o.id = ? AND p.vendedor_id = ?
                LIMIT 1
            ");
            $stmt->execute([$oferta_id, $vendedor_id]);

            if (!$stmt->fetch()) {
                return false;
            }

            // Actualizar estado
            $stmt = $pdo->prepare("UPDATE ofertas SET estado = ? WHERE id = ?");
            return $stmt->execute([$estado, $oferta_id]);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener oferta por ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function obtener(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("
                SELECT o.*, 
                    p.titulo AS publicacion_titulo,
                    u.nombre AS comprador_nombre
                FROM ofertas o
                JOIN publicaciones p ON p.id = o.publicacion_id
                JOIN usuarios u ON u.id = o.comprador_id
                WHERE o.id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Eliminar oferta
     * 
     * @param int $id
     * @return bool
     */
    public static function eliminar(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            $pdo = DB::conn();
            $stmt = $pdo->prepare("DELETE FROM ofertas WHERE id = ?");
            return $stmt->execute([$id]);

        } catch (Exception $e) {
            return false;
        }
    }
}
<?php
/**
 * src/Conversacion.php
 * Gestión de conversaciones - CORREGIDA
 * 
 * Cambios:
 * - Mejor validación
 * - Previene crear conversación consigo mismo
 * - Manejo de errores mejorado
 */

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Helper.php';

class Conversacion {

    /**
     * Obtener o crear conversación
     * 
     * @param int $comprador_id
     * @param int $vendedor_id
     * @param int $publicacion_id
     * @return int ID de conversación
     * @throws Exception
     */
    public static function obtenerOCrear(int $comprador_id, int $vendedor_id, int $publicacion_id): int {
        if ($comprador_id <= 0 || $vendedor_id <= 0 || $publicacion_id <= 0) {
            throw new Exception("Parámetros inválidos");
        }

        // PREVENIR: No permitir conversación consigo mismo
        if ($comprador_id === $vendedor_id) {
            throw new Exception("No puedes iniciar conversación contigo mismo");
        }

        try {
            $pdo = DB::conn();

            // Buscar conversación existente
            $stmt = $pdo->prepare("
                SELECT id FROM conversaciones 
                WHERE comprador_id = ? 
                AND vendedor_id = ? 
                AND publicacion_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$comprador_id, $vendedor_id, $publicacion_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                return (int)$existing['id'];
            }

            // Crear nueva conversación
            $stmt = $pdo->prepare("
                INSERT INTO conversaciones 
                (comprador_id, vendedor_id, publicacion_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$comprador_id, $vendedor_id, $publicacion_id]);

            return (int)$pdo->lastInsertId();

        } catch (Exception $e) {
            throw new Exception("Error al obtener/crear conversación: " . $e->getMessage());
        }
    }

    /**
     * Obtener conversación completa
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
                SELECT 
                    c.id,
                    c.comprador_id,
                    c.vendedor_id,
                    c.publicacion_id,
                    c.creado_en,
                    uc.nombre AS comprador_nombre,
                    uv.nombre AS vendedor_nombre,
                    p.titulo AS publicacion_titulo
                FROM conversaciones c
                JOIN usuarios uc ON uc.id = c.comprador_id
                JOIN usuarios uv ON uv.id = c.vendedor_id
                JOIN publicaciones p ON p.id = c.publicacion_id
                WHERE c.id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Listar conversaciones de un usuario
     * 
     * @param int $usuario_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function listarDelUsuario(int $usuario_id, int $limit = 50, int $offset = 0): array {
        if ($usuario_id <= 0) {
            return [];
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.comprador_id,
                    c.vendedor_id,
                    c.publicacion_id,
                    c.creado_en,
                    p.titulo AS publicacion_titulo,
                    CASE 
                        WHEN c.comprador_id = ? THEN uv.nombre 
                        ELSE uc.nombre 
                    END AS otro_nombre,
                    CASE 
                        WHEN c.comprador_id = ? THEN uv.id 
                        ELSE uc.id 
                    END AS otro_id
                FROM conversaciones c
                JOIN publicaciones p ON p.id = c.publicacion_id
                JOIN usuarios uc ON uc.id = c.comprador_id
                JOIN usuarios uv ON uv.id = c.vendedor_id
                WHERE c.comprador_id = ? OR c.vendedor_id = ?
                ORDER BY c.creado_en DESC
                LIMIT ? OFFSET ?
            ");

            $stmt->execute([$usuario_id, $usuario_id, $usuario_id, $usuario_id, $limit, $offset]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Contar conversaciones no leídas
     * 
     * @param int $usuario_id
     * @return int
     */
    public static function contarNoLedas(int $usuario_id): int {
        if ($usuario_id <= 0) {
            return 0;
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT m.conversacion_id)
                FROM mensajes m
                JOIN conversaciones c ON c.id = m.conversacion_id
                WHERE m.destinatario_id = ? AND m.leido = 0
            ");
            $stmt->execute([$usuario_id]);

            return (int)$stmt->fetchColumn();

        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Eliminar conversación
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
            $pdo->beginTransaction();

            // Eliminar mensajes (cascada)
            $stmt = $pdo->prepare("DELETE FROM mensajes WHERE conversacion_id = ?");
            $stmt->execute([$id]);

            // Eliminar conversación
            $stmt = $pdo->prepare("DELETE FROM conversaciones WHERE id = ?");
            $result = $stmt->execute([$id]);

            $pdo->commit();
            return $result;

        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
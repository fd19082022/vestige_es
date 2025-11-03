<?php
/**
 * src/Mensaje.php
 * Gestión de mensajes - CORREGIDA
 * 
 * Cambios:
 * - SQL injection arreglado (sin concatenación)
 * - Mejor manejo de columnas dinámicas
 * - Validación mejorada
 */

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Helper.php';

class Mensaje {
    
    /**
     * Crear nuevo mensaje
     * 
     * @param int $conversacion_id
     * @param int $emisor_id
     * @param string $contenido
     * @return bool
     * @throws Exception
     */
    public static function crear(int $conversacion_id, int $emisor_id, string $contenido): bool {
        $contenido = trim($contenido);
        
        if ($conversacion_id <= 0) {
            throw new Exception("ID de conversación inválido");
        }
        
        if ($emisor_id <= 0) {
            throw new Exception("ID de emisor inválido");
        }
        
        if (strlen($contenido) === 0 || strlen($contenido) > 10000) {
            throw new Exception("El mensaje debe tener entre 1 y 10000 caracteres");
        }

        try {
            $pdo = DB::conn();

            // 1) Verificar que la conversación existe y obtener participantes
            $stmt = $pdo->prepare("
                SELECT comprador_id, vendedor_id 
                FROM conversaciones 
                WHERE id = ? 
                LIMIT 1
            ");
            $stmt->execute([$conversacion_id]);
            $conv = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conv) {
                throw new Exception("Conversación no encontrada");
            }

            $comprador_id = (int)$conv['comprador_id'];
            $vendedor_id = (int)$conv['vendedor_id'];

            // 2) Verificar que el emisor pertenece a la conversación
            if ($emisor_id !== $comprador_id && $emisor_id !== $vendedor_id) {
                throw new Exception("No tienes permiso para enviar mensajes en esta conversación");
            }

            // 3) Calcular destinatario
            $destinatario_id = ($emisor_id === $comprador_id) ? $vendedor_id : $comprador_id;

            // 4) Insertar mensaje (prepared statement, sin concatenación)
            $stmt = $pdo->prepare("
                INSERT INTO mensajes 
                    (conversacion_id, emisor_id, destinatario_id, contenido, leido) 
                VALUES 
                    (?, ?, ?, ?, 0)
            ");

            return $stmt->execute([$conversacion_id, $emisor_id, $destinatario_id, $contenido]);

        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw new Exception("Error BD: " . $e->getMessage());
            }
            throw new Exception("No se pudo crear el mensaje");
        }
    }

    /**
     * Listar mensajes de una conversación
     * 
     * @param int $conversacion_id
     * @param int $limit
     * @return array
     */
    public static function listar(int $conversacion_id, int $limit = 100): array {
        if ($conversacion_id <= 0) {
            return [];
        }

        try {
            $pdo = DB::conn();
            $limit = max(1, min($limit, 1000));

            $stmt = $pdo->prepare("
                SELECT m.*, u.nombre AS emisor_nombre
                FROM mensajes m
                JOIN usuarios u ON u.id = m.emisor_id
                WHERE m.conversacion_id = ?
                ORDER BY m.creado_en ASC, m.id ASC
                LIMIT ?
            ");
            
            $stmt->bindValue(1, $conversacion_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            if (APP_DEBUG) {
                throw $e;
            }
            return [];
        }
    }

    /**
     * Marcar mensajes como leídos
     * 
     * @param int $conversacion_id
     * @param int $usuario_id
     * @return bool
     */
    public static function marcarLeido(int $conversacion_id, int $usuario_id): bool {
        if ($conversacion_id <= 0 || $usuario_id <= 0) {
            return false;
        }

        try {
            $pdo = DB::conn();
            $stmt = $pdo->prepare("
                UPDATE mensajes 
                SET leido = 1 
                WHERE conversacion_id = ? 
                AND destinatario_id = ? 
                AND leido = 0
            ");

            return $stmt->execute([$conversacion_id, $usuario_id]);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener cantidad de mensajes no leídos
     * 
     * @param int $usuario_id
     * @return int
     */
    public static function contarNoLedos(int $usuario_id): int {
        if ($usuario_id <= 0) {
            return 0;
        }

        try {
            $pdo = DB::conn();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM mensajes 
                WHERE destinatario_id = ? AND leido = 0
            ");
            $stmt->execute([$usuario_id]);

            return (int)$stmt->fetchColumn();

        } catch (Exception $e) {
            return 0;
        }
    }
}
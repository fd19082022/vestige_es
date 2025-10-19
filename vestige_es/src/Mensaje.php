<?php
require_once __DIR__ . '/DB.php';

class Mensaje
{
    private static function hasColumn(PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch();
    }

    private static function senderCol(PDO $pdo): string {
        // Si existe remitente_id, lo usamos; si no, usamos emisor_id
        if (self::hasColumn($pdo, 'mensajes', 'remitente_id')) return 'remitente_id';
        return 'emisor_id';
    }

    private static function hasDest(PDO $pdo): bool {
        return self::hasColumn($pdo, 'mensajes', 'destinatario_id');
    }

    public static function crear(int $conversacion_id, int $usuario_envia_id, string $contenido)
    {
        $contenido = trim($contenido);
        if ($contenido === '') return false;

        $pdo = DB::conn();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1) Participantes de la conversación
        $stmt = $pdo->prepare("SELECT comprador_id, vendedor_id FROM conversaciones WHERE id = ? LIMIT 1");
        $stmt->execute([$conversacion_id]);
        $cv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cv) return false;

        $comprador_id = (int)$cv['comprador_id'];
        $vendedor_id  = (int)$cv['vendedor_id'];

        // El usuario que envía debe pertenecer a la conversación
        if ($usuario_envia_id !== $comprador_id && $usuario_envia_id !== $vendedor_id) {
            return false;
        }

        // 2) Calcular destinatario
        $destinatario_id = ($usuario_envia_id === $comprador_id) ? $vendedor_id : $comprador_id;

        // 3) Armar INSERT según el esquema real
        $senderCol = self::senderCol($pdo);
        $hasDest   = self::hasDest($pdo);

        if ($hasDest) {
            $sql = "INSERT INTO mensajes (conversacion_id, {$senderCol}, destinatario_id, contenido) VALUES (?,?,?,?)";
            $params = [$conversacion_id, $usuario_envia_id, $destinatario_id, $contenido];
        } else {
            $sql = "INSERT INTO mensajes (conversacion_id, {$senderCol}, contenido) VALUES (?,?,?)";
            $params = [$conversacion_id, $usuario_envia_id, $contenido];
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function listar(int $conversacion_id): array
    {
        $pdo = DB::conn();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $senderCol = self::senderCol($pdo);
        $sql = "SELECT m.*, u.nombre AS emisor_nombre
                FROM mensajes m
                JOIN usuarios u ON u.id = m.{$senderCol}
                WHERE m.conversacion_id = ?
                ORDER BY m.creado_en ASC, m.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$conversacion_id]);
        return $stmt->fetchAll() ?: [];
    }

    public static function ultimosDeUsuario(int $usuario_id, int $limit = 50): array
    {
        $pdo = DB::conn();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $senderCol = self::senderCol($pdo);
        $hasDest   = self::hasDest($pdo);
        $limit = max(1, min(200, $limit));

        if ($hasDest) {
            $sql = "SELECT m.*, c.publicacion_id
                    FROM mensajes m
                    JOIN conversaciones c ON c.id = m.conversacion_id
                    WHERE m.{$senderCol} = ? OR m.destinatario_id = ?
                    ORDER BY m.creado_en DESC, m.id DESC
                    LIMIT {$limit}";
            $params = [$usuario_id, $usuario_id];
        } else {
            // Sin destinatario_id, solo podemos filtrar por el remitente/emisor
            $sql = "SELECT m.*, c.publicacion_id
                    FROM mensajes m
                    JOIN conversaciones c ON c.id = m.conversacion_id
                    WHERE m.{$senderCol} = ?
                    ORDER BY m.creado_en DESC, m.id DESC
                    LIMIT {$limit}";
            $params = [$usuario_id];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}
?>

<?php

require_once __DIR__ . '/../config/database.php';

class Usuario {
    public static function crear(array $data): int {
        $pdo = db_conectar();


        $rolDeseado = 'comprador';
        $stmtRol = $pdo->prepare("SELECT id FROM roles WHERE nombre = ? LIMIT 1");
        $stmtRol->execute([$rolDeseado]);
        $rowRol = $stmtRol->fetch();
        $rolId = $rowRol ? (int)$rowRol['id'] : null;

        if ($rolId === null) {
            throw new RuntimeException("No existe el rol '$rolDeseado' en la tabla roles.");
        }


        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, correo, password_hash, telefono, rol_id, creado_en)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['nombre'],
            $data['correo'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['telefono'] ?? null,
            $rolId
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function obtener_por_id(int $id): ?array {
        $pdo = db_conectar();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function actualizar(int $id, array $data): bool {
        $pdo = db_conectar();
        $campos = [];
        $valores = [];
        if (isset($data['nombre']))   { $campos[] = "nombre = ?";         $valores[] = $data['nombre']; }
        if (isset($data['telefono'])) { $campos[] = "telefono = ?";       $valores[] = $data['telefono']; }
        if (isset($data['password']) && $data['password']) {
            $campos[] = "password_hash = ?"; $valores[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (!$campos) return false;
        $valores[] = $id;
        $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id = ?";
        return $pdo->prepare($sql)->execute($valores);
    }
}

?>
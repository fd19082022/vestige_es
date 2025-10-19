<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Helper.php';

class Auth {
    public static function login(string $correo, string $password): bool {
        $pdo = db_conectar();
        $stmt = $pdo->prepare("
    SELECT id, nombre, correo, password_hash, rol_id FROM usuarios
    WHERE correo = ?
    LIMIT 1
");

        $stmt->execute([$correo]);
        $u = $stmt->fetch();
        if (!$u) return false;
        if (!password_verify($password, $u['password_hash'])) return false;
        $_SESSION['usuario_id'] = (int)$u['id'];
        $_SESSION['usuario_nombre'] = $u['nombre'];
        $_SESSION['usuario_correo'] = $u['correo'];
        $_SESSION['rol_id'] = (int)$u['rol_id'];
        return true;
    }

    public static function logout() {
        session_unset();
        session_destroy();
        session_start();
        Helper::flash_mensaje('Sesión cerrada correctamente.', 'success');
    }
}
?>
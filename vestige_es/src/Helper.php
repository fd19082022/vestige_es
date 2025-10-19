<?php

class Helper {
    public static function limpiar(string $texto): string {
        return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
    }
    public static function redir(string $ruta) {
        header('Location: ' . $ruta);
        exit;
    }
    public static function flash_mensaje(string $mensaje, string $tipo = 'info') {
        $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }
    public static function obtener_flash(): array {
        $msgs = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $msgs;
    }
    public static function usuario_actual_id(): ?int {
        return $_SESSION['usuario_id'] ?? null;
    }
    public static function esta_logueado(): bool {
        return isset($_SESSION['usuario_id']);
    }
        // ==========================================================
    // CSRF Protection
    // ==========================================================
    public static function csrf_token()
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function csrf_validar($token)
    {
        return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }

}
?>
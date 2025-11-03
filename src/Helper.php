<?php
/**
 * src/Helper.php
 * Funciones auxiliares - CORREGIDAS
 * 
 * Cambios:
 * - Sanitización vs Validación claramente separadas
 * - Mejor manejo de CSRF
 * - Alertas estandarizadas
 * - Validación mejorada
 */

require_once __DIR__ . '/../config/loader.php';

class Helper {
    
    /**
     * Sanitizar texto para output en HTML
     * NOTA: Solo para mostrar en HTML, NO para DB
     * 
     * @param string $texto
     * @return string
     */
    public static function sanitizeOutput(string $texto): string {
        return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Alias para compatibilidad - DEPRECADO: usar sanitizeOutput
     * 
     * @param string $texto
     * @return string
     */
    public static function limpiar(string $texto): string {
        return self::sanitizeOutput($texto);
    }

    /**
     * Validar email
     * 
     * @param string $email
     * @return bool
     */
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validar URL
     * 
     * @param string $url
     * @return bool
     */
    public static function validateUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validar número entero
     * 
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return bool
     */
    public static function validateInt($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): bool {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return $int !== false && $int >= $min && $int <= $max;
    }

    /**
     * Validar número flotante
     * 
     * @param mixed $value
     * @param float $min
     * @param float $max
     * @return bool
     */
    public static function validateFloat($value, float $min = PHP_FLOAT_MIN, float $max = PHP_FLOAT_MAX): bool {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $float !== false && $float >= $min && $float <= $max;
    }

    /**
     * Redirigir a URL
     * 
     * @param string $url
     * @param int $code Código HTTP (302, 301, etc)
     * @return void
     */
    public static function redirect(string $url, int $code = 302): void {
        header("Location: " . $url, true, $code);
        exit;
    }

    /**
     * Alias compatibilidad - DEPRECADO: usar redirect()
     */
    public static function redir(string $ruta): void {
        self::redirect($ruta);
    }

    /**
     * Guardar mensaje flash en sesión
     * 
     * @param string $mensaje
     * @param string $tipo success|error|info|warning|danger
     * @return void
     */
    public static function setFlash(string $mensaje, string $tipo = 'info'): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validar tipo
        if (!in_array($tipo, ALERT_TYPES, true)) {
            $tipo = 'info';
        }

        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }

        $_SESSION['flash'][] = [
            'mensaje' => $mensaje,
            'tipo' => $tipo
        ];
    }

    /**
     * Alias compatibilidad - DEPRECADO: usar setFlash()
     */
    public static function flash_mensaje(string $mensaje, string $tipo = 'info'): void {
        self::setFlash($mensaje, $tipo);
    }

    /**
     * Obtener y limpiar mensajes flash
     * 
     * @return array
     */
    public static function getFlash(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $mensajes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $mensajes;
    }

    /**
     * Alias compatibilidad - DEPRECADO: usar getFlash()
     */
    public static function obtener_flash(): array {
        return self::getFlash();
    }

    /**
     * Obtener ID del usuario actual
     * 
     * @return int|null
     */
    public static function getCurrentUserId(): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
    }

    /**
     * Alias compatibilidad - DEPRECADO: usar getCurrentUserId()
     */
    public static function usuario_actual_id(): ?int {
        return self::getCurrentUserId();
    }

    /**
     * Verificar si usuario está logueado
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['usuario_id']);
    }

    /**
     * Alias compatibilidad - DEPRECADO: usar isLoggedIn()
     */
    public static function esta_logueado(): bool {
        return self::isLoggedIn();
    }

    /**
     * Generar o obtener token CSRF
     * ✅ CORREGIDO: Usa 'csrf' en lugar de 'csrf_token' para compatibilidad
     * 
     * @return string
     */
    public static function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
        }

        return $_SESSION['csrf'];
    }

    /**
     * Validar token CSRF
     * ✅ CORREGIDO: Valida contra 'csrf' en lugar de 'csrf_token'
     * 
     * @param string $token
     * @return bool
     */
    public static function validateCsrf(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf']) && 
               hash_equals($_SESSION['csrf'], $token);
    }

    /**
     * Alias compatibilidad - DEPRECADO: usar validateCsrf()
     */
    public static function csrf_validar($token): bool {
        return self::validateCsrf($token);
    }

    /**
     * Generar campo HTML oculto con token CSRF
     * ✅ CORREGIDO: Campo 'csrf' para compatibilidad con formularios existentes
     * 
     * @return string
     */
    public static function csrfField(): string {
        $token = self::csrf_token();
        return '<input type="hidden" name="csrf" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Obtener IP del cliente
     * 
     * @return string
     */
    public static function getClientIp(): string {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    /**
     * Obtener User Agent
     * 
     * @return string
     */
    public static function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    }

    /**
     * Generar slug desde texto
     * 
     * @param string $text
     * @return string
     */
    public static function toSlug(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Formatear dinero
     * 
     * @param float $amount
     * @param int $decimals
     * @return string
     */
    public static function formatMoney(float $amount, int $decimals = 2): string {
        return number_format($amount, $decimals, '.', ',');
    }

    /**
     * Formatear fecha
     * 
     * @param string $date Formato: Y-m-d H:i:s
     * @param string $format Formato deseado: d/m/Y H:i
     * @return string
     */
    public static function formatDate(string $date, string $format = 'd/m/Y H:i'): string {
        try {
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date);
            return $dt ? $dt->format($format) : $date;
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Tiempo transcurrido en lenguaje natural
     * 
     * @param string $date
     * @return string
     */
    public static function timeAgo(string $date): string {
        try {
            $dt = new DateTime($date);
            $now = new DateTime();
            $diff = $now->diff($dt);

            if ($diff->d == 0 && $diff->h == 0 && $diff->i < 1) {
                return 'hace unos segundos';
            }
            if ($diff->d == 0 && $diff->h == 0) {
                return 'hace ' . $diff->i . ' minuto(s)';
            }
            if ($diff->d == 0) {
                return 'hace ' . $diff->h . ' hora(s)';
            }
            if ($diff->d < 7) {
                return 'hace ' . $diff->d . ' día(s)';
            }
            return $dt->format('d/m/Y');
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Verificar si el usuario actual es administrador
     * 
     * @return bool
     */
    public static function isAdmin(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 1;
    }

    /**
     * Alias compatibilidad - DEPRECADO: usar isAdmin()
     */
    public static function es_admin(): bool {
        return self::isAdmin();
    }

    /**
     * Obtener rol del usuario actual
     * 
     * @return int|null
     */
    public static function getUserRole(): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;
    }

    /**
     * Obtener nombre del usuario actual
     * 
     * @return string|null
     */
    public static function getUserName(): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['usuario_nombre'] ?? null;
    }

    /**
     * Obtener correo del usuario actual
     * 
     * @return string|null
     */
    public static function getUserEmail(): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['usuario_correo'] ?? null;
    }
}
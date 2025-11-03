<?php
/**
 * src/Auth.php
 * Autenticación - CORREGIDA
 * 
 * Cambios:
 * - Usa DB::conn() en lugar de db_conectar()
 * - Mejor validación
 * - Logging de intentos fallidos
 */

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Helper.php';
require_once __DIR__ . '/../config/loader.php';

class Auth {

    /**
     * Intentar login con email y contraseña
     * 
     * @param string $correo
     * @param string $password
     * @return bool
     */
    public static function login(string $correo, string $password): bool {
        $correo = strtolower(trim($correo));
        $password = trim($password);

        // Validación básica
        if (!Helper::validateEmail($correo) || strlen($password) < 1) {
            return false;
        }

        try {
            $pdo = DB::conn();

            // Buscar usuario por email
            $stmt = $pdo->prepare("
                SELECT id, nombre, correo, password_hash, rol_id, estado 
                FROM usuarios 
                WHERE LOWER(correo) = ? AND estado = 'activo'
                LIMIT 1
            ");

            $stmt->execute([$correo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                self::logFailedAttempt($correo, 'Usuario no encontrado');
                return false;
            }

            // Verificar contraseña
            if (!password_verify($password, $usuario['password_hash'])) {
                self::logFailedAttempt($correo, 'Contraseña incorrecta');
                return false;
            }

            // Iniciar sesión
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['usuario_id'] = (int)$usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_correo'] = $usuario['correo'];
            $_SESSION['rol_id'] = (int)$usuario['rol_id'];

            // Regenerar session ID para seguridad
            session_regenerate_id(true);

            return true;

        } catch (Exception $e) {
            if (APP_DEBUG) {
                error_log("Auth::login error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Logout
     * 
     * @return void
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();
        session_start();
        
        Helper::setFlash('Sesión cerrada correctamente', 'success');
    }

    /**
     * Cambiar contraseña
     * 
     * @param int $usuario_id
     * @param string $password_actual
     * @param string $password_nueva
     * @return bool
     */
    public static function cambiarPassword(int $usuario_id, string $password_actual, string $password_nueva): bool {
        if ($usuario_id <= 0) {
            return false;
        }

        $password_actual = trim($password_actual);
        $password_nueva = trim($password_nueva);

        if (strlen($password_actual) < 1 || strlen($password_nueva) < 6) {
            return false;
        }

        try {
            $pdo = DB::conn();

            // Obtener hash actual
            $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([$usuario_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($password_actual, $row['password_hash'])) {
                return false;
            }

            // Actualizar contraseña
            $nuevo_hash = password_hash($password_nueva, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");

            return $stmt->execute([$nuevo_hash, $usuario_id]);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Registrar intento de login fallido
     * 
     * @param string $correo
     * @param string $razon
     * @return void
     */
    private static function logFailedAttempt(string $correo, string $razon): void {
        try {
            $pdo = DB::conn();
            
            $stmt = $pdo->prepare("
                INSERT INTO auditoria_log 
                (accion, tabla, datos_json, ip, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");

            $datos = json_encode(['correo' => $correo, 'razon' => $razon]);
            
            $stmt->execute([
                'login_fallido',
                'usuarios',
                $datos,
                Helper::getClientIp(),
                Helper::getUserAgent()
            ]);
        } catch (Exception $e) {
            // No romper el flujo si falla el logging
        }
    }
}
<?php
class DB {
    private static $conn;

    public static function conn() {
        if (!self::$conn) {
            $dsn  = "mysql:host=localhost;dbname=vestige_es;charset=utf8mb4";
            $user = "root";
            $pass = "";
            self::$conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$conn;
    }
}
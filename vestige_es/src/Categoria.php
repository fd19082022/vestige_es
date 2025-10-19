<?php

require_once __DIR__ . '/../config/database.php';

class Categoria {
    public static function todas(): array {
        $pdo = db_conectar();
        return $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();
    }
}
?>
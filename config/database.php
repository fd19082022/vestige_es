<?php
/**
 * config/database.php
 * Función compatibilidad con código antiguo
 * 
 * DEPRECADO: Usar DB::conn() en su lugar
 * Se mantiene por compatibilidad con código existente
 */

require_once __DIR__ . '/loader.php';
require_once __DIR__ . '/../src/DB.php';

/**
 * Función wrapper para compatibilidad
 * 
 * @deprecated Usar DB::conn() en su lugar
 * @return PDO
 */
function db_conectar(): PDO {
    return DB::conn();
}

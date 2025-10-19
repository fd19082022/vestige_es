<?php

// Bootstrap mínimo para asegurar disponibilidad de DB y Helper
if (!class_exists('DB')) {
    $__db = __DIR__ . '/DB.php';
    if (file_exists($__db)) require_once $__db;
}
if (!class_exists('Helper')) {
    $__helper = __DIR__ . '/Helper.php';
    if (file_exists($__helper)) require_once $__helper;
}

// Parche mínimo para visibilidad en "Mis publicaciones".
// Reemplaza SOLO si tu clase mantiene esta interfaz. Si tu clase tiene otros métodos,
// adapta los nombres pero conserva la lógica de INSERT y del SELECT por vendedor.
//
// Requisitos existentes del proyecto (no incluidos aquí):
//  - DB::conn(): retorna PDO
//  - Helper::limpiar($txt): sanitiza cadenas
//
// Estados
const ESTADO_PUBLICADA = 2; // debe existir en estados_publicacion.id

class Publicacion
{
    // Crea publicación y asegura que se vea en "Mis publicaciones"
    public static function crear(array $data, int $usuarioId): int
    {
        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            // Asegurar vendedor_id = usuarioId si no vino
            $vendedorId = isset($data['vendedor_id']) && (int)$data['vendedor_id'] > 0 ? (int)$data['vendedor_id'] : $usuarioId;

            // Estado publicada por defecto (ajusta si manejas borradores)
            $estadoId = isset($data['estado_id']) ? (int)$data['estado_id'] : ESTADO_PUBLICADA;

            $sql = "INSERT INTO publicaciones (
                        usuario_id, vendedor_id,
                        categoria_id, subcategoria_id,
                        titulo, descripcion, condicion,
                        talla_id, color_id,
                        estado, imagen_principal,
                        precio_bs, es_subasta,
                        fecha_inicio_subasta, fecha_fin_subasta,
                        estado_id
                    ) VALUES (
                        :usuario_id, :vendedor_id,
                        :categoria_id, :subcategoria_id,
                        :titulo, :descripcion, :condicion,
                        :talla_id, :color_id,
                        :estado, :imagen_principal,
                        :precio_bs, :es_subasta,
                        :fecha_inicio_subasta, :fecha_fin_subasta,
                        :estado_id
                    )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':vendedor_id' => $vendedorId,
                ':categoria_id' => (int)($data['categoria_id'] ?? 1),
                ':subcategoria_id' => !empty($data['subcategoria_id']) ? (int)$data['subcategoria_id'] : null,
                ':titulo' => (string)$data['titulo'],
                ':descripcion' => $data['descripcion'] ?? null,
                ':condicion' => $data['condicion'] ?? 'buen_estado',
                ':talla_id' => !empty($data['talla_id']) ? (int)$data['talla_id'] : null,
                ':color_id' => !empty($data['color_id']) ? (int)$data['color_id'] : null,
                ':estado' => $data['estado'] ?? 'Usado - Bueno',
                ':imagen_principal' => $data['imagen_principal'] ?? null,
                ':precio_bs' => (float)$data['precio_bs'],
                ':es_subasta' => (int)($data['es_subasta'] ?? 0),
                ':fecha_inicio_subasta' => $data['fecha_inicio_subasta'] ?? null,
                ':fecha_fin_subasta' => $data['fecha_fin_subasta'] ?? null,
                ':estado_id' => $estadoId,
            ]);

            $pubId = (int)$pdo->lastInsertId();

            // Si vino imagen subida como 'imagen_principal' o 'ruta', guardamos también en publicaciones_imagenes
            $rutaImg = $data['imagen_principal'] ?? ($data['ruta'] ?? null);
            if ($rutaImg) {
                $pdo->prepare("INSERT INTO publicaciones_imagenes (publicacion_id, ruta, es_principal)
                               VALUES (:p, :r, 1)")
                    ->execute([':p' => $pubId, ':r' => $rutaImg]);
            }

            $pdo->commit();
            return $pubId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Listado para "Mis publicaciones" (usa vendedor_id, con fallback a usuario_id)
    public static function listarPorVendedor(int $vendedorId, ?int $estadoId = null): array
    {
        $pdo = DB::conn();

        $where = ["(p.vendedor_id = :yo OR p.usuario_id = :yo)"];
        $params = [':yo' => $vendedorId];

        if ($estadoId !== null) {
            $where[] = "p.estado_id = :estado_id";
            $params[':estado_id'] = $estadoId;
        }

        $sql = "SELECT 
                    p.*,
                    COALESCE(pi.ruta,
                    (
                        SELECT pi2.ruta FROM publicaciones_imagenes pi2
                        WHERE pi2.publicacion_id = p.id
                        ORDER BY pi2.es_principal DESC, pi2.id ASC
                        LIMIT 1
                    ),
                    p.imagen_principal) AS principal_img
                FROM publicaciones p
                LEFT JOIN publicaciones_imagenes pi 
                    ON pi.publicacion_id = p.id AND pi.es_principal = 1
                WHERE " . implode(" AND ", $where) . "
                ORDER BY p.creado_en DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Compat: algunos módulos llaman Publicacion::listar() sin parámetros.
    // Retorna las publicaciones del usuario logueado (vendedor) en estado publicado.
    public static function listar(array $opts = []): array
    {
        $vendedorId = null;

        // 1) Si viene por opciones (por compatibilidad con vistas antiguas)
        if (!empty($opts['vendedor_id'])) {
            $vendedorId = (int)$opts['vendedor_id'];
        }

        // 2) Si no vino, intenta desde sesión
        if ($vendedorId === null) {
            if (session_status() === PHP_SESSION_NONE) {
                // No forzamos session_start() si la app ya lo maneja afuera;
                // pero si no está, lo iniciamos para obtener el usuario.
                @session_start();
            }
            if (!empty($_SESSION['usuario']['id'])) {
                $vendedorId = (int)$_SESSION['usuario']['id'];
            }
        }

        // 3) Si aún no hay usuario, devuelve vacío para evitar romper vistas públicas
        if (empty($vendedorId)) {
            return [];
        }

        // Estado publicado por defecto, pero permitimos override
        $estadoId = isset($opts['estado_id']) ? (int)$opts['estado_id'] : ESTADO_PUBLICADA;
        return self::listarPorVendedor($vendedorId, $estadoId);
    }
    

    // Compat: obtener por ID (usado por publicacion_ver.php en algunos proyectos)
    public static function obtener($id, array $opts = []): ?array
    {
        $id = (int)$id;
        if ($id <= 0) return null;

        $soloPublicadas = isset($opts['solo_publicadas']) ? (bool)$opts['solo_publicadas'] : false;

        $pdo = DB::conn();
        $where = ["p.id = :id"];
        $params = [':id' => $id];

        if ($soloPublicadas) {
            $where[] = "p.estado_id = :estado_id";
            $params[':estado_id'] = ESTADO_PUBLICADA;
        }

        $sql = "SELECT 
                    p.*,
                    COALESCE(pi.ruta,
                    (
                        SELECT pi2.ruta FROM publicaciones_imagenes pi2
                        WHERE pi2.publicacion_id = p.id
                        ORDER BY pi2.es_principal DESC, pi2.id ASC
                        LIMIT 1
                    ),
                    p.imagen_principal) AS principal_img
                FROM publicaciones p
                LEFT JOIN publicaciones_imagenes pi 
                    ON pi.publicacion_id = p.id AND pi.es_principal = 1
                WHERE " . implode(" AND ", $where) . " 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

}

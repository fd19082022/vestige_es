<?php
/**
 * src/Publicacion.php
 * Gestión de publicaciones - CORREGIDA
 * 
 * Cambios:
 * - Validación de estado_id antes de insert
 * - Solo usa vendedor_id (no usuario_id)
 * - Mejor manejo de errores
 * - Constantes centralizadas
 */

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Helper.php';
require_once __DIR__ . '/../config/loader.php';

class Publicacion {

    /**
     * Crear publicación
     * 
     * @param array $data
     * @param int $usuario_id (vendedor)
     * @return int ID de la publicación creada
     * @throws Exception
     */
    public static function crear(array $data, int $usuario_id): int {
        if ($usuario_id <= 0) {
            throw new Exception("Usuario inválido");
        }

        // Validar datos requeridos
        $titulo = trim($data['titulo'] ?? '');
        $categoria_id = (int)($data['categoria_id'] ?? 0);
        $precio = (float)($data['precio_bs'] ?? 0);

        if (empty($titulo) || strlen($titulo) < 3 || strlen($titulo) > 200) {
            throw new Exception("Título debe tener entre 3 y 200 caracteres");
        }

        if ($categoria_id <= 0) {
            throw new Exception("Categoría inválida");
        }

        if ($precio <= 0) {
            throw new Exception("Precio debe ser mayor a 0");
        }

        try {
            $pdo = DB::conn();
            $pdo->beginTransaction();

            // Validar que categoría existe
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? LIMIT 1");
            $stmt->execute([$categoria_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Categoría no existe");
            }

            // Validar estado_id
            $estado_id = (int)($data['estado_id'] ?? ESTADO_PUBLICADA);
            $stmt = $pdo->prepare("SELECT id FROM estados_publicacion WHERE id = ? LIMIT 1");
            $stmt->execute([$estado_id]);
            if (!$stmt->fetch()) {
                $estado_id = ESTADO_PUBLICADA; // Default seguro
            }

            // Validar referencias opcionales
            $talla_id = !empty($data['talla_id']) ? (int)$data['talla_id'] : null;
            $color_id = !empty($data['color_id']) ? (int)$data['color_id'] : null;
            $subcategoria_id = !empty($data['subcategoria_id']) ? (int)$data['subcategoria_id'] : null;

            if ($talla_id !== null) {
                $stmt = $pdo->prepare("SELECT id FROM tallas WHERE id = ? LIMIT 1");
                $stmt->execute([$talla_id]);
                if (!$stmt->fetch()) $talla_id = null;
            }

            if ($color_id !== null) {
                $stmt = $pdo->prepare("SELECT id FROM colores WHERE id = ? LIMIT 1");
                $stmt->execute([$color_id]);
                if (!$stmt->fetch()) $color_id = null;
            }

            // Insertar publicación
            $stmt = $pdo->prepare("
                INSERT INTO publicaciones 
                (usuario_id, vendedor_id, categoria_id, subcategoria_id, 
                 titulo, descripcion, condicion, talla_id, color_id, 
                 estado, imagen_principal, precio_bs, es_subasta, 
                 fecha_inicio_subasta, fecha_fin_subasta, estado_id)
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $usuario_id,
                $usuario_id,
                $categoria_id,
                $subcategoria_id,
                $titulo,
                trim($data['descripcion'] ?? ''),
                $data['condicion'] ?? 'buen_estado',
                $talla_id,
                $color_id,
                $data['estado'] ?? 'Usado - Bueno',
                $data['imagen_principal'] ?? null,
                $precio,
                (int)($data['es_subasta'] ?? 0),
                $data['fecha_inicio_subasta'] ?? null,
                $data['fecha_fin_subasta'] ?? null,
                $estado_id
            ]);

            $pub_id = (int)$pdo->lastInsertId();

            // Si vino imagen, guardar en publicaciones_imagenes
            if (!empty($data['imagen_principal'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO publicaciones_imagenes 
                    (publicacion_id, ruta, es_principal)
                    VALUES (?, ?, 1)
                ");
                $stmt->execute([$pub_id, $data['imagen_principal']]);
            }

            $pdo->commit();
            return $pub_id;

        } catch (PDOException $e) {
            $pdo->rollBack();
            if (APP_DEBUG) {
                throw new Exception("Error BD: " . $e->getMessage());
            }
            throw new Exception("No se pudo crear la publicación");
        }
    }

    /**
     * Listar publicaciones por vendedor
     * 
     * @param int $vendedor_id
     * @param int|null $estado_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function listarPorVendedor(int $vendedor_id, ?int $estado_id = null, int $limit = 60, int $offset = 0): array {
        if ($vendedor_id <= 0) {
            return [];
        }

        try {
            $pdo = DB::conn();

            $sql = "
                SELECT p.*,
                    COALESCE(pi.ruta, p.imagen_principal) AS principal_img
                FROM publicaciones p
                LEFT JOIN publicaciones_imagenes pi 
                    ON pi.publicacion_id = p.id AND pi.es_principal = 1
                WHERE p.vendedor_id = ?
            ";
            $params = [$vendedor_id];

            if ($estado_id !== null) {
                $sql .= " AND p.estado_id = ?";
                $params[] = $estado_id;
            }

            $sql .= " ORDER BY p.creado_en DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            if (APP_DEBUG) throw $e;
            return [];
        }
    }

    /**
     * Obtener publicación por ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function obtener(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        try {
            $pdo = DB::conn();

            $stmt = $pdo->prepare("
                SELECT p.*,
                    COALESCE(pi.ruta, p.imagen_principal) AS principal_img,
                    c.nombre AS categoria
                FROM publicaciones p
                LEFT JOIN publicaciones_imagenes pi 
                    ON pi.publicacion_id = p.id AND pi.es_principal = 1
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Actualizar estado de publicación
     * 
     * @param int $id
     * @param int $estado_id
     * @return bool
     */
    public static function actualizarEstado(int $id, int $estado_id): bool {
        if ($id <= 0 || $estado_id < 0) {
            return false;
        }

        try {
            $pdo = DB::conn();

            // Validar que estado existe
            $stmt = $pdo->prepare("SELECT id FROM estados_publicacion WHERE id = ? LIMIT 1");
            $stmt->execute([$estado_id]);
            if (!$stmt->fetch()) {
                return false;
            }

            $stmt = $pdo->prepare("UPDATE publicaciones SET estado_id = ? WHERE id = ?");
            return $stmt->execute([$estado_id, $id]);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Eliminar publicación
     * 
     * @param int $id
     * @return bool
     */
    public static function eliminar(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            $pdo = DB::conn();
            $pdo->beginTransaction();

            // Eliminar imágenes
            $stmt = $pdo->prepare("DELETE FROM publicaciones_imagenes WHERE publicacion_id = ?");
            $stmt->execute([$id]);

            // Eliminar publicación (cascada elimina favoritos, conversaciones, mensajes)
            $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
            $result = $stmt->execute([$id]);

            $pdo->commit();
            return $result;

        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
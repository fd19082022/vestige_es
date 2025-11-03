<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Helper.php';

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$titulo = '';
$descripcion = '';
$precio_bs = 0;
$categoria_id = 0;
$condicion = 'buen_estado';
$talla_id = null;
$color_id = null;

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_bs = floatval($_POST['precio_bs'] ?? 0);
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $condicion = $_POST['condicion'] ?? 'buen_estado';
    $talla_id = !empty($_POST['talla_id']) ? intval($_POST['talla_id']) : null;
    $color_id = !empty($_POST['color_id']) ? intval($_POST['color_id']) : null;
    $estado_id = 2; // Publicada por defecto
    $usuario_id = $_SESSION['usuario_id'];
    $vendedor_id = $_SESSION['usuario_id'];

    // Validaciones
    if (empty($titulo) || empty($descripcion) || $precio_bs <= 0 || $categoria_id <= 0) {
        $mensaje = 'Por favor completa todos los campos obligatorios.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Insertar publicación
            $sql = "INSERT INTO publicaciones 
                    (usuario_id, vendedor_id, categoria_id, titulo, descripcion, condicion, 
                     talla_id, color_id, precio_bs, estado_id, creado_en) 
                    VALUES 
                    (:usuario_id, :vendedor_id, :categoria_id, :titulo, :descripcion, :condicion, 
                     :talla_id, :color_id, :precio_bs, :estado_id, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':vendedor_id' => $vendedor_id,
                ':categoria_id' => $categoria_id,
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':condicion' => $condicion,
                ':talla_id' => $talla_id,
                ':color_id' => $color_id,
                ':precio_bs' => $precio_bs,
                ':estado_id' => $estado_id
            ]);
            
            $publicacion_id = $pdo->lastInsertId();
            
            // Procesar imagen si existe
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $nombre_archivo = md5(uniqid()) . '.' . $extension;
                $ruta_destino = $upload_dir . $nombre_archivo;
                $ruta_relativa = 'uploads/' . $nombre_archivo;
                
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                    // Guardar en publicaciones_imagenes
                    $sql_img = "INSERT INTO publicaciones_imagenes (publicacion_id, ruta, es_principal) 
                                VALUES (:pub_id, :ruta, 1)";
                    $stmt_img = $pdo->prepare($sql_img);
                    $stmt_img->execute([
                        ':pub_id' => $publicacion_id,
                        ':ruta' => $ruta_relativa
                    ]);
                }
            }
            
            Helper::setFlash('¡Publicación creada exitosamente!', 'success');
            header('Location: dashboard.php');
            exit;
            
        } catch (Exception $e) {
            $mensaje = 'Error al crear la publicación: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener datos para los selects
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();
$tallas = $pdo->query("SELECT id, codigo, nombre FROM tallas ORDER BY codigo")->fetchAll();
$colores = $pdo->query("SELECT id, nombre FROM colores ORDER BY nombre")->fetchAll();

// Obtener mensajes flash
$flash = Helper::getFlash();

require_once __DIR__ . '/../templates/header.php';
?>

<main class="contenedor principal">
  <h1>Nueva Publicación</h1>
  <p class="texto-suave">Completa la información de tu prenda para publicarla</p>

  <?php if (!empty($flash)): ?>
    <?php foreach ($flash as $f): ?>
      <div class="alerta alerta--<?php echo $f['tipo']; ?>">
        <?php echo htmlspecialchars($f['mensaje']); ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($mensaje): ?>
    <div class="alerta alerta--<?php echo $tipo_mensaje; ?>">
      <?php echo htmlspecialchars($mensaje); ?>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="formulario" style="max-width: 800px; margin-top: 1.5rem;">
    
    <!-- Título -->
    <label>
      <span style="display:block; margin-bottom:.3rem;">Título *</span>
      <input 
        type="text" 
        name="titulo" 
        placeholder="Ej: Chaqueta de cuero negra talla M" 
        value="<?php echo htmlspecialchars($titulo); ?>"
        required
      >
    </label>

    <!-- Grid para categoría y precio -->
    <div class="grid-2">
      <label>
        <span style="display:block; margin-bottom:.3rem;">Categoría *</span>
        <select name="categoria_id" required>
          <option value="">Selecciona una categoría</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" 
              <?php echo ($categoria_id == $cat['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        <span style="display:block; margin-bottom:.3rem;">Precio (Bs) *</span>
        <input 
          type="number" 
          name="precio_bs" 
          placeholder="0.00" 
          step="0.01" 
          min="0.01"
          value="<?php echo htmlspecialchars($precio_bs); ?>"
          required
        >
      </label>
    </div>

    <!-- Grid para condición, talla y color -->
    <div class="grid-3">
      <label>
        <span style="display:block; margin-bottom:.3rem;">Condición *</span>
        <select name="condicion" required>
          <option value="nuevo" <?php echo ($condicion === 'nuevo') ? 'selected' : ''; ?>>Nuevo</option>
          <option value="como_nuevo" <?php echo ($condicion === 'como_nuevo') ? 'selected' : ''; ?>>Como nuevo</option>
          <option value="muy_buen_estado" <?php echo ($condicion === 'muy_buen_estado') ? 'selected' : ''; ?>>Muy buen estado</option>
          <option value="buen_estado" <?php echo ($condicion === 'buen_estado') ? 'selected' : ''; ?>>Buen estado</option>
          <option value="aceptable" <?php echo ($condicion === 'aceptable') ? 'selected' : ''; ?>>Aceptable</option>
        </select>
      </label>

      <label>
        <span style="display:block; margin-bottom:.3rem;">Talla (opcional)</span>
        <select name="talla_id">
          <option value="">Sin talla</option>
          <?php foreach ($tallas as $t): ?>
            <option value="<?php echo $t['id']; ?>" <?php echo ($talla_id == $t['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($t['nombre'] ?: $t['codigo']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        <span style="display:block; margin-bottom:.3rem;">Color (opcional)</span>
        <select name="color_id">
          <option value="">Sin color</option>
          <?php foreach ($colores as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo ($color_id == $c['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($c['nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <!-- Descripción -->
    <label>
      <span style="display:block; margin-bottom:.3rem;">Descripción *</span>
      <textarea 
        name="descripcion" 
        placeholder="Describe tu prenda: estado, detalles, marca, etc."
        required
      ><?php echo htmlspecialchars($descripcion); ?></textarea>
    </label>

    <!-- Imagen -->
    <label>
      <span style="display:block; margin-bottom:.3rem;">Imagen principal</span>
      <input 
        type="file" 
        name="imagen" 
        accept="image/*"
        style="padding:.6rem; background:#0b1226; border:1px solid rgba(255,255,255,.1); border-radius:14px; color:var(--text);"
      >
      <small style="display:block; color:var(--muted); margin-top:.3rem;">
        Formatos: JPG, PNG, WEBP (máx. 5MB)
      </small>
    </label>

    <!-- Botones de acción -->
    <div class="acciones">
      <a href="dashboard.php" class="btn">Cancelar</a>
      <button type="submit" class="btn-primario">Publicar</button>
    </div>
  </form>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
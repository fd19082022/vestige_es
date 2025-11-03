<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar el formulario cuando se envíe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $talla = trim($_POST['talla'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $estado = $_POST['estado'] ?? 'disponible';
    $usuario_id = $_SESSION['usuario_id'];

    // Validaciones básicas
    if (empty($titulo) || empty($descripcion) || $precio <= 0 || $categoria_id <= 0) {
        $mensaje = 'Por favor completa todos los campos obligatorios correctamente.';
        $tipo_mensaje = 'error';
    } else {
        // Preparar la consulta SQL
        $sql = "INSERT INTO publicaciones (usuario_id, titulo, descripcion, precio, categoria_id, talla, color, estado, fecha_publicacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssisss", $usuario_id, $titulo, $descripcion, $precio, $categoria_id, $talla, $color, $estado);
        
        if ($stmt->execute()) {
            $mensaje = '¡Publicación creada exitosamente!';
            $tipo_mensaje = 'success';
            
            // Limpiar el formulario después del éxito
            $titulo = $descripcion = $talla = $color = '';
            $precio = 0;
            $categoria_id = 0;
        } else {
            $mensaje = 'Error al crear la publicación: ' . $conn->error;
            $tipo_mensaje = 'error';
        }
        
        $stmt->close();
    }
}

// Obtener categorías para el select
$categorias = [];
$result = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Publicación - Marketplace</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Encabezado -->
    <header class="encabezado">
        <div class="contenedor">
            <div class="encabezado__contenido">
                <a href="index.php" class="logo">
                    <img src="https://api.dicebear.com/7.x/shapes/svg?seed=marketplace" alt="Logo">
                    <span>Marketplace</span>
                </a>
                <nav class="navegacion">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="mis_publicaciones.php">Mis Publicaciones</a>
                    <a href="logout.php" class="btn-salir">Cerrar Sesión</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="principal">
        <div class="contenedor">
            
            <!-- Hero Section -->
            <div class="hero" style="background-image: url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1600&auto=format&fit=crop'); min-height: 320px;">
                <div class="hero-content">
                    <div class="hero__kicker">Vende tus prendas</div>
                    <h1>Crear Nueva Publicación</h1>
                    <p class="hero__texto">Completa la información de tu prenda y compártela con miles de compradores</p>
                </div>
            </div>

            <!-- Mensajes de estado -->
            <?php if ($mensaje): ?>
                <div class="alerta alerta--<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Pasos del proceso -->
            <section class="seccion">
                <div class="pasos">
                    <div class="paso">
                        <div class="paso__num">1</div>
                        <h4>Información Básica</h4>
                        <p>Completa el título, categoría y detalles esenciales de tu prenda</p>
                    </div>
                    <div class="paso">
                        <div class="paso__num">2</div>
                        <h4>Descripción Detallada</h4>
                        <p>Describe el estado, características y cualquier detalle relevante</p>
                    </div>
                    <div class="paso">
                        <div class="paso__num">3</div>
                        <h4>Publicar</h4>
                        <p>Revisa toda la información y publica tu prenda</p>
                    </div>
                </div>
            </section>

            <!-- Formulario de Publicación -->
            <form method="POST" class="form-publicacion">
                
                <!-- Sección: Información Principal -->
                <div class="cuadro">
                    <h3 class="cuadro__title">📝 Información Principal</h3>
                    
                    <div class="grupo-grid">
                        <div class="campo-card campo-card--xl">
                            <label class="label-text">Título de la publicación *</label>
                            <input 
                                type="text" 
                                name="titulo" 
                                placeholder="Ej: Chaqueta de cuero negra talla M" 
                                value="<?php echo htmlspecialchars($titulo ?? ''); ?>"
                                required
                            >
                            <small class="help">Un título claro ayuda a los compradores a encontrar tu prenda</small>
                        </div>

                        <div class="campo-card">
                            <label class="label-text">Categoría *</label>
                            <select name="categoria_id" required>
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo (isset($categoria_id) && $categoria_id == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="campo-card">
                            <label class="label-text">Precio (Bs) *</label>
                            <input 
                                type="number" 
                                name="precio" 
                                placeholder="0.00" 
                                step="0.01" 
                                min="0.01"
                                value="<?php echo htmlspecialchars($precio ?? ''); ?>"
                                required
                            >
                        </div>
                    </div>
                </div>

                <!-- Sección: Características -->
                <div class="cuadro cuadro--soft">
                    <h3 class="cuadro__title">🎨 Características</h3>
                    
                    <div class="grupo-grid">
                        <div class="campo-card">
                            <label class="label-text">Talla</label>
                            <input 
                                type="text" 
                                name="talla" 
                                placeholder="Ej: M, L, XL, 42" 
                                value="<?php echo htmlspecialchars($talla ?? ''); ?>"
                            >
                            <small class="help">Opcional</small>
                        </div>

                        <div class="campo-card">
                            <label class="label-text">Color</label>
                            <input 
                                type="text" 
                                name="color" 
                                placeholder="Ej: Negro, Azul marino" 
                                value="<?php echo htmlspecialchars($color ?? ''); ?>"
                            >
                            <small class="help">Opcional</small>
                        </div>

                        <div class="campo-card">
                            <label class="label-text">Estado</label>
                            <select name="estado">
                                <option value="disponible" <?php echo (isset($estado) && $estado === 'disponible') ? 'selected' : ''; ?>>
                                    Disponible
                                </option>
                                <option value="vendido" <?php echo (isset($estado) && $estado === 'vendido') ? 'selected' : ''; ?>>
                                    Vendido
                                </option>
                                <option value="reservado" <?php echo (isset($estado) && $estado === 'reservado') ? 'selected' : ''; ?>>
                                    Reservado
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sección: Descripción -->
                <div class="cuadro">
                    <h3 class="cuadro__title">✍️ Descripción Detallada</h3>
                    
                    <div class="campo-card campo-card--xl">
                        <label class="label-text">Descripción completa *</label>
                        <textarea 
                            name="descripcion" 
                            placeholder="Describe tu prenda: estado, detalles, marca, motivo de venta, etc."
                            required
                        ><?php echo htmlspecialchars($descripcion ?? ''); ?></textarea>
                        <small class="help">Sé honesto y detallado. Una buena descripción genera confianza</small>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="alerta alerta--info">
                    <strong>💡 Consejo:</strong> Las publicaciones con descripciones completas y honestas tienen un 60% más de probabilidad de venderse rápido.
                </div>

                <!-- Botones de acción -->
                <div class="acciones">
                    <a href="dashboard.php" class="btn">Cancelar</a>
                    <button type="submit" class="btn-primario">Publicar Prenda</button>
                </div>
            </form>

        </div>
    </main>

    <!-- Pie de página -->
    <footer class="pie">
        <div class="contenedor">
            <div class="pie__contenido">
                <p>&copy; 2025 Marketplace. Todos los derechos reservados.</p>
                <p>Plataforma de compra y venta de ropa</p>
            </div>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>
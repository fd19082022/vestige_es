<?php
// public/publicacion_nueva.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/DB.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
    exit;
}

// CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$pdo = DB::conn();

// Catálogos
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$tallas     = $pdo->query("SELECT id, COALESCE(nombre, codigo) AS nombre FROM tallas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$colores    = $pdo->query("SELECT id, nombre FROM colores ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../templates/header.php';
?>

<style>
  /* 🎨 Paleta celeste / azul */
  :root {
    --azul-principal: #3A80BA;
    --azul-hover: #4F9BD9;
  }

  .form-publicacion {
    display: grid;
    gap: 16px;
    padding: 24px 28px;
    background: transparent;
    border: none;
    border-radius: 14px;
  }

  .form-publicacion label {
    color: var(--azul-principal);  /* 🔥 nuevo color para los títulos */
    font-weight: 600;
    letter-spacing: .3px;
  }

  .form-publicacion input[type="text"],
  .form-publicacion input[type="number"],
  .form-publicacion textarea,
  .form-publicacion select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d9c8cf;
    border-radius: 10px;
    background: #fff;
    outline: none;
    font-size: .95rem;
  }
  .form-publicacion input:focus,
  .form-publicacion select:focus,
  .form-publicacion textarea:focus {
    border-color: var(--azul-hover);
    box-shadow: 0 0 0 3px rgba(79,155,217,.2);
  }

  .form-publicacion .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
    gap: 14px;
  }

  .form-publicacion small.help {
    color:#666;
    font-size:.9rem;
  }

  .acciones {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:10px;
  }

  /* 🔥 Botón principal con color celeste */
  .btn-primario {
    background: var(--azul-principal);
    color:#fff;
    padding:10px 18px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
    letter-spacing:.3px;
    transition: all .15s ease;
  }
  .btn-primario:hover {
    background: var(--azul-hover);
    transform: translateY(-1px);
  }

  .btn {
    border:1px solid #d9c8cf;
    border-radius:10px;
    padding:10px 18px;
    text-decoration:none;
    color:#4a4a4a;
    transition:background .15s ease;
  }
  .btn:hover { background:#f2e9ed; }
</style>

<section class="seccion">
  <main class="wrap" style="max-width: 980px; margin: 0 auto;">
    <h1 style="margin: 0 0 18px; color: var(--azul-principal);">Subir publicación</h1>

    <?php if ($flash = Helper::obtener_flash()): ?>
      <div class="flash">
        <?= is_array($flash) ? htmlspecialchars($flash['msg'] ?? '') : htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/publicacion_guardar.php" method="post" enctype="multipart/form-data"
          class="form-publicacion" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <label>
        Título
        <input type="text" name="titulo" required maxlength="200"
               placeholder="Ej: Campera oversize negra, talla M">
      </label>

      <label>
        Descripción
        <textarea name="descripcion" rows="4"
                  placeholder="Cuenta detalles: marca, estado real, medidas, etiquetas..."></textarea>
      </label>

      <div class="grid">
        <label>
          Categoría
          <select name="categoria_id" required>
            <option value="">-- Selecciona --</option>
            <?php foreach($categorias as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          Subcategoría (opcional)
          <input type="number" name="subcategoria_id" min="1" placeholder="ID subcategoría (opcional)">
        </label>

        <label>
          Condición
          <select name="condicion" required>
            <option value="nuevo">Nuevo</option>
            <option value="como_nuevo">Como nuevo</option>
            <option value="muy_buen_estado">Muy buen estado</option>
            <option value="buen_estado" selected>Buen estado</option>
            <option value="aceptable">Aceptable</option>
          </select>
        </label>

        <label>
          Talla
          <select name="talla_id">
            <option value="">--</option>
            <?php foreach($tallas as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          Color
          <select name="color_id">
            <option value="">--</option>
            <?php foreach($colores as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          Precio (Bs)
          <input type="number" name="precio_bs" step="0.01" min="0" required placeholder="Ej: 120.00">
        </label>
      </div>

      <label>
        Imagen principal (opcional)
        <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp" style="display:block; margin-top:6px;">
        <small class="help">Se guardará en <code>uploads/</code>. Formatos: JPG / PNG / WEBP.</small>
      </label>

      <div class="acciones">
        <button type="submit" class="btn-primario">Guardar publicación</button>
        <a class="btn" href="<?= BASE_URL ?>/panel.php">Cancelar</a>
      </div>
    </form>
  </main>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

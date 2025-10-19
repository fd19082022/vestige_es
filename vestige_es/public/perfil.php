<?php
session_start();
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Usuario.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión para acceder a tu perfil.', 'danger');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$nombreActual = $_SESSION['usuario_nombre'] ?? '';

include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/mensajes.php';
?>

<h2>Mi Perfil</h2>

<form class="formulario" method="POST" action="perfil_actualizar.php" autocomplete="off">
    <div class="campo">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombreActual) ?>" required>
    </div>

    <hr>

    <p><strong>Cambiar contraseña (opcional)</strong></p>
    <div class="campo">
        <label for="password_actual">Contraseña actual</label>
        <input type="password" id="password_actual" name="password_actual" placeholder="••••••••">
    </div>
    <div class="campo">
        <label for="password_nueva">Contraseña nueva</label>
        <input type="password" id="password_nueva" name="password_nueva" placeholder="Mínimo 6 caracteres">
    </div>
    <div class="campo">
        <label for="password_confirmar">Confirmar contraseña nueva</label>
        <input type="password" id="password_confirmar" name="password_confirmar" placeholder="Repite la nueva contraseña">
    </div>

   <button type="submit" class="btn-primario">Guardar cambios</button>

</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>

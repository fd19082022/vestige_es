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

// Obtener datos completos del usuario
$usuario = Usuario::obtenerPorId($usuarioId);

if (!$usuario) {
    Helper::flash_mensaje('Usuario no encontrado.', 'danger');
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/mensajes.php';
?>

<h2>Mi Perfil</h2>

<form class="formulario" method="POST" action="perfil_actualizar.php" autocomplete="off">
    
    <fieldset>
        <legend>Información Personal</legend>
        
        <div class="campo">
            <label for="nombre">Nombre *</label>
            <input 
                type="text" 
                id="nombre" 
                name="nombre" 
                value="<?= htmlspecialchars($usuario['nombre']) ?>" 
                required
                minlength="3"
                maxlength="100"
            >
            <small>Mínimo 3 caracteres</small>
        </div>

        <div class="campo">
            <label for="apellido">Apellido</label>
            <input 
                type="text" 
                id="apellido" 
                name="apellido" 
                value="<?= htmlspecialchars($usuario['apellido'] ?? '') ?>"
                maxlength="100"
            >
            <small>Opcional</small>
        </div>

        <div class="campo">
            <label for="telefono">Teléfono</label>
            <input 
                type="text" 
                id="telefono" 
                name="telefono" 
                value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>"
                maxlength="30"
            >
            <small>Opcional</small>
        </div>

        <div class="campo">
            <label>Correo electrónico</label>
            <input 
                type="email" 
                value="<?= htmlspecialchars($usuario['correo']) ?>" 
                disabled
                class="input-disabled"
            >
            <small>El correo no se puede cambiar</small>
        </div>
    </fieldset>

    <hr>

    <fieldset>
        <legend>Cambiar Contraseña (Opcional)</legend>
        <p><small>Solo completa estos campos si deseas cambiar tu contraseña</small></p>
        
        <div class="campo">
            <label for="password_actual">Contraseña actual</label>
            <input 
                type="password" 
                id="password_actual" 
                name="password_actual" 
                placeholder="Ingresa tu contraseña actual"
                autocomplete="current-password"
            >
        </div>

        <div class="campo">
            <label for="password_nueva">Nueva contraseña</label>
            <input 
                type="password" 
                id="password_nueva" 
                name="password_nueva" 
                placeholder="Mínimo 6 caracteres"
                minlength="6"
                autocomplete="new-password"
            >
        </div>

        <div class="campo">
            <label for="password_confirmar">Confirmar nueva contraseña</label>
            <input 
                type="password" 
                id="password_confirmar" 
                name="password_confirmar" 
                placeholder="Repite la nueva contraseña"
                autocomplete="new-password"
            >
        </div>
    </fieldset>

    <div class="acciones">
        <button type="submit" class="btn-primario">Guardar cambios</button>
        <a href="dashboard.php" class="btn-secundario">Cancelar</a>
    </div>
</form>

<style>
fieldset {
    border: 1px solid #ddd;
    padding: 1.5rem;
    margin: 1.5rem 0;
    border-radius: 8px;
}

legend {
    font-weight: bold;
    padding: 0 0.5rem;
    color: #333;
}

.campo {
    margin-bottom: 1rem;
}

.campo small {
    display: block;
    color: #666;
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.input-disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
}

.acciones {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn-secundario {
    background-color: #6c757d;
    color: white;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
}

.btn-secundario:hover {
    background-color: #5a6268;
}
</style>

<?php include __DIR__ . '/../templates/footer.php'; ?>
<?php

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';
    if (!$correo || !$password) $errores[] = 'Completa todos los campos.';
    if (!$errores) {
        if (Auth::login($correo, $password)) {
            Helper::flash_mensaje('¡Bienvenido/a de nuevo!', 'success');
            Helper::redir(BASE_URL . '/dashboard.php');
        } else {
            $errores[] = 'Correo o contraseña incorrectos.';
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>
<h2>Iniciar sesión</h2>
<?php $errores and require __DIR__ . '/../templates/mensajes.php'; ?>
<form class="formulario" method="post">
    <label>Correo electrónico
        <input type="email" name="correo" placeholder="tucorreo@ejemplo.com" required>
    </label>
    <label>Contraseña
        <input type="password" name="password" placeholder="*******" required>
    </label>
    <button class="btn-primario" type="submit">Entrar</button>
    <p>¿No tienes cuenta? <a href="<?= BASE_URL ?>/registro.php">Regí­strate</a></p>
</form>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
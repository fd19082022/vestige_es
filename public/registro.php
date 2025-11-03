<?php

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Usuario.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = Helper::limpiar($_POST['nombre']   ?? '');
    $correo   = Helper::limpiar($_POST['correo']   ?? '');
    $telefono = Helper::limpiar($_POST['telefono'] ?? '');
    $password = $_POST['password']  ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (!$nombre || !$correo || !$password || !$confirmar) {
        $errores[] = 'Completa todos los campos obligatorios.';
    }
    if ($password !== $confirmar) {
        $errores[] = 'Las contraseÃ±as no coinciden.';
    }

    if (!$errores) {
        try {

            $id = Usuario::crear([
                'nombre'   => $nombre,
                'correo'   => $correo,
                'telefono' => $telefono,
                'password' => $password,

            ]);


            $_SESSION['usuario_id']     = $id;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_correo'] = $correo;

            Helper::flash_mensaje('Cuenta creada con Ã©xito. Â¡Bienvenida/o!', 'success');
            Helper::redir(BASE_URL . '/dashboard.php');
       } catch (Throwable $e) {
    $msg = $e->getMessage();
    
    // SIEMPRE mostrar el error completo para debugging
    $errores[] = 'Error completo: ' . $msg;
    
    // También escribir en el log de PHP
    error_log('Error registro usuario: ' . $msg);
    
    if (strpos($msg, 'Duplicate entry') !== false || strpos($msg, 'ya está registrado') !== false) {
        $errores[] = 'Ese correo ya está registrado.';
    } elseif (stripos($msg, 'foreign key') !== false || strpos($msg, 'Rol por defecto') !== false) {
        $errores[] = 'No se pudo registrar: el rol por defecto no existe en la tabla roles.';
    }
}
    }
}

require_once __DIR__ . '/../templates/header.php';
?>
<h2>Crear cuenta</h2>

<?php if (!empty($errores)): ?>
    <div class="alerta alerta--error">
        <?php foreach ($errores as $e): ?>
            <p><?= $e ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="formulario" method="post">
    <label>Nombre completo
        <input type="text" name="nombre" value="<?= isset($nombre)?Helper::limpiar($nombre):'' ?>" required>
    </label>
    <label>Correo electrÃ³nico
        <input type="email" name="correo" value="<?= isset($correo)?Helper::limpiar($correo):'' ?>" required>
    </label>
    <label>TelÃ©fono
        <input type="text" name="telefono" value="<?= isset($telefono)?Helper::limpiar($telefono):'' ?>">
    </label>
    <label>ContraseÃ±a
        <input type="password" name="password" required>
    </label>
    <label>Confirmar contraseÃ±a
        <input type="password" name="confirmar" required>
    </label>
    <button class="btn-primario" type="submit">Registrarme</button>
</form>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
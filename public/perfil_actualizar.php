<?php
session_start();
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Usuario.php';
require_once __DIR__ . '/../src/DB.php';

if (!Helper::esta_logueado()) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'danger');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/perfil.php');
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$pass_actual = $_POST['password_actual'] ?? '';
$pass_nueva = $_POST['password_nueva'] ?? '';
$pass_conf = $_POST['password_confirmar'] ?? '';

$errores = [];

// Validar nombre (siempre requerido)
if ($nombre === '') {
    $errores[] = 'El nombre no puede estar vacío.';
} elseif (strlen($nombre) < 3 || strlen($nombre) > 100) {
    $errores[] = 'El nombre debe tener entre 3 y 100 caracteres.';
}

// Determinar si el usuario quiere cambiar la contraseña
$cambiar_pass = ($pass_actual !== '' || $pass_nueva !== '' || $pass_conf !== '');

if ($cambiar_pass) {
    // Si ingresó algún campo de contraseña, validar todo el proceso
    
    if ($pass_actual === '') {
        $errores[] = 'Debes ingresar tu contraseña actual para cambiarla.';
    }
    
    if ($pass_nueva === '') {
        $errores[] = 'Debes ingresar una nueva contraseña.';
    } elseif (strlen($pass_nueva) < 6) {
        $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
    }
    
    if ($pass_conf === '') {
        $errores[] = 'Debes confirmar la nueva contraseña.';
    }
    
    if ($pass_nueva !== '' && $pass_conf !== '' && $pass_nueva !== $pass_conf) {
        $errores[] = 'Las contraseñas nuevas no coinciden.';
    }
    
    // Verificar contraseña actual solo si no hay errores previos
    if (empty($errores) && $pass_actual !== '') {
        try {
            $pdo = DB::conn();
            $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = ? LIMIT 1');
            $stmt->execute([$usuarioId]);
            $row = $stmt->fetch();
            
            if (!$row) {
                $errores[] = 'Usuario no encontrado.';
            } elseif (!password_verify($pass_actual, $row['password_hash'])) {
                $errores[] = 'La contraseña actual es incorrecta.';
            }
        } catch (Exception $e) {
            $errores[] = 'Error al verificar contraseña.';
        }
    }
}

// Si hay errores, redirigir con mensajes
if (!empty($errores)) {
    foreach ($errores as $e) {
        Helper::flash_mensaje($e, 'danger');
    }
    header('Location: ' . BASE_URL . '/perfil.php');
    exit;
}

// Construir datos para actualizar
$data = [
    'nombre' => $nombre,
    'apellido' => $apellido,
    'telefono' => $telefono
];

// Solo agregar password si se va a cambiar
if ($cambiar_pass) {
    $data['password'] = $pass_nueva;
}

// Ejecutar actualización
try {
    $ok = Usuario::actualizar($usuarioId, $data);
    
    if ($ok) {
        // Actualizar sesión con el nuevo nombre
        $_SESSION['usuario_nombre'] = $nombre;
        
        if ($cambiar_pass) {
            Helper::flash_mensaje('Perfil y contraseña actualizados correctamente.', 'success');
        } else {
            Helper::flash_mensaje('Perfil actualizado correctamente.', 'success');
        }
    } else {
        Helper::flash_mensaje('No se realizaron cambios.', 'info');
    }
} catch (Exception $e) {
    Helper::flash_mensaje('Error al actualizar el perfil: ' . $e->getMessage(), 'danger');
}

header('Location: ' . BASE_URL . '/perfil.php');
exit;
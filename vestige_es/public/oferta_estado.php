<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Oferta.php';

if (!isset($_SESSION['usuario_id'])) {
    Helper::flash_mensaje('Debes iniciar sesión.', 'error');
    Helper::redir(BASE_URL . '/login.php');
}
$usuario_id = (int)$_SESSION['usuario_id'];

$oferta_id = isset($_POST['oferta_id']) ? (int)$_POST['oferta_id'] : 0;
$accion    = trim($_POST['accion'] ?? '');

$estado = $accion === 'aceptar' ? 'aceptada' : ($accion === 'rechazar' ? 'rechazada' : '');
if ($oferta_id <= 0 || $estado === '') {
    Helper::flash_mensaje('Solicitud inválida.', 'error');
    Helper::redir(BASE_URL . '/vendedor_panel.php');
}

if (Oferta::cambiarEstado($oferta_id, $usuario_id, $estado)) {
    Helper::flash_mensaje('Oferta ' . $estado . ' correctamente.', 'success');
} else {
    Helper::flash_mensaje('No se pudo cambiar el estado. Verifica que la oferta sea tuya.', 'error');
}

Helper::redir(BASE_URL . '/vendedor_panel.php');

<?php

session_start();
require_once __DIR__ . '/../src/Auth.php';
Auth::logout();
require_once __DIR__ . '/../config/config.php';
header('Location: ' . BASE_URL . '/index.php');
exit;
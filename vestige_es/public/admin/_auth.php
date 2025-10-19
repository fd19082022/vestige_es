<?php
require_once __DIR__ . '/_common.php';
if (!can_access_admin()) {
    header('Location: ../index.php');
    exit;
}

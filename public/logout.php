<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helper.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::logout();
Helper::redir(BASE_URL . '/index.php');
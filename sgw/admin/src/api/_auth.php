<?php
// API 鉴权中间件，每个 API 文件首行 require 此文件
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';

if (empty($_SESSION['auth'])) {
    json_out(['ok' => false, 'error' => 'Unauthorized'], 401);
}

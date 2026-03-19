<?php
// API 鉴权中间件，每个 API 文件首行 require 此文件
// 关闭 HTML 错误输出，确保所有响应均为 JSON
error_reporting(0);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';

if (empty($_SESSION['auth'])) {
    json_out(['ok' => false, 'error' => 'Unauthorized'], 401);
}

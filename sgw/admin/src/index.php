<?php
session_start();
require_once __DIR__ . '/config.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . ltrim($uri, '/');

// ── Secret path 校验 ─────────────────────────────────────────
// 若配置了 ADMIN_SECRET_PATH，所有请求必须以 /SECRET 开头
// 不匹配的请求直接 444 断连，不给任何提示
if (ADMIN_SECRET_PATH !== '') {
    $prefix = '/' . ADMIN_SECRET_PATH;
    if ($uri !== $prefix && !str_starts_with($uri, $prefix . '/')) {
        http_response_code(444);
        exit;
    }
    // 剥离前缀，后续逻辑正常处理 /、/logout、/api/xxx.php
    $uri = substr($uri, strlen($prefix)) ?: '/';
}

// ── API 路由：直接 include 对应的 PHP 文件 ───────────────────
// 修复：secret path 模式下 apiFetch 会带上前缀，nginx 转发到 index.php
// 这里在剥离前缀后，将 /api/xxx.php 请求路由到实际文件
if (str_starts_with($uri, '/api/')) {
    $apiFile = realpath(__DIR__ . $uri);
    $apiDir  = realpath(__DIR__ . '/api');
    if ($apiFile && $apiDir && str_starts_with($apiFile, $apiDir . DIRECTORY_SEPARATOR)) {
        require $apiFile;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Not found']);
    }
    exit;
}

// 退出
if ($uri === '/logout') {
    session_destroy();
    header('Location: /');
    exit;
}

// 处理登录 POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($uri === '/' || $uri === '/index.php')) {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user === ADMIN_USER && ADMIN_PASS !== '' && hash_equals(ADMIN_PASS, $pass)) {
        $_SESSION['auth'] = true;
        $_SESSION['ts']   = time();
        header('Location: /');
    } else {
        $_SESSION['login_error'] = '用户名或密码错误';
        header('Location: /');
    }
    exit;
}

// Session 超时检查
if (isset($_SESSION['auth']) && (time() - ($_SESSION['ts'] ?? 0)) > SESSION_LIFETIME) {
    session_destroy();
    session_start();
}

// 刷新 session 时间戳
if (isset($_SESSION['auth'])) {
    $_SESSION['ts'] = time();
    require __DIR__ . '/views/dashboard.php';
} else {
    require __DIR__ . '/views/login.php';
}

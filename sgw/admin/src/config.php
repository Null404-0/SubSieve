<?php
// =============================================================
// config.php — 从环境变量加载配置
// =============================================================

define('ADMIN_USER',        getenv('ADMIN_USER')        ?: 'admin');
define('ADMIN_PASS',        getenv('ADMIN_PASS')        ?: '');
define('GATEWAY_CONTAINER', getenv('GATEWAY_CONTAINER') ?: 'subscribe-gateway');
define('SESSION_LIFETIME',  (int)(getenv('SESSION_LIFETIME') ?: 28800)); // 8小时
// 后台访问路径前缀，留空则不校验（例如 ef9d1566 → 必须访问 /ef9d1566 才能进入后台）
define('ADMIN_SECRET_PATH', trim(getenv('ADMIN_SECRET_PATH') ?: '', '/'));

// 文件路径（共享 volume）
define('LOG_FILE',         '/var/log/subscribe/access.log');
define('WHITELIST_IPS',    '/etc/nginx/subscribe/whitelist_ips.txt');
define('WHITELIST_CONF',   '/etc/nginx/subscribe/whitelist.conf');
define('BLACKLIST_JSON',   '/etc/nginx/subscribe/blacklist.json');
define('BLACKLIST_CONF',   '/etc/nginx/subscribe/blacklist.conf');
define('CLOUD_GEO_LOG',    '/var/log/subscribe/update_cloud_geo.log');

// ── 辅助函数 ──────────────────────────────────────────────────

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_err(string $msg, int $code = 400): void {
    json_out(['ok' => false, 'error' => $msg], $code);
}

/**
 * 在 gateway 容器内执行命令（通过 docker exec）
 */
function gateway_exec(string $cmd): array {
    $container = escapeshellarg(GATEWAY_CONTAINER);
    $full = "docker exec $container sh -c " . escapeshellarg($cmd) . " 2>&1";
    $output = shell_exec($full);
    return ['output' => trim($output ?? '')];
}

/**
 * 触发 gateway nginx reload
 */
function nginx_reload(): bool {
    $result = gateway_exec('nginx -t && nginx -s reload');
    return str_contains($result['output'], 'successful') ||
           str_contains($result['output'], 'signal process started');
}

// ── V2B 数据库接口（预留，后续填充）─────────────────────────
// TODO: 连接 V2B MySQL 查询 token 对应用户信息
// function v2b_get_user_by_token(string $token): ?array { return null; }

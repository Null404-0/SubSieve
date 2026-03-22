<?php
// =============================================================
// config.php — 从环境变量加载配置
// =============================================================

// 文件路径（共享 volume）— 先定义，以便读取 settings.json
define('LOG_FILE',          '/var/log/subscribe/access.log');
define('WHITELIST_IPS',     '/etc/nginx/subscribe/whitelist_ips.txt');
define('WHITELIST_CONF',    '/etc/nginx/subscribe/whitelist.conf');
define('BLACKLIST_JSON',    '/etc/nginx/subscribe/blacklist.json');
define('BLACKLIST_CONF',    '/etc/nginx/subscribe/blacklist.conf');
define('CLOUD_GEO_LOG',     '/var/log/subscribe/update_cloud_geo.log');
define('CLOUD_GEO_CONF',    '/etc/nginx/subscribe/cloud_geo.conf');
define('UA_BLACKLIST_JSON', '/etc/nginx/subscribe/ua_blacklist.json');
define('UA_CUSTOM_CONF',    '/etc/nginx/subscribe/ua_custom.conf');
define('UA_WHITELIST_JSON', '/etc/nginx/subscribe/ua_whitelist.json');
define('UA_WHITELIST_CONF',    '/etc/nginx/subscribe/ua_whitelist.conf');
define('TOKEN_BLACKLIST_JSON', '/etc/nginx/subscribe/token_blacklist.json');
define('SETTINGS_JSON',     '/etc/nginx/subscribe/admin_settings.json');
define('PROTECT_CONF',      '/etc/nginx/subscribe/protect.conf');
define('DEPLOY_INFO_FILE',  '/var/log/subscribe/DEPLOY_INFO.txt');

// 读取持久化设置（覆盖环境变量）
$_sg = [];
if (file_exists(SETTINGS_JSON)) {
    $_d = json_decode(file_get_contents(SETTINGS_JSON), true);
    if (is_array($_d)) $_sg = $_d;
}

define('ADMIN_USER',        $_sg['admin_user']      ?? (getenv('ADMIN_USER')        ?: 'admin'));
define('ADMIN_PASS',        $_sg['admin_pass']      ?? (getenv('ADMIN_PASS')        ?: ''));
define('NGINX_RELOAD_SIGNAL',     '/etc/nginx/subscribe/.reload');
define('WHITELIST_RELOAD_SIGNAL', '/etc/nginx/subscribe/.reload_whitelist');
define('GATEWAY_PORT',      (int)(getenv('GATEWAY_PORT') ?: 443));
define('SESSION_LIFETIME',  (int)(getenv('SESSION_LIFETIME') ?: 28800)); // 8小时
// 后台访问路径前缀，留空则不校验（例如 ef9d1566 → 必须访问 /ef9d1566 才能进入后台）
define('ADMIN_SECRET_PATH', trim(trim(getenv('ADMIN_SECRET_PATH') ?: ''), '/'));

// 界面显示设置
define('SITE_TITLE', $_sg['site_title'] ?? 'SubSieve');
define('PAGE_TITLE', $_sg['page_title'] ?? 'SubSieve Admin');

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
 * 触发 gateway nginx reload
 * 向共享 volume 写入信号文件，gateway 的 watcher 检测后执行 nginx -s reload
 * 无需挂载 Docker socket，避免宿主机 root 权限暴露
 */
function nginx_reload(): bool {
    return file_put_contents(NGINX_RELOAD_SIGNAL, '1', LOCK_EX) !== false;
}

function whitelist_reload(): bool {
    return file_put_contents(WHITELIST_RELOAD_SIGNAL, '1', LOCK_EX) !== false;
}

// ── V2B 数据库接口（预留，后续填充）─────────────────────────
// TODO: 连接 V2B MySQL 查询 token 对应用户信息
// function v2b_get_user_by_token(string $token): ?array { return null; }

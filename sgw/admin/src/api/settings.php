<?php
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — 读取当前设置
if ($method === 'GET') {
    // 读取 DEPLOY_INFO.txt 内容
    if (!empty($_GET['deploy_info'])) {
        $content = file_exists(DEPLOY_INFO_FILE) ? file_get_contents(DEPLOY_INFO_FILE) : '';
        json_out(['ok' => true, 'content' => $content ?: '']);
    }

    $s = read_settings();
    // 读取证书信息
    $certInfo = get_cert_info();
    // 从 protect.conf 读取当前上游配置（若 settings.json 未记录则解析 conf 文件）
    if (empty($s['upstream_url']) || empty($s['subscribe_path'])) {
        $parsed = parse_protect_conf();
        if ($parsed) {
            $s['upstream_url']   = $s['upstream_url']   ?? $parsed['upstream_url'];
            $s['upstream_host']  = $s['upstream_host']  ?? $parsed['upstream_host'];
            $s['subscribe_path'] = $s['subscribe_path'] ?? $parsed['subscribe_path'];
        }
    }
    json_out(['ok' => true, 'settings' => $s, 'cert' => $certInfo]);
}

// POST — 保存设置
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // 仅同步部署信息
    if (!empty($body['_sync_deploy'])) {
        $s = read_settings();
        update_deploy_info($s);
        json_out(['ok' => true]);
    }

    $s = read_settings();

    // ── 界面标题 ───────────────────────────────────────────────
    if (isset($body['site_title'])) $s['site_title'] = trim($body['site_title']) ?: 'SubSieve';
    if (isset($body['page_title'])) $s['page_title'] = trim($body['page_title']) ?: 'SubSieve Admin';

    // ── 管理员凭证 ─────────────────────────────────────────────
    if (!empty($body['admin_user'])) {
        $s['admin_user'] = trim($body['admin_user']);
    }
    if (!empty($body['new_pass'])) {
        $newPass = $body['new_pass'];
        $confPass = $body['confirm_pass'] ?? '';
        if ($newPass !== $confPass) {
            json_err('两次输入的密码不一致');
        }
        if (strlen($newPass) < 6) {
            json_err('密码至少需要6位');
        }
        $s['admin_pass'] = $newPass;
    }

    // ── 上游（机场）配置 ────────────────────────────────────────
    $upstreamChanged = false;
    if (isset($body['upstream_url']) && $body['upstream_url'] !== '') {
        $url = trim($body['upstream_url']);
        // 自动加 https:// 前缀
        if (!preg_match('#^https?://#', $url)) $url = 'https://' . $url;
        $s['upstream_url'] = $url;
        // 自动提取 host
        $host = parse_url($url, PHP_URL_HOST);
        $s['upstream_host'] = $host ?: $url;
        $upstreamChanged = true;
    }
    if (isset($body['subscribe_path']) && $body['subscribe_path'] !== '') {
        $path = trim($body['subscribe_path']);
        if (!str_starts_with($path, '/')) $path = '/' . $path;
        $s['subscribe_path'] = $path;
        $upstreamChanged = true;
    }

    // 保存 settings.json
    if (!write_settings($s)) {
        json_err('保存设置失败，请检查文件权限');
    }

    $nginxReloaded = false;
    $protectUpdated = false;

    // 若上游配置变更，重新生成 protect.conf
    if ($upstreamChanged && !empty($s['upstream_url']) && !empty($s['subscribe_path'])) {
        $host = $s['upstream_host'] ?? parse_url($s['upstream_url'], PHP_URL_HOST);
        $protectUpdated = write_protect_conf($s['subscribe_path'], $s['upstream_url'], $host);
        if ($protectUpdated) {
            $nginxReloaded = nginx_reload();
        }
    }

    // 更新 DEPLOY_INFO.txt
    update_deploy_info($s);

    json_out([
        'ok'              => true,
        'nginx_reloaded'  => $nginxReloaded,
        'protect_updated' => $protectUpdated,
        'msg'             => '设置已保存' . ($nginxReloaded ? '，nginx 已重载' : ''),
    ]);
}

json_err('不支持的请求方式', 405);

// ── 辅助函数 ──────────────────────────────────────────────────

function read_settings(): array {
    if (!file_exists(SETTINGS_JSON)) return [];
    $data = json_decode(file_get_contents(SETTINGS_JSON), true);
    return is_array($data) ? $data : [];
}

function write_settings(array $s): bool {
    return file_put_contents(SETTINGS_JSON, json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

/**
 * 重新生成 protect.conf（覆盖上游配置）
 */
function write_protect_conf(string $subscribePath, string $backend, string $host): bool {
    $conf = <<<NGINX
location ^~ $subscribePath {

    if (\$whitelist_ip = 1) { set \$block_reason ""; }

    if (\$is_cloud_ip = 1)       { set \$block_reason "cloud"; }
    if (\$bad_subscribe_ua = 1)  { set \$block_reason "ua"; }
    if (\$is_custom_bad_ua = 1)  { set \$block_reason "ua"; }
    if (\$is_ua_whitelisted = 1) { set \$block_reason ""; }

    if (\$whitelist_ip = 1) { set \$block_reason ""; }

    if (\$block_reason = "cloud") { return 403 "Forbidden: Cloud IP"; }
    if (\$block_reason = "ua")    { return 403 "Forbidden: Invalid Client"; }

    limit_req zone=subscribe_limit burst=5 nodelay;
    limit_req_status 429;

    proxy_pass          $backend;
    proxy_set_header    Host              $host;
    proxy_set_header    X-Real-IP         \$remote_addr;
    proxy_set_header    X-Forwarded-For   \$proxy_add_x_forwarded_for;
    proxy_set_header    REMOTE-HOST       \$remote_addr;
    proxy_ssl_server_name on;
    proxy_set_header    Upgrade           \$http_upgrade;
    proxy_set_header    Connection        \$connection_upgrade;
    proxy_http_version  1.1;
    resolver            1.1.1.1           ipv6=off;

    add_header Cache-Control no-store;
    add_header X-Subscribe-Filter "active";
}
NGINX;
    return file_put_contents(PROTECT_CONF, $conf, LOCK_EX) !== false;
}

/**
 * 解析 protect.conf 提取上游配置
 */
function parse_protect_conf(): ?array {
    if (!file_exists(PROTECT_CONF)) return null;
    $content = file_get_contents(PROTECT_CONF);
    $result = [];
    if (preg_match('/^location\s+\^~\s+(\S+)/m', $content, $m)) {
        $result['subscribe_path'] = $m[1];
    }
    if (preg_match('/proxy_pass\s+(\S+);/m', $content, $m)) {
        $result['upstream_url'] = rtrim($m[1], ';');
    }
    if (preg_match('/proxy_set_header\s+Host\s+(\S+);/m', $content, $m)) {
        $result['upstream_host'] = rtrim($m[1], ';');
    }
    return $result ?: null;
}

/**
 * 获取 SSL 证书信息
 */
function get_cert_info(): array {
    $certFile = '/etc/nginx/ssl/cert.pem';
    if (!file_exists($certFile)) {
        return ['exists' => false];
    }
    $info = ['exists' => true, 'path' => $certFile];
    $certData = @openssl_x509_parse(file_get_contents($certFile));
    if ($certData) {
        $info['subject']   = $certData['subject']['CN'] ?? '';
        $info['valid_to']  = date('Y-m-d', $certData['validTo_time_t']);
        $info['valid_from']= date('Y-m-d', $certData['validFrom_time_t']);
        $info['issuer']    = $certData['issuer']['O'] ?? $certData['issuer']['CN'] ?? '';
        $san = '';
        if (!empty($certData['extensions']['subjectAltName'])) {
            $san = $certData['extensions']['subjectAltName'];
        }
        $info['san'] = $san;
        $daysLeft = (int)(($certData['validTo_time_t'] - time()) / 86400);
        $info['days_left'] = $daysLeft;
    }
    return $info;
}

/**
 * 更新 DEPLOY_INFO.txt（在共享日志卷中）
 */
function update_deploy_info(array $s): void {
    $protectInfo = parse_protect_conf();
    $subscribePath = $protectInfo['subscribe_path'] ?? $s['subscribe_path'] ?? '—';
    $upstreamUrl   = $protectInfo['upstream_url']   ?? $s['upstream_url']   ?? '—';
    $adminUser     = $s['admin_user'] ?? ADMIN_USER;
    $siteTitle     = $s['site_title'] ?? SITE_TITLE;

    $content = <<<TXT
$siteTitle 部署信息
更新时间: {$_SERVER['REQUEST_TIME_FLOAT'] ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s')}

管理后台
  用户名: $adminUser
  （密码已隐藏，请从系统设置中修改）

订阅网关
  订阅路径: $subscribePath
  代理到:   $upstreamUrl
TXT;
    @file_put_contents(DEPLOY_INFO_FILE, $content, LOCK_EX);
}

<?php
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — 列出UA白名单
if ($method === 'GET') {
    json_out(['ok' => true, 'entries' => read_ua_whitelist()]);
}

// POST — 添加并立即生效
if ($method === 'POST') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $ua      = trim($body['ua'] ?? '');
    $comment = trim($body['comment'] ?? '');

    if (!$ua) json_err('请输入 UA 关键词');

    $entries = read_ua_whitelist();
    foreach ($entries as $e) {
        if ($e['ua'] === $ua) json_err('该 UA 已在白名单中');
    }

    $entries[] = [
        'ua'       => $ua,
        'comment'  => $comment,
        'added_at' => date('Y-m-d H:i'),
    ];

    if (!write_ua_whitelist($entries)) json_err('写入UA白名单文件失败，请检查文件权限');
    $reload = nginx_reload();
    json_out(['ok' => true, 'nginx_reloaded' => $reload]);
}

// PATCH — 更新备注（仅更新 JSON，不 reload nginx）
if ($method === 'PATCH') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $ua      = trim($body['ua'] ?? '');
    $comment = trim($body['comment'] ?? '');

    if (!$ua) json_err('缺少 ua 参数');

    $entries = read_ua_whitelist();
    $found   = false;
    foreach ($entries as &$e) {
        if ($e['ua'] === $ua) { $e['comment'] = $comment; $found = true; break; }
    }
    unset($e);

    if (!$found) json_err('未找到该UA');
    file_put_contents(UA_WHITELIST_JSON, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    json_out(['ok' => true]);
}

// DELETE — 移除并立即生效
if ($method === 'DELETE') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $ua   = trim($body['ua'] ?? '');

    if (!$ua) json_err('缺少 ua 参数');

    $entries = array_filter(read_ua_whitelist(), fn($e) => $e['ua'] !== $ua);
    if (!write_ua_whitelist(array_values($entries))) json_err('写入UA白名单文件失败，请检查文件权限');
    $reload = nginx_reload();
    json_out(['ok' => true, 'nginx_reloaded' => $reload]);
}

json_err('不支持的请求方式', 405);

// ── 读写 UA 白名单 ─────────────────────────────────────────────

function read_ua_whitelist(): array {
    if (!file_exists(UA_WHITELIST_JSON)) return [];
    $data = json_decode(file_get_contents(UA_WHITELIST_JSON), true);
    return is_array($data) ? $data : [];
}

function write_ua_whitelist(array $entries): bool {
    $r1 = file_put_contents(UA_WHITELIST_JSON, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    // 生成 nginx map conf（$is_ua_whitelisted）
    $lines   = ['# UA白名单 - 由 admin 自动生成 | ' . date('Y-m-d H:i:s')];
    $lines[] = 'map $http_user_agent $is_ua_whitelisted {';
    $lines[] = '    default 0;';
    foreach ($entries as $e) {
        $pattern = str_replace(['\\', '"', '~'], ['\\\\', '\\"', '\\~'], $e['ua']);
        $cmt     = $e['comment'] ? " # {$e['comment']}" : '';
        $lines[] = "    \"~*{$pattern}\" 1;{$cmt}";
    }
    $lines[] = '}';

    $r2 = file_put_contents(UA_WHITELIST_CONF, implode("\n", $lines) . "\n", LOCK_EX);

    return $r1 !== false && $r2 !== false;
}

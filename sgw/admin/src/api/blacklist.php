<?php
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — 列出黑名单
if ($method === 'GET') {
    json_out(['ok' => true, 'entries' => read_blacklist()]);
}

// POST — 添加并立即生效
if ($method === 'POST') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $ip      = trim($body['ip'] ?? '');
    $comment = trim($body['comment'] ?? '');

    if (!$ip || !preg_match('/^\d{1,3}(\.\d{1,3}){3}(\/\d+)?$/', $ip)) {
        json_err('IP 格式不合法（仅支持 IPv4）');
    }

    $entries = read_blacklist();
    foreach ($entries as $e) {
        if ($e['ip'] === $ip) json_err('该IP已在黑名单中');
    }

    $entries[] = [
        'ip'       => $ip,
        'comment'  => $comment,
        'added_at' => date('Y-m-d H:i'),
    ];

    if (!write_blacklist($entries)) json_err('写入黑名单文件失败，请检查文件权限');
    $reload = nginx_reload();

    json_out(['ok' => true, 'nginx_reloaded' => $reload]);
}

// DELETE — 移除并立即生效
if ($method === 'DELETE') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $ip   = trim($body['ip'] ?? '');

    if (!$ip) json_err('缺少 ip 参数');

    $entries = array_filter(read_blacklist(), fn($e) => $e['ip'] !== $ip);
    if (!write_blacklist(array_values($entries))) json_err('写入黑名单文件失败，请检查文件权限');
    $reload = nginx_reload();

    json_out(['ok' => true, 'nginx_reloaded' => $reload]);
}

json_err('不支持的请求方式', 405);

// ── 读写黑名单 ────────────────────────────────────────────────

function read_blacklist(): array {
    if (!file_exists(BLACKLIST_JSON)) return [];
    $data = json_decode(file_get_contents(BLACKLIST_JSON), true);
    return is_array($data) ? $data : [];
}

function write_blacklist(array $entries): bool {
    // 写 JSON（含元数据）
    $r1 = file_put_contents(BLACKLIST_JSON, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    // 生成 nginx deny conf（返回444，比403更彻底）
    $lines = ["# 黑名单 - 由 admin 自动生成 | " . date('Y-m-d H:i:s')];
    foreach ($entries as $e) {
        $cmt = $e['comment'] ? " # {$e['comment']} ({$e['added_at']})" : " # {$e['added_at']}";
        $lines[] = "deny {$e['ip']};{$cmt}";
    }
    $r2 = file_put_contents(BLACKLIST_CONF, implode("\n", $lines) . "\n", LOCK_EX);

    return $r1 !== false && $r2 !== false;
}

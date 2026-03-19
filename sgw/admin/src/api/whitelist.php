<?php
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — 列出白名单
if ($method === 'GET') {
    json_out(['ok' => true, 'entries' => read_whitelist()]);
}

// POST — 添加条目
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $ip      = trim($body['ip'] ?? '');
    $comment = trim($body['comment'] ?? '');

    if (!$ip || !preg_match('/^[\d\.\/\:a-fA-F]+$/', $ip)) {
        json_err('IP 格式不合法');
    }

    $entries = read_whitelist();
    foreach ($entries as $e) {
        if ($e['ip'] === $ip) json_err('该IP已在白名单中');
    }

    $line = $ip . ($comment ? "  # $comment" : '');
    file_put_contents(WHITELIST_IPS, $line . "\n", FILE_APPEND | LOCK_EX);

    json_out(['ok' => true]);
}

// DELETE — 删除条目
if ($method === 'DELETE') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $ip   = trim($body['ip'] ?? '');

    if (!$ip) json_err('缺少 ip 参数');

    $lines = file_exists(WHITELIST_IPS)
        ? file(WHITELIST_IPS, FILE_IGNORE_NEW_LINES)
        : [];

    $new = array_filter($lines, function($l) use ($ip) {
        $entry = strtok(trim($l), ' ');
        $entry = strtok($entry, "\t");
        return $entry !== $ip;
    });

    file_put_contents(WHITELIST_IPS, implode("\n", $new) . "\n", LOCK_EX);
    json_out(['ok' => true]);
}

// PUT — 应用白名单（触发 reload_whitelist.sh + nginx reload）
if ($method === 'PUT') {
    // 在 gateway 容器内执行 reload_whitelist.sh
    $result = gateway_exec('/scripts/reload_whitelist.sh');
    if (str_contains($result['output'], 'error') || str_contains($result['output'], 'failed')) {
        json_err('生效失败: ' . $result['output']);
    }
    json_out(['ok' => true, 'msg' => '白名单已生效']);
}

json_err('不支持的请求方式', 405);

// ── 读取并解析白名单文件 ──────────────────────────────────────
function read_whitelist(): array {
    if (!file_exists(WHITELIST_IPS)) return [];

    $entries = [];
    foreach (file(WHITELIST_IPS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        // 分离 IP 和注释
        $comment = '';
        if (preg_match('/^(\S+)\s+#\s*(.*)$/', $line, $m)) {
            $ip      = $m[1];
            $comment = $m[2];
        } else {
            $ip = strtok($line, " \t");
        }

        $entries[] = ['ip' => $ip, 'comment' => $comment];
    }
    return $entries;
}

<?php
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — 列出白名单
if ($method === 'GET') {
    json_out(['ok' => true, 'entries' => read_whitelist()]);
}

// POST — 添加条目（单个或批量导入）
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // 批量导入（来自文件导入）
    if (!empty($body['import_ips']) && is_array($body['import_ips'])) {
        $entries = read_whitelist();
        $existingSet = [];
        foreach ($entries as $e) $existingSet[$e['ip']] = true;
        $newLines = []; $added = 0; $skipped = 0; $invalid = 0;
        foreach ($body['import_ips'] as $rawIp) {
            $ip = trim($rawIp);
            if (!$ip) continue;
            if (!preg_match('/^[\d\.\/\:a-fA-F]+$/', $ip)) { $invalid++; continue; }
            if (isset($existingSet[$ip])) { $skipped++; continue; }
            $newLines[] = $ip . '  # 从文件导入';
            $existingSet[$ip] = true;
            $added++;
        }
        if ($added > 0) {
            file_put_contents(WHITELIST_IPS, implode("\n", $newLines) . "\n", FILE_APPEND | LOCK_EX);
        }
        json_out(['ok' => true, 'added' => $added, 'skipped' => $skipped, 'invalid' => $invalid]);
    }

    // 单个添加
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

// PATCH — 更新备注
if ($method === 'PATCH') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $ip      = trim($body['ip'] ?? '');
    $comment = trim($body['comment'] ?? '');

    if (!$ip) json_err('缺少 ip 参数');
    if (!file_exists(WHITELIST_IPS)) json_err('白名单文件不存在');

    $lines = file(WHITELIST_IPS, FILE_IGNORE_NEW_LINES);
    $found = false;
    $new   = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            $new[] = $line;
            continue;
        }
        if (preg_match('/^(\S+)/', $trimmed, $m) && $m[1] === $ip) {
            $new[] = $ip . ($comment ? "  # $comment" : '');
            $found = true;
        } else {
            $new[] = $line;
        }
    }

    if (!$found) json_err('未找到该IP');
    file_put_contents(WHITELIST_IPS, implode("\n", $new) . "\n", LOCK_EX);
    json_out(['ok' => true]);
}

// DELETE — 删除条目（支持单个 ip 或批量 ips 数组）
if ($method === 'DELETE') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $toRemove = [];
    if (!empty($body['ips']) && is_array($body['ips'])) {
        $toRemove = array_map('trim', $body['ips']);
    } else {
        $ip = trim($body['ip'] ?? '');
        if (!$ip) json_err('缺少 ip 参数');
        $toRemove = [$ip];
    }

    $lines = file_exists(WHITELIST_IPS)
        ? file(WHITELIST_IPS, FILE_IGNORE_NEW_LINES)
        : [];

    $new = array_filter($lines, function($l) use ($toRemove) {
        $entry = strtok(trim($l), " \t");
        return !in_array($entry, $toRemove);
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

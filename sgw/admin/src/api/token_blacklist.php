<?php
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — 列出黑名单 Token + 今日各 IP 拉取统计
if ($method === 'GET') {
    $entries = read_token_blacklist();
    if (empty($entries)) {
        json_out(['ok' => true, 'entries' => []]);
    }

    $blacklistedSet = array_flip(array_column($entries, 'token'));

    // 读取今日日志，统计每个黑名单 Token 被哪些 IP 拉取及次数
    $today = date('d/M/Y');
    $tokenIpCount = []; // token => [ip => count]

    if (file_exists(LOG_FILE)) {
        $handle = fopen(LOG_FILE, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (!str_contains($line, "[$today:")) continue;
                if (!preg_match('/^(\S+) \[[^\]]+\] "([^"]*)" (\d+)/', $line, $m)) continue;
                [, $ip, $request, $status] = $m;
                if ((int)$status !== 200) continue;
                if (!preg_match('/[?&]token=([^&\s]+)/i', $request, $tm)) continue;
                $tok = $tm[1];
                if (!isset($blacklistedSet[$tok])) continue;
                $tokenIpCount[$tok][$ip] = ($tokenIpCount[$tok][$ip] ?? 0) + 1;
            }
            fclose($handle);
        }
    }

    $result = array_map(function ($e) use ($tokenIpCount) {
        $tok   = $e['token'];
        $pulls = [];
        if (isset($tokenIpCount[$tok])) {
            arsort($tokenIpCount[$tok]);
            foreach ($tokenIpCount[$tok] as $ip => $cnt) {
                $pulls[] = ['ip' => $ip, 'count' => $cnt];
            }
        }
        $e['today_pulls'] = $pulls;
        $e['today_total'] = array_sum(array_column($pulls, 'count'));
        return $e;
    }, $entries);

    json_out(['ok' => true, 'entries' => $result]);
}

// POST — 添加 Token 黑名单
if ($method === 'POST') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $token   = trim($body['token'] ?? '');
    $comment = trim($body['comment'] ?? '');

    if (!$token) json_err('请输入 Token');

    $entries = read_token_blacklist();
    foreach ($entries as $e) {
        if ($e['token'] === $token) json_err('该 Token 已在黑名单中');
    }

    $entries[] = ['token' => $token, 'comment' => $comment, 'added_at' => date('Y-m-d H:i')];
    if (!write_token_blacklist($entries)) json_err('写入失败，请检查文件权限');
    json_out(['ok' => true]);
}

// PATCH — 更新备注
if ($method === 'PATCH') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $token   = trim($body['token'] ?? '');
    $comment = trim($body['comment'] ?? '');

    if (!$token) json_err('缺少 token 参数');

    $entries = read_token_blacklist();
    $found   = false;
    foreach ($entries as &$e) {
        if ($e['token'] === $token) { $e['comment'] = $comment; $found = true; break; }
    }
    unset($e);

    if (!$found) json_err('未找到该Token');
    if (!write_token_blacklist($entries)) json_err('写入失败，请检查文件权限');
    json_out(['ok' => true]);
}

// DELETE — 移除 Token 黑名单
if ($method === 'DELETE') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = trim($body['token'] ?? '');

    if (!$token) json_err('缺少 token 参数');

    $entries = array_values(array_filter(read_token_blacklist(), fn($e) => $e['token'] !== $token));
    if (!write_token_blacklist($entries)) json_err('写入失败，请检查文件权限');
    json_out(['ok' => true]);
}

json_err('不支持的请求方式', 405);

// ── 读写 Token 黑名单 ────────────────────────────────────────

function read_token_blacklist(): array {
    if (!file_exists(TOKEN_BLACKLIST_JSON)) return [];
    $data = json_decode(file_get_contents(TOKEN_BLACKLIST_JSON), true);
    return is_array($data) ? $data : [];
}

function write_token_blacklist(array $entries): bool {
    return file_put_contents(TOKEN_BLACKLIST_JSON, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

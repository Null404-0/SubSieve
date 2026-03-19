<?php
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// DELETE — 删除7天前的日志行
if ($method === 'DELETE') {
    if (!file_exists(LOG_FILE)) {
        json_out(['ok' => true, 'deleted' => 0, 'kept' => 0]);
    }

    $cutoff = strtotime('-7 days');
    $lines  = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $kept = []; $deletedCount = 0;

    foreach ($lines as $line) {
        // nginx 日志格式：IP [dd/Mon/YYYY:HH:MM:SS +xxxx] ...
        if (preg_match('/\[(\d{2}\/\w+\/\d{4})/', $line, $m)) {
            $d = DateTime::createFromFormat('d/M/Y', $m[1]);
            if ($d && $d->getTimestamp() < $cutoff) {
                $deletedCount++;
                continue;
            }
        }
        $kept[] = $line;
    }

    file_put_contents(LOG_FILE, implode("\n", $kept) . (count($kept) ? "\n" : ''), LOCK_EX);
    json_out(['ok' => true, 'deleted' => $deletedCount, 'kept' => count($kept)]);
}

// GET — 返回日志列表
$mode    = $_GET['mode'] ?? 'today';  // today | all
$today   = date('d/M/Y');
$maxRows = 3000;
$logs    = [];

if (file_exists(LOG_FILE)) {
    $handle = fopen(LOG_FILE, 'r');
    if ($handle) {
        $buffer = [];
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line);
            if ($line === '') continue;
            if ($mode === 'today' && !str_contains($line, "[$today:")) continue;
            $buffer[] = $line;
            if (count($buffer) > $maxRows) array_shift($buffer);
        }
        fclose($handle);

        foreach ($buffer as $raw) {
            $entry = parse_line($raw);
            if ($entry) $logs[] = $entry;
        }
    }
}

json_out(['ok' => true, 'logs' => $logs, 'date' => $today, 'mode' => $mode]);

// ── 解析一行 nginx access log ──────────────────────────────
function parse_line(string $line): ?array {
    // 格式: IP [time] "REQUEST" STATUS BYTES "UA"
    $pat = '/^(\S+) \[([^\]]+)\] "([^"]*)" (\d+) (\S+) "([^"]*)"$/';
    if (!preg_match($pat, $line, $m)) return null;

    [, $ip, $time, $request, $status, $bytes, $ua] = $m;

    // 提取 token
    $token = '';
    if (preg_match('/[?&]token=([^&\s"]+)/i', $request, $tm)) {
        $token = $tm[1];
    }

    // 时间只取 时:分:秒
    $timeShort = preg_replace('/^\d+\/\w+\/\d+:/', '', $time);
    $timeShort = preg_replace('/ \+\d+$/', '', $timeShort);

    return [
        'ip'      => $ip,
        'time'    => $timeShort,
        'request' => $request,
        'status'  => (int)$status,
        'bytes'   => $bytes,
        'ua'      => $ua,
        'token'   => $token,
    ];
}

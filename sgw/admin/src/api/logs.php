<?php
require_once __DIR__ . '/_auth.php';

$today   = date('d/M/Y');
$maxRows = 2000;
$logs    = [];

if (file_exists(LOG_FILE)) {
    $handle = fopen(LOG_FILE, 'r');
    if ($handle) {
        $buffer = [];
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line);
            if ($line === '' || !str_contains($line, "[$today:")) continue;
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

json_out(['ok' => true, 'logs' => $logs, 'date' => $today]);

// ── 解析一行 nginx access log ──────────────────────────────
function parse_line(string $line): ?array {
    // 格式: IP [time] "REQUEST" STATUS BYTES "UA"
    $pat = '/^(\S+) \[([^\]]+)\] "([^"]*)" (\d+) (\S+) "([^"]*)"$/';
    if (!preg_match($pat, $line, $m)) return null;

    [, $ip, $time, $request, $status, $bytes, $ua] = $m;

    // 提取 token
    $token = '';
    if (preg_match('/[?&]token=([^&\s]+)/i', $request, $tm)) {
        $token = $tm[1];
    }

    // 时间只取 时:分:秒
    $timeShort = preg_replace('/^\d+\/\w+\/\d+:/', '', $time);
    $timeShort = preg_replace('/ \+\d+$/', '', $timeShort);

    return [
        'ip'          => $ip,
        'time'        => $timeShort,
        'request'     => $request,
        'status'      => (int)$status,
        'bytes'       => $bytes,
        'ua'          => $ua,
        'token'       => $token,
        'token_short' => $token ? (substr($token, 0, 8) . '…') : '',
    ];
}

<?php
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── 导出 — 直接输出原始日志文件 ──────────────────────────────
if ($method === 'GET' && !empty($_GET['export'])) {
    if (!file_exists(LOG_FILE)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo '';
        exit;
    }
    $filename = 'access-' . date('Ymd-His') . '.log';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize(LOG_FILE));
    readfile(LOG_FILE);
    exit;
}

// ── 导入 — 接收 nginx 日志文本，转换格式后合并 ────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $raw  = trim($body['content'] ?? '');
    if ($raw === '') json_err('内容为空');

    $lines = explode("\n", str_replace("\r\n", "\n", $raw));
    $imported = 0;
    $newLines  = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $internal = nginx_combined_to_internal($line);
        if ($internal === null) continue;      // 无法解析，跳过
        $newLines[] = $internal;
        $imported++;
    }

    if (!$imported) json_err('未能解析任何有效日志行，请确认为标准 nginx 日志格式');

    // 读取现有日志，合并、去重、按时间排序
    $existing = [];
    if (file_exists(LOG_FILE)) {
        $existing = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    $merged  = array_unique(array_merge($existing, $newLines));
    $mergedT = [];
    foreach ($merged as $l) {
        $ts = extract_timestamp($l);
        $mergedT[] = [$ts, $l];
    }
    usort($mergedT, fn($a,$b) => $a[0] <=> $b[0]);
    $sorted = array_map(fn($r) => $r[1], $mergedT);

    file_put_contents(LOG_FILE, implode("\n", $sorted) . "\n", LOCK_EX);
    json_out(['ok' => true, 'imported' => $imported, 'total' => count($sorted)]);
}

// ── DELETE — 删除7天前的日志行 ──────────────────────────────
if ($method === 'DELETE') {
    if (!file_exists(LOG_FILE)) {
        json_out(['ok' => true, 'deleted' => 0, 'kept' => 0]);
    }

    $cutoff = strtotime('-7 days');
    $lines  = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $kept = []; $deletedCount = 0;

    foreach ($lines as $line) {
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

// ── GET — 返回日志列表 ──────────────────────────────────────
$mode    = $_GET['mode'] ?? 'today';
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

// ── 解析一行内部格式日志 ──────────────────────────────────────
function parse_line(string $line): ?array {
    // 内部格式: IP [time] "REQUEST" STATUS BYTES "UA"
    $pat = '/^(\S+) \[([^\]]+)\] "([^"]*)" (\d+) (\S+) "([^"]*)"$/';
    if (!preg_match($pat, $line, $m)) return null;

    [, $ip, $time, $request, $status, $bytes, $ua] = $m;

    $token = '';
    if (preg_match('/[?&]token=([^&\s"]+)/i', $request, $tm)) {
        $token = $tm[1];
    }

    $timeShort = preg_replace('/ \+\d+$/', '', $time);
    if (preg_match('/^(\d{2})\/(\w{3})\/(\d{4}):(\d{2}:\d{2}:\d{2})$/', $timeShort, $dm)) {
        $months = ['Jan'=>'01','Feb'=>'02','Mar'=>'03','Apr'=>'04','May'=>'05','Jun'=>'06',
                   'Jul'=>'07','Aug'=>'08','Sep'=>'09','Oct'=>'10','Nov'=>'11','Dec'=>'12'];
        $timeShort = "{$dm[3]}-" . ($months[$dm[2]] ?? '??') . "-{$dm[1]} {$dm[4]}";
    }

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

// ── nginx combined 格式 → 内部格式 ────────────────────────────
// nginx combined: IP - user [time] "request" status bytes "referer" "ua"
// 内部格式:       IP [time] "request" status bytes "ua"
function nginx_combined_to_internal(string $line): ?string {
    $pat = '/^(\S+) \S+ \S+ \[([^\]]+)\] "([^"]*)" (\d+) (\S+) "[^"]*" "([^"]*)"$/';
    if (preg_match($pat, $line, $m)) {
        [, $ip, $time, $request, $status, $bytes, $ua] = $m;
        return "$ip [$time] \"$request\" $status $bytes \"$ua\"";
    }
    // 如果已经是内部格式，直接返回
    if (preg_match('/^\S+ \[[^\]]+\] "[^"]*" \d+ \S+ "[^"]*"$/', $line)) {
        return $line;
    }
    return null;
}

// ── 从日志行提取时间戳（用于排序）────────────────────────────
function extract_timestamp(string $line): int {
    if (!preg_match('/\[(\d{2}\/\w{3}\/\d{4}:\d{2}:\d{2}:\d{2} [+-]\d{4})\]/', $line, $m)) {
        return 0;
    }
    $dt = DateTime::createFromFormat('d/M/Y:H:i:s O', $m[1]);
    return $dt ? $dt->getTimestamp() : 0;
}

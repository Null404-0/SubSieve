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
    clearstatcache(true, LOG_FILE);
    $filename = 'access-' . date('Ymd-His') . '.log';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize(LOG_FILE));
    readfile(LOG_FILE);
    exit;
}

// ── 导入 — multipart 文件上传，流式合并到现有日志 ──────────────
if ($method === 'POST') {
    // 检测 multipart 上传
    if (!isset($_FILES['log'])) {
        json_err('请通过文件上传方式导入日志');
    }

    $uploadErr = $_FILES['log']['error'];
    if ($uploadErr === UPLOAD_ERR_INI_SIZE || $uploadErr === UPLOAD_ERR_FORM_SIZE) {
        json_err('文件超过服务器上传限制（upload_max_filesize: '
            . ini_get('upload_max_filesize') . '），请拆分后分批导入');
    }
    if ($uploadErr !== UPLOAD_ERR_OK) {
        json_err('文件上传失败（PHP 错误码 ' . $uploadErr . '）');
    }

    // ── 1. 流式读取并转换上传的日志文件（仅新行占内存）──────────
    $newLines = [];
    $imported = 0;
    $fh = fopen($_FILES['log']['tmp_name'], 'r');
    if (!$fh) json_err('无法读取上传文件');
    while (($line = fgets($fh)) !== false) {
        $line = trim($line);
        if ($line === '') continue;
        $internal = nginx_combined_to_internal($line);
        if ($internal === null) continue;
        $newLines[] = $internal;
        $imported++;
    }
    fclose($fh);

    if (!$imported) json_err('未能解析任何有效日志行，请确认为标准 nginx 日志格式');

    // ── 2. 对新行去重、按时间排序 ────────────────────────────────
    $newLines = array_values(array_unique($newLines));
    usort($newLines, fn($a, $b) => extract_timestamp($a) <=> extract_timestamp($b));
    $nc = count($newLines);
    $ni = 0;

    // ── 3. 流式归并现有日志文件（O(1) 内存，支持超大文件）────────
    // 用系统 /tmp 目录写临时文件，避免 /var/log/subscribe/ 新建文件权限不足
    $tmpOut = tempnam(sys_get_temp_dir(), 'ss_import_');
    if ($tmpOut === false) json_err('无法创建临时文件（sys_get_temp_dir 不可写）');
    $outFh = fopen($tmpOut, 'w');
    if (!$outFh) { @unlink($tmpOut); json_err('无法打开临时文件进行写入'); }

    $existFh  = file_exists(LOG_FILE) ? fopen(LOG_FILE, 'r') : null;
    $existBuf = null;   // 当前从现有文件读取的行
    $lastWritten = null;
    $total = 0;

    // 读取现有文件的下一行（跳过空行）
    $readExist = function() use ($existFh, &$existBuf) {
        if (!$existFh) { $existBuf = null; return; }
        while (($l = fgets($existFh)) !== false) {
            $l = trim($l);
            if ($l !== '') { $existBuf = $l; return; }
        }
        $existBuf = null;
    };
    $readExist();   // 初始化第一行

    while ($ni < $nc || $existBuf !== null) {
        $newTs   = ($ni < $nc)         ? extract_timestamp($newLines[$ni]) : PHP_INT_MAX;
        $existTs = ($existBuf !== null) ? extract_timestamp($existBuf)     : PHP_INT_MAX;

        if ($newTs <= $existTs) {
            $toWrite = $newLines[$ni++];
        } else {
            $toWrite = $existBuf;
            $readExist();
        }

        if ($toWrite === $lastWritten) continue;   // 去重
        $lastWritten = $toWrite;
        fwrite($outFh, $toWrite . "\n");
        $total++;
    }

    if ($existFh) fclose($existFh);
    fclose($outFh);

    // rename 跨文件系统会失败（/tmp → /var/log），降级为 copy + unlink
    if (!rename($tmpOut, LOG_FILE)) {
        if (!copy($tmpOut, LOG_FILE)) {
            @unlink($tmpOut);
            json_err('无法更新日志文件，请检查 ' . LOG_FILE . ' 的写入权限');
        }
        @unlink($tmpOut);
    }

    json_out(['ok' => true, 'imported' => $imported, 'total' => $total]);
}

// ── DELETE — 删除日志 ───────────────────────────────────────
if ($method === 'DELETE') {
    // 删除当前所有日志
    if (($_SERVER['HTTP_X_DELETE_ALL'] ?? '') === '1') {
        if (file_exists(LOG_FILE)) {
            file_put_contents(LOG_FILE, '', LOCK_EX);
        }
        json_out(['ok' => true, 'deleted' => 'all', 'kept' => 0]);
    }

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

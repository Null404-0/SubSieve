<?php
require_once __DIR__ . '/_auth.php';

$today  = date('d/M/Y');
$ips    = [];   // ip => [total,200,403,429,444]
$tokens = [];   // token => [count, last_time]
$badUas = [];   // ua => count (403 only)

if (file_exists(LOG_FILE)) {
    $handle = fopen(LOG_FILE, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line);
            if ($line === '' || !str_contains($line, "[$today:")) continue;

            $pat = '/^(\S+) \[([^\]]+)\] "([^"]*)" (\d+) (\S+) "([^"]*)"$/';
            if (!preg_match($pat, $line, $m)) continue;

            [, $ip, $time, $request, $status, , $ua] = $m;
            $status = (int)$status;

            // IP 统计
            if (!isset($ips[$ip])) $ips[$ip] = ['total'=>0,'s200'=>0,'s403'=>0,'s429'=>0,'s444'=>0];
            $ips[$ip]['total']++;
            if ($status === 200) $ips[$ip]['s200']++;
            elseif ($status === 403) $ips[$ip]['s403']++;
            elseif ($status === 429) $ips[$ip]['s429']++;
            elseif ($status === 444) $ips[$ip]['s444']++;

            // Token 统计（只统计订阅路径）
            if (preg_match('/[?&]token=([^&\s]+)/i', $request, $tm)) {
                $tok = $tm[1];
                if (!isset($tokens[$tok])) $tokens[$tok] = ['count'=>0,'last_time'=>''];
                $tokens[$tok]['count']++;
                $tokens[$tok]['last_time'] = trim(preg_replace('/^\d+\/\w+\/\d+:/', '', preg_replace('/ \+\d+$/', '', $time)));
            }

            // 可疑 UA（状态403 且 UA 不为云IP拦截的常见UA）
            if ($status === 403 && $ua !== '') {
                if (!isset($badUas[$ua])) $badUas[$ua] = 0;
                $badUas[$ua]++;
            }
        }
        fclose($handle);
    }
}

// 排序：Top 10 IP
uasort($ips, fn($a,$b) => $b['total'] - $a['total']);
$topIps = [];
foreach (array_slice($ips, 0, 10, true) as $ip => $v) {
    $topIps[] = array_merge(['ip' => $ip], $v);
}

// Top 10 Token
uasort($tokens, fn($a,$b) => $b['count'] - $a['count']);
$topTokens = [];
foreach (array_slice($tokens, 0, 10, true) as $tok => $v) {
    $topTokens[] = [
        'token'     => substr($tok, 0, 8) . '…',
        'token_full'=> $tok,
        'count'     => $v['count'],
        'last_time' => $v['last_time'],
    ];
}

// Top 可疑 UA（按次数降序）
arsort($badUas);
$badUaList = [];
foreach (array_slice($badUas, 0, 20, true) as $ua => $cnt) {
    $badUaList[] = ['ua' => $ua, 'count' => $cnt];
}

json_out([
    'ok'          => true,
    'top_ips'     => $topIps,
    'top_tokens'  => $topTokens,
    'bad_uas'     => $badUaList,
    // TODO: v2b_db - 在此处用 v2b_get_user_by_token() 丰富 token 信息
]);

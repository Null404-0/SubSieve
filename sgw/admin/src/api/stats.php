<?php
require_once __DIR__ . '/_auth.php';

$today  = date('d/M/Y');
$ips    = [];   // ip => [total,200,403,429,444]  (today only)
$tokens = [];   // token => [count, last_time]     (today only)
$badUas = [];   // ua => count (403 only, today)

// 全量日志用于可疑分析
$suspTokenIps = [];  // token => {ip => true}
$suspIpTokens = [];  // ip    => {token => true}

// 读取Token黑名单（用于从统计中排除）
$tokenBlacklist = [];
if (file_exists(TOKEN_BLACKLIST_JSON)) {
    $tbData = json_decode(file_get_contents(TOKEN_BLACKLIST_JSON), true);
    if (is_array($tbData)) {
        foreach ($tbData as $e) {
            if (!empty($e['token'])) $tokenBlacklist[$e['token']] = true;
        }
    }
}

// 读取白名单（用于排除）
$whitelistIps = [];
if (file_exists(WHITELIST_IPS)) {
    foreach (file(WHITELIST_IPS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $wl) {
        $wl = trim($wl);
        if ($wl === '' || str_starts_with($wl, '#')) continue;
        $ip = strtok($wl, " \t#");
        if ($ip) $whitelistIps[$ip] = true;
    }
}

if (file_exists(LOG_FILE)) {
    $handle = fopen(LOG_FILE, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line);
            if ($line === '') continue;

            $pat = '/^(\S+) \[([^\]]+)\] "([^"]*)" (\d+) (\S+) "([^"]*)"$/';
            if (!preg_match($pat, $line, $m)) continue;

            [, $ip, $time, $request, $status, , $ua] = $m;
            $status = (int)$status;

            // ── 今日统计 ──────────────────────────────────────────
            if (str_contains($line, "[$today:")) {
                if (!isset($ips[$ip])) $ips[$ip] = ['total'=>0,'s200'=>0,'s403'=>0,'s429'=>0,'s444'=>0];
                $ips[$ip]['total']++;
                if ($status === 200) $ips[$ip]['s200']++;
                elseif ($status === 403) $ips[$ip]['s403']++;
                elseif ($status === 429) $ips[$ip]['s429']++;
                elseif ($status === 444) $ips[$ip]['s444']++;

                if (preg_match('/[?&]token=([^&\s]+)/i', $request, $tm)) {
                    $tok = $tm[1];
                    if (!isset($tokenBlacklist[$tok])) {
                        if (!isset($tokens[$tok])) $tokens[$tok] = ['count'=>0,'last_time'=>''];
                        $tokens[$tok]['count']++;
                        $tokens[$tok]['last_time'] = trim(preg_replace('/^\d+\/\w+\/\d+:/', '', preg_replace('/ \+\d+$/', '', $time)));
                    }
                }

                if ($status === 403 && $ua !== '') {
                    if (!isset($badUas[$ua])) $badUas[$ua] = 0;
                    $badUas[$ua]++;
                }
            }

            // ── 全量可疑分析（200 状态的订阅请求，排除白名单IP和Token黑名单）──
            if ($status === 200
                && !isset($whitelistIps[$ip])
                && preg_match('/[?&]token=([^&\s]+)/i', $request, $tm)
            ) {
                $tok = $tm[1];
                if (!isset($tokenBlacklist[$tok])) {
                    $suspTokenIps[$tok][$ip] = true;
                    $suspIpTokens[$ip][$tok]  = true;
                }
            }
        }
        fclose($handle);
    }
}

// Top IP（今日，最多返回500条，前端负责显示限制）
uasort($ips, fn($a,$b) => $b['total'] - $a['total']);
$topIps = [];
foreach (array_slice($ips, 0, 500, true) as $ip => $v) {
    $topIps[] = array_merge(['ip' => $ip], $v);
}

// Top Token（今日，最多返回500条，前端负责显示限制）
uasort($tokens, fn($a,$b) => $b['count'] - $a['count']);
$topTokens = [];
foreach (array_slice($tokens, 0, 500, true) as $tok => $v) {
    $topTokens[] = [
        'token'      => substr($tok, 0, 8) . '…',
        'token_full' => $tok,
        'count'      => $v['count'],
        'last_time'  => $v['last_time'],
    ];
}

// UA TOP（最多返回500条，前端负责显示限制）
arsort($badUas);
$badUaList = [];
foreach (array_slice($badUas, 0, 500, true) as $ua => $cnt) {
    $badUaList[] = ['ua' => $ua, 'count' => $cnt];
}

// 可疑 Token（日志周期内被 3+ 个不同IP拉取）
$SUSP_TOKEN_THRESHOLD = 3;
$suspTokenList = [];
foreach ($suspTokenIps as $tok => $ipSet) {
    $cnt = count($ipSet);
    if ($cnt >= $SUSP_TOKEN_THRESHOLD) {
        $suspTokenList[] = ['token' => $tok, 'ip_count' => $cnt, 'ips' => array_keys($ipSet)];
    }
}
usort($suspTokenList, fn($a,$b) => $b['ip_count'] - $a['ip_count']);

// 可疑 IP（日志周期内拉取了 3+ 个不同Token）
$SUSP_IP_THRESHOLD = 3;
$suspIpList = [];
foreach ($suspIpTokens as $ip => $tokSet) {
    $cnt = count($tokSet);
    if ($cnt >= $SUSP_IP_THRESHOLD) {
        $suspIpList[] = ['ip' => $ip, 'token_count' => $cnt];
    }
}
usort($suspIpList, fn($a,$b) => $b['token_count'] - $a['token_count']);

json_out([
    'ok'          => true,
    'top_ips'     => $topIps,
    'top_tokens'  => $topTokens,
    'bad_uas'     => $badUaList,
    'susp_tokens' => $suspTokenList,
    'susp_ips'    => $suspIpList,
]);

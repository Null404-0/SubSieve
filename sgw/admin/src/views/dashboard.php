<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(PAGE_TITLE, ENT_QUOTES) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0f1117;--bg2:#13161f;--bg3:#1a1d2e;--bg-input:#0f1117;
  --border:#1e2236;--border2:#2d3144;
  --text:#e2e8f0;--text2:#94a3b8;--text3:#64748b;
  --accent:#6366f1;
}
[data-theme="light"]{
  --bg:#f0f2f5;--bg2:#ffffff;--bg3:#ffffff;--bg-input:#f8fafc;
  --border:#e2e8f0;--border2:#cbd5e1;
  --text:#1e293b;--text2:#475569;--text3:#94a3b8;
  --accent:#6366f1;
}
body{background:var(--bg);color:var(--text);font:14px/1.5 system-ui,sans-serif;display:flex;min-height:100vh}

/* Sidebar */
.sidebar{width:200px;background:var(--bg2);border-right:1px solid var(--border);flex-shrink:0;display:flex;flex-direction:column;padding:20px 12px}
.logo{font-size:15px;font-weight:600;color:var(--text);padding:8px 10px 24px}
.logo span{color:var(--accent)}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;cursor:pointer;color:var(--text3);font-size:13px;transition:all .15s;border:none;background:none;width:100%;text-align:left}
.nav-item:hover{background:var(--border);color:var(--text)}
.nav-item.active{background:var(--border);color:var(--accent)}
.nav-icon{font-size:15px;width:18px;text-align:center}
.sidebar-bottom{margin-top:auto}
.logout{color:#ef4444!important}
.logout:hover{background:rgba(239,68,68,.1)!important}

/* Main */
.main{flex:1;display:flex;flex-direction:column;min-width:0}
.topbar{background:var(--bg2);border-bottom:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;justify-content:space-between}
.topbar-title{font-size:15px;font-weight:600}
.topbar-right{display:flex;align-items:center;gap:12px}
.status-dot{width:8px;height:8px;background:#22c55e;border-radius:50%;display:inline-block}
.status-text{color:var(--text3);font-size:12px}
.refresh-btn{background:var(--border);border:1px solid var(--border2);color:var(--text2);padding:6px 14px;border-radius:8px;cursor:pointer;font-size:12px;transition:all .15s}
.refresh-btn:hover{border-color:var(--accent);color:var(--accent)}
/* 主题切换按钮 */
.theme-btn{background:var(--border);border:1px solid var(--border2);color:var(--text2);padding:6px 12px;border-radius:8px;cursor:pointer;font-size:12px;transition:all .15s;white-space:nowrap}
.theme-btn:hover{border-color:var(--accent);color:var(--accent)}

/* Content */
.content{padding:24px;flex:1;overflow:auto}
.tab-panel{display:none}
.tab-panel.active{display:block}

/* Cards */
.card{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:16px}
.card-title{font-size:13px;font-weight:600;color:var(--text2);margin-bottom:14px;text-transform:uppercase;letter-spacing:.5px}

/* Log panel */
.log-controls{display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap}
.log-filter{background:var(--bg-input);border:1px solid var(--border2);color:var(--text);padding:7px 12px;border-radius:7px;font-size:12px;outline:none;width:160px}
.log-filter:focus{border-color:var(--accent)}
.log-mode-btns{display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap;align-items:center}
.mode-btn{background:var(--border);border:1px solid var(--border2);color:var(--text2);padding:5px 14px;border-radius:7px;cursor:pointer;font-size:12px;transition:all .15s}
.mode-btn:hover{border-color:var(--accent);color:var(--accent)}
.mode-btn.active{background:var(--accent);border-color:var(--accent);color:#fff}
.mode-btn.danger{border-color:rgba(239,68,68,.3);color:#ef4444}
.mode-btn.danger:hover{background:rgba(239,68,68,.15)}
.mode-btn.import-btn{border-color:rgba(99,102,241,.3);color:var(--accent)}
.mode-btn.import-btn:hover{background:rgba(99,102,241,.15)}
.radio-group{display:flex;align-items:center;gap:14px;margin-left:auto}
.radio-group label{display:flex;align-items:center;gap:5px;color:var(--text2);font-size:12px;cursor:pointer;white-space:nowrap}
.radio-group input[type=radio]{accent-color:var(--accent)}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:500}
.badge-200{background:rgba(34,197,94,.12);color:#22c55e}
.badge-403{background:rgba(239,68,68,.12);color:#ef4444}
.badge-429{background:rgba(234,179,8,.12);color:#eab308}
.badge-444{background:rgba(100,116,139,.12);color:#64748b}
.badge-other{background:rgba(99,102,241,.12);color:#6366f1}
.log-table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:12px}
th{text-align:left;padding:8px 10px;color:var(--text3);border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg3);white-space:nowrap;z-index:1}
td{padding:7px 10px;border-bottom:1px solid var(--bg);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(99,102,241,.04)}
.ip-cell{font-family:monospace;font-size:11px;white-space:nowrap}
.ua-cell{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text3);font-size:11px}
.req-cell{max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;color:var(--text2)}
.token-cell{font-family:monospace;font-size:11px;color:#818cf8;display:flex;align-items:center;gap:6px;min-width:0}
.token-text{word-break:break-all;flex:1}
.auto-timer{color:var(--text3);font-size:11px}
.copy-btn{background:none;border:1px solid var(--border2);color:var(--text3);padding:1px 6px;border-radius:4px;cursor:pointer;font-size:10px;flex-shrink:0;transition:all .15s}
.copy-btn:hover{border-color:var(--accent);color:var(--accent)}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
.top-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #13161f}
.top-row:last-child{border-bottom:none}
.top-rank{color:#64748b;font-size:11px;width:18px}
.top-val{font-family:monospace;font-size:12px;flex:1;padding:0 10px;word-break:break-all}
.top-count{color:#6366f1;font-size:12px;font-weight:600;white-space:nowrap}
.top-sub{color:#64748b;font-size:11px}
.add-btn-sm{background:#6366f1;color:#fff;border:none;padding:3px 10px;border-radius:5px;cursor:pointer;font-size:11px;margin-left:8px;transition:opacity .15s;flex-shrink:0}
.add-btn-sm:hover{opacity:.8}

/* Whitelist / Blacklist / UA */
.ip-form{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.ip-input{background:var(--bg-input);border:1px solid var(--border2);color:var(--text);padding:9px 12px;border-radius:8px;font-size:13px;font-family:monospace;outline:none;flex:1;min-width:160px}
.ip-input:focus{border-color:var(--accent)}
.comment-input{background:var(--bg-input);border:1px solid var(--border2);color:var(--text);padding:9px 12px;border-radius:8px;font-size:13px;outline:none;flex:2;min-width:140px}
.comment-input:focus{border-color:var(--accent)}
.btn-primary{background:var(--accent);color:#fff;border:none;padding:9px 18px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;transition:opacity .15s;white-space:nowrap}
.btn-primary:hover{opacity:.85}
.btn-danger{background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.2);padding:5px 12px;border-radius:6px;cursor:pointer;font-size:12px;transition:all .15s}
.btn-danger:hover{background:rgba(239,68,68,.25)}
.btn-apply{background:rgba(34,197,94,.12);color:#22c55e;border:1px solid rgba(34,197,94,.2);padding:7px 16px;border-radius:8px;cursor:pointer;font-size:13px;transition:all .15s}
.btn-apply:hover{background:rgba(34,197,94,.2)}
.apply-row{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.apply-hint{color:var(--text3);font-size:12px}

/* Toast */
#toast{position:fixed;bottom:28px;right:28px;background:var(--bg3);border:1px solid var(--border2);padding:12px 20px;border-radius:10px;font-size:13px;z-index:999;opacity:0;transform:translateY(10px);transition:all .25s;pointer-events:none}
#toast.show{opacity:1;transform:none}
#toast.ok{border-color:#22c55e;color:#22c55e}
#toast.err{border-color:#ef4444;color:#ef4444}

.empty{color:var(--text3);font-size:13px;padding:20px 0}
.loading{color:var(--text3);font-size:13px}

/* 黑名单标签按钮 */
.bl-badge-btn{background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);padding:2px 7px;border-radius:5px;cursor:pointer;font-size:10px;transition:all .15s;flex-shrink:0}
.bl-badge-btn:hover{background:rgba(239,68,68,.3)}
/* 请求/UA 单元格（带复制按钮） */
.req-cell-wrap{display:flex;align-items:center;gap:4px;max-width:260px}
.ua-cell-wrap{display:flex;align-items:center;gap:4px;max-width:220px}
/* 分页控件 */
.page-controls{display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap}
/* 批量操作行 */
.batch-row{display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap}
.batch-row label{color:var(--text2);font-size:12px;display:flex;align-items:center;gap:5px;cursor:pointer}
/* IDC 汇总区域 */
.idc-section{margin-top:20px;padding-top:16px;border-top:1px solid var(--border)}
.idc-section .card-title{margin-bottom:10px}
</style>
</head>
<body>

<nav class="sidebar">
  <div class="logo"><?= htmlspecialchars(SITE_TITLE, ENT_QUOTES) ?></div>
  <button class="nav-item active" onclick="switchTab('logs',this)">
    <span class="nav-icon">📋</span>日志
  </button>
  <button class="nav-item" onclick="switchTab('stats',this)">
    <span class="nav-icon">📊</span>分析
  </button>
  <button class="nav-item" onclick="switchTab('ua_blacklist',this)">
    <span class="nav-icon">🛡</span>UA
  </button>
  <button class="nav-item" onclick="switchTab('whitelist',this)">
    <span class="nav-icon">✅</span>白名单
  </button>
  <button class="nav-item" onclick="switchTab('blacklist',this)">
    <span class="nav-icon">🚫</span>黑名单
  </button>
  <button class="nav-item" onclick="switchTab('settings',this)">
    <span class="nav-icon">⚙</span>系统设置
  </button>
  <div class="sidebar-bottom">
    <a href="<?= ADMIN_SECRET_PATH !== '' ? '/' . ADMIN_SECRET_PATH . '/logout' : '/logout' ?>" style="text-decoration:none">
      <button class="nav-item logout"><span class="nav-icon">↩</span>退出</button>
    </a>
  </div>
</nav>

<div class="main">
  <div class="topbar">
    <div class="topbar-title" id="tab-title">日志</div>
    <div class="topbar-right">
      <span class="status-dot"></span>
      <span class="status-text">运行中</span>
      <span class="status-text auto-timer" id="auto-timer"></span>
      <button class="theme-btn" id="theme-btn" onclick="cycleTheme()" title="切换主题">🌙 深色</button>
      <button class="refresh-btn" onclick="manualRefresh()">手动刷新</button>
    </div>
  </div>

  <div class="content">

    <!-- ─── 日志 ─────────────────────────────────────────── -->
    <div class="tab-panel active" id="panel-logs">
      <div class="card">
        <!-- 日志模式切换 -->
        <div class="log-mode-btns">
          <button class="mode-btn active" id="btn-today" onclick="setLogMode('today')">仅显示今日日志</button>
          <button class="mode-btn" id="btn-all" onclick="setLogMode('all')">显示全部日志</button>
          <button class="mode-btn danger" onclick="deleteLogs()">删除7日前的日志</button>
          <button class="mode-btn import-btn" onclick="document.getElementById('log-import-file').click()">导入日志</button>
          <button class="mode-btn import-btn" onclick="exportLogs()">导出日志</button>
          <input type="file" id="log-import-file" accept=".log,.txt" style="display:none" onchange="importLogs(this)">
        </div>
        <!-- 过滤器 -->
        <div class="log-controls">
          <input class="log-filter" id="filter-ip" placeholder="过滤 IP" oninput="logPage=1;renderLogs()">
          <input class="log-filter" id="filter-status" placeholder="状态码 如 403" oninput="logPage=1;renderLogs()">
          <input class="log-filter" id="filter-token" placeholder="过滤 Token（自动去重）" oninput="logPage=1;renderLogs()">
          <span class="auto-timer" id="log-count">—</span>
          <div class="radio-group">
            <label><input type="radio" name="sub-filter" value="subscribe" checked onchange="logPage=1;renderLogs()"> 仅订阅相关</label>
            <label><input type="radio" name="sub-filter" value="all" onchange="logPage=1;renderLogs()"> 显示全部</label>
          </div>
          <div style="display:flex;gap:4px;margin-left:8px">
            <button class="mode-btn" id="limit-btn-50"  onclick="setLogLimit(50)">50条</button>
            <button class="mode-btn active" id="limit-btn-100" onclick="setLogLimit(100)">100条</button>
            <button class="mode-btn" id="limit-btn-500" onclick="setLogLimit(500)">500条</button>
            <button class="mode-btn" id="limit-btn-inf" onclick="setLogLimit(0)">瀑布流</button>
          </div>
        </div>
        <div class="log-table-wrap">
          <table>
            <thead>
              <tr>
                <th>时间</th><th>IP</th><th>状态</th><th>Token</th>
                <th>请求</th><th>UA</th><th>操作</th>
              </tr>
            </thead>
            <tbody id="log-tbody"><tr><td colspan="7" class="loading">加载中…</td></tr></tbody>
          </table>
        </div>
        <!-- 分页控件（瀑布流模式下隐藏） -->
        <div id="log-pagination" class="page-controls" style="display:none;margin-top:10px;align-items:center;gap:8px;flex-wrap:wrap">
          <button class="mode-btn" id="page-prev" onclick="changePage(-1)">上一页</button>
          <span id="page-info" style="color:var(--text2);font-size:12px;white-space:nowrap"></span>
          <button class="mode-btn" id="page-next" onclick="changePage(1)">下一页</button>
          <span style="color:var(--text3);font-size:12px;margin-left:8px">跳至</span>
          <input id="page-jump" type="number" min="1" style="width:52px;background:var(--bg-input);border:1px solid var(--border2);color:var(--text);padding:4px 6px;border-radius:6px;font-size:12px;outline:none" onkeydown="if(event.key==='Enter')jumpPage()">
          <button class="mode-btn" onclick="jumpPage()">页</button>
        </div>
      </div><!-- .card -->
    </div><!-- .tab-panel #panel-logs -->

    <!-- ─── 分析 ─────────────────────────────────────────── -->
    <div class="tab-panel" id="panel-stats">
      <div class="stats-grid">
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <div class="card-title" style="margin-bottom:0">今日 Top IP</div>
            <div style="display:flex;gap:4px">
              <button class="mode-btn active" id="stats-ips-10" onclick="setStatsLimit('ips',10)">10</button>
              <button class="mode-btn" id="stats-ips-25" onclick="setStatsLimit('ips',25)">25</button>
              <button class="mode-btn" id="stats-ips-50" onclick="setStatsLimit('ips',50)">50</button>
              <button class="mode-btn" id="stats-ips-0" onclick="setStatsLimit('ips',0)">全部</button>
            </div>
          </div>
          <div style="font-size:10px;color:var(--text3);margin-bottom:10px">
            右侧计数：<span style="color:#22c55e">●</span>成功 / <span style="color:#ef4444">●</span>403拦截 / <span style="color:#eab308">●</span>限速429 / <span style="color:#64748b">●</span>黑名单444
          </div>
          <div id="top-ips"><div class="loading">加载中…</div></div>
          <div id="stats-ips-pg" class="page-controls" style="display:none;margin-top:10px"></div>
        </div>
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <div class="card-title" style="margin-bottom:0">今日 Top Token</div>
            <div style="display:flex;gap:4px">
              <button class="mode-btn active" id="stats-tokens-10" onclick="setStatsLimit('tokens',10)">10</button>
              <button class="mode-btn" id="stats-tokens-25" onclick="setStatsLimit('tokens',25)">25</button>
              <button class="mode-btn" id="stats-tokens-50" onclick="setStatsLimit('tokens',50)">50</button>
              <button class="mode-btn" id="stats-tokens-0" onclick="setStatsLimit('tokens',0)">全部</button>
            </div>
          </div>
          <div id="top-tokens"><div class="loading">加载中…</div></div>
          <div id="stats-tokens-pg" class="page-controls" style="display:none;margin-top:10px"></div>
        </div>
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <div class="card-title" style="margin-bottom:0">可疑 Token（被多IP拉取）</div>
            <div style="display:flex;gap:4px">
              <button class="mode-btn active" id="stats-suspTokens-10" onclick="setStatsLimit('suspTokens',10)">10</button>
              <button class="mode-btn" id="stats-suspTokens-25" onclick="setStatsLimit('suspTokens',25)">25</button>
              <button class="mode-btn" id="stats-suspTokens-50" onclick="setStatsLimit('suspTokens',50)">50</button>
              <button class="mode-btn" id="stats-suspTokens-0" onclick="setStatsLimit('suspTokens',0)">全部</button>
            </div>
          </div>
          <div id="susp-tokens"><div class="loading">加载中…</div></div>
          <div id="stats-suspTokens-pg" class="page-controls" style="display:none;margin-top:10px"></div>
        </div>
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <div class="card-title" style="margin-bottom:0">可疑 IP（拉取多Token）</div>
            <div style="display:flex;gap:4px">
              <button class="mode-btn active" id="stats-suspIps-10" onclick="setStatsLimit('suspIps',10)">10</button>
              <button class="mode-btn" id="stats-suspIps-25" onclick="setStatsLimit('suspIps',25)">25</button>
              <button class="mode-btn" id="stats-suspIps-50" onclick="setStatsLimit('suspIps',50)">50</button>
              <button class="mode-btn" id="stats-suspIps-0" onclick="setStatsLimit('suspIps',0)">全部</button>
            </div>
          </div>
          <div id="susp-ips"><div class="loading">加载中…</div></div>
          <div id="stats-suspIps-pg" class="page-controls" style="display:none;margin-top:10px"></div>
        </div>
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <div class="card-title" style="margin-bottom:0">UA TOP</div>
            <div style="display:flex;gap:4px">
              <button class="mode-btn active" id="stats-uas-10" onclick="setStatsLimit('uas',10)">10</button>
              <button class="mode-btn" id="stats-uas-25" onclick="setStatsLimit('uas',25)">25</button>
              <button class="mode-btn" id="stats-uas-50" onclick="setStatsLimit('uas',50)">50</button>
              <button class="mode-btn" id="stats-uas-0" onclick="setStatsLimit('uas',0)">全部</button>
            </div>
          </div>
          <div id="bad-uas"><div class="loading">加载中…</div></div>
          <div id="stats-uas-pg" class="page-controls" style="display:none;margin-top:10px"></div>
        </div>
      </div>
    </div>

    <!-- ─── UA ─────────────────────────────────────────── -->
    <div class="tab-panel" id="panel-ua_blacklist">
      <div class="card">
        <div class="card-title">添加封禁 UA</div>
        <div class="ip-form">
          <input class="ip-input" id="ua-keyword" placeholder="UA 关键词（如 python-requests、clash）">
          <input class="comment-input" id="ua-comment" placeholder="备注（可选）">
          <button class="btn-primary" onclick="uaAdd()">添加并立即生效</button>
        </div>
        <div class="apply-hint" style="margin-bottom:14px;color:#eab308">
          ⚡ 封禁 UA 后立即 reload nginx 生效，大小写不敏感，支持关键词匹配
        </div>
        <div style="display:flex;align-items:center;gap:4px;margin-bottom:10px">
          <span style="color:var(--text3);font-size:12px">显示：</span>
          <button class="mode-btn active" id="ua-bl-limit-50" onclick="setUaBlLimit(50)">50条</button>
          <button class="mode-btn" id="ua-bl-limit-100" onclick="setUaBlLimit(100)">100条</button>
          <button class="mode-btn" id="ua-bl-limit-500" onclick="setUaBlLimit(500)">500条</button>
          <button class="mode-btn" id="ua-bl-limit-0" onclick="setUaBlLimit(0)">全部</button>
        </div>
        <div id="ua-list"><div class="loading">加载中…</div></div>
      </div>
      <div class="card" style="margin-top:16px">
        <div class="card-title">UA 白名单</div>
        <div class="apply-hint" style="margin-bottom:14px;color:#22c55e">
          ✅ 白名单UA不受封禁UA规则影响，可保护自己的客户端UA不被误封
        </div>
        <div class="ip-form">
          <input class="ip-input" id="ua-wl-keyword" placeholder="UA 关键词（如 Surge、Clash.Meta）">
          <input class="comment-input" id="ua-wl-comment" placeholder="备注（可选）">
          <button class="btn-primary" onclick="uaWlAdd()">添加并立即生效</button>
        </div>
        <div style="display:flex;align-items:center;gap:4px;margin-bottom:10px">
          <span style="color:var(--text3);font-size:12px">显示：</span>
          <button class="mode-btn active" id="ua-wl-limit-50" onclick="setUaWlLimit(50)">50条</button>
          <button class="mode-btn" id="ua-wl-limit-100" onclick="setUaWlLimit(100)">100条</button>
          <button class="mode-btn" id="ua-wl-limit-500" onclick="setUaWlLimit(500)">500条</button>
          <button class="mode-btn" id="ua-wl-limit-0" onclick="setUaWlLimit(0)">全部</button>
        </div>
        <div id="ua-wl-list"><div class="loading">加载中…</div></div>
      </div>
    </div>

    <!-- ─── 白名单 ─────────────────────────────────────────── -->
    <div class="tab-panel" id="panel-whitelist">
      <div class="card">
        <div class="card-title">添加白名单 IP</div>
        <div class="ip-form">
          <input class="ip-input" id="wl-ip" placeholder="支持批量，逗号分隔：1.1.1.1,2.2.2.0/24">
          <input class="comment-input" id="wl-comment" placeholder="备注（可选）">
          <button class="btn-primary" onclick="wlAdd()">添加</button>
        </div>
        <div class="apply-row">
          <button class="btn-apply" onclick="wlApply()">▶ 生效（reload nginx）</button>
          <span class="apply-hint">添加后需点击"生效"才会应用到拦截规则</span>
          <button class="mode-btn import-btn" onclick="exportWhitelist()" style="margin-left:auto">导出</button>
          <button class="mode-btn import-btn" onclick="document.getElementById('wl-import-file').click()">导入</button>
          <input type="file" id="wl-import-file" accept=".txt,.conf" style="display:none" onchange="importWhitelist(this)">
        </div>
        <div id="wl-list"><div class="loading">加载中…</div></div>
      </div>
    </div>

    <!-- ─── 黑名单 ─────────────────────────────────────────── -->
    <div class="tab-panel" id="panel-blacklist">
      <div class="card">
        <div class="card-title">添加黑名单 IP</div>
        <div class="ip-form">
          <input class="ip-input" id="bl-ip" placeholder="1.2.3.4 或 1.2.3.0/24">
          <input class="comment-input" id="bl-comment" placeholder="备注（可选）">
          <button class="btn-primary" onclick="blAdd()">添加并立即生效</button>
          <button class="mode-btn import-btn" onclick="exportBlacklist()">导出</button>
          <button class="mode-btn import-btn" onclick="document.getElementById('bl-import-file').click()">导入</button>
          <input type="file" id="bl-import-file" accept=".txt,.conf" style="display:none" onchange="importBlacklist(this)">
        </div>
        <div class="apply-hint" style="margin-bottom:14px;color:#eab308">
          ⚡ 黑名单添加后立即 reload nginx 生效，无需额外操作。导入支持 IP/CIDR 格式（每行一条），自动去重。
        </div>
        <div id="bl-list"><div class="loading">加载中…</div></div>
      </div>
    </div>

    <!-- ─── 系统设置 ───────────────────────────────────────── -->
    <div class="tab-panel" id="panel-settings">
      <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr))">

        <!-- 界面设置 -->
        <div class="card">
          <div class="card-title">界面设置</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div>
              <label style="display:block;color:var(--text2);font-size:12px;margin-bottom:5px">网站标题（左上角 Logo）</label>
              <input class="ip-input" id="cfg-site-title" placeholder="SubSieve" style="width:100%">
            </div>
            <div>
              <label style="display:block;color:var(--text2);font-size:12px;margin-bottom:5px">网页标题（浏览器 Tab）</label>
              <input class="ip-input" id="cfg-page-title" placeholder="SubSieve Admin" style="width:100%">
            </div>
            <button class="btn-primary" onclick="saveTitleSettings()">保存标题设置</button>
          </div>
        </div>

        <!-- 管理员凭证 -->
        <div class="card">
          <div class="card-title">登录凭证</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div>
              <label style="display:block;color:var(--text2);font-size:12px;margin-bottom:5px">用户名</label>
              <input class="ip-input" id="cfg-admin-user" placeholder="admin" style="width:100%">
            </div>
            <div>
              <label style="display:block;color:var(--text2);font-size:12px;margin-bottom:5px">新密码</label>
              <input class="ip-input" id="cfg-new-pass" type="password" placeholder="留空则不修改" style="width:100%">
            </div>
            <div>
              <label style="display:block;color:var(--text2);font-size:12px;margin-bottom:5px">确认新密码</label>
              <input class="ip-input" id="cfg-confirm-pass" type="password" placeholder="再次输入新密码" style="width:100%">
            </div>
            <div class="apply-hint" style="color:#eab308">⚠️ 修改后需重新登录，请牢记新密码</div>
            <button class="btn-primary" onclick="saveCredSettings()">保存凭证设置</button>
          </div>
        </div>

        <!-- 机场（上游）配置 -->
        <div class="card">
          <div class="card-title">机场（反代目标）</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div>
              <label style="display:block;color:var(--text2);font-size:12px;margin-bottom:5px">机场地址（如 https://panel.yourdomain.com）</label>
              <input class="ip-input" id="cfg-upstream-url" placeholder="https://panel.yourdomain.com" style="width:100%">
            </div>
            <div>
              <label style="display:block;color:var(--text2);font-size:12px;margin-bottom:5px">订阅路径</label>
              <input class="ip-input" id="cfg-subscribe-path" placeholder="/api/v1/client/subscribe" style="width:100%">
            </div>
            <div class="apply-hint" style="color:#eab308">⚡ 保存后立即更新 nginx 配置并 reload</div>
            <button class="btn-primary" onclick="saveUpstreamSettings()">保存并立即生效</button>
          </div>
        </div>

        <!-- SSL 证书信息 -->
        <div class="card">
          <div class="card-title">SSL 证书</div>
          <div id="cert-info"><div class="loading">加载中…</div></div>
          <div class="apply-hint" style="margin-top:12px;color:var(--text3)">
            证书文件位置：<code style="font-size:11px;background:var(--bg);padding:2px 5px;border-radius:3px">/etc/nginx/ssl/cert.pem</code><br>
            如需更换证书，请替换宿主机 <code style="font-size:11px;background:var(--bg);padding:2px 5px;border-radius:3px">sgw/ssl/</code> 目录下的文件后重启容器
          </div>
        </div>

        <!-- 部署信息 -->
        <div class="card">
          <div class="card-title">部署信息 (DEPLOY_INFO.txt)</div>
          <pre id="deploy-info-content" style="font-size:11px;color:var(--text2);background:var(--bg);padding:12px;border-radius:6px;overflow-x:auto;white-space:pre-wrap;min-height:80px">加载中…</pre>
          <div style="display:flex;gap:8px;margin-top:10px">
            <button class="mode-btn import-btn" onclick="syncDeployInfo()">同步更新</button>
            <button class="mode-btn import-btn" onclick="downloadDeployInfo()">下载</button>
          </div>
        </div>

      </div>
    </div>

  </div><!-- .content -->
</div><!-- .main -->

<div id="toast"></div>

<script>
// ── 状态 ─────────────────────────────────────────────────────
const BASE = <?= json_encode(ADMIN_SECRET_PATH !== '' ? '/' . ADMIN_SECRET_PATH : '') ?>;
let allLogs = [];
let logMode = 'today';   // 'today' | 'all'
let logLimit = 100;      // 0=瀑布流（无限制）
let logPage = 1;         // 当前页（分页模式）
let blacklistIpSet = new Set();
let cloudCidrs = [];     // 云服务商CIDR列表，用于检测云IP
let allStatsData = null; // 完整统计数据缓存
let statsLimits = {ips: 10, tokens: 10, uas: 10, suspTokens: 10, suspIps: 10};
let statsPages  = {ips:  1, tokens:  1, uas:  1, suspTokens:  1, suspIps:  1};
let allBlEntries = [];   // 黑名单完整数据缓存
let allWlEntries = [];   // 白名单完整数据缓存
let uaBlLimit = 50;      // UA封禁列表显示数量
let uaWlLimit = 50;      // UA白名单显示数量
let allUaBlEntries = []; // UA封禁列表完整数据缓存
let allUaWlEntries = []; // UA白名单完整数据缓存
let autoTimer, countdown = 300;

// ── 主题 ──────────────────────────────────────────────────────
const THEMES = ['dark','light','auto'];
const THEME_LABELS = {dark:'🌙 深色', light:'☀️ 浅色', auto:'💻 跟随系统'};
let themeMode = localStorage.getItem('theme') || 'dark';

function applyTheme() {
  const html = document.documentElement;
  if (themeMode === 'auto') {
    const sys = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    html.setAttribute('data-theme', sys);
  } else {
    html.setAttribute('data-theme', themeMode);
  }
  const btn = document.getElementById('theme-btn');
  if (btn) btn.textContent = THEME_LABELS[themeMode];
}

function cycleTheme() {
  const idx = THEMES.indexOf(themeMode);
  themeMode = THEMES[(idx + 1) % THEMES.length];
  localStorage.setItem('theme', themeMode);
  applyTheme();
}

// 系统主题变化时自动更新
window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => {
  if (themeMode === 'auto') applyTheme();
});
applyTheme();
const TABS = {
  logs:         {title:'日志',   loader:loadLogs},
  stats:        {title:'分析',   loader:loadStats},
  ua_blacklist: {title:'UA',     loader:loadUaBlacklist},
  whitelist:    {title:'白名单', loader:loadWhitelist},
  blacklist:    {title:'黑名单', loader:loadBlacklist},
  settings:     {title:'系统设置', loader:loadSettings},
};
let currentTab = 'logs';

// ── Tab 切换 ──────────────────────────────────────────────────
function switchTab(name, el) {
  currentTab = name;
  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + name).classList.add('active');
  document.getElementById('tab-title').textContent = TABS[name].title;
  resetCountdown();
  TABS[name].loader();
}

// ── 自动刷新倒计时 ─────────────────────────────────────────────
function resetCountdown() {
  clearInterval(autoTimer);
  countdown = 300;
  updateTimerLabel();
  autoTimer = setInterval(() => {
    countdown--;
    updateTimerLabel();
    if (countdown <= 0) {
      resetCountdown();
      TABS[currentTab].loader();
    }
  }, 1000);
}

function updateTimerLabel() {
  const m = String(Math.floor(countdown/60)).padStart(2,'0');
  const s = String(countdown % 60).padStart(2,'0');
  document.getElementById('auto-timer').textContent = `自动刷新 ${m}:${s}`;
}

function manualRefresh() {
  resetCountdown();
  TABS[currentTab].loader();
}

// ── 工具 ──────────────────────────────────────────────────────
async function apiFetch(url, opts={}) {
  try {
    const r = await fetch(BASE + url, {headers:{'X-Requested-With':'XMLHttpRequest'}, ...opts});
    const ct = r.headers.get('Content-Type') || '';
    if (!ct.includes('application/json')) {
      return {ok: false, error: '服务器内部错误，请检查日志'};
    }
    const json = await r.json();
    return json;
  } catch(e) {
    return {ok: false, error: '网络请求失败'};
  }
}

function toast(msg, type='ok') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = 'show ' + type;
  setTimeout(() => el.className = '', 2500);
}

function statusBadge(code) {
  const cls = code == 200 ? 'badge-200' : code == 403 ? 'badge-403' :
              code == 429 ? 'badge-429' : code == 444 ? 'badge-444' : 'badge-other';
  return `<span class="badge ${cls}">${code}</span>`;
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function copyText(text) {
  navigator.clipboard.writeText(text)
    .then(() => toast('已复制'))
    .catch(() => toast('复制失败，请手动复制','err'));
}

// ── 日志模式切换 ───────────────────────────────────────────────
function setLogMode(mode) {
  logMode = mode;
  document.getElementById('btn-today').classList.toggle('active', mode === 'today');
  document.getElementById('btn-all').classList.toggle('active', mode === 'all');
  loadLogs();
}

// ── 日志显示数量切换 ────────────────────────────────────────────
function setLogLimit(n) {
  logLimit = n;
  logPage = 1;
  ['50','100','500','inf'].forEach(k => {
    const btn = document.getElementById('limit-btn-' + k);
    if (btn) btn.classList.remove('active');
  });
  const key = n === 0 ? 'inf' : String(n);
  const btn = document.getElementById('limit-btn-' + key);
  if (btn) btn.classList.add('active');
  renderLogs();
}

// ── 分页控制 ──────────────────────────────────────────────────
function changePage(delta) {
  logPage += delta;
  renderLogs();
}

function jumpPage() {
  const v = parseInt(document.getElementById('page-jump').value);
  if (!isNaN(v) && v >= 1) { logPage = v; renderLogs(); }
}


// ── 云IP检测辅助函数 ───────────────────────────────────────────
function ipToInt(ip) {
  const parts = ip.split('.');
  if (parts.length !== 4) return null;
  return parts.reduce((acc, p) => (acc * 256 + parseInt(p, 10)), 0);
}

function ipInCidr(ipInt, cidr) {
  const slash = cidr.indexOf('/');
  const base = slash >= 0 ? cidr.slice(0, slash) : cidr;
  const bits = slash >= 0 ? parseInt(cidr.slice(slash + 1)) : 32;
  const baseParts = base.split('.');
  if (baseParts.length !== 4) return false;
  const baseInt = baseParts.reduce((acc, p) => (acc * 256 + parseInt(p, 10)), 0);
  const mask = bits === 0 ? 0 : (0xFFFFFFFF << (32 - bits)) >>> 0;
  return ((ipInt >>> 0) & mask) === ((baseInt >>> 0) & mask);
}

function isCloudIp(ip) {
  const ipInt = ipToInt(ip);
  if (ipInt === null) return false;
  return cloudCidrs.some(cidr => ipInCidr(ipInt, cidr));
}

// ── 日志 ──────────────────────────────────────────────────────
async function loadLogs() {
  document.getElementById('log-tbody').innerHTML = '<tr><td colspan="7" class="loading">加载中…</td></tr>';
  const [logsData, blData, cloudData] = await Promise.all([
    apiFetch('/api/logs.php?mode=' + logMode),
    apiFetch('/api/blacklist.php?no_idc=1'),
    apiFetch('/api/blacklist.php?cloud_cidrs=1'),
  ]);
  blacklistIpSet = new Set((blData.entries || []).map(e => e.ip));
  cloudCidrs = cloudData.cidrs || [];
  if (!logsData.ok) {
    document.getElementById('log-tbody').innerHTML = '<tr><td colspan="7" class="empty">加载失败：' + esc(logsData.error||'未知错误') + '</td></tr>';
    toast('加载日志失败: ' + (logsData.error||''), 'err'); return;
  }
  allLogs = logsData.logs || [];
  renderLogs();
}

function renderLogs() {
  const fIp     = document.getElementById('filter-ip').value.trim().toLowerCase();
  const fStatus = document.getElementById('filter-status').value.trim();
  const fToken  = document.getElementById('filter-token').value.trim().toLowerCase();
  const subOnly = document.querySelector('input[name="sub-filter"][value="subscribe"]').checked;

  let rows = allLogs.filter(l => {
    if (subOnly && !l.request.includes('/api/v1/client/subscribe')) return false;
    if (fIp     && !l.ip.toLowerCase().includes(fIp)) return false;
    if (fStatus && String(l.status) !== fStatus) return false;
    if (fToken  && !l.token.toLowerCase().includes(fToken)) return false;
    return true;
  });

  // 最新的在最上面
  rows = rows.slice().reverse();

  // Token过滤时按IP去重（每个IP只保留最新一条）
  if (fToken) {
    const seen = new Set();
    rows = rows.filter(l => {
      if (seen.has(l.ip)) return false;
      seen.add(l.ip);
      return true;
    });
  }

  const total = rows.length;

  // ── 分页 ──────────────────────────────────────────────────────
  const pg = document.getElementById('log-pagination');
  if (logLimit > 0 && total > 0) {
    const totalPages = Math.ceil(total / logLimit);
    logPage = Math.max(1, Math.min(logPage, totalPages));
    const start = (logPage - 1) * logLimit;
    const displayRows = rows.slice(start, start + logLimit);
    document.getElementById('log-count').textContent =
      `${total} 条（第${logPage}/${totalPages}页，每页${logLimit}条）`;
    document.getElementById('page-info').textContent =
      `第 ${logPage} / ${totalPages} 页`;
    document.getElementById('page-prev').disabled = logPage <= 1;
    document.getElementById('page-next').disabled = logPage >= totalPages;
    pg.style.display = 'flex';

    if (!displayRows.length) {
      document.getElementById('log-tbody').innerHTML =
        '<tr><td colspan="7" class="empty">暂无匹配记录</td></tr>';
      return;
    }
    renderLogRows(displayRows);
  } else {
    // 瀑布流：显示全部，隐藏分页
    pg.style.display = 'none';
    document.getElementById('log-count').textContent = `${total} / ${allLogs.length} 条`;
    if (!total) {
      document.getElementById('log-tbody').innerHTML =
        '<tr><td colspan="7" class="empty">暂无匹配记录</td></tr>';
      return;
    }
    renderLogRows(rows);
  }
}

function renderLogRows(rows) {
  document.getElementById('log-tbody').innerHTML = rows.map(l => {
    const isBlacklisted = blacklistIpSet.has(l.ip);
    const isCloud = !isBlacklisted && isCloudIp(l.ip);
    const ipBtn = isBlacklisted
      ? `<button class="bl-badge-btn" onclick="quickWhitelist('${esc(l.ip)}')">黑名单</button>`
      : isCloud
        ? `<span class="bl-badge-btn" style="cursor:default;background:rgba(234,179,8,.15);color:#eab308;border-color:rgba(234,179,8,.3)">黑名单</span>`
        : `<button class="add-btn-sm" onclick="quickBlacklist('${esc(l.ip)}')">封</button>`;
    const tokenHtml = l.token
      ? `<div style="display:inline-flex;align-items:center;gap:3px;font-family:monospace;font-size:11px;color:#818cf8"><span title="${esc(l.token)}">${esc(l.token)}</span><button class="copy-btn" data-val="${esc(l.token)}" onclick="copyText(this.dataset.val)">复制</button></div>`
      : '—';
    return `
    <tr>
      <td style="white-space:nowrap;color:#64748b;font-size:11px">${esc(l.time)}</td>
      <td class="ip-cell"><div style="display:inline-flex;align-items:center;gap:4px;flex-wrap:nowrap"><span>${esc(l.ip)}</span><button class="copy-btn" data-val="${esc(l.ip)}" onclick="copyText(this.dataset.val)">复制</button><span style="display:inline-block;width:2px"></span>${ipBtn}</div></td>
      <td>${statusBadge(l.status)}</td>
      <td style="min-width:100px;max-width:200px">${tokenHtml}</td>
      <td><div class="req-cell-wrap"><span class="req-cell" title="${esc(l.request)}">${esc(l.request)}</span><button class="copy-btn" data-val="${esc(l.request)}" onclick="copyText(this.dataset.val)">复制</button></div></td>
      <td><div class="ua-cell-wrap"><span class="ua-cell" title="${esc(l.ua)}">${esc(l.ua)||'—'}</span>${l.ua ? `<button class="copy-btn" data-val="${esc(l.ua)}" onclick="copyText(this.dataset.val)">复制</button>` : ''}</div></td>
      <td></td>
    </tr>`;
  }).join('');
}

async function deleteLogs() {
  if (!confirm('确定要删除7天前的所有日志行吗？\n此操作不可撤销。')) return;
  const d = await apiFetch('/api/logs.php', {
    method: 'DELETE',
    headers: {'X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) {
    toast(`✅ 已删除 ${d.deleted} 行，保留 ${d.kept} 行`);
    loadLogs();
  } else {
    toast(d.error || '删除失败', 'err');
  }
}

// ── 从日志加入白名单 ───────────────────────────────────────────
async function quickWhitelist(ip) {
  if (!confirm(`是否将 ${ip} 加入白名单？`)) return;
  const d1 = await apiFetch('/api/blacklist.php', {method:'DELETE', body:JSON.stringify({ip}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}});
  if (!d1.ok) { toast(d1.error || '解封失败', 'err'); return; }
  const d2 = await apiFetch('/api/whitelist.php', {method:'POST', body:JSON.stringify({ip, comment:'从日志加入白名单'}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}});
  // 若IP已在白名单则视为成功（目的已达到）
  if (d2.ok || (d2.error && d2.error.includes('已在白名单'))) {
    toast(`✅ ${ip} 已加入白名单`); loadLogs();
  } else {
    toast(d2.error || '加入白名单失败', 'err');
  }
}

// ── 分析 ──────────────────────────────────────────────────────
async function loadStats() {
  const data = await apiFetch('/api/stats.php');
  if (!data.ok) {
    ['top-ips','top-tokens','bad-uas','susp-tokens','susp-ips'].forEach(id => {
      document.getElementById(id).innerHTML = '<div class="empty">加载失败：' + esc(data.error||'未知错误') + '</div>';
    });
    toast('加载统计失败: ' + (data.error||''), 'err'); return;
  }
  allStatsData = data;
  renderStats();
}

function setStatsLimit(key, n) {
  statsLimits[key] = n;
  statsPages[key] = 1;  // 切换每页数量时重置到第1页
  [10, 25, 50, 0].forEach(v => {
    const btn = document.getElementById(`stats-${key}-${v}`);
    if (btn) btn.classList.toggle('active', v === n);
  });
  renderStats();
}

function changeStatsPage(key, delta) {
  statsPages[key] += delta;
  renderStats();
}

function renderStatsPagination(key, total, pageSize) {
  const el = document.getElementById(`stats-${key}-pg`);
  if (!el) return;
  if (pageSize === 0 || total <= pageSize) { el.style.display = 'none'; return; }
  const totalPages = Math.ceil(total / pageSize);
  statsPages[key] = Math.max(1, Math.min(statsPages[key], totalPages));
  const p = statsPages[key];
  el.style.display = 'flex';
  el.innerHTML = `
    <button class="mode-btn" onclick="changeStatsPage('${key}',-1)" ${p<=1?'disabled':''}>上一页</button>
    <span style="color:var(--text2);font-size:12px;white-space:nowrap;padding:0 6px">第 ${p} / ${totalPages} 页（共 ${total} 条）</span>
    <button class="mode-btn" onclick="changeStatsPage('${key}',1)" ${p>=totalPages?'disabled':''}>下一页</button>`;
}

function renderStats() {
  if (!allStatsData) return;
  const data = allStatsData;

  // Top IP
  const allIps = data.top_ips || [];
  const ipsLimit = statsLimits.ips;
  const ipsPage  = statsPages.ips;
  const ips = ipsLimit > 0 ? allIps.slice((ipsPage-1)*ipsLimit, ipsPage*ipsLimit) : allIps;
  const ipsStart = ipsLimit > 0 ? (ipsPage-1)*ipsLimit : 0;
  renderStatsPagination('ips', allIps.length, ipsLimit);
  document.getElementById('top-ips').innerHTML = ips.length ? ips.map((r,i) => `
    <div class="top-row">
      <span class="top-rank">${ipsStart+i+1}</span>
      <span class="top-val">
        ${esc(r.ip)}
        <button class="add-btn-sm" onclick="quickBlacklist('${esc(r.ip)}')">封</button>
      </span>
      <span class="top-count">${r.total}次</span>
      <span class="top-sub" style="margin-left:8px;font-size:11px" title="成功/手动黑名单/限速/连接重置">
        <span style="color:#22c55e">${r.s200}</span>/<span style="color:#ef4444">${r.s403}</span>/<span style="color:#eab308">${r.s429}</span>/<span style="color:#64748b">${r.s444}</span>
      </span>
    </div>`).join('') : '<div class="empty">暂无数据</div>';

  // Top Token
  const allToks = data.top_tokens || [];
  const toksLimit = statsLimits.tokens;
  const toksPage  = statsPages.tokens;
  const toks = toksLimit > 0 ? allToks.slice((toksPage-1)*toksLimit, toksPage*toksLimit) : allToks;
  const toksStart = toksLimit > 0 ? (toksPage-1)*toksLimit : 0;
  renderStatsPagination('tokens', allToks.length, toksLimit);
  document.getElementById('top-tokens').innerHTML = toks.length ? toks.map((r,i) => `
    <div class="top-row">
      <span class="top-rank">${toksStart+i+1}</span>
      <span class="top-val token-cell" style="display:flex;align-items:center;gap:6px">
        <span class="token-text" title="${esc(r.token_full)}">${esc(r.token_full)}</span>
        <button class="copy-btn" data-val="${esc(r.token_full)}" onclick="copyText(this.dataset.val)">复制</button>
      </span>
      <span class="top-count" style="white-space:nowrap;margin-left:6px">${r.count}次</span>
      <span class="top-sub" style="margin-left:8px">${esc(r.last_time)}</span>
    </div>`).join('') : '<div class="empty">暂无数据</div>';

  // UA TOP
  const allUas = data.bad_uas || [];
  const uasLimit = statsLimits.uas;
  const uasPage  = statsPages.uas;
  const uas = uasLimit > 0 ? allUas.slice((uasPage-1)*uasLimit, uasPage*uasLimit) : allUas;
  renderStatsPagination('uas', allUas.length, uasLimit);
  document.getElementById('bad-uas').innerHTML = uas.length ? `
    <table><thead><tr><th>UA</th><th>403次数</th><th>操作</th></tr></thead>
    <tbody>${uas.map(r => `
      <tr>
        <td class="ua-cell" style="max-width:400px" title="${esc(r.ua)}">${esc(r.ua)||'（空UA）'}</td>
        <td style="color:#ef4444;font-weight:600">${r.count}</td>
        <td><button class="add-btn-sm" onclick="quickBanUA('${esc(r.ua)}')">封禁UA</button></td>
      </tr>`).join('')}
    </tbody></table>` : '<div class="empty">今日暂无可疑UA</div>';

  // 可疑 Token
  const allSuspToks = data.susp_tokens || [];
  const suspToksLimit = statsLimits.suspTokens;
  const suspToksPage  = statsPages.suspTokens;
  const suspToks = suspToksLimit > 0 ? allSuspToks.slice((suspToksPage-1)*suspToksLimit, suspToksPage*suspToksLimit) : allSuspToks;
  renderStatsPagination('suspTokens', allSuspToks.length, suspToksLimit);
  document.getElementById('susp-tokens').innerHTML = suspToks.length ? suspToks.map(r => `
    <div class="top-row">
      <span class="top-val token-cell" style="display:flex;align-items:center;gap:6px">
        <span class="token-text" title="${esc(r.token)}">${esc(r.token.substr(0,20))}…</span>
        <button class="copy-btn" data-val="${esc(r.token)}" onclick="copyText(this.dataset.val)">复制</button>
      </span>
      <span class="top-count" style="white-space:nowrap">${r.ip_count} 个不同IP</span>
    </div>`).join('') : '<div class="empty">暂无可疑Token（阈值：3个以上不同IP）</div>';

  // 可疑 IP
  const allSuspIps = data.susp_ips || [];
  const suspIpsLimit = statsLimits.suspIps;
  const suspIpsPage  = statsPages.suspIps;
  const suspIps = suspIpsLimit > 0 ? allSuspIps.slice((suspIpsPage-1)*suspIpsLimit, suspIpsPage*suspIpsLimit) : allSuspIps;
  renderStatsPagination('suspIps', allSuspIps.length, suspIpsLimit);
  document.getElementById('susp-ips').innerHTML = suspIps.length ? suspIps.map(r => `
    <div class="top-row">
      <span class="top-val">${esc(r.ip)}
        <button class="add-btn-sm" onclick="quickBlacklist('${esc(r.ip)}')">封</button>
        <button class="add-btn-sm" style="background:#22c55e;margin-left:4px" onclick="quickWhitelistIp('${esc(r.ip)}')">白</button>
      </span>
      <span class="top-count" style="white-space:nowrap">${r.token_count} 个Token</span>
    </div>`).join('') : '<div class="empty">暂无可疑IP（阈值：拉取3个以上不同Token）</div>';
}

// ── 从分析页加入白名单（不要求先在黑名单）──────────────────────
async function quickWhitelistIp(ip) {
  if (!confirm(`是否将 ${ip} 加入白名单？`)) return;
  const d = await apiFetch('/api/whitelist.php', {
    method: 'POST', body: JSON.stringify({ip, comment: '从分析页加入白名单'}),
    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
  });
  if (d.ok || (d.error && d.error.includes('已在白名单'))) {
    toast(`✅ ${ip} 已加入白名单`);
    // 从本地缓存移除该IP并立即重绘，无需等待重新拉取数据
    if (allStatsData) {
      allStatsData.susp_ips = (allStatsData.susp_ips || []).filter(r => r.ip !== ip);
      renderStats();
    }
  } else {
    toast(d.error || '加入白名单失败', 'err');
  }
}

// ── UA 管理 ─────────────────────────────────────────────────────
async function loadUaBlacklist() {
  const [blData, wlData] = await Promise.all([
    apiFetch('/api/ua_blacklist.php'),
    apiFetch('/api/ua_whitelist.php'),
  ]);
  if (!blData.ok) {
    document.getElementById('ua-list').innerHTML = '<div class="empty">加载失败：' + esc(blData.error||'未知错误') + '</div>';
    toast('加载失败: ' + (blData.error||''), 'err');
  } else {
    allUaBlEntries = blData.entries || [];
    renderUaBlacklist();
  }
  if (!wlData.ok) {
    document.getElementById('ua-wl-list').innerHTML = '<div class="empty">加载失败：' + esc(wlData.error||'未知错误') + '</div>';
  } else {
    allUaWlEntries = wlData.entries || [];
    renderUaWhitelist();
  }
}

function setUaBlLimit(n) {
  uaBlLimit = n;
  ['50','100','500','0'].forEach(k => {
    const btn = document.getElementById('ua-bl-limit-' + k);
    if (btn) btn.classList.remove('active');
  });
  const btn = document.getElementById('ua-bl-limit-' + n);
  if (btn) btn.classList.add('active');
  renderUaBlacklist();
}

function setUaWlLimit(n) {
  uaWlLimit = n;
  ['50','100','500','0'].forEach(k => {
    const btn = document.getElementById('ua-wl-limit-' + k);
    if (btn) btn.classList.remove('active');
  });
  const btn = document.getElementById('ua-wl-limit-' + n);
  if (btn) btn.classList.add('active');
  renderUaWhitelist();
}

function renderUaBlacklist() {
  const entries = uaBlLimit > 0 ? allUaBlEntries.slice(0, uaBlLimit) : allUaBlEntries;
  if (!allUaBlEntries.length) {
    document.getElementById('ua-list').innerHTML = '<div class="empty">封禁列表为空</div>';
    return;
  }
  document.getElementById('ua-list').innerHTML = `
    <table><thead><tr><th>UA 关键词</th><th>备注</th><th>添加时间</th><th>操作</th></tr></thead>
    <tbody>${entries.map(e => `
      <tr>
        <td class="ip-cell">${esc(e.ua)}</td>
        <td style="color:#64748b">${esc(e.comment)||'—'}</td>
        <td style="color:#64748b;font-size:11px">${esc(e.added_at||'')}</td>
        <td><button class="btn-danger" onclick="uaDel('${esc(e.ua)}')">移除</button></td>
      </tr>`).join('')}
    </tbody></table>`;
}

function renderUaWhitelist() {
  const entries = uaWlLimit > 0 ? allUaWlEntries.slice(0, uaWlLimit) : allUaWlEntries;
  if (!allUaWlEntries.length) {
    document.getElementById('ua-wl-list').innerHTML = '<div class="empty">白名单为空</div>';
    return;
  }
  document.getElementById('ua-wl-list').innerHTML = `
    <table><thead><tr><th>UA 关键词</th><th>备注</th><th>添加时间</th><th>操作</th></tr></thead>
    <tbody>${entries.map(e => `
      <tr>
        <td class="ip-cell">${esc(e.ua)}</td>
        <td style="color:#64748b">${esc(e.comment)||'—'}</td>
        <td style="color:#64748b;font-size:11px">${esc(e.added_at||'')}</td>
        <td><button class="btn-danger" onclick="uaWlDel('${esc(e.ua)}')">移除</button></td>
      </tr>`).join('')}
    </tbody></table>`;
}

async function uaAdd() {
  const ua  = document.getElementById('ua-keyword').value.trim();
  const cmt = document.getElementById('ua-comment').value.trim();
  if (!ua) { toast('请输入 UA 关键词','err'); return; }
  const d = await apiFetch('/api/ua_blacklist.php', {
    method:'POST', body:JSON.stringify({ua, comment:cmt}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) {
    document.getElementById('ua-keyword').value = '';
    document.getElementById('ua-comment').value = '';
    toast('✅ 已封禁并立即生效');
    loadUaBlacklist();
  } else {
    toast(d.error||'添加失败','err');
  }
}

async function uaDel(ua) {
  const d = await apiFetch('/api/ua_blacklist.php', {
    method:'DELETE', body:JSON.stringify({ua}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) { toast('✅ 已移除并立即生效'); loadUaBlacklist(); }
  else toast(d.error||'移除失败','err');
}

async function quickBanUA(ua) {
  const cmt = prompt(`封禁 UA "${ua}"，备注（可留空）：`);
  if (cmt === null) return;
  const d = await apiFetch('/api/ua_blacklist.php', {
    method:'POST', body:JSON.stringify({ua, comment:cmt}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) toast(`✅ UA 已封禁`);
  else toast(d.error||'封禁失败','err');
}

// ── UA 白名单 ──────────────────────────────────────────────────
async function uaWlAdd() {
  const ua  = document.getElementById('ua-wl-keyword').value.trim();
  const cmt = document.getElementById('ua-wl-comment').value.trim();
  if (!ua) { toast('请输入 UA 关键词','err'); return; }
  const d = await apiFetch('/api/ua_whitelist.php', {
    method:'POST', body:JSON.stringify({ua, comment:cmt}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) {
    document.getElementById('ua-wl-keyword').value = '';
    document.getElementById('ua-wl-comment').value = '';
    toast('✅ UA 已加入白名单并立即生效');
    loadUaBlacklist();
  } else {
    toast(d.error||'添加失败','err');
  }
}

async function uaWlDel(ua) {
  const d = await apiFetch('/api/ua_whitelist.php', {
    method:'DELETE', body:JSON.stringify({ua}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) { toast('✅ 已从白名单移除并立即生效'); loadUaBlacklist(); }
  else toast(d.error||'移除失败','err');
}

// ── 白名单 ────────────────────────────────────────────────────
async function loadWhitelist() {
  const data = await apiFetch('/api/whitelist.php');
  if (!data.ok) {
    document.getElementById('wl-list').innerHTML = '<div class="empty">加载失败：' + esc(data.error||'未知错误') + '</div>';
    toast('加载失败: ' + (data.error||''), 'err'); return;
  }
  allWlEntries = data.entries || [];
  if (!allWlEntries.length) {
    document.getElementById('wl-list').innerHTML = '<div class="empty">白名单为空</div>';
    return;
  }
  document.getElementById('wl-list').innerHTML = `
    <div class="batch-row">
      <label><input type="checkbox" id="wl-check-all" onchange="toggleAllWl(this)"> 全选</label>
      <button class="btn-danger" onclick="wlBatchDel()">批量删除选中</button>
    </div>
    <table><thead><tr><th style="width:30px"></th><th>IP / CIDR</th><th>备注</th><th>操作</th></tr></thead>
    <tbody>${allWlEntries.map(e => `
      <tr>
        <td><input type="checkbox" class="wl-check" value="${esc(e.ip)}"></td>
        <td class="ip-cell">${esc(e.ip)}</td>
        <td style="color:#64748b">${esc(e.comment)||'—'}</td>
        <td><button class="btn-danger" onclick="wlDel('${esc(e.ip)}')">删除</button></td>
      </tr>`).join('')}
    </tbody></table>`;
}

function exportWhitelist() {
  if (!allWlEntries.length) { toast('白名单为空，无需导出', 'err'); return; }
  const lines = allWlEntries.map(e => e.comment ? `${e.ip}  # ${e.comment}` : e.ip);
  const blob = new Blob([lines.join('\n') + '\n'], {type: 'text/plain'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'whitelist_' + new Date().toISOString().slice(0,10) + '.txt';
  a.click();
  URL.revokeObjectURL(a.href);
}

async function importWhitelist(input) {
  const file = input.files[0];
  if (!file) return;
  input.value = '';
  toast('解析中…');
  const text = await file.text();
  const ips = [];
  for (const line of text.split('\n')) {
    const t = line.trim();
    if (!t || t.startsWith('#')) continue;
    const ip = t.split(/[\s#]/)[0].trim();
    if (ip) ips.push(ip);
  }
  if (!ips.length) { toast('文件中未找到有效IP/CIDR', 'err'); return; }
  const d = await apiFetch('/api/whitelist.php', {
    method: 'POST', body: JSON.stringify({import_ips: ips}),
    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
  });
  if (d.ok) {
    toast(`✅ 导入完成：新增 ${d.added} 个，跳过 ${d.skipped} 个重复${d.invalid ? `，${d.invalid} 个格式错误` : ''}。点击"生效"应用`);
    loadWhitelist();
  } else {
    toast(d.error || '导入失败', 'err');
  }
}

function toggleAllWl(cb) {
  document.querySelectorAll('.wl-check').forEach(c => c.checked = cb.checked);
}

async function wlAdd() {
  const raw = document.getElementById('wl-ip').value.trim();
  const cmt = document.getElementById('wl-comment').value.trim();
  if (!raw) { toast('请输入IP','err'); return; }
  const ips = raw.split(',').map(s => s.trim()).filter(Boolean);
  let ok = 0, errs = [];
  for (const ip of ips) {
    const d = await apiFetch('/api/whitelist.php', {
      method:'POST', body:JSON.stringify({ip, comment:cmt}),
      headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
    });
    if (d.ok) ok++; else errs.push(`${ip}: ${d.error}`);
  }
  document.getElementById('wl-ip').value = '';
  document.getElementById('wl-comment').value = '';
  if (!errs.length) toast(`✅ 已添加 ${ok} 个，点击"生效"应用`);
  else if (ok) toast(`添加 ${ok} 个成功，${errs.length} 个失败`, 'err');
  else toast(errs[0]||'添加失败', 'err');
  loadWhitelist();
}

async function wlDel(ip) {
  const d = await apiFetch('/api/whitelist.php', {
    method:'DELETE', body:JSON.stringify({ip}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) { toast('已删除，点击"生效"应用'); loadWhitelist(); }
  else toast(d.error||'删除失败','err');
}

async function wlBatchDel() {
  const ips = Array.from(document.querySelectorAll('.wl-check:checked')).map(c => c.value);
  if (!ips.length) { toast('请先勾选要删除的条目','err'); return; }
  if (!confirm(`确定删除选中的 ${ips.length} 个IP/CIDR？`)) return;
  const d = await apiFetch('/api/whitelist.php', {
    method:'DELETE', body:JSON.stringify({ips}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) { toast(`✅ 已删除 ${ips.length} 个，点击"生效"应用`); loadWhitelist(); }
  else toast(d.error||'批量删除失败','err');
}

async function wlApply() {
  const d = await apiFetch('/api/whitelist.php', {method:'PUT',
    headers:{'X-Requested-With':'XMLHttpRequest'}});
  if (d.ok) toast('✅ 白名单已生效');
  else toast(d.error||'生效失败','err');
}

// ── 黑名单 ────────────────────────────────────────────────────
async function loadBlacklist() {
  const data = await apiFetch('/api/blacklist.php');
  if (!data.ok) {
    document.getElementById('bl-list').innerHTML = '<div class="empty">加载失败：' + esc(data.error||'未知错误') + '</div>';
    toast('加载失败: ' + (data.error||''), 'err'); return;
  }
  allBlEntries = data.entries || [];
  const entries = allBlEntries;
  const idcSummary = data.idc_summary || [];

  let html = '';
  if (entries.length) {
    html += `
    <div class="batch-row">
      <label><input type="checkbox" id="bl-check-all" onchange="toggleAllBl(this)"> 全选</label>
      <button class="btn-danger" onclick="blBatchDel()">批量解封选中</button>
    </div>
    <table><thead><tr><th style="width:30px"></th><th>IP / CIDR</th><th>备注</th><th>添加时间</th><th>操作</th></tr></thead>
    <tbody>${entries.map(e => `
      <tr>
        <td><input type="checkbox" class="bl-check" value="${esc(e.ip)}"></td>
        <td class="ip-cell">${esc(e.ip)}</td>
        <td style="color:#64748b">${esc(e.comment)||'—'}</td>
        <td style="color:#64748b;font-size:11px">${esc(e.added_at||'')}</td>
        <td><button class="btn-danger" onclick="blDel('${esc(e.ip)}')">解封</button></td>
      </tr>`).join('')}
    </tbody></table>`;
  } else {
    html += '<div class="empty">手动黑名单为空</div>';
  }

  if (idcSummary.length) {
    html += `<div class="idc-section">
      <div class="card-title">系统内置IDC封禁（自动拦截，共 ${idcSummary.reduce((s,r)=>s+r.count,0)} 条CIDR）</div>
      <table><thead><tr><th>云服务商 / IDC</th><th>CIDR数量</th></tr></thead>
      <tbody>${idcSummary.map(s => `
        <tr>
          <td class="ip-cell">${esc(s.name)}</td>
          <td style="color:#6366f1;font-weight:600">${s.count} 条</td>
        </tr>`).join('')}
      </tbody></table>
    </div>`;
  }

  document.getElementById('bl-list').innerHTML = html;
}

function toggleAllBl(cb) {
  document.querySelectorAll('.bl-check').forEach(c => c.checked = cb.checked);
}

async function blBatchDel() {
  const ips = Array.from(document.querySelectorAll('.bl-check:checked')).map(c => c.value);
  if (!ips.length) { toast('请先勾选要解封的条目','err'); return; }
  if (!confirm(`确定解封选中的 ${ips.length} 个IP/CIDR？`)) return;
  const d = await apiFetch('/api/blacklist.php', {
    method:'DELETE', body:JSON.stringify({ips}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) { toast(`✅ 已解封 ${ips.length} 个并立即生效`); loadBlacklist(); }
  else toast(d.error||'批量解封失败','err');
}

async function blAdd() {
  const ip  = document.getElementById('bl-ip').value.trim();
  const cmt = document.getElementById('bl-comment').value.trim();
  if (!ip) { toast('请输入IP','err'); return; }
  const d = await apiFetch('/api/blacklist.php', {
    method:'POST', body:JSON.stringify({ip,comment:cmt}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) {
    document.getElementById('bl-ip').value = '';
    document.getElementById('bl-comment').value = '';
    toast('✅ 已封禁并立即生效');
    loadBlacklist();
  } else {
    toast(d.error||'添加失败','err');
  }
}

async function blDel(ip) {
  const d = await apiFetch('/api/blacklist.php', {
    method:'DELETE', body:JSON.stringify({ip}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) { toast('✅ 已解封并立即生效'); loadBlacklist(); }
  else toast(d.error||'解封失败','err');
}

// ── 黑名单导入/导出 ────────────────────────────────────────────
function exportBlacklist() {
  if (!allBlEntries.length) { toast('黑名单为空，无需导出', 'err'); return; }
  const lines = allBlEntries.map(e => {
    const cmt = e.comment ? `  # ${e.comment} (${e.added_at||''})` : (e.added_at ? `  # ${e.added_at}` : '');
    return e.ip + cmt;
  });
  const blob = new Blob([lines.join('\n') + '\n'], {type: 'text/plain'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'blacklist_' + new Date().toISOString().slice(0,10) + '.txt';
  a.click();
  URL.revokeObjectURL(a.href);
}

async function importBlacklist(input) {
  const file = input.files[0];
  if (!file) return;
  input.value = '';
  toast('解析中…');
  const text = await file.text();
  const ips = [];
  for (const line of text.split('\n')) {
    const t = line.trim();
    if (!t || t.startsWith('#')) continue;
    // 跳过 nginx deny 语法行
    const m = t.match(/^(?:deny\s+)?(\d{1,3}(?:\.\d{1,3}){3}(?:\/\d+)?)/);
    if (m) ips.push(m[1]);
  }
  if (!ips.length) { toast('文件中未找到有效IP/CIDR', 'err'); return; }
  const d = await apiFetch('/api/blacklist.php', {
    method: 'POST', body: JSON.stringify({import_ips: ips}),
    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
  });
  if (d.ok) {
    toast(`✅ 导入完成：新增 ${d.added} 个，跳过 ${d.skipped} 个重复${d.invalid ? `，${d.invalid} 个格式错误` : ''}，nginx 已重载`);
    loadBlacklist();
  } else {
    toast(d.error || '导入失败', 'err');
  }
}

// ── 系统设置 ───────────────────────────────────────────────────
let currentSettings = {};

async function loadSettings() {
  const data = await apiFetch('/api/settings.php');
  if (!data.ok) { toast('加载设置失败: ' + (data.error||''), 'err'); return; }
  currentSettings = data.settings || {};
  // 填充界面设置
  document.getElementById('cfg-site-title').value   = currentSettings.site_title || '';
  document.getElementById('cfg-page-title').value   = currentSettings.page_title || '';
  // 填充凭证设置
  document.getElementById('cfg-admin-user').value   = currentSettings.admin_user || '';
  document.getElementById('cfg-new-pass').value     = '';
  document.getElementById('cfg-confirm-pass').value = '';
  // 填充上游设置
  document.getElementById('cfg-upstream-url').value    = currentSettings.upstream_url || '';
  document.getElementById('cfg-subscribe-path').value  = currentSettings.subscribe_path || '';
  // 显示证书信息
  const cert = data.cert || {};
  const certEl = document.getElementById('cert-info');
  if (!cert.exists) {
    certEl.innerHTML = '<div class="empty" style="color:#ef4444">⚠️ 未找到证书文件</div>';
  } else if (cert.subject) {
    const color = cert.days_left > 30 ? '#22c55e' : cert.days_left > 7 ? '#eab308' : '#ef4444';
    certEl.innerHTML = `
      <table style="font-size:12px;width:100%">
        <tr><td style="color:var(--text3);padding:4px 0;white-space:nowrap">域名</td><td style="color:var(--text);padding:4px 0 4px 10px">${esc(cert.subject)}</td></tr>
        <tr><td style="color:var(--text3);padding:4px 0;white-space:nowrap">颁发机构</td><td style="color:var(--text2);padding:4px 0 4px 10px">${esc(cert.issuer)}</td></tr>
        <tr><td style="color:var(--text3);padding:4px 0;white-space:nowrap">有效期</td><td style="padding:4px 0 4px 10px">${esc(cert.valid_from)} ~ ${esc(cert.valid_to)}</td></tr>
        <tr><td style="color:var(--text3);padding:4px 0;white-space:nowrap">剩余天数</td><td style="color:${color};font-weight:600;padding:4px 0 4px 10px">${cert.days_left} 天</td></tr>
        ${cert.san ? `<tr><td style="color:var(--text3);padding:4px 0;white-space:nowrap">SAN</td><td style="color:var(--text3);font-size:11px;padding:4px 0 4px 10px;word-break:break-all">${esc(cert.san)}</td></tr>` : ''}
      </table>`;
  } else {
    certEl.innerHTML = '<div class="empty" style="color:#eab308">证书存在但无法解析（可能是非标准格式）</div>';
  }
  // 显示部署信息
  await loadDeployInfo();
}

async function loadDeployInfo() {
  const r = await apiFetch('/api/settings.php?deploy_info=1');
  const el = document.getElementById('deploy-info-content');
  if (r.ok && r.content) {
    el.textContent = r.content;
  } else {
    el.textContent = '（尚未生成，点击"同步更新"创建）';
  }
}

async function saveTitleSettings() {
  const d = await apiFetch('/api/settings.php', {
    method: 'POST',
    body: JSON.stringify({
      site_title: document.getElementById('cfg-site-title').value.trim(),
      page_title: document.getElementById('cfg-page-title').value.trim(),
    }),
    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
  });
  if (d.ok) { toast('✅ 标题设置已保存，刷新页面生效'); }
  else toast(d.error || '保存失败', 'err');
}

async function saveCredSettings() {
  const user    = document.getElementById('cfg-admin-user').value.trim();
  const newPass = document.getElementById('cfg-new-pass').value;
  const confPass= document.getElementById('cfg-confirm-pass').value;
  if (!user) { toast('用户名不能为空', 'err'); return; }
  const body = {admin_user: user};
  if (newPass) { body.new_pass = newPass; body.confirm_pass = confPass; }
  const d = await apiFetch('/api/settings.php', {
    method: 'POST', body: JSON.stringify(body),
    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
  });
  if (d.ok) {
    toast('✅ 凭证已更新，请重新登录');
    document.getElementById('cfg-new-pass').value = '';
    document.getElementById('cfg-confirm-pass').value = '';
    if (newPass) setTimeout(() => location.reload(), 2000);
  } else {
    toast(d.error || '保存失败', 'err');
  }
}

async function saveUpstreamSettings() {
  const url  = document.getElementById('cfg-upstream-url').value.trim();
  const path = document.getElementById('cfg-subscribe-path').value.trim();
  if (!url && !path) { toast('请填写机场地址或订阅路径', 'err'); return; }
  const body = {};
  if (url) body.upstream_url = url;
  if (path) body.subscribe_path = path;
  const d = await apiFetch('/api/settings.php', {
    method: 'POST', body: JSON.stringify(body),
    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
  });
  if (d.ok) {
    toast('✅ ' + (d.msg || '上游配置已更新'));
    loadSettings();
  } else {
    toast(d.error || '保存失败', 'err');
  }
}

async function syncDeployInfo() {
  const d = await apiFetch('/api/settings.php', {
    method: 'POST', body: JSON.stringify({_sync_deploy: true}),
    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
  });
  if (d.ok) { toast('✅ 部署信息已更新'); loadDeployInfo(); }
  else toast(d.error || '同步失败', 'err');
}

function downloadDeployInfo() {
  const content = document.getElementById('deploy-info-content').textContent;
  if (!content || content.includes('尚未生成')) { toast('请先点击"同步更新"', 'err'); return; }
  const blob = new Blob([content], {type: 'text/plain'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'DEPLOY_INFO.txt';
  a.click();
  URL.revokeObjectURL(a.href);
}

// ── 快捷封禁 IP（从日志/分析页直接封） ──────────────────────────
async function quickBlacklist(ip) {
  const cmt = prompt(`封禁 ${ip}，备注（可留空）：`);
  if (cmt === null) return;
  const d = await apiFetch('/api/blacklist.php', {
    method:'POST', body:JSON.stringify({ip, comment: cmt}),
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  });
  if (d.ok) { toast(`✅ ${ip} 已封禁`); TABS[currentTab].loader(); }
  else toast(d.error||'封禁失败','err');
}

// ── 导出日志 ──────────────────────────────────────────────────
function exportLogs() {
  const a = document.createElement('a');
  a.href = BASE + '/api/logs.php?export=1';
  a.download = '';
  a.click();
}

// ── 导入日志（multipart 上传，绕过 post_max_size 限制）──────────
async function importLogs(input) {
  const file = input.files[0];
  if (!file) return;
  input.value = '';   // 重置，允许再次选同一文件
  toast('导入中…');
  try {
    const fd = new FormData();
    fd.append('log', file);
    const r = await fetch(BASE + '/api/logs.php', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: fd,
    });
    const d = await r.json();
    if (d.ok) {
      toast(`✅ 导入成功：新增 ${d.imported} 行，共 ${d.total} 行`);
      loadLogs();
    } else {
      toast(d.error || '导入失败', 'err');
    }
  } catch(e) {
    toast('导入失败：网络错误', 'err');
  }
}

// ── 初始化 ────────────────────────────────────────────────────
loadLogs();
resetCountdown();
</script>
</body>
</html>

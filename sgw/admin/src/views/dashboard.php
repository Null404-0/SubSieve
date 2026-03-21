<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SubSieve Admin</title>
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
  <div class="logo">Sub<span>Sieve</span></div>
  <button class="nav-item active" onclick="switchTab('logs',this)">
    <span class="nav-icon">📋</span>日志
  </button>
  <button class="nav-item" onclick="switchTab('stats',this)">
    <span class="nav-icon">📊</span>分析
  </button>
  <button class="nav-item" onclick="switchTab('ua_blacklist',this)">
    <span class="nav-icon">🛡</span>封禁UA
  </button>
  <button class="nav-item" onclick="switchTab('whitelist',this)">
    <span class="nav-icon">✅</span>白名单
  </button>
  <button class="nav-item" onclick="switchTab('blacklist',this)">
    <span class="nav-icon">🚫</span>黑名单
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
          <input class="log-filter" id="filter-ip" placeholder="过滤 IP" oninput="renderLogs()">
          <input class="log-filter" id="filter-status" placeholder="状态码 如 403" oninput="renderLogs()">
          <input class="log-filter" id="filter-token" placeholder="过滤 Token" oninput="renderLogs()">
          <span class="auto-timer" id="log-count">—</span>
          <div class="radio-group">
            <label><input type="radio" name="sub-filter" value="subscribe" onchange="renderLogs()"> 仅订阅相关</label>
            <label><input type="radio" name="sub-filter" value="all" checked onchange="renderLogs()"> 显示全部</label>
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
      </div>
    </div>

    <!-- ─── 分析 ─────────────────────────────────────────── -->
    <div class="tab-panel" id="panel-stats">
      <div class="stats-grid">
        <div class="card">
          <div class="card-title">今日 Top IP</div>
          <div id="top-ips"><div class="loading">加载中…</div></div>
        </div>
        <div class="card">
          <div class="card-title">今日 Top Token</div>
          <div id="top-tokens"><div class="loading">加载中…</div></div>
        </div>
        <div class="card">
          <div class="card-title">可疑 Token（被多IP拉取）</div>
          <div id="susp-tokens"><div class="loading">加载中…</div></div>
        </div>
        <div class="card">
          <div class="card-title">可疑 IP（拉取多Token）</div>
          <div id="susp-ips"><div class="loading">加载中…</div></div>
        </div>
        <div class="card" style="grid-column:1/-1">
          <div class="card-title">可疑 UA（触发403）</div>
          <div id="bad-uas"><div class="loading">加载中…</div></div>
        </div>
      </div>
    </div>

    <!-- ─── 封禁UA ─────────────────────────────────────────── -->
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
        <div id="ua-list"><div class="loading">加载中…</div></div>
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
        </div>
        <div class="apply-hint" style="margin-bottom:14px;color:#eab308">
          ⚡ 黑名单添加后立即 reload nginx 生效，无需额外操作
        </div>
        <div id="bl-list"><div class="loading">加载中…</div></div>
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
let blacklistIpSet = new Set();
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
  logs:         {title:'日志',     loader:loadLogs},
  stats:        {title:'分析',     loader:loadStats},
  ua_blacklist: {title:'封禁UA',   loader:loadUaBlacklist},
  whitelist:    {title:'白名单',   loader:loadWhitelist},
  blacklist:    {title:'黑名单',   loader:loadBlacklist},
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


// ── 日志 ──────────────────────────────────────────────────────
async function loadLogs() {
  document.getElementById('log-tbody').innerHTML = '<tr><td colspan="7" class="loading">加载中…</td></tr>';
  const [logsData, blData] = await Promise.all([
    apiFetch('/api/logs.php?mode=' + logMode),
    apiFetch('/api/blacklist.php?no_idc=1'),
  ]);
  blacklistIpSet = new Set((blData.entries || []).map(e => e.ip));
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

  const total = rows.length;
  document.getElementById('log-count').textContent = `${total} / ${allLogs.length} 条`;

  // 最新的在最上面
  rows = rows.slice().reverse();

  if (!rows.length) {
    document.getElementById('log-tbody').innerHTML =
      '<tr><td colspan="7" class="empty">暂无匹配记录</td></tr>';
    return;
  }

  document.getElementById('log-tbody').innerHTML = rows.map(l => {
    const isBlacklisted = blacklistIpSet.has(l.ip);
    const ipBtn = isBlacklisted
      ? `<button class="bl-badge-btn" onclick="quickWhitelist('${esc(l.ip)}')">黑名单</button>`
      : `<button class="add-btn-sm" onclick="quickBlacklist('${esc(l.ip)}')">封</button>`;
    const tokenHtml = l.token
      ? `<div class="token-cell"><span class="token-text" title="${esc(l.token)}">${esc(l.token)}</span><button class="copy-btn" data-val="${esc(l.token)}" onclick="copyText(this.dataset.val)">复制</button></div>`
      : '—';
    return `
    <tr>
      <td style="white-space:nowrap;color:#64748b;font-size:11px">${esc(l.time)}</td>
      <td class="ip-cell">${esc(l.ip)} ${ipBtn}</td>
      <td>${statusBadge(l.status)}</td>
      <td style="min-width:120px;max-width:220px">${tokenHtml}</td>
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

  // Top IP
  const ipHtml = (data.top_ips||[]).length ? (data.top_ips||[]).map((r,i) => `
    <div class="top-row">
      <span class="top-rank">${i+1}</span>
      <span class="top-val">
        ${esc(r.ip)}
        <button class="add-btn-sm" onclick="quickBlacklist('${esc(r.ip)}')">封</button>
      </span>
      <span class="top-count">${r.total}次</span>
      <span class="top-sub" style="margin-left:8px;font-size:11px">
        <span style="color:#22c55e">${r.s200}</span>/
        <span style="color:#ef4444">${r.s403}</span>/
        <span style="color:#eab308">${r.s429}</span>
      </span>
    </div>`).join('') : '<div class="empty">暂无数据</div>';
  document.getElementById('top-ips').innerHTML = ipHtml;

  // Top Token
  const tokHtml = (data.top_tokens||[]).length ? (data.top_tokens||[]).map((r,i) => `
    <div class="top-row">
      <span class="top-rank">${i+1}</span>
      <span class="top-val token-cell" style="display:flex;align-items:center;gap:6px">
        <span class="token-text" title="${esc(r.token_full)}">${esc(r.token_full)}</span>
        <button class="copy-btn" data-val="${esc(r.token_full)}" onclick="copyText(this.dataset.val)">复制</button>
      </span>
      <span class="top-count" style="white-space:nowrap;margin-left:6px">${r.count}次</span>
      <span class="top-sub" style="margin-left:8px">${esc(r.last_time)}</span>
    </div>`).join('') : '<div class="empty">暂无数据</div>';
  document.getElementById('top-tokens').innerHTML = tokHtml;

  // 可疑 UA
  const uaHtml = (data.bad_uas||[]).length ? `
    <table><thead><tr><th>UA</th><th>403次数</th><th>操作</th></tr></thead>
    <tbody>${(data.bad_uas||[]).map(r => `
      <tr>
        <td class="ua-cell" style="max-width:500px" title="${esc(r.ua)}">${esc(r.ua)||'（空UA）'}</td>
        <td style="color:#ef4444;font-weight:600">${r.count}</td>
        <td><button class="add-btn-sm" onclick="quickBanUA('${esc(r.ua)}')">封禁UA</button></td>
      </tr>`).join('')}
    </tbody></table>` : '<div class="empty">今日暂无可疑UA</div>';
  document.getElementById('bad-uas').innerHTML = uaHtml;

  // 可疑 Token
  const suspToks = data.susp_tokens || [];
  document.getElementById('susp-tokens').innerHTML = suspToks.length ? suspToks.map(r => `
    <div class="top-row">
      <span class="top-val token-cell" style="display:flex;align-items:center;gap:6px">
        <span class="token-text" title="${esc(r.token)}">${esc(r.token.substr(0,20))}…</span>
        <button class="copy-btn" data-val="${esc(r.token)}" onclick="copyText(this.dataset.val)">复制</button>
      </span>
      <span class="top-count" style="white-space:nowrap">${r.ip_count} 个不同IP</span>
    </div>`).join('') : '<div class="empty">暂无可疑Token（阈值：3个以上不同IP）</div>';

  // 可疑 IP
  const suspIps = data.susp_ips || [];
  document.getElementById('susp-ips').innerHTML = suspIps.length ? suspIps.map(r => `
    <div class="top-row">
      <span class="top-val">${esc(r.ip)}
        <button class="add-btn-sm" onclick="quickBlacklist('${esc(r.ip)}')">封</button>
      </span>
      <span class="top-count" style="white-space:nowrap">${r.token_count} 个Token</span>
    </div>`).join('') : '<div class="empty">暂无可疑IP（阈值：拉取3个以上不同Token）</div>';
}

// ── 封禁UA ─────────────────────────────────────────────────────
async function loadUaBlacklist() {
  const data = await apiFetch('/api/ua_blacklist.php');
  if (!data.ok) {
    document.getElementById('ua-list').innerHTML = '<div class="empty">加载失败：' + esc(data.error||'未知错误') + '</div>';
    toast('加载失败: ' + (data.error||''), 'err'); return;
  }
  const entries = data.entries || [];
  if (!entries.length) {
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

// ── 白名单 ────────────────────────────────────────────────────
async function loadWhitelist() {
  const data = await apiFetch('/api/whitelist.php');
  if (!data.ok) {
    document.getElementById('wl-list').innerHTML = '<div class="empty">加载失败：' + esc(data.error||'未知错误') + '</div>';
    toast('加载失败: ' + (data.error||''), 'err'); return;
  }
  const entries = data.entries || [];
  if (!entries.length) {
    document.getElementById('wl-list').innerHTML = '<div class="empty">白名单为空</div>';
    return;
  }
  document.getElementById('wl-list').innerHTML = `
    <div class="batch-row">
      <label><input type="checkbox" id="wl-check-all" onchange="toggleAllWl(this)"> 全选</label>
      <button class="btn-danger" onclick="wlBatchDel()">批量删除选中</button>
    </div>
    <table><thead><tr><th style="width:30px"></th><th>IP / CIDR</th><th>备注</th><th>操作</th></tr></thead>
    <tbody>${entries.map(e => `
      <tr>
        <td><input type="checkbox" class="wl-check" value="${esc(e.ip)}"></td>
        <td class="ip-cell">${esc(e.ip)}</td>
        <td style="color:#64748b">${esc(e.comment)||'—'}</td>
        <td><button class="btn-danger" onclick="wlDel('${esc(e.ip)}')">删除</button></td>
      </tr>`).join('')}
    </tbody></table>`;
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
  const entries = data.entries || [];
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

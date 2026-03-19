<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Subscribe Gateway — 登录</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f1117;color:#e2e8f0;font:14px/1.5 system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{background:#1a1d2e;border:1px solid #2d3144;border-radius:12px;padding:40px;width:360px}
h1{font-size:18px;font-weight:600;margin-bottom:6px}
.sub{color:#64748b;font-size:13px;margin-bottom:28px}
label{display:block;font-size:12px;color:#94a3b8;margin-bottom:6px;margin-top:16px}
input{width:100%;background:#0f1117;border:1px solid #2d3144;color:#e2e8f0;padding:10px 12px;border-radius:8px;font-size:14px;outline:none;transition:border-color .15s}
input:focus{border-color:#6366f1}
.btn{width:100%;margin-top:24px;padding:11px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;transition:opacity .15s}
.btn:hover{opacity:.85}
.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#ef4444;padding:10px 12px;border-radius:8px;font-size:13px;margin-bottom:8px}
</style>
</head>
<body>
<div class="card">
  <h1>Subscribe Gateway</h1>
  <p class="sub">管理后台</p>
  <?php if (!empty($_SESSION['login_error'])): ?>
    <div class="err"><?= htmlspecialchars($_SESSION['login_error']) ?></div>
    <?php unset($_SESSION['login_error']); ?>
  <?php endif; ?>
  <form method="POST" action="/">
    <label>用户名</label>
    <input type="text" name="username" autocomplete="username" required autofocus>
    <label>密码</label>
    <input type="password" name="password" autocomplete="current-password" required>
    <button class="btn" type="submit">登录</button>
  </form>
</div>
</body>
</html>

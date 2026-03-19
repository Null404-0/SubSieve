# Subscribe Gateway

订阅清洗网关 + 可视化管理后台，Docker Compose 一键部署。

## 目录结构

```
sgw-v2/
├── docker-compose.yml
├── .env.example
├── whitelist_ips.txt        ← 白名单（后台管理 或 直接编辑）
├── ssl/
│   ├── cert.pem
│   └── key.pem
├── gateway/                 ← nginx 拦截 + proxy_pass
│   ├── Dockerfile
│   ├── nginx/nginx.conf
│   ├── nginx/subscribe_protect.conf.template
│   └── scripts/
│       ├── entrypoint.sh
│       ├── update_cloud_geo.sh   ← 周更新云IP库
│       └── reload_whitelist.sh   ← 白名单生效
└── admin/                   ← PHP 管理后台
    ├── Dockerfile
    ├── nginx.conf
    └── src/
        ├── index.php             ← 路由 + 鉴权
        ├── config.php            ← 配置 + 工具函数
        ├── api/
        │   ├── _auth.php         ← API 鉴权中间件
        │   ├── logs.php          ← 今日日志
        │   ├── stats.php         ← IP/Token/UA 分析
        │   ├── whitelist.php     ← 白名单 CRUD
        │   └── blacklist.php     ← 黑名单（nginx deny，即时生效）
        └── views/
            ├── login.php
            └── dashboard.php     ← 主界面（4个选项卡）
```

## 部署步骤

```bash
# 1. 配置
cp .env.example .env
vi .env   # 填写 V2B_BACKEND / V2B_HOST / ADMIN_PASS

# 2. 放入 SSL 证书
cp /path/to/cert.pem ssl/cert.pem
cp /path/to/key.pem  ssl/key.pem

# 3. 启动
docker compose up -d --build

# 4. 查看启动日志
docker logs -f subscribe-gateway
docker logs -f subscribe-admin
```

## 访问后台

`https://你的域名:64444`

用户名密码在 `.env` 中配置。

## 后台功能

| 选项卡 | 功能 |
|--------|------|
| 今日日志 | 实时展示今日访问记录，可按 IP/状态码/Token 过滤，一键封禁 |
| 分析 | Top10 IP、Top10 Token、可疑UA列表，支持快速封禁 |
| 白名单 | 增删白名单IP，点击"生效"触发 nginx reload |
| 黑名单 | 增删黑名单IP（nginx deny），增删后立即生效 |

## 后续扩展预留

- `config.php` 末尾有 `v2b_get_user_by_token()` 函数接口，填充后即可在分析页展示 token 对应用户
- 新增 API 模块：在 `admin/src/api/` 新建 PHP 文件，在 `dashboard.php` 加一个 Tab 即可

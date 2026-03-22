# SubSieve

订阅清洗网关 + 可视化管理后台，Docker Compose 一键部署。

订阅请求先经过黑名单、云厂商 IP 识别、UA 过滤、速率限制等多层拦截，通过后才反代到机场后端，防止订阅链接被扫描或滥用。

---

## 目录结构

```
sgw/
├── setup.sh                 ← 首次部署向导（一键运行）
├── update.sh                ← 已安装用户更新脚本
├── docker-compose.yml
├── .env                     ← 由 setup.sh 自动生成，含账号密码等敏感信息
├── DEPLOY_INFO.txt          ← 部署完成后生成，记录访问地址和账号
├── ssl/
│   ├── cert.pem             ← 由 setup.sh 自动申请，或手动放入
│   └── key.pem
├── gateway/                 ← nginx 拦截层 + proxy_pass
│   ├── Dockerfile
│   ├── nginx/
│   │   ├── nginx.conf
│   │   └── subscribe_protect.conf.template
│   └── scripts/
│       ├── entrypoint.sh
│       ├── update_cloud_geo.sh   ← 每周自动更新云IP库
│       └── reload_whitelist.sh   ← 白名单生效脚本
└── admin/                   ← PHP 管理后台
    ├── Dockerfile
    ├── nginx.conf
    └── src/
        ├── index.php             ← 路由 + 鉴权 + API转发
        ├── config.php            ← 配置常量 + 工具函数
        ├── api/
        │   ├── _auth.php         ← API 鉴权中间件
        │   ├── logs.php          ← 日志读取 / 删除旧日志
        │   ├── stats.php         ← IP/Token/UA 分析
        │   ├── whitelist.php     ← 白名单 CRUD
        │   ├── blacklist.php     ← 黑名单（nginx deny，即时生效）
        │   ├── token_blacklist.php ← Token 黑名单
        │   ├── ua_blacklist.php  ← 自定义封禁UA（nginx map，即时生效）
        │   └── settings.php      ← 系统设置
        └── views/
            ├── login.php
            └── dashboard.php     ← 主界面（7个选项卡）
```

---

## 首次部署

### 前置要求

- 一台有公网 IP 的 VPS（Debian/Ubuntu 最低1c0.5g）
- 已安装 Docker + Docker Compose
- 如需自动申请 SSL 证书：提前把域名解析到本机（A 记录），且 **80 端口未被占用**
- 不要装其他任何玩意儿

### 一键部署

```bash
git clone https://github.com/Null404-0/SubSieve.git
cd SubSieve/sgw
chmod +x setup.sh
./setup.sh
```

向导会依次询问：

| 提示 | 说明 |
|------|------|
| 机场地址 | 你的机场面板域名，如 `panel.example.com`，不含 `https://` |
| 订阅路径 | 默认 `/api/v1/client/subscribe`，直接回车即可 |
| 订阅端口 | 机场后端监听端口，默认 `443` |
| 网关端口 | 客户端订阅链接对外暴露的端口，默认 `443` |
| 域名（SSL） | 输入已解析到本机的域名，脚本自动调用 acme.sh 申请证书；留空则手动放证书 |

部署完成后，访问信息会打印在终端，同时保存到 `DEPLOY_INFO.txt`。
## 食用方法
部署完成后，将原订阅链接中的域名和端口替换为部署了本项目的域名和端口即可。

示例：
# 原订阅链接
https://aaaa.bbbb.com:11111/api/v1/client/subscribe?token=xxxxxxxxxxxxxxxxxxxxxxx

# 替换为
https://your-domain.com:端口/api/v1/client/subscribe?token=xxxxxxxxxxxxxxxxxxxxxxx


---

## 后续更新

已部署的用户，直接运行：

```bash
cd SubSieve/sgw
./update.sh
```

脚本会自动 git pull 最新代码、保留 `.env`、重新构建容器。

> **注意**：如果在后台修改了**网关端口**，需要在宿主机执行一次 `./update.sh` 才能让新端口生效（`.env` 中的 `GATEWAY_PORT` 由该脚本同步更新）。

---

## 访问后台

```
https://你的域名或IP:64444/<随机路径>
```

路径和账号密码见 `DEPLOY_INFO.txt`，或查看 `.env` 中的 `ADMIN_SECRET_PATH` / `ADMIN_PASS`。

---

## 后台功能

| 选项卡 | 功能 |
|--------|------|
| 日志 | 今日/全部日志切换，按 IP / 状态码 / Token 过滤，仅显示订阅相关请求，Token 全文展示并支持一键复制，一键封禁 IP，删除7日前旧日志 |
| 分析 | Top10 IP、Top10 Token（支持复制）、可疑 UA 列表（可一键封禁 UA） |
| 封禁UA | 添加/删除自定义封禁 UA 关键词，大小写不敏感，立即 reload nginx 生效 |
| 白名单 | 增删、导入白名单 IP，立即生效；白名单 IP 跳过所有拦截 |
| 黑名单 | 增删黑名单 IP（nginx deny 444），增删后立即生效 |
| Token黑名单 | 封禁指定订阅 Token，命中后返回 403，支持添加备注 |
| 设置 | 修改机场上游地址、订阅路径、网关对外端口、管理员密码 |

---

## 拦截层说明

订阅请求按以下顺序过滤，通过全部拦截后才反代到机场后端：

1. **黑名单**：精确 IP 封禁，`deny` 返回 444
2. **云厂商 IP**：自动识别阿里云、腾讯云、字节、华为云、Google、AWS、Azure、DigitalOcean 等，返回 403
3. **可疑 UA**：空 UA、curl、wget、python、Go、Java 等爬虫特征，返回 403
4. **自定义封禁 UA**：管理员在后台手动添加的 UA 关键词，返回 403
5. **Token 黑名单**：命中封禁 Token 返回 403
6. **速率限制**：每分钟 20 次，burst 5，超出返回 429
7. **白名单**：白名单 IP 跳过上述所有拦截，直接放行

云厂商 IP 库每周自动更新，更新日志见容器内 `/var/log/subscribe/update_cloud_geo.log`。

---

## 常用命令

```bash
# 查看实时日志
docker logs -f subscribe-gateway
docker logs -f subscribe-admin

# 重启服务
docker compose restart

# 完整重建
docker compose up -d --build

# 进入 gateway 容器调试
docker exec -it subscribe-gateway sh
```

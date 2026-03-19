#!/bin/bash
# =============================================================
# setup.sh — 首次部署脚本
# 自动生成随机密钥 → 写入 .env → 申请SSL → 启动容器 → 打印访问信息
# =============================================================

set -euo pipefail

cd "$(dirname "$0")"

# ── 颜色 ──────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

echo -e "${BOLD}SubSieve — 部署向导${RESET}"
echo "────────────────────────────────────────"

# ── 检查 .env 是否已存在 ──────────────────────────────────────
if [[ -f .env ]]; then
    echo -e "${YELLOW}⚠  检测到已有 .env 文件${RESET}"
    read -rp "是否覆盖重新生成？(y/N): " CONFIRM
    [[ "${CONFIRM,,}" != "y" ]] && echo "已取消。" && exit 0
fi

# ── 随机生成函数 ───────────────────────────────────────────────
gen_random() { head -c 48 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c "$1"; }

# ── 收集机场信息 ───────────────────────────────────────────────
echo ""
echo -e "${CYAN}请填写机场信息${RESET}"
read -rp "机场地址（如 panel.yourdomain.com，不含 https://）: " V2B_HOST
V2B_HOST="${V2B_HOST#https://}"
V2B_BACKEND="https://${V2B_HOST}"

read -rp "订阅路径（直接回车使用默认 /api/v1/client/subscribe）: " SUBSCRIBE_PATH
SUBSCRIBE_PATH="${SUBSCRIBE_PATH:-/api/v1/client/subscribe}"

read -rp "请输入用来接收客户订阅请求的端口（直接回车默认 443）: " GATEWAY_PORT
GATEWAY_PORT="${GATEWAY_PORT:-443}"

# ── SSL 证书 ───────────────────────────────────────────────────
echo ""
echo -e "${CYAN}SSL 证书配置${RESET}"
echo "  方式一：输入已解析到本机的域名，脚本自动申请证书"
echo "  方式二：直接回车跳过，手动将证书放入 ssl/ 目录"
echo ""
read -rp "请输入域名（如 sub.yourdomain.com，留空跳过）: " SSL_DOMAIN
SSL_DOMAIN="${SSL_DOMAIN#https://}"
SSL_DOMAIN="${SSL_DOMAIN%%/*}"

mkdir -p ssl

if [[ -n "$SSL_DOMAIN" ]]; then
    # 查找 acme.sh
    ACME_CMD=""
    if [[ -f "$HOME/.acme.sh/acme.sh" ]]; then
        ACME_CMD="$HOME/.acme.sh/acme.sh"
    elif command -v acme.sh &>/dev/null; then
        ACME_CMD="acme.sh"
    fi

    if [[ -z "$ACME_CMD" ]]; then
        echo -e "${YELLOW}未检测到 acme.sh，正在安装…${RESET}"
        curl -fsSL https://get.acme.sh | sh -s "email=admin@${SSL_DOMAIN}"
        # shellcheck source=/dev/null
        source "$HOME/.bashrc" 2>/dev/null || true
        ACME_CMD="$HOME/.acme.sh/acme.sh"
    fi

    echo -e "${CYAN}正在为 ${SSL_DOMAIN} 申请 SSL 证书（需要80端口未被占用）…${RESET}"
    if "$ACME_CMD" --issue -d "$SSL_DOMAIN" --standalone --httpport 80; then
        "$ACME_CMD" --install-cert -d "$SSL_DOMAIN" \
            --cert-file  ssl/cert.pem \
            --key-file   ssl/key.pem
        echo -e "${GREEN}✅ 证书已安装到 ssl/${RESET}"
    else
        echo -e "${RED}❌ 证书申请失败，请检查：${RESET}"
        echo "   1. 域名 ${SSL_DOMAIN} 是否已正确解析到本机"
        echo "   2. 80 端口是否未被占用（sudo lsof -i :80）"
        echo "   3. 防火墙是否放行了 80 端口"
        echo ""
        echo -e "${YELLOW}你可以手动申请后将证书放到 ssl/cert.pem 和 ssl/key.pem，再运行 docker compose up -d --build${RESET}"
        exit 1
    fi
else
    SSL_DOMAIN=""
    # 手动证书检查
    if [[ ! -f ssl/cert.pem || ! -f ssl/key.pem ]]; then
        echo -e "${YELLOW}⚠  未检测到 SSL 证书${RESET}"
        echo "   请将证书放入 ssl/ 目录："
        echo "     ssl/cert.pem"
        echo "     ssl/key.pem"
        echo ""
        read -rp "证书已放好了？(y/N): " CERT_OK
        if [[ "${CERT_OK,,}" != "y" ]]; then
            echo -e "${YELLOW}请放好证书后重新运行 ./setup.sh${RESET}"
            exit 0
        fi
    fi
fi

# ── 随机生成账号密码和访问路径 ────────────────────────────────
ADMIN_USER="admin"
ADMIN_PASS="$(gen_random 16)"
ADMIN_SECRET_PATH="$(gen_random 12)"
GATEWAY_CONTAINER="subscribe-gateway"

# ── 写入 .env ─────────────────────────────────────────────────
cat > .env <<EOF
# 由 setup.sh 自动生成 | $(date '+%Y-%m-%d %H:%M:%S')
# 请妥善保管此文件，勿泄露

V2B_BACKEND=${V2B_BACKEND}
V2B_HOST=${V2B_HOST}
SUBSCRIBE_PATH=${SUBSCRIBE_PATH}
GATEWAY_PORT=${GATEWAY_PORT}

ADMIN_USER=${ADMIN_USER}
ADMIN_PASS=${ADMIN_PASS}
ADMIN_SECRET_PATH=${ADMIN_SECRET_PATH}
GATEWAY_CONTAINER=${GATEWAY_CONTAINER}
EOF

echo -e "${GREEN}✅ .env 已生成${RESET}"

# ── 启动容器 ──────────────────────────────────────────────────
echo ""
echo -e "${CYAN}正在构建并启动容器（首次约需 3-5 分钟）…${RESET}"
docker compose up -d --build

# ── 等待 gateway 初始化完成 ────────────────────────────────────
echo -e "${CYAN}等待 gateway 初始化（拉取云IP库，请稍候）…${RESET}"
for i in $(seq 1 60); do
    if docker logs subscribe-gateway 2>&1 | grep -q "nginx 启动\|daemon off\|start worker"; then
        break
    fi
    sleep 3
    echo -n "."
done
echo ""

# ── 打印访问信息 ──────────────────────────────────────────────
print_summary() {
    # 优先使用域名，否则获取公网IP
    if [[ -n "$SSL_DOMAIN" ]]; then
        DISPLAY_HOST="$SSL_DOMAIN"
    else
        DISPLAY_HOST=$(curl -s --max-time 5 ifconfig.me 2>/dev/null \
                    || curl -s --max-time 5 ip.sb 2>/dev/null \
                    || hostname -I | awk '{print $1}')
    fi

    local PORT_SUFFIX=""
    [[ "$GATEWAY_PORT" != "443" ]] && PORT_SUFFIX=":${GATEWAY_PORT}"

    echo ""
    echo -e "${BOLD}════════════════════════════════════════════${RESET}"
    echo -e "${GREEN}${BOLD}  ✅ 部署完成！以下是你的访问信息${RESET}"
    echo -e "${BOLD}════════════════════════════════════════════${RESET}"
    echo ""
    echo -e "  ${BOLD}管理后台${RESET}"
    echo -e "  地址：${CYAN}https://${DISPLAY_HOST}:64444/${ADMIN_SECRET_PATH}${RESET}"
    echo -e "  用户名：${YELLOW}${ADMIN_USER}${RESET}"
    echo -e "  密码：  ${YELLOW}${ADMIN_PASS}${RESET}"
    echo ""
    echo -e "  ${BOLD}订阅网关${RESET}"
    echo -e "  拦截端口：${CYAN}https://${DISPLAY_HOST}${PORT_SUFFIX}${RESET}"
    echo -e "  订阅路径：${CYAN}${SUBSCRIBE_PATH}${RESET}"
    echo -e "  代理到：  ${CYAN}${V2B_BACKEND}${RESET}"
    echo ""
    echo -e "  ${BOLD}以上信息已保存到 .env 和 DEPLOY_INFO.txt${RESET}"
    echo -e "${BOLD}════════════════════════════════════════════${RESET}"
    echo ""
}

print_summary

# ── 保存一份到本地文件 ─────────────────────────────────────────
if [[ -n "$SSL_DOMAIN" ]]; then
    DISPLAY_HOST="$SSL_DOMAIN"
else
    DISPLAY_HOST=$(curl -s --max-time 5 ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')
fi

PORT_SUFFIX=""
[[ "$GATEWAY_PORT" != "443" ]] && PORT_SUFFIX=":${GATEWAY_PORT}"

cat > DEPLOY_INFO.txt <<EOF
SubSieve 部署信息
生成时间: $(date '+%Y-%m-%d %H:%M:%S')

管理后台
  地址:   https://${DISPLAY_HOST}:64444/${ADMIN_SECRET_PATH}
  用户名: ${ADMIN_USER}
  密码:   ${ADMIN_PASS}

订阅网关
  端口:     ${GATEWAY_PORT}
  订阅路径: ${SUBSCRIBE_PATH}
  代理到:   ${V2B_BACKEND}
EOF

echo -e "  ${GREEN}访问信息已同步保存到 ./DEPLOY_INFO.txt${RESET}"
echo ""

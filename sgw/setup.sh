#!/bin/bash
# =============================================================
# setup.sh — 首次部署脚本（支持断点续跑）
# 自动生成随机密钥 → 写入 .env → 申请SSL → 启动容器 → 打印访问信息
# =============================================================

set -euo pipefail

cd "$(dirname "$0")"

# ── 颜色 ──────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

STATE_FILE=".setup_state"

echo -e "${BOLD}SubSieve — 部署向导${RESET}"
echo "────────────────────────────────────────"

# ── 加载上次保存的输入 ─────────────────────────────────────────
_S_V2B_HOST=""; _S_V2B_PORT=""; _S_SUBSCRIBE_PATH=""; _S_GATEWAY_PORT=""; _S_SSL_DOMAIN=""
if [[ -f "$STATE_FILE" ]]; then
    # shellcheck source=/dev/null
    source "$STATE_FILE" 2>/dev/null || true
fi

# ── 辅助：带默认值的 read ──────────────────────────────────────
ask() {
    # ask "提示文字" "默认值" VARNAME
    local prompt="$1" default="$2" var="$3" val
    if [[ -n "$default" ]]; then
        read -rp "${prompt} [${default}]: " val
        printf -v "$var" '%s' "${val:-$default}"
    else
        read -rp "${prompt}: " val
        printf -v "$var" '%s' "$val"
    fi
}

# ── 随机生成函数 ───────────────────────────────────────────────
gen_random() { head -c 48 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c "$1"; }

# ── 检查 .env → 决定是否重新生成凭证 ─────────────────────────
REGEN_CREDS=true
ADMIN_USER="admin"; ADMIN_PASS=""; ADMIN_SECRET_PATH=""
if [[ -f .env ]]; then
    echo -e "${YELLOW}⚠  检测到已有 .env 文件${RESET}"
    read -rp "是否重新生成账号密码和访问路径？(y/N): " _CONFIRM
    if [[ "${_CONFIRM,,}" != "y" ]]; then
        REGEN_CREDS=false
        # 从现有 .env 逐行解析凭证（避免 source 副作用）
        while IFS='=' read -r _key _val; do
            [[ "$_key" =~ ^[[:space:]]*# ]] && continue
            [[ -z "${_key// /}" ]] && continue
            _key="${_key// /}"; _val="${_val// /}"
            case "$_key" in
                ADMIN_USER)        ADMIN_USER="$_val" ;;
                ADMIN_PASS)        ADMIN_PASS="$_val" ;;
                ADMIN_SECRET_PATH) ADMIN_SECRET_PATH="$_val" ;;
            esac
        done < .env
        # 若解析失败则重新生成
        if [[ -z "$ADMIN_PASS" || -z "$ADMIN_SECRET_PATH" ]]; then
            echo -e "${YELLOW}⚠  无法从 .env 读取凭证，将重新生成${RESET}"
            REGEN_CREDS=true
        fi
    fi
fi

# ── 收集机场信息 ───────────────────────────────────────────────
echo ""
echo -e "${CYAN}请填写机场信息${RESET}"
ask "机场地址（如 panel.yourdomain.com，不含 https://）" "$_S_V2B_HOST" V2B_HOST
V2B_HOST="${V2B_HOST#https://}"
ask "机场面板端口（标准 HTTPS 填 443）" "${_S_V2B_PORT:-443}" V2B_PORT
if [[ "$V2B_PORT" == "443" ]]; then
    V2B_BACKEND="https://${V2B_HOST}"
else
    V2B_BACKEND="https://${V2B_HOST}:${V2B_PORT}"
fi

ask "订阅路径（默认 /api/v1/client/subscribe）" "${_S_SUBSCRIBE_PATH:-/api/v1/client/subscribe}" SUBSCRIBE_PATH

ask "用来接收客户订阅请求的端口（默认 443）" "${_S_GATEWAY_PORT:-443}" GATEWAY_PORT

# ── SSL 证书域名 ───────────────────────────────────────────────
echo ""
echo -e "${CYAN}SSL 证书配置${RESET}"
echo "  方式一：输入已解析到本机的域名，脚本自动申请证书"
echo "  方式二：直接回车跳过，手动将证书放入 ssl/ 目录"
echo ""
ask "请输入域名（如 sub.yourdomain.com，留空跳过）" "$_S_SSL_DOMAIN" SSL_DOMAIN
SSL_DOMAIN="${SSL_DOMAIN#https://}"
SSL_DOMAIN="${SSL_DOMAIN%%/*}"

# ── 持久化本次输入（下次重跑时作为默认值）────────────────────
cat > "$STATE_FILE" <<EOF
_S_V2B_HOST="${V2B_HOST}"
_S_V2B_PORT="${V2B_PORT}"
_S_SUBSCRIBE_PATH="${SUBSCRIBE_PATH}"
_S_GATEWAY_PORT="${GATEWAY_PORT}"
_S_SSL_DOMAIN="${SSL_DOMAIN}"
EOF

mkdir -p ssl

# ── SSL 证书申请 ───────────────────────────────────────────────
if [[ -n "$SSL_DOMAIN" ]]; then
    # 检查证书是否已存在且域名匹配
    _CERT_OK=false
    if [[ -f ssl/cert.pem && -f ssl/key.pem ]]; then
        if command -v openssl &>/dev/null; then
            _CERT_DOMAINS=$(openssl x509 -noout -text -in ssl/cert.pem 2>/dev/null \
                | grep -oP '(?<=DNS:)[^,\s]+' | tr '\n' ' ' || true)
            # CN 兜底
            if [[ -z "$_CERT_DOMAINS" ]]; then
                _CERT_DOMAINS=$(openssl x509 -noout -subject -in ssl/cert.pem 2>/dev/null \
                    | grep -oP 'CN\s*=\s*\K[^,/]+' || true)
            fi
            echo "$_CERT_DOMAINS" | grep -qF "$SSL_DOMAIN" && _CERT_OK=true
        else
            _CERT_OK=true   # openssl 不可用时信任已有文件
        fi
    fi

    if [[ "$_CERT_OK" == "true" ]]; then
        echo -e "${GREEN}✅ 检测到 ${SSL_DOMAIN} 的有效证书，跳过申请${RESET}"
    else
        # 查找 acme.sh
        ACME_CMD=""
        if [[ -f "$HOME/.acme.sh/acme.sh" ]]; then
            ACME_CMD="$HOME/.acme.sh/acme.sh"
        elif command -v acme.sh &>/dev/null; then
            ACME_CMD="acme.sh"
        fi

        if [[ -z "$ACME_CMD" ]]; then
            echo -e "${YELLOW}未检测到 acme.sh，正在安装…${RESET}"
            # acme.sh 需要 cron 来设置自动续期任务，Debian/Ubuntu 默认未安装
            if ! command -v crontab &>/dev/null; then
                echo -e "${YELLOW}正在安装 cron（acme.sh 自动续期所需）…${RESET}"
                apt-get install -y -q cron 2>/dev/null || true
            fi
            curl -fsSL https://get.acme.sh | sh -s "email=admin@${SSL_DOMAIN}"
            # shellcheck source=/dev/null
            source "$HOME/.bashrc" 2>/dev/null || true
            ACME_CMD="$HOME/.acme.sh/acme.sh"
        fi

        echo -e "${CYAN}正在为 ${SSL_DOMAIN} 申请 SSL 证书（需要80端口未被占用）…${RESET}"
        "$ACME_CMD" --issue -d "$SSL_DOMAIN" --standalone --httpport 80 || _ACME_EXIT=$?
        # 退出码 0 = 新申请成功；2 = 证书仍有效已跳过（RENEW_SKIP）
        # 两种情况都直接安装证书到 ssl/
        if [[ "${_ACME_EXIT:-0}" -eq 0 || "${_ACME_EXIT:-0}" -eq 2 ]]; then
            "$ACME_CMD" --install-cert -d "$SSL_DOMAIN" \
                --fullchain-file ssl/cert.pem \
                --key-file       ssl/key.pem
            echo -e "${GREEN}✅ 证书已安装到 ssl/${RESET}"
        else
            echo -e "${RED}❌ 证书申请失败，请检查：${RESET}"
            echo "   1. 域名 ${SSL_DOMAIN} 是否已正确解析到本机"
            echo "   2. 80 端口是否未被占用（sudo lsof -i :80）"
            echo "   3. 防火墙是否放行了 80 端口"
            echo ""
            echo -e "${YELLOW}修复后直接重新运行 ./setup.sh，已填写的信息会自动保留${RESET}"
            exit 1
        fi
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

# ── 检测并安装 Docker ──────────────────────────────────────────
if ! command -v docker &>/dev/null; then
    echo ""
    echo -e "${YELLOW}未检测到 Docker，正在自动安装…${RESET}"
    curl -fsSL https://get.docker.com | sh
    systemctl enable --now docker 2>/dev/null || true
    echo -e "${GREEN}✅ Docker 安装完成${RESET}"
    echo ""
elif ! docker info &>/dev/null 2>&1; then
    echo -e "${YELLOW}Docker 已安装但未运行，正在启动…${RESET}"
    systemctl start docker 2>/dev/null || true
fi

# ── 生成/保留凭证 ──────────────────────────────────────────────
GATEWAY_CONTAINER="subscribe-gateway"
if [[ "$REGEN_CREDS" == "true" ]]; then
    ADMIN_USER="admin"
    ADMIN_PASS="$(gen_random 16)"
    ADMIN_SECRET_PATH="$(gen_random 12)"
fi

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
    if docker logs subscribe-gateway 2>&1 | grep -q "启动 nginx\|daemon off\|start worker"; then
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

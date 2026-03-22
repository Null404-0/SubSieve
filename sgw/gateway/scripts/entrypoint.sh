#!/bin/bash
set -euo pipefail

LOG="/var/log/subscribe/entrypoint.log"
mkdir -p /var/log/subscribe /etc/nginx/subscribe

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] [entrypoint] $*" | tee -a "$LOG"; }

[[ -z "${V2B_BACKEND:-}" ]] && { echo "❌ V2B_BACKEND 未设置"; exit 1; }
[[ -z "${V2B_HOST:-}" ]]    && { echo "❌ V2B_HOST 未设置"; exit 1; }

log "生成 protect.conf ..."
# 若 protect.conf 已由管理员面板配置（PHP 格式，无 set $upstream_backend 行），
# 则保留现有配置，避免重启覆盖管理员在 UI 中保存的端口等设置。
# 只有模板格式（含 set $upstream_backend）或文件不存在时才重新生成。
_PROTECT_CONF="/etc/nginx/subscribe/protect.conf"
if [[ -f "$_PROTECT_CONF" ]] && ! grep -q 'set \$upstream_backend' "$_PROTECT_CONF"; then
    log "protect.conf 已由管理员配置，保留现有配置（跳过模板生成）"
else
    envsubst '${V2B_BACKEND} ${V2B_HOST} ${SUBSCRIBE_PATH}' \
        < /etc/nginx/templates-src/subscribe_protect.conf.template \
        > "$_PROTECT_CONF"
fi

cp /etc/nginx/templates-src/nginx.conf /etc/nginx/nginx.conf

# 初始化空白名单
[[ ! -f /etc/nginx/subscribe/whitelist_ips.txt ]] && touch /etc/nginx/subscribe/whitelist_ips.txt
chmod 666 /etc/nginx/subscribe/whitelist_ips.txt

# 生成白名单 geo 块
SKIP_NGINX_RELOAD=1 /scripts/reload_whitelist.sh

# 初始化空黑名单
[[ ! -f /etc/nginx/subscribe/blacklist.conf ]] && echo "# blacklist" > /etc/nginx/subscribe/blacklist.conf
[[ ! -f /etc/nginx/subscribe/blacklist.json ]] && echo "[]" > /etc/nginx/subscribe/blacklist.json
# 确保 admin 容器可写（admin php-fpm 以非 root 用户运行）
chmod 666 /etc/nginx/subscribe/blacklist.conf /etc/nginx/subscribe/blacklist.json

# 初始化自定义UA封禁
if [[ ! -f /etc/nginx/subscribe/ua_custom.conf ]]; then
    cat > /etc/nginx/subscribe/ua_custom.conf <<'UAEOF'
# 自定义封禁UA - 由 admin 自动生成
map $http_user_agent $is_custom_bad_ua {
    default 0;
}
UAEOF
fi
[[ ! -f /etc/nginx/subscribe/ua_blacklist.json ]] && echo "[]" > /etc/nginx/subscribe/ua_blacklist.json
chmod 666 /etc/nginx/subscribe/ua_custom.conf /etc/nginx/subscribe/ua_blacklist.json

# 初始化UA白名单
if [[ ! -f /etc/nginx/subscribe/ua_whitelist.conf ]]; then
    cat > /etc/nginx/subscribe/ua_whitelist.conf <<'UAWEOF'
# UA白名单 - 由 admin 自动生成
map $http_user_agent $is_ua_whitelisted {
    default 0;
}
UAWEOF
fi
[[ ! -f /etc/nginx/subscribe/ua_whitelist.json ]] && echo "[]" > /etc/nginx/subscribe/ua_whitelist.json
chmod 666 /etc/nginx/subscribe/ua_whitelist.conf /etc/nginx/subscribe/ua_whitelist.json

# 首次拉取云IP库
if [[ ! -f /etc/nginx/subscribe/cloud_geo.conf ]]; then
    log "首次启动：拉取云厂商IP库..."
    SKIP_NGINX_RELOAD=1 /scripts/update_cloud_geo.sh
else
    log "cloud_geo.conf 已存在，跳过初次拉取"
fi

# 每周定时更新
(
    while true; do
        sleep 604800
        log "定时更新云IP库..."
        SKIP_NGINX_RELOAD=0 /scripts/update_cloud_geo.sh || log "[警告] 定时更新失败"
    done
) &

log "启动 nginx reload watcher..."
/scripts/nginx_reload_watcher.sh &

log "启动 nginx..."
exec nginx -g 'daemon off;'

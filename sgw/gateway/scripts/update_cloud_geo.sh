#!/bin/bash
set -euo pipefail

OUTPUT="/etc/nginx/subscribe/cloud_geo.conf"
OUTPUT_TMP="${OUTPUT}.tmp"
LOG_FILE="/var/log/subscribe/update_cloud_geo.log"
TEMP_DIR=$(mktemp -d)
SKIP_NGINX_RELOAD="${SKIP_NGINX_RELOAD:-0}"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }
cleanup() { rm -rf "$TEMP_DIR" "$OUTPUT_TMP" 2>/dev/null || true; }
trap cleanup EXIT

declare -A SOURCES=(
    ["阿里云"]="https://metowolf.github.io/iplist/data/isp/aliyun.txt"
    ["腾讯云"]="https://metowolf.github.io/iplist/data/isp/tencent.txt"
    ["字节跳动"]="https://metowolf.github.io/iplist/data/isp/bytedance.txt"
    ["华为云"]="https://metowolf.github.io/iplist/data/isp/huawei.txt"
    ["Google Cloud"]="https://metowolf.github.io/iplist/data/isp/googlecloud.txt"
)
declare -A ASN_SOURCES=(
    ["UCloud"]="AS135377"
    ["Azure"]="AS8075"
    ["DigitalOcean"]="AS14061"
    ["Vultr"]="AS20473"
)
AWS_URL="https://ip-ranges.amazonaws.com/ip-ranges.json"

log "开始更新云厂商IP段..."

cat > "$OUTPUT_TMP" <<EOF
# 由 update_cloud_geo.sh 自动生成 | $(date '+%Y-%m-%d %H:%M:%S')

limit_req_zone \$binary_remote_addr zone=subscribe_limit:10m rate=20r/m;

geo \$is_cloud_ip {
    default 0;
EOF

TOTAL=0

for NAME in "阿里云" "腾讯云" "字节跳动" "华为云" "Google Cloud"; do
    URL="${SOURCES[$NAME]}"
    TMPFILE="$TEMP_DIR/$(echo "$NAME" | tr ' ' '_').txt"
    log "拉取 $NAME ..."
    if curl -sfL --max-time 15 "$URL" -o "$TMPFILE"; then
        COUNT=$(grep -cE '^[0-9]' "$TMPFILE" || true)
        TOTAL=$((TOTAL + COUNT))
        echo "    # === $NAME ===" >> "$OUTPUT_TMP"
        grep -E '^[0-9]{1,3}\.' "$TMPFILE" | while read -r cidr; do
            echo "    $cidr 1;" >> "$OUTPUT_TMP"
        done || true
        echo "" >> "$OUTPUT_TMP"
        log "  $NAME: ${COUNT} 条"
    else
        log "  [警告] $NAME 拉取失败"
    fi
done

for NAME in "UCloud" "Azure" "DigitalOcean" "Vultr"; do
    ASN="${ASN_SOURCES[$NAME]}"
    TMPFILE="$TEMP_DIR/${NAME}.json"
    log "拉取 $NAME ($ASN) ..."
    if curl -sfL --max-time 20 \
        "https://stat.ripe.net/data/announced-prefixes/data.json?resource=${ASN}" \
        -o "$TMPFILE"; then
        COUNT=$(grep -o '"prefix":"[0-9][^"]*"' "$TMPFILE" | sed 's/"prefix":"//;s/"//' | wc -l || echo 0)
        TOTAL=$((TOTAL + COUNT))
        echo "    # === $NAME ===" >> "$OUTPUT_TMP"
        grep -o '"prefix":"[0-9][^"]*"' "$TMPFILE" | sed 's/"prefix":"//;s/"//' | while read -r cidr; do
            echo "    $cidr 1;" >> "$OUTPUT_TMP"
        done || true
        echo "" >> "$OUTPUT_TMP"
        log "  $NAME: ${COUNT} 条"
    else
        log "  [警告] $NAME 拉取失败"
    fi
done

log "拉取 AWS ..."
AWS_TMP="$TEMP_DIR/aws.json"
if curl -sfL --max-time 20 "$AWS_URL" -o "$AWS_TMP"; then
    echo "    # === AWS ===" >> "$OUTPUT_TMP"
    grep -o '"ip_prefix":"[^"]*"' "$AWS_TMP" | sed 's/"ip_prefix":"//;s/"//' | sort -u | while read -r cidr; do
        echo "    $cidr 1;" >> "$OUTPUT_TMP"
    done || true
    log "  AWS: $(grep -o '"ip_prefix"' "$AWS_TMP" | wc -l || echo 0) 条"
    echo "" >> "$OUTPUT_TMP"
else
    log "  [警告] AWS 拉取失败"
fi

cat >> "$OUTPUT_TMP" <<'EOF'
}

map $http_user_agent $bad_subscribe_ua {
    default                    0;
    ""                         1;
    "clash"                    1;
    "~^curl/"                  1;
    "~^python"                 1;
    "~^wget"                   1;
    "~^Go-http-client"         1;
    "~^Java/"                  1;
    "~^libcurl"                1;
    "~^axios"                  1;
    "~^node-fetch"             1;
    "~^okhttp/3\.(12|13|14)\." 1;
}
EOF

log "共 $TOTAL 条CIDR（不含AWS）"

# 原子替换：写完整再覆盖，避免容器被杀时生成损坏的配置文件
mv "$OUTPUT_TMP" "$OUTPUT"

if [[ "$SKIP_NGINX_RELOAD" != "1" ]]; then
    nginx -t 2>/dev/null && nginx -s reload && log "✅ Nginx 重载成功" || log "❌ 配置测试失败"
fi
log "完成。"

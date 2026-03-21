#!/bin/sh
set -e

SUBSCRIBE_DIR=/etc/nginx/subscribe

# 确保目录存在且 admin 可写
mkdir -p "$SUBSCRIBE_DIR"
chmod 777 "$SUBSCRIBE_DIR"

# 确保所有可写文件存在
[ -f "$SUBSCRIBE_DIR/blacklist.json" ]    || echo "[]" > "$SUBSCRIBE_DIR/blacklist.json"
[ -f "$SUBSCRIBE_DIR/blacklist.conf" ]    || echo "# blacklist" > "$SUBSCRIBE_DIR/blacklist.conf"
[ -f "$SUBSCRIBE_DIR/ua_blacklist.json" ] || echo "[]" > "$SUBSCRIBE_DIR/ua_blacklist.json"
[ -f "$SUBSCRIBE_DIR/ua_custom.conf" ]    || printf 'map $http_user_agent $is_custom_bad_ua {\n    default 0;\n}\n' > "$SUBSCRIBE_DIR/ua_custom.conf"
[ -f "$SUBSCRIBE_DIR/whitelist_ips.txt" ] || touch "$SUBSCRIBE_DIR/whitelist_ips.txt"

chmod 666 \
    "$SUBSCRIBE_DIR/blacklist.json" \
    "$SUBSCRIBE_DIR/blacklist.conf" \
    "$SUBSCRIBE_DIR/ua_blacklist.json" \
    "$SUBSCRIBE_DIR/ua_custom.conf" \
    "$SUBSCRIBE_DIR/whitelist_ips.txt"

php-fpm -D
exec nginx -g 'daemon off;'

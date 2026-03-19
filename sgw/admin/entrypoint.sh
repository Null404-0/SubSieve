#!/bin/sh
set -e

SUBSCRIBE_DIR=/etc/nginx/subscribe

# 确保目录存在且 admin 可写
mkdir -p "$SUBSCRIBE_DIR"
chmod 777 "$SUBSCRIBE_DIR"

# 确保黑名单文件存在且可写
[ -f "$SUBSCRIBE_DIR/blacklist.json" ] || echo "[]" > "$SUBSCRIBE_DIR/blacklist.json"
[ -f "$SUBSCRIBE_DIR/blacklist.conf" ] || echo "# blacklist" > "$SUBSCRIBE_DIR/blacklist.conf"
[ -f "$SUBSCRIBE_DIR/ua_blacklist.json" ] || echo "[]" > "$SUBSCRIBE_DIR/ua_blacklist.json"
chmod 666 "$SUBSCRIBE_DIR/blacklist.json" "$SUBSCRIBE_DIR/blacklist.conf" "$SUBSCRIBE_DIR/ua_blacklist.json"

php-fpm -D
exec nginx -g 'daemon off;'

FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx docker-cli tzdata
ENV TZ=Asia/Shanghai

# php-fpm + nginx 配置
COPY nginx.conf /etc/nginx/nginx.conf
COPY src/ /var/www/html/
RUN chown -R nginx:nginx /var/www/html

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 64444

ENTRYPOINT ["/entrypoint.sh"]

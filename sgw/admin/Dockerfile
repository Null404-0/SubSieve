FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx docker-cli tzdata
ENV TZ=Asia/Shanghai

# php-fpm + nginx 配置
COPY nginx.conf /etc/nginx/nginx.conf
COPY src/ /var/www/html/
RUN chown -R nginx:nginx /var/www/html \
    # 让 php-fpm 以 nginx 用户运行，确保可写入共享 volume
    && sed -i 's/^user = nobody/user = nginx/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^group = nobody/group = nginx/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 64444

CMD ["sh", "-c", "php-fpm -D && exec nginx -g 'daemon off;'"]

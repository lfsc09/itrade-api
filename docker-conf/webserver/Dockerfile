FROM composer as composer
COPY . /app
RUN composer install --optimize-autoloader --no-interaction --no-progress

# https://github.com/TrafeX/docker-php-nginx/blob/master/docs/composer-support.md
FROM trafex/php-nginx:latest
USER root
RUN apk add --no-cache php83-pdo_mysql
USER nobody
COPY --chown=nginx --from=composer /app /var/www/html
COPY docker-conf/server.conf /etc/nginx/conf.d/default.conf
COPY docker-conf/php.ini /etc/php83/conf.d/settings.ini

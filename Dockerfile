FROM composer as composer
COPY . /app
RUN composer install --optimize-autoloader --no-interaction --no-progress

# https://github.com/TrafeX/docker-php-nginx/blob/master/docs/composer-support.md
FROM trafex/php-nginx:latest
COPY --chown=nginx --from=composer /app /var/www/html
# COPY docker-conf/nginx.conf /etc/nginx/conf.d/server.conf
COPY docker-conf/php.ini /etc/php83/conf.d/settings.ini

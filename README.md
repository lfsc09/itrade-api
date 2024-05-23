[![Static Badge](https://img.shields.io/badge/licence-Apache--2.0-brightgreen)](https://github.com/lfsc09/itrade-api/blob/main/LICENSE)
![Static Badge](https://img.shields.io/badge/docker--compose-3.8-blue)
[![Static Badge](https://img.shields.io/badge/trafex/php--nginx-latest-blue)](https://github.com/TrafeX/docker-php-nginx)

## Generate config file for Slim

Generate the **config.php** from _config.example.php_:

```php
putenv('DISPLAY_ERRORS_DETAILS='. TRUE);

putenv('DB_HOSTNAME=localhost');
putenv('DB_PORT=3306');
putenv('DB_USER=root');
putenv('DB_PASS=%PASSWORD%');
putenv('DB_NAME=u631028490_iTrade');

putenv('JWT_SECRET_KEY=%ENCRYPTION_KEY_512%');
putenv('JWT_SECURE=' . TRUE);
```

</br>

## Deploy

It uses (https://github.com/TrafeX/docker-php-nginx) docker image for PHP-fpm with Nginx.

```bash
docker compose up -d
```

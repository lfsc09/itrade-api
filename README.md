[![Static Badge](https://img.shields.io/badge/license-Apache--2.0-brightgreen)](https://github.com/lfsc09/itrade-api/blob/main/LICENSE)
![Static Badge](https://img.shields.io/badge/docker--compose-3.9-blue)
[![Static Badge](https://img.shields.io/badge/trafex/php--nginx-latest-blue)](https://github.com/TrafeX/docker-php-nginx)
![Static Badge](https://img.shields.io/badge/mysql-latest-blue)

## Generate config file for Slim

Generate the **config.php** from _config.example.php_:

```php
putenv('DISPLAY_ERRORS_DETAILS='. TRUE);

putenv('DB_HOSTNAME=localhost');
putenv('DB_PORT=3306');
putenv('DB_USER=root');
putenv('DB_PASS=%PASSWORD%');
putenv('DB_NAME=u631028490_itrade');

putenv('JWT_SECRET_KEY=%ENCRYPTION_KEY_512%');
putenv('JWT_SECURE=' . FALSE);
```

> If you change the DB user password, also update it on `docker-conf/mysql/init/add-user.sql`.

> To use `JWT_SECURE` as `TRUE`, the Webserver container `Dockerfile` must be changed to listen for `ssl` connections. Also the `fullchain.pem` and `privkey.pem` must be provided to the container.

</br>

## Deploy

> Make sure you did the step above before running the `compose up`.

It uses (https://github.com/TrafeX/docker-php-nginx) docker image for PHP-fpm with Nginx.

```bash
docker compose up -d
```

### Local Testing

#### In Windows

> https://stackoverflow.com/questions/8652948/using-port-number-in-windows-host-file

Setting up the api url dns, for locally testing with front-end.

In the `hosts` file of Windows.

```properties
127.x.x.x api.itrade-dongs.com.br
```

```bash
netsh interface portproxy add v4tov4 listenport=80 listenaddress=127.x.x.x connectport=8001 connectaddress=192.168.0.4
```

You can test the choosen local IPv4 address with

```bash
netstat -a -n -p TCP | grep "LISTENING"
```

Show the proxy maps with

```bash
netsh interface portproxy show v4tov4
```

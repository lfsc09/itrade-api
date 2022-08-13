### BD

Para desfragmentar a contagem de _ID_ em **_rv_operacoes_**:

```
SET @a:=0; UPDATE rv__operacoes SET id=@a:=@a+1 ORDER BY id;
SET @lastID = (SELECT MAX(id)+1 FROM rv__operacoes);
SET @query = CONCAT('ALTER TABLE rv__operacoes AUTO_INCREMENT=', @lastID);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
```

### Para gerar ambientes locais (DEV)

1 - Configurar no Apache **_httpd.conf_**:

```
<VirtualHost dev.api.itrade-dongs.net:80>
    ServerName dev.api.itrade-dongs.net
    DocumentRoot "[C|E]:\xampp\htdocs\itrade-api\public"
</VirtualHost>
```

<br>

2 - Jogar no **_host_** do windows

```
127.0.0.1 dev.api.itrade-dongs.net
```

<br>

3 - Configurar o usuario de acesso no BD
<br>

4 - Exportar o BD:

```
mysqldump.exe -u root -pitrade@124 u631028490_iTrade > u631028490_iTrade.sql
```

**_Powershell_**

```
cmd.exe /c "mysqldump.exe -u root -pitrade@124 u631028490_iTrade > u631028490_iTrade.sql"
```

<br>

5 - Importar o BD:

```
mysql.exe -u root -pitrade@124 u631028490_iTrade < u631028490_iTrade.sql
```

**_Powershell_**

```
cmd.exe /c "mysql.exe -u root -pitrade@124 u631028490_iTrade < u631028490_iTrade.sql"
```

<br>

6 - Gerar o arquivo **config.php** a partir do _config.example.php_:

```
putenv('DISPLAY_ERRORS_DETAILS='. TRUE);

putenv('DB_HOSTNAME=localhost');
putenv('DB_PORT=3306');
putenv('DB_USER=root');
putenv('DB_PASS=itrade@124');
putenv('DB_NAME=u631028490_iTrade');

putenv('JWT_SECRET_KEY=NcQfTjWnZr4u7x!A%D*G-KaPdSgVkXp2s5v8y/B?E(H+MbQeThWmZq3t6w9z$C&F');
putenv('JWT_SECURE=' . FALSE);
```

### Para ambientes de produção (PROD)

6 - Gerar o arquivo **config.php** a partir do _config.example.php_:

```
putenv('DISPLAY_ERRORS_DETAILS='. TRUE);

putenv('DB_HOSTNAME=localhost');
putenv('DB_PORT=3306');
putenv('DB_USER=root');
putenv('DB_PASS=itrade@124');
putenv('DB_NAME=u631028490_iTrade');

putenv('JWT_SECRET_KEY=ENCRYPTION_KEY_512');
putenv('JWT_SECURE=' . TRUE);
```

> Gerar a ENCRYPTION_KEY_512 em (https://www.allkeysgenerator.com/Random/Security-Encryption-Key-Generator.aspx)

FROM mysql:latest

ENV MYSQL_ROOT_PASSWORD=sFg$8d7QW@sd

COPY docker-conf/mysql/add-user.sql /docker-entrypoint-initdb.d
COPY docker-conf/mysql/u631028490_itrade.schema.sql /docker-entrypoint-initdb.d
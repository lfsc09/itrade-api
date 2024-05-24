FROM mysql:latest

ENV MYSQL_ROOT_PASSWORD=sFg$8d7QW@sd

ADD docker-conf/mysql/add-user.sql /docker-entrypoint-initdb.d
ADD docker-conf/mysql/u631028490_itrade.schema.sql /docker-entrypoint-initdb.d
FROM debian:bullseye

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    syslog-ng \
    syslog-ng-mod-sql \
    libdbd-mysql \
    libdbi-perl \
    default-mysql-client \
    php-cli \
    php-mysqli \
    procps \
    dos2unix \
    && apt-get clean


COPY log_to_mysql.php /usr/local/bin/log_to_mysql.php
RUN chmod +x /usr/local/bin/log_to_mysql.php


CMD ["syslog-ng", "-F", "--no-caps"]

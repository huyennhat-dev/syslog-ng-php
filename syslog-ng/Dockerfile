FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Cài đặt các gói cần thiết
RUN apt-get update && apt-get install -y \
    syslog-ng \
    syslog-ng-mod-sql \
    libdbi1 \
    libdbd-mysql \
    php-cli \
    php-mysqli \
    default-mysql-client \
    procps \
    dos2unix \
    curl \
    vim \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy script PHP vào container
COPY log_to_mysql.php /usr/local/bin/log_to_mysql.php
RUN chmod +x /usr/local/bin/log_to_mysql.php

# Mặc định chạy syslog-ng ở foreground
CMD ["syslog-ng", "-F", "--no-caps"]

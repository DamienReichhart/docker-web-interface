FROM php:8.3-cli

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN echo 'memory_limit = 1000M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;
RUN echo 'post_max_size = 500M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;
RUN echo 'upload_max_filesize = 500M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions intl pdo_mysql gd

RUN apt-get update && apt-get install -y \
    git \
    unzip

WORKDIR /var/www/html

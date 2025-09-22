# Use official PHP 8.3 FPM image
FROM php:8.3-fpm


ARG UID=1015
ARG GID=1015


# Create the ssh keu pair
RUN apt-get update && apt-get install -y openssh-client && rm -rf /var/lib/apt/lists/*
RUN mkdir -p /root/.ssh
RUN ssh-keygen -t rsa -b 4096 -f /root/.ssh/id_rsa -N "" \
    && chmod 555 -R /root/.ssh \
    && chmod 555 /root/.ssh/id_rsa \
    && chmod 555 /root/.ssh/id_rsa.pub \
    && chmod 555 -R /root       ### @TODO Fix the permissions of the root directory


# Create the user and group for Apache rights on the socket
# Create the group with GID 1010
RUN addgroup --gid 1010 apache
# Create the user with UID 1010 and assign to the apache group
RUN adduser --uid 1010 --ingroup apache --system apache


# Create the group with specified GID
RUN addgroup --gid ${GID} php
# Create the user with specified UID and assign to the php group
RUN adduser --uid ${UID} --ingroup php --system php


# Add the PHP extension installer from GitHub
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/install-php-extensions



# Make the PHP extension installer executable and install required extensions
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions intl pdo_mysql gd ssh2


# Update and upgrade system packages, clean cache after to reduce image size
RUN apt-get update && apt-get upgrade -y && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo 'listen=/var/run/php.sock' >> /usr/local/etc/php-fpm.conf

# Configure PHP-FPM to listen on a Unix socket, with secure permissions to permit apache to connect
RUN sed -i 's|listen = .*|listen = /var/run/php/php-fpm.sock|g' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|;listen.owner = www-data|listen.owner = apache|g' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|;listen.group = www-data|listen.group = apache|g' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|;listen.mode = 0660|listen.mode = 0660|g' /usr/local/etc/php-fpm.d/www.conf

# Set the working directory
WORKDIR /var/www/html


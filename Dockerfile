FROM php:7.4-cli as php

FROM php AS build

# Use the default production configuration
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# libxml2: xml
# libedit: opcache
# git, zip: composer
# wget: mysql

RUN apt-get update && apt-get install -y \
    libedit-dev \
    git \
    zip

RUN docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql

FROM build as configure

RUN echo "date.timezone = UTC" > /usr/local/etc/php/conf.d/tz.ini \
 && echo "memory_limit = -1" > /usr/local/etc/php/conf.d/memory_limit.ini

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/local/bin/ --filename=composer;

WORKDIR /usr/src/api
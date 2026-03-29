FROM php:8.3-fpm-alpine

RUN apk add --no-cache git curl unzip icu-dev libxml2-dev \
    && docker-php-ext-install intl xml pdo_mysql

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM php:8.3-fpm-alpine

RUN apk add --no-cache git curl unzip icu-dev libxml2-dev \
    && docker-php-ext-install intl xml

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

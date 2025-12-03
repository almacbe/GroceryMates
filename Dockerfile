# syntax=docker/dockerfile:1

FROM composer:2.7 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --no-scripts --prefer-dist --no-interaction

COPY . .
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

FROM node:20-bullseye-slim AS node_builder
WORKDIR /app

COPY package*.json ./
RUN --mount=type=cache,target=/tmp/npm-cache \
    npm set cache /tmp/npm-cache --global \
 && npm ci

COPY . .
COPY --from=vendor /app/vendor /app/vendor
RUN npm run build

FROM php:8.2-apache-bullseye AS runtime

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=8080

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip \
    && a2enmod rewrite \
    && sed -ri 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/apache/laravel.conf /etc/apache2/sites-available/000-default.conf
COPY --from=vendor /app /var/www/html
COPY --from=node_builder /app/public/build /var/www/html/public/build

RUN printf "<?php\nrequire __DIR__ . '/public/index.php';\n" > /var/www/html/index.php

RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache public/build

EXPOSE 8080

CMD ["apache2-foreground"]

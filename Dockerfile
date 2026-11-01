# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:php8.3-bookworm AS base

WORKDIR /app

ENV SERVER_NAME=:80 \
    COMPOSER_ALLOW_SUPERUSER=1

RUN install-php-extensions \
    bcmath \
    intl \
    mbstring \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY Caddyfile /etc/caddy/Caddyfile

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

ARG INSTALL_DEV=false

RUN if [ "$INSTALL_DEV" = "true" ]; then \
        composer install --prefer-dist --no-interaction --no-progress --no-scripts; \
    else \
        composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts; \
    fi

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package*.json ./
RUN npm install --no-audit --no-fund

COPY resources ./resources
COPY public ./public
COPY vite.config.js .

RUN npm run build

FROM base AS application

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && composer dump-autoload --classmap-authoritative --no-dev --no-scripts

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]

FROM application AS development

RUN composer install --prefer-dist --no-interaction --no-progress --no-scripts \
    && composer dump-autoload --classmap-authoritative --no-scripts

ENV APP_ENV=local \
    APP_DEBUG=true

FROM application AS staging

ENV APP_ENV=staging \
    APP_DEBUG=false

FROM application AS production

ENV APP_ENV=production \
    APP_DEBUG=false
FROM dunglas/frankenphp:php8.3-bookworm AS base

# Install extensions Laravel & Octane
RUN install-php-extensions \
    pcntl \
    bcmath \
    opcache \
    pdo_mysql \
    redis \
    zip \
    intl

WORKDIR /app

# Ambil binary Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 1. Cache Layer Dependencies: Copy lockfile dulu
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# 2. Copy source code aplikasi setelah dependensi ter-cache
COPY . /app

# Jalankan dump-autoload akhir & package discovery
RUN composer dump-autoload --optimize --classmap-authoritative

# Setup permission direktori storage dan cache
RUN mkdir -p /app/storage/framework/{sessions,views,cache} /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache
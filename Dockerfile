FROM dunglas/frankenphp:php8.3-bookworm AS base

# Install extensions yang diperlukan Laravel & Octane
RUN install-php-extensions \
    pcntl \
    bcmath \
    opcache \
    pdo_mysql \
    redis \
    zip \
    intl

WORKDIR /app

# Copy seluruh source code
COPY . /app

# Install dependency tanpa dev packages
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permission storage & bootstrap cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Direct entrypoint FrankenPHP Octane Worker
ENV SERVER_NAME=":80"
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

# Jalankan via Octane CLI atau langsung FrankenPHP
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=80", "--workers=auto"]
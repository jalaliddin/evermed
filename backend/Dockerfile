FROM php:8.3-fpm-alpine

# ── System dependencies ────────────────────────────────────────────────────────
RUN apk add --no-cache \
    curl git unzip \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libxml2-dev libzip-dev \
    oniguruma-dev \
    icu-dev \
    mysql-client \
    linux-headers

# ── PHP extensions ─────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) \
      pdo_mysql mbstring exif pcntl bcmath gd zip xml intl

# ── Composer ───────────────────────────────────────────────────────────────────
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ── App dependencies (cached layer) ───────────────────────────────────────────
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --prefer-dist \
      --optimize-autoloader

# ── App source ─────────────────────────────────────────────────────────────────
COPY . .

# Finalize autoloader and set permissions
RUN composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/framework/{sessions,views,cache} \
              storage/app/public \
              storage/logs \
              bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# ── Entrypoint ─────────────────────────────────────────────────────────────────
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

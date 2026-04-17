# ═══════════════════════════════════════════
# STAGE 1: Node — сборка JS/CSS assets
# ═══════════════════════════════════════════
# ═══════════════════════════════════════════
# STAGE 0: Composer — установка PHP зависимостей
# ═══════════════════════════════════════════
FROM php:8.4-alpine AS composer-builder

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --ignore-platform-reqs


# ═══════════════════════════════════════════
# STAGE 1: Node — сборка JS/CSS assets
# ═══════════════════════════════════════════
FROM node:20-alpine AS node-builder

WORKDIR /app

# Берём vendor/ из composer-builder (включая vendor/livewire/flux)
COPY --from=composer-builder /app/vendor ./vendor

# package файлы — кэш слоя
COPY package*.json ./
RUN npm ci

# Копируем исходники и собираем assets
COPY . .
RUN npm run build


# ═══════════════════════════════════════════
# STAGE 2: PHP Production
# ═══════════════════════════════════════════
FROM php:8.4-fpm-alpine AS production

# Системные зависимости (alpine — минимальный набор)
RUN apk add --no-cache \
    git \
    unzip \
    postgresql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev

# PHP расширения
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        gd \
        zip \
        soap \
        pdo_pgsql \
        pcntl \
        intl \
        opcache

# OPcache настройки для production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/default

# Сначала только composer файлы — слой кэшируется если код не менялся
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction

# Копируем исходники Laravel
COPY . .

# Берём скомпилированные assets из Stage 1 (Node не попадает в образ)
COPY --from=node-builder /app/public/build ./public/build

# Права для Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Переключаемся на non-root пользователя
USER www-data

CMD ["/usr/local/sbin/php-fpm"]

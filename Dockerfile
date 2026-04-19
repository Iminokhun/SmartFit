# ═══════════════════════════════════════════
# STAGE 0: Composer — PHP зависимости
# ═══════════════════════════════════════════
FROM php:8.4-alpine AS composer-builder

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Нужны для некоторых composer пакетов
RUN apk add --no-cache git unzip

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --ignore-platform-reqs \
    --optimize-autoloader


# ═══════════════════════════════════════════
# STAGE 1: Node — сборка JS/CSS assets
# ═══════════════════════════════════════════
FROM node:20-alpine AS node-builder

WORKDIR /app

# vendor/ нужен для Flux CSS (vendor/livewire/flux/dist/flux.css)
COPY --from=composer-builder /app/vendor ./vendor

# Кэш слоя: package файлы отдельно от исходников
COPY package*.json ./
RUN npm ci

# Копируем исходники и собираем
COPY . .
RUN npm run build


# ═══════════════════════════════════════════
# STAGE 2: Production
# ═══════════════════════════════════════════
FROM php:8.4-fpm-alpine AS production

# Системные зависимости
RUN apk add --no-cache \
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
        opcache \
        redis

# OPcache для production
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=256"; \
    echo "opcache.max_accelerated_files=20000"; \
    echo "opcache.validate_timestamps=0"; \
} >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/default

# vendor/ из Stage 0 (не пересобираем)
COPY --from=composer-builder /app/vendor ./vendor

# Исходники Laravel
COPY . .

# Скомпилированные assets из Stage 1
COPY --from=node-builder /app/public/build ./public/build

# Создаём все нужные папки Laravel и выставляем права
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Non-root пользователь
USER www-data

CMD ["/usr/local/sbin/php-fpm"]

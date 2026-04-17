#!/bin/bash
set -e

# Скрипт для ПЕРВОГО получения SSL сертификата на сервере.
# Запускать один раз после первого деплоя.

DOMAIN="smartfit.uz"
EMAIL=""  
COMPOSE="docker compose -f docker-compose.prod.yml"

echo "==> Step 1: Starting Nginx with HTTP-only config..."
cp docker/nginx/conf.d/app.conf docker/nginx/conf.d/app.conf.bak
cp docker/nginx/conf.d/app-init.conf docker/nginx/conf.d/app.conf
$COMPOSE up -d gym-webserver

echo "==> Step 2: Getting SSL certificate from Let's Encrypt..."
$COMPOSE run --rm certbot certonly \
    --webroot \
    --webroot-path /var/www/certbot \
    --email $EMAIL \
    --agree-tos \
    --no-eff-email \
    -d $DOMAIN \
    -d www.$DOMAIN

echo "==> Step 3: Restoring HTTPS Nginx config..."
cp docker/nginx/conf.d/app.conf.bak docker/nginx/conf.d/app.conf

echo "==> Step 4: Restarting Nginx with SSL..."
$COMPOSE restart gym-webserver

echo "==> SSL certificate installed! Site available at https://$DOMAIN"

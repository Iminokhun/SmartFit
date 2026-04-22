#!/bin/bash
set -e

# Скрипт для ПЕРВОГО получения SSL сертификата на сервере.
# Запускать один раз после первого деплоя.

DOMAIN="qoyilmaqom.uz"
COMPOSE="docker compose -f docker-compose.prod.yml"

echo "==> Step 1: Starting Nginx with HTTP-only init config..."
cp docker/nginx/conf.d/app.conf docker/nginx/conf.d/app.conf.bak
cp docker/nginx/ssl/app-init.conf docker/nginx/conf.d/app.conf
$COMPOSE up -d gym-webserver
$COMPOSE exec gym-webserver nginx -s reload 2>/dev/null || $COMPOSE restart gym-webserver

echo "==> Step 2: Getting SSL certificate from Let's Encrypt..."
$COMPOSE run --rm --entrypoint certbot certbot certonly \
    --webroot \
    --webroot-path /var/www/certbot \
    --register-unsafely-without-email \
    --agree-tos \
    -d $DOMAIN

echo "==> Step 3: Switching to HTTPS Nginx config..."
cp docker/nginx/ssl/app-ssl.conf docker/nginx/conf.d/app.conf

echo "==> Step 4: Restarting Nginx with SSL..."
$COMPOSE restart gym-webserver

echo "==> SSL certificate installed! Site available at https://$DOMAIN"

#!/bin/bash
set -e

DOMAIN="smartfit.uz"
EMAIL="" 
COMPOSE="docker compose -f docker-compose.prod.yml"

echo "==> Pulling latest images..."
$COMPOSE pull

echo "==> Stopping old containers..."
$COMPOSE down

echo "==> Removing public_data volume (assets rebuilt in new image)..."
docker volume rm gym_erp_public_data 2>/dev/null || true

echo "==> Starting containers..."
$COMPOSE up -d

echo "==> Waiting for gym-app to be ready..."
sleep 5

echo "==> Running migrations..."
$COMPOSE exec gym-app php artisan migrate --force

echo "==> Clearing cache..."
$COMPOSE exec gym-app php artisan optimize:clear
$COMPOSE exec gym-app php artisan optimize

echo "==> Done! App is running at https://$DOMAIN"

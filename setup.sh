#!/bin/bash
set -e

COMPOSE="docker compose -f docker-compose.prod.yml"

echo "==> Checking .env file..."
if [ ! -f .env ]; then
    echo "ERROR: .env file not found. Run: cp .env.example .env && nano .env"
    exit 1
fi

echo "==> Generating APP_KEY..."
APP_KEY=$(openssl rand -base64 32)
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$APP_KEY|" .env
echo "    APP_KEY generated and saved to .env"

echo "==> Building Docker image..."
$COMPOSE build

echo "==> Starting containers..."
$COMPOSE up -d

echo "==> Waiting for database to be ready..."
sleep 10

echo "==> Running migrations..."
$COMPOSE exec -T gym-app php artisan migrate --force

echo "==> Optimizing..."
$COMPOSE exec -T gym-app php artisan optimize:clear
$COMPOSE exec -T gym-app php artisan optimize

echo ""
echo "==> Setup complete!"
echo "==> App is running at http://$(curl -s ifconfig.me 2>/dev/null || echo 'YOUR_SERVER_IP')"
echo ""
echo "==> Check status: docker compose -f docker-compose.prod.yml ps"

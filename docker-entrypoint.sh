#!/bin/bash
set -e

echo "==> Clearing caches..."
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Running seeders..."
php artisan db:seed --force

echo "==> Starting server on port 10000..."
exec php -S 0.0.0.0:10000 -t public
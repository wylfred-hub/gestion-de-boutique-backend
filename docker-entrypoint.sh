#!/bin/bash
set -e

echo "==> Checking PHP extensions..."
php -m | grep -i pgsql || echo "!!! pgsql extensions NOT found !!!"
php -r "var_dump(PDO::getAvailableDrivers());"

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Running seeders..."
php artisan db:seed --force

echo "==> Caching config for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting server on port 10000..."
exec php -S 0.0.0.0:10000 -t public
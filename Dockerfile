FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git unzip curl zip libzip-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 777 storage bootstrap/cache

EXPOSE 10000

# Migrer puis démarrer le serveur
CMD php artisan migrate --force && php artisan config:clear && php -S 0.0.0.0:10000 -t public
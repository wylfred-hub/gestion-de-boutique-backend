FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    zip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install \
       pdo \
       pdo_pgsql \
       pgsql \
       bcmath \
       zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan migrate --force && \
    php -S 0.0.0.0:10000 -t public
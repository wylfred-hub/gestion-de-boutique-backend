FROM php:8.2-cli

WORKDIR /app

# 1. Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    zip \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Configurer et installer l'extension GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# 3. Installer les autres extensions PHP nécessaires
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    bcmath \
    zip

# 4. Récupérer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Copier les fichiers du projet
COPY . .

# 6. Installer les dépendances
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 7. Permissions Laravel
RUN chmod -R 775 storage bootstrap/cache

# 8. Script de démarrage
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
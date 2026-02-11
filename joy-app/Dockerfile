FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libicu-dev libzip-dev libpq-dev nodejs npm \
    && docker-php-ext-install intl zip pdo_pgsql pgsql opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-scripts --no-interaction

# Install Node dependencies and build
RUN npm ci && npm run build

# Expose port
EXPOSE 8080

# Start
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}

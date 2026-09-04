FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo pdo_mysql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}

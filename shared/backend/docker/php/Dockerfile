FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y git unzip libicu-dev libzip-dev libpng-dev libonig-dev libxml2-dev && docker-php-ext-install pdo_mysql bcmath intl mbstring opcache zip && pecl install redis && docker-php-ext-enable redis && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
COPY . .
RUN chown -R www-data:www-data storage bootstrap/cache
CMD ["php-fpm"]

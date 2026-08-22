FROM php:8.4-cli

RUN apt-get update && apt-get install -y libpq-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY . .

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "index.php"]

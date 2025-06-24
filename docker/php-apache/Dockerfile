# Stage di builder: Composer
FROM composer:2 AS builder

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Stage finale: PHP-Apache
FROM php:8.2.0-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN a2enmod rewrite

# Imposta la DocumentRoot a /var/www/html/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Installa estensioni grafiche GD
RUN apt-get update \
    && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copia vendor da builder
COPY --from=builder /app/vendor /var/www/html/vendor
COPY --from=builder /app/composer.json /var/www/html/composer.json
COPY --from=builder /app/composer.lock /var/www/html/composer.lock
COPY --from=builder /usr/bin/composer /usr/bin/composer

EXPOSE 80

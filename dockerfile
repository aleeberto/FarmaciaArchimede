# Usa un'immagine base PHP con Apache
FROM php:7.4-apache

# Abilita il modulo mod_rewrite per Apache (utile per le URL dinamiche in PHP)
RUN a2enmod rewrite

# Installa le dipendenze necessarie per PHP e MariaDB (MySQL)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libmariadb-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Copia il codice PHP dalla tua cartella "src" nella cartella del server web di Apache
COPY ./src/ /var/www/html/

# Imposta i permessi giusti
RUN chown -R www-data:www-data /var/www/html

# Avvia Apache in foreground (per mantenere il container in esecuzione)
CMD ["apache2-foreground"]

# Usa l'ultima versione di PHP con Apache
FROM php:8.2-apache

# Abilitare il modulo rewrite di Apache
RUN a2enmod rewrite

# Aggiornare i pacchetti e installare le librerie necessarie
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libmariadb-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mysqli

# Copiare il codice sorgente nell'immagine Docker
COPY ./src/ /var/www/html/

# Dare i permessi alla cartella del codice
RUN chown -R www-data:www-data /var/www/html

# Impostare il comando di avvio del container
CMD ["apache2-foreground"]

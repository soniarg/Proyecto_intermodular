# ETAPA 1: Construir el Frontend
FROM node:20 AS frontend-builder
WORKDIR /app
COPY frontend/package*.json ./
RUN npm install
COPY frontend/ ./
RUN npm run build

# ETAPA 2: Configurar Backend (Laravel + Apache)
FROM php:8.2-apache

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Configurar Apache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar backend
COPY backend/ .
RUN composer install --no-dev --optimize-autoloader

# Traer el frontend compilado
COPY --from=frontend-builder /app/dist/ /var/www/html/public/

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Script de arranque
RUN echo '#!/bin/bash\nphp artisan migrate --force\napache2-foreground' > /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80
CMD ["/usr/local/bin/start.sh"]
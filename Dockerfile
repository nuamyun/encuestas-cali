# Imagen de la aplicación: PHP 8.4 + Apache
FROM php:8.4-apache

# Extensión para MySQL y mod_rewrite para las rutas amigables
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "date.timezone=America/Bogota" > "$PHP_INI_DIR/conf.d/timezone.ini" \
    && echo "expose_php=Off" > "$PHP_INI_DIR/conf.d/security.ini"

# Apache sirve solo la carpeta public/ (el código fuente no queda expuesto)
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencias primero, para aprovechar la caché de capas de Docker
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

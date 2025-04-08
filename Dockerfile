FROM php:8.2-fpm


# Instalar el instalador de extensiones

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/


# Instalar mbstring y dependencias

RUN chmod +x /usr/local/bin/install-php-extensions && \

install-php-extensions mbstring

# Instalar dependencias y extensiones PHP necesarias

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    unzip \
    postgresql-client \
    libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    zip \
    exif \
    gd \
    opcache
# Instalar composer

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# Configurar directorio de trabajo

WORKDIR /var/www/html


# Copiar archivos de la aplicación

COPY . .


# Instalar dependencias de producción

RUN composer install --optimize-autoloader --no-dev


# Optimizar Laravel

RUN php artisan config:cache && \

php artisan route:cache && \

php artisan view:cache && \

php artisan storage:link
# Configurar permisos

RUN chown -R $(id -u www-data):$(id -u www-data) /var/www/html/storage /var/www/html/bootstrap/cache


# Configuración de PHP para producción

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"


# Exponer puerto

EXPOSE 9000


# Copiar el script de entrada
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Ejecutar el script de entrada al iniciar el contenedor
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Comando para iniciar PHP-FPM (esto ahora se maneja en el entrypoint)
# CMD ["php-fpm"]

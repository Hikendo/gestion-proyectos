FROM php:8.4-fpm

# Instalar dependencias del sistema requeridas para tus paquetes actuales y extensiones nuevas
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    $PHPIZE_DEPS \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP nativas (Manteniendo tus requerimientos de phpoffice + intl para Laravel 13)
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Instalar y habilitar de forma limpia la extensión de Redis vía PECL (Exigido por Horizon)
RUN pecl install redis && docker-php-ext-enable redis

# Copiar Composer desde la imagen oficial más reciente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Solución para problemas de propiedad de Git en entornos compartidos/Docker
RUN git config --global --add safe.directory /var/www

# Configurar el entrypoint original de tu aplicación
COPY docker-entrypoint.sh /var/www/docker-entrypoint.sh
RUN chmod +x /var/www/docker-entrypoint.sh

# Copiar el código de la aplicación
COPY . .

# Asegurar permisos correctos sobre los directorios de escritura de Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Horizon y FPM exigen correr bajo el usuario nativo del servidor web por seguridad
USER www-data

EXPOSE 9000

ENTRYPOINT ["/var/www/docker-entrypoint.sh"]
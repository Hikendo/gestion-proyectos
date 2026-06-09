FROM php:8.4-fpm

# Instalar dependencias del sistema
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

# Instalar extensiones de PHP
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

# Instalar Redis
RUN pecl install redis && docker-php-ext-enable redis

# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Configurar Git safe directory
RUN git config --global --add safe.directory /var/www

# Copiar entrypoint (primero, antes de copiar el código)
COPY docker-entrypoint.sh /var/www/docker-entrypoint.sh
RUN chmod +x /var/www/docker-entrypoint.sh

# Copiar el código de la aplicación
COPY . .

# Crear directorios necesarios y establecer permisos iniciales
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache /var/www/.composer && \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/.composer && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/.composer

EXPOSE 9000

ENTRYPOINT ["/var/www/docker-entrypoint.sh"]
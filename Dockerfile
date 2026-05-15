FROM php:8.5-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Limpiar caché de apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www

# Copiar el código de la aplicación al contenedor
COPY . .

# ⚠️ CLAVE: Asegurar permisos de ejecución para el script de entrada
RUN chmod +x /var/www/docker-entrypoint.sh

# Dar permisos a las carpetas de almacenamiento de Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Exponer el puerto interno donde correrá 'php artisan serve'
EXPOSE 8000

# Ejecutar el script de entrada
ENTRYPOINT ["/var/www/docker-entrypoint.sh"]

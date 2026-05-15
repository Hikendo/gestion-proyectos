#!/bin/bash

# Instalar dependencias
composer install --no-interaction --optimize-autoloader

# Esperar unos segundos a que el contenedor de MySQL termine de iniciar
echo "Esperando que la base de datos esté lista..."
sleep 5

# Forzar migraciones sin confirmación interactiva
php artisan migrate --force

# Limpiar cachés para desarrollo limpio
php artisan config:clear
php artisan route:clear

# 🚀 CAMBIO AQUÍ: Usar el servidor nativo de PHP en segundo plano de forma estable
echo "Iniciando servidor PHP en el puerto 8000..."
exec php -S 0.0.0.0:8000 -t public

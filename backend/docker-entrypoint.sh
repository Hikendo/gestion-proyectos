#!/bin/bash

set -e

git config --global --add safe.directory /var/www 2>/dev/null || true

echo "================================================"
echo " Instalando dependencias..."
echo "================================================"
composer install --no-interaction --optimize-autoloader

echo "================================================"
echo " Generando APP_KEY si no existe..."
echo "================================================"
if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ]; then
    php artisan key:generate --force
    echo " ✓ APP_KEY generado."
else
    echo " → APP_KEY ya existe, omitido."
fi

echo "================================================"
echo " Esperando que la base de datos esté lista..."
echo "================================================"
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-gestion_user}"
DB_PASSWORD="${DB_PASSWORD:-gestion_password_local}"
DB_DATABASE="${DB_DATABASE:-gestion_db}"

until php -r "
    \$conn = @new mysqli('${DB_HOST}', '${DB_USERNAME}', '${DB_PASSWORD}', '${DB_DATABASE}', ${DB_PORT});
    if (\$conn->connect_error) exit(1);
    exit(0);
" 2>/dev/null; do
    echo " → DB no disponible, reintentando en 2s..."
    sleep 2
done

echo " ✓ Base de datos disponible."

echo "================================================"
echo " Ejecutando migraciones pendientes..."
echo "================================================"
php artisan migrate --force
echo " ✓ Migraciones al día."

echo "================================================"
echo " Ejecutando seeders (solo si es primera vez)..."
echo "================================================"
# Consultamos si ya existen roles en la base de datos
ROLES_COUNT=$(php artisan tinker --no-interaction \
    --execute="echo \Spatie\Permission\Models\Role::count();" \
    2>/dev/null | tail -1 | tr -d '[:space:]')

if [ "$ROLES_COUNT" = "0" ] || [ -z "$ROLES_COUNT" ]; then
    echo " → Ejecutando RolesAndPermissionsSeeder..."
    php artisan db:seed --class=RolesAndPermissionsSeeder --force
    echo " ✓ Roles y permisos creados."
else
    echo " → Roles ya existen ($ROLES_COUNT), seeder omitido."
fi

echo "================================================"
echo " Limpiando y optimizando cachés..."
echo "================================================"
php artisan optimize:clear

echo "================================================"
echo " Iniciando PHP-FPM..."
echo "================================================"
# Nginx necesita PHP-FPM corriendo en el puerto 9000 internamente
exec php-fpm -F

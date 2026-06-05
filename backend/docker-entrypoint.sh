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
# Solo generar si no viene en el entorno NI en el .env
ENV_KEY=$(grep -E '^APP_KEY=' /var/www/.env 2>/dev/null | cut -d= -f2 | tr -d '[:space:]')
if [ -z "${APP_KEY}" ] && ( [ -z "${ENV_KEY}" ] || [ "${ENV_KEY}" = "SomeRandomString" ] ); then
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
# Consultamos si ya existen roles en la base de datos (sin bootstrap de Laravel
# para evitar que tinker se quede colgado en versiones recientes de PsySH)
ROLES_COUNT=$(php -r "
    \$conn = @new mysqli('${DB_HOST}', '${DB_USERNAME}', '${DB_PASSWORD}', '${DB_DATABASE}', ${DB_PORT});
    if (\$conn->connect_error) { echo '0'; exit; }
    \$result = \$conn->query('SELECT COUNT(*) AS cnt FROM roles');
    if (\$result) { \$row = \$result->fetch_assoc(); echo \$row['cnt']; } else { echo '0'; }
" 2>/dev/null || echo "0")

if [ "$ROLES_COUNT" = "0" ] || [ -z "$ROLES_COUNT" ]; then
    echo " → Ejecutando RolesAndPermissionsSeeder..."
    php artisan db:seed --class=RolesAndPermissionsSeeder --force
    echo " ✓ Roles y permisos creados."
else
    echo " → Roles ya existen ($ROLES_COUNT), seeder omitido."
fi

echo "================================================"
echo " Configurando permisos de Storage y Cache..."
echo "================================================"
# Forzamos que pertenezcan a www-data por encima del volumen montado
# El 2>/dev/null || true silencia errores de bind-mount en WSL2/Windows
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
echo " ✓ Permisos corregidos para el servidor web."

echo "================================================"
echo " Limpiando y optimizando cachés..."
echo "================================================"
php artisan optimize:clear

# ─────────────────────────────────────────────────────────────
# MODO DE EJECUCIÓN
# ─────────────────────────────────────────────────────────────
# Si se pasa un comando personalizado (ej: "php artisan horizon"),
# se ejecuta ese comando en lugar de PHP-FPM.
# Esto permite que el contenedor horizon use la misma imagen
# pero ejecute un proceso diferente.
if [ $# -gt 0 ]; then
    echo "================================================"
    echo " Ejecutando comando personalizado: $@"
    echo "================================================"
    exec "$@"
fi

echo "================================================"
echo " Iniciando PHP-FPM..."
echo "================================================"
# Nginx necesita PHP-FPM corriendo en el puerto 9000 internamente
exec php-fpm -F

#!/usr/bin/env bash
set -euo pipefail

# ══════════════════════════════════════════════════════════════
# 02-deploy-backend.sh
# Sube el proyecto completo al servidor y despliega el stack de
# producción (backend + horizon + scheduler + nginx + reverb +
# redis + mysql) con docker-compose.prod.yml.
#
#  • No sobreescribe backend/.env si ya existe en el servidor.
#  • Ejecuta migraciones, crea usuarios base y optimiza cachés.
# ══════════════════════════════════════════════════════════════

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$DEPLOY_DIR/config.sh"
_require_domain

LOCAL_REPO="$(cd "${DEPLOY_DIR}/.." && pwd)"
LOCAL_BACKEND="${LOCAL_REPO}/backend"
REMOTE_BACKEND="${REMOTE_ROOT}/backend"
SSH_BASE=(-p "${SSH_PORT}" "${SSH_USER}@${SSH_HOST}")

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'

# ─────────────────────────────────────────────────────────────
# 1) Preparar el .env del backend (local, temporal)
#    Prioridad: deploy/.env.production  >  backend/.env (adaptado)
#    >  deploy/env.production.example
# ─────────────────────────────────────────────────────────────
TMP_ENV="$(mktemp)"
TMP_CONF="$(mktemp)"
trap 'rm -f "${TMP_ENV}" "${TMP_CONF}"' EXIT

if [ -f "${DEPLOY_DIR}/.env.production" ]; then
    echo -e "${BLUE}>>> Usando deploy/.env.production como .env remoto${NC}"
    cp "${DEPLOY_DIR}/.env.production" "${TMP_ENV}"
elif [ -f "${LOCAL_BACKEND}/.env" ]; then
    echo -e "${BLUE}>>> Adaptando backend/.env local a producción (conserva FIREBASE/REVERB/etc.)${NC}"
    sed -E \
        -e 's|^APP_NAME=.*|APP_NAME="Gestión de Proyectos"|' \
        -e 's|^APP_ENV=.*|APP_ENV=production|' \
        -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
        -e "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" \
        -e 's|^APP_LOCALE=.*|APP_LOCALE=es|' \
        -e 's|^LOG_LEVEL=.*|LOG_LEVEL=warning|' \
        -e "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=.${DOMAIN}|" \
        -e "s|^SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=${DOMAIN}|" \
        -e "s|^RESEND_FROM_EMAIL=.*|RESEND_FROM_EMAIL=\"noreply@${DOMAIN}\"|" \
        "${LOCAL_BACKEND}/.env" > "${TMP_ENV}"
else
    echo -e "${YELLOW}>>> No hay .env local: uso la plantilla deploy/env.production.example${NC}"
    sed -E "s|__DOMAIN__|${DOMAIN}|g" "${DEPLOY_DIR}/env.production.example" > "${TMP_ENV}"
fi

# Generar APP_KEY si está vacía (solo para plantillas sin clave)
if grep -q '^APP_KEY=$' "${TMP_ENV}"; then
    NEW_KEY="base64:$(openssl rand -base64 32)"
    sed -i "s|^APP_KEY=$|APP_KEY=${NEW_KEY}|" "${TMP_ENV}"
    echo -e "${GREEN}>>> APP_KEY generada${NC}"
fi

# ─────────────────────────────────────────────────────────────
# 2) Sincronizar el código (todo el repo, excluyendo pesados y .env)
# ─────────────────────────────────────────────────────────────
echo -e "${BLUE}>>> Creando directorio remoto ${REMOTE_ROOT} ...${NC}"
ssh "${SSH_BASE[@]}" "mkdir -p '${REMOTE_ROOT}' '${REMOTE_BACKEND}'"

echo -e "${BLUE}>>> Sincronizando proyecto...${NC}"
rsync -avz --delete \
    -e "ssh -p ${SSH_PORT}" \
    --exclude '.git' \
    --exclude 'deploy' \
    --exclude '.env' \
    --exclude 'gestion_db' \
    --exclude 'vendor' \
    --exclude 'node_modules' \
    --exclude 'dist' \
    --exclude 'storage/app/*' \
    --exclude 'storage/logs/*' \
    --exclude 'storage/framework/cache/data/*' \
    --exclude 'test-results' \
    --exclude 'playwright-report' \
    --exclude '.phpunit.result.cache' \
    --exclude '.composer' \
    "${LOCAL_REPO}/" "${SSH_USER}@${SSH_HOST}:${REMOTE_ROOT}/"

echo -e "${BLUE}>>> Subiendo docker-compose.prod.yml...${NC}"
scp -P "${SSH_PORT}" "${DEPLOY_DIR}/docker-compose.prod.yml" \
    "${SSH_USER}@${SSH_HOST}:${REMOTE_ROOT}/docker-compose.prod.yml"

# ─────────────────────────────────────────────────────────────
# 3) Instalar backend/.env SOLO si no existe (primer deploy)
# ─────────────────────────────────────────────────────────────
if ssh "${SSH_BASE[@]}" "test -f '${REMOTE_BACKEND}/.env'"; then
    echo -e "${YELLOW}>>> backend/.env ya existe en el servidor (no se sobreescribe)${NC}"
    echo "    Para regenerarlo: rm ${REMOTE_BACKEND}/.env y vuelve a ejecutar."
else
    scp -P "${SSH_PORT}" "${TMP_ENV}" "${SSH_USER}@${SSH_HOST}:${REMOTE_BACKEND}/.env"
    echo -e "${GREEN}>>> backend/.env creado en el servidor${NC}"
fi

# El .env debe ser legible por www-data (PHP-FPM). El archivo se sube como
# root con permisos 600, lo que rompe el runtime (Laravel no lo lee → cae a
# sqlite / sin APP_KEY). Forzamos lectura para todos los usuarios.
ssh "${SSH_BASE[@]}" "chmod 644 '${REMOTE_BACKEND}/.env'"

# ─────────────────────────────────────────────────────────────
# 4) Script remoto: build, up, migraciones, seed y cachés
# ─────────────────────────────────────────────────────────────
echo -e "${BLUE}>>> Desplegando en el servidor...${NC}"
ssh "${SSH_BASE[@]}" 'bash -s' -- \
    "${REMOTE_ROOT}" "${DOMAIN}" "${DB_DATABASE}" "${DB_USERNAME}" "${SEED_DEMO:-0}" <<'REMOTE'
set -euo pipefail

ROOT="$1"; DOMAIN="$2"; DB_DATABASE="$3"; DB_USERNAME="$4"; SEED_DEMO="$5"
cd "$ROOT"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
COMPOSE=(docker compose -f docker-compose.prod.yml)

# ── Secrets para interpolación de compose ───────────────────
# Si .env no existe → genera passwords aleatorios (primera vez).
# Si existe → conserva los passwords y refresca el dominio/BD.
if [ -f ".env" ]; then
    echo -e "${YELLOW}>>> ${ROOT}/.env existe: conservo passwords, refresco DOMAIN/BD${NC}"
    # shellcheck disable=SC1090
    . ./.env 2>/dev/null || true
fi
: "${DB_PASSWORD:=$(openssl rand -hex 16)}"
: "${DB_ROOT_PASSWORD:=$(openssl rand -hex 20)}"
umask 177
cat > .env <<EOF
DOMAIN=${DOMAIN}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
EOF
[ "$(stat -c '%a' .env)" != "600" ] && chmod 600 .env || true
echo -e "${GREEN}>>> ${ROOT}/.env listo (passwords ${YELLOW}ocultos${GREEN})${NC}"

# ── Build e inicio del stack de producción ───────────────────
echo -e "${BLUE}>>> Construyendo y levantando contenedores (puede tardar la 1ª vez)...${NC}"
"${COMPOSE[@]}" up -d --build

# ── Migraciones (el entrypoint ya las hace; aseguramos) ──────
echo -e "${BLUE}>>> Ejecutando migraciones...${NC}"
"${COMPOSE[@]}" exec -T backend php artisan migrate --force

echo -e "${BLUE}>>> Creando enlace simbólico de storage...${NC}"
"${COMPOSE[@]}" exec -T backend php artisan storage:link || true

# ── Seeders: usuarios base siempre; demo opcional con SEED_DEMO=1
echo -e "${BLUE}>>> Asegurando usuarios base (superadmin@test.com / password)...${NC}"
"${COMPOSE[@]}" exec -T backend php artisan db:seed --class=UserSeeder --force || \
    "${COMPOSE[@]}" exec -T backend php artisan db:seed --class=UserSeeder || true

if [ "$SEED_DEMO" = "1" ]; then
    echo -e "${BLUE}>>> Sembrando proyectos demo (SEED_DEMO=1)...${NC}"
    "${COMPOSE[@]}" exec -T backend php artisan db:seed --class=DemoProjectsSeeder --force || true
    "${COMPOSE[@]}" exec -T backend php artisan db:seed --class=CrmBackRefactorSeeder --force || true
fi

# ── Optimización de cachés ────────────────────────────────────
echo -e "${BLUE}>>> Optimizando cachés de Laravel...${NC}"
"${COMPOSE[@]}" exec -T backend php artisan optimize:clear
"${COMPOSE[@]}" exec -T backend php artisan config:cache || true
"${COMPOSE[@]}" exec -T backend php artisan route:cache || true
"${COMPOSE[@]}" exec -T backend php artisan view:cache || true

# ── Verificación local ────────────────────────────────────────
sleep 2
echo -e "${BLUE}>>> Verificando API en 127.0.0.1:8000 ...${NC}"
if curl -fsS -o /dev/null "http://127.0.0.1:8000/api/v1/ping" 2>/dev/null; then
    echo -e "${GREEN}✅ API respondiendo${NC}"
else
    curl -sS -o /dev/null "http://127.0.0.1:8000/" && echo -e "${GREEN}✅ Nginx/API arriba (raíz)${NC}" \
        || echo -e "${YELLOW}⚠️  La API aún no responde; revisa: docker compose -f docker-compose.prod.yml logs -f${NC}"
fi

# ── Recarga de nginx del host ─────────────────────────────────
if command -v nginx >/dev/null 2>&1; then
    nginx -t && systemctl reload nginx || true
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✅ Backend desplegado en ${DOMAIN}${NC}"
echo -e "${GREEN}========================================${NC}"
echo "• Usuario admin: superadmin@test.com / password"
echo "• Logs: docker compose -f docker-compose.prod.yml logs -f"
echo "• Estado: docker compose -f docker-compose.prod.yml ps"
REMOTE

echo -e "${GREEN}✅ Proceso 02 completado.${NC}"


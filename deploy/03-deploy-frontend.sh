#!/usr/bin/env bash
set -euo pipefail

# ══════════════════════════════════════════════════════════════
# 03-deploy-frontend.sh
#  • Compila el frontend localmente (Vue + Vite) con las
#    variables de producción (API relativa + Reverb wss).
#  • Sube solo dist/ al servidor vía rsync.
#  • Instala/actualiza la config del nginx del HOST
#    (http si aún no hay certificado; https si ya existe).
# ══════════════════════════════════════════════════════════════

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$DEPLOY_DIR/config.sh"
_require_domain

LOCAL_REPO="$(cd "${DEPLOY_DIR}/.." && pwd)"
LOCAL_FRONT="${LOCAL_REPO}/frontend"
REMOTE_DIST="${REMOTE_ROOT}/frontend/dist"
SSH_BASE=(-p "${SSH_PORT}" "${SSH_USER}@${SSH_HOST}")

# Compilamos a un directorio temporal (no tocamos frontend/dist local,
# que puede no ser escribible o estar en uso por el dev en Docker).
BUILD_OUT="$(mktemp -d "/tmp/gp-frontend.XXXXXX")"
LOCAL_DIST="${BUILD_OUT}"
# mktemp crea el directorio en 0700; lo dejamos legible para que rsync no
# propague permisos restrictivos al dist/ del servidor (nginx corre como www-data).
chmod 755 "${BUILD_OUT}"
trap 'rm -rf "${BUILD_OUT}" "${TMP_CONF:-}"' EXIT

GREEN='\033[0;32m'; BLUE='\033[0;34m'; NC='\033[0m'

# ── 1) Compilar localmente ─────────────────────────────────────
echo -e "${BLUE}>>> Compilando el frontend (producción)...${NC}"

build_frontend() {
    (
        cd "${LOCAL_FRONT}"
        export VITE_API_BASE_URL="${VITE_API_BASE_URL:-/api/v1}"
        export VITE_REVERB_HOST="${DOMAIN}"
        export VITE_REVERB_SCHEME="https"
        # La clave Reverb pública debe coincidir con REVERB_APP_KEY del backend.
        export VITE_REVERB_APP_KEY="${VITE_REVERB_APP_KEY:-gestion_proyectos_reverb_key}"
        npx vite build --outDir "${BUILD_OUT}" --emptyOutDir
    )
}

if [ ! -d "${LOCAL_FRONT}/node_modules" ]; then
    echo ">>> Instalando dependencias locales..."
    ( cd "${LOCAL_FRONT}" && npm ci --no-audit --no-fund )
fi

if ! build_frontend; then
    # Vite 8 (rolldown/lightningcss) usa binarios nativos por libc.
    # En hosts glibc npm a veces instala la variante musl y el build falla.
    # Reinstalamos las variantes gnu correctas y reintentamos.
    echo -e "${BLUE}>>> Build falló: corrigiendo bindings nativos (glibc) y reintentando...${NC}"
    (
        cd "${LOCAL_FRONT}"
        RD_V="$(node -p "require('./node_modules/rolldown/package.json').version" 2>/dev/null || true)"
        LC_V="$(node -p "require('./node_modules/lightningcss/package.json').version" 2>/dev/null || true)"
        if getconf GNU_LIBC_VERSION >/dev/null 2>&1 && [ -n "${RD_V}" ] && [ -n "${LC_V}" ]; then
            npm install --no-save --no-audit --no-fund \
                "@rolldown/binding-linux-x64-gnu@${RD_V}" \
                "lightningcss-linux-x64-gnu@${LC_V}" >/dev/null 2>&1 || true
        fi
    )
    build_frontend
fi

# ── 2) Subir dist/ ─────────────────────────────────────────────
echo -e "${BLUE}>>> Subiendo dist/ a ${SSH_USER}@${SSH_HOST}:${REMOTE_DIST} ...${NC}"
ssh "${SSH_BASE[@]}" "mkdir -p '${REMOTE_DIST}'"
rsync -avz --delete --no-owner --no-group --chmod=D755,F644 \
    -e "ssh -p ${SSH_PORT}" \
    "${LOCAL_DIST}/" "${SSH_USER}@${SSH_HOST}:${REMOTE_DIST}/"
# rsync en modo "contenidos" no ajusta el modo del directorio raíz dist/, que
# pudo quedar en 0700 (→ nginx/www-data no puede leerlo → 500). Lo forzamos.
ssh "${SSH_BASE[@]}" "chmod 755 '${REMOTE_DIST}' && chmod -R a+rX '${REMOTE_DIST}'"

# ── 3) Instalar config del nginx del host ─────────────────────
# Si ya existe el certificado → config HTTPS; si no → HTTP (para certbot).
CERT_FILE="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
if ssh "${SSH_BASE[@]}" "test -f '${CERT_FILE}'"; then
    NGINX_KIND="https"
else
    NGINX_KIND="http"
fi
echo -e "${BLUE}>>> Instalando nginx-site-${NGINX_KIND}.conf en el host...${NC}"

WWW_LINE=""
[ -n "${WWW_DOMAIN:-}" ] && WWW_LINE=" ${WWW_DOMAIN}"

TMP_CONF="$(mktemp)"

sed -e "s|__DOMAIN__|${DOMAIN}|g" -e "s|__WWW__|${WWW_LINE}|g" \
    "${DEPLOY_DIR}/nginx-site-${NGINX_KIND}.conf" > "${TMP_CONF}"

# Backup defensivo del conf vigente ANTES de sobreescribir: si la nueva
# config resulta inválida (nginx -t), se restaura esta para no dejar el
# nginx del host en un estado roto tras un reload/reboot.
ssh "${SSH_BASE[@]}" 'CONF=/etc/nginx/sites-available/gestion-proyectos.conf; if [ -f "$CONF" ]; then cp -a "$CONF" "$CONF.bak" && echo ">>> Backup de nginx conf creado ($CONF.bak)"; else echo ">>> Sin conf de nginx previo (primer deploy)"; fi'

scp -P "${SSH_PORT}" "${TMP_CONF}" \
    "${SSH_USER}@${SSH_HOST}:/etc/nginx/sites-available/gestion-proyectos.conf"

ssh "${SSH_BASE[@]}" 'bash -s' <<REMOTE
set -euo pipefail
CONF=/etc/nginx/sites-available/gestion-proyectos.conf
BAK=\${CONF}.bak
ln -sf "\$CONF" /etc/nginx/sites-enabled/gestion-proyectos.conf
rm -f /etc/nginx/sites-enabled/default
mkdir -p /opt/gestion-proyectos/frontend/dist /var/www/certbot
if nginx -t; then
    systemctl reload nginx
    echo ">>> Nginx recargado."
else
    echo "❌ nginx -t FALLÓ con la nueva config; NO se recarga nginx." >&2
    if [ -f "\$BAK" ]; then
        echo ">>> Restaurando config previa desde \$BAK ..." >&2
        cp -a "\$BAK" "\$CONF"
        nginx -t && systemctl reload nginx && echo ">>> Config previa restaurada y recargada." >&2
    fi
    exit 1
fi
REMOTE

echo -e "${GREEN}✅ Frontend desplegado en https://${DOMAIN}${NC}"
echo "   (Si aún no hay certificado, ejecuta ./deploy/deploy.sh ssl)"

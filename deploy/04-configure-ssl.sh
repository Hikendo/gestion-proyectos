#!/usr/bin/env bash
set -euo pipefail

# ══════════════════════════════════════════════════════════════
# 04-configure-ssl.sh
# Emite el certificado HTTPS con Let's Encrypt (certbot, método
# webroot) y activa la config definitiva de nginx (80→443).
#
# Requisitos:
#   • El registro A de $DOMAIN debe apuntar a $SSH_HOST.
#   • El frontend ya debe estar servido (03-deploy-frontend.sh),
#     porque certbot valida la propiedad del dominio por HTTP.
# ══════════════════════════════════════════════════════════════

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$DEPLOY_DIR/config.sh"
_require_domain
_require_email

LOCAL_REPO="$(cd "${DEPLOY_DIR}/.." && pwd)"
SSH_BASE=(-p "${SSH_PORT}" "${SSH_USER}@${SSH_HOST}")

GREEN='\033[0;32m'; BLUE='\033[0;34m'; YELLOW='\033[1;33m'; NC='\033[0m'

CERT_FILE="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"

# ── 1) Asegurar que el nginx del host sirve HTTP (para el challenge) ──
echo -e "${BLUE}>>> Asegurando site HTTP en nginx del host...${NC}"

WWW_LINE=""
[ -n "${WWW_DOMAIN:-}" ] && WWW_LINE=" ${WWW_DOMAIN}"

TMP_CONF="$(mktemp)"
trap 'rm -f "${TMP_CONF}"' EXIT
sed -e "s|__DOMAIN__|${DOMAIN}|g" -e "s|__WWW__|${WWW_LINE}|g" \
    "${DEPLOY_DIR}/nginx-site-http.conf" > "${TMP_CONF}"

scp -P "${SSH_PORT}" "${TMP_CONF}" \
    "${SSH_USER}@${SSH_HOST}:/etc/nginx/sites-available/gestion-proyectos.conf"

ssh "${SSH_BASE[@]}" 'bash -s' <<'REMOTE'
set -euo pipefail
ln -sf /etc/nginx/sites-available/gestion-proyectos.conf /etc/nginx/sites-enabled/gestion-proyectos.conf
rm -f /etc/nginx/sites-enabled/default
mkdir -p /var/www/certbot
nginx -t
systemctl reload nginx
echo ">>> Nginx sirviendo HTTP (challenge listo)."
REMOTE

# ── 2) Comprobar que el dominio apunta al servidor ─────────────
echo -e "${BLUE}>>> Comprobando resolución de ${DOMAIN} -> ${SSH_HOST} ...${NC}"
REMOTE_IP="$(getent hosts "${DOMAIN}" | awk '{print $1; exit}' || true)"
if [ -z "${REMOTE_IP}" ]; then
    echo -e "${YELLOW}⚠️  No se pudo resolver ${DOMAIN}. Revisa el registro A antes de continuar.${NC}"
    exit 1
fi
if [ "${REMOTE_IP}" != "${SSH_HOST}" ]; then
    echo -e "${YELLOW}⚠️  ${DOMAIN} resuelve a ${REMOTE_IP}, no a ${SSH_HOST}.${NC}"
    echo "    El certificado podría fallar; corrige el DNS o cambia SSH_HOST en config.sh."
fi

# ── 3) Emitir / renovar certificado ────────────────────────────
echo -e "${BLUE}>>> Ejecutando certbot (webroot)...${NC}"

ssh "${SSH_BASE[@]}" 'bash -s' -- \
    "${CERT_FILE}" "${LETSENCRYPT_EMAIL}" "${DOMAIN}" "${WWW_DOMAIN:-}" <<'REMOTE'
set -euo pipefail
CERT_FILE="$1"; EMAIL="$2"; DOMAIN="$3"; WWW="$4"

if [ -f "$CERT_FILE" ]; then
    echo ">>> Certificado existente: renuevo solo si hace falta."
    certbot renew --webroot -w /var/www/certbot --quiet \
        --deploy-hook "systemctl reload nginx" || true
else
    echo ">>> Solicitando certificado para ${DOMAIN}${WWW:+ y ${WWW}}..."
    certbot certonly --webroot -w /var/www/certbot \
        -d "$DOMAIN" \
        ${WWW:+-d "$WWW"} \
        --email "$EMAIL" --agree-tos --no-eff-email \
        --non-interactive --keep-until-expiring \
        --deploy-hook "systemctl reload nginx"
fi
REMOTE

echo -e "${BLUE}>>> Activando config HTTPS definitiva...${NC}"
sed -e "s|__DOMAIN__|${DOMAIN}|g" -e "s|__WWW__|${WWW_LINE}|g" \
    "${DEPLOY_DIR}/nginx-site-https.conf" > "${TMP_CONF}"

scp -P "${SSH_PORT}" "${TMP_CONF}" \
    "${SSH_USER}@${SSH_HOST}:/etc/nginx/sites-available/gestion-proyectos.conf"

ssh "${SSH_BASE[@]}" 'bash -s' <<'REMOTE'
set -euo pipefail
nginx -t
systemctl reload nginx
echo ">>> Nginx HTTPS activo."
REMOTE

echo -e "${GREEN}✅ HTTPS configurado: https://${DOMAIN}${NC}"
echo "   La renovación automática la gestiona certbot.timer (2×/día)."

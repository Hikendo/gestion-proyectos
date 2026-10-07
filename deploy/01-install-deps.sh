#!/usr/bin/env bash
set -euo pipefail

# ══════════════════════════════════════════════════════════════
# 01-install-deps.sh
# Instala las dependencias del servidor Ubuntu/Debian:
#   • Docker + Docker Compose plugin
#   • rsync, nginx, git, unzip
#   • certbot (Let's Encrypt)
# Se ejecuta una sola vez contra el servidor (o si lo pides).
# ══════════════════════════════════════════════════════════════

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$DEPLOY_DIR/config.sh"

echo ">>> Conectando a ${SSH_USER}@${SSH_HOST} ..."

ssh -p "${SSH_PORT}" "${SSH_USER}@${SSH_HOST}" 'bash -s' <<'REMOTE'
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

echo ">>> Actualizando paquetes..."
apt-get update -y

echo ">>> Instalando herramientas base (sin Node — la compilación es local)..."
apt-get install -y \
  ca-certificates \
  curl \
  gnupg \
  lsb-release \
  rsync \
  nginx \
  git \
  unzip

echo ">>> Instalando certbot + plugin nginx (Let's Encrypt)..."
apt-get install -y certbot python3-certbot-nginx || {
  echo "⚠️  certbot no disponible por apt en esta distro; instálalo manualmente (snap install certbot --classic)."
}

echo ">>> Instalando Docker + Compose plugin..."
if ! command -v docker >/dev/null 2>&1; then
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  chmod a+r /etc/apt/keyrings/docker.gpg
  echo \
    "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
    $(lsb_release -cs) stable" \
    > /etc/apt/sources.list.d/docker.list
  apt-get update -y
fi

apt-get install -y \
  docker-ce \
  docker-ce-cli \
  containerd.io \
  docker-buildx-plugin \
  docker-compose-plugin

echo ">>> Habilitando servicios..."
systemctl enable --now docker
systemctl enable --now nginx

if command -v certbot >/dev/null 2>&1; then
  systemctl enable --now certbot.timer || true
fi

echo ">>> Versiones instaladas:"
docker --version
docker compose version
nginx -v
certbot --version 2>/dev/null || echo "(certbot pendiente)"

echo ">>> Dependencias instaladas correctamente."
REMOTE

echo ""
echo "✅ Siguiente paso:"
echo "   1. Edita DOMAIN (y WWW_DOMAIN, LETSENCRYPT_EMAIL) en deploy/config.sh"
echo "   2. Ejecuta: ./deploy/deploy.sh all"

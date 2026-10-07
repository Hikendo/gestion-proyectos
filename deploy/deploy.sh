#!/usr/bin/env bash
set -euo pipefail

# ══════════════════════════════════════════════════════════════
# deploy.sh — Orquestador de despliegue
# ─────────────────────────────────────────────────────────────
# Uso:
#   ./deploy/deploy.sh            → actualiza backend + frontend
#   ./deploy/deploy.sh all        → instalación completa:
#                                    deps → backend → frontend → ssl
#   ./deploy/deploy.sh deps       → instala dependencias del server
#   ./deploy/deploy.sh backend    → solo backend
#   ./deploy/deploy.sh frontend   → solo frontend
#   ./deploy/deploy.sh ssl        → solo HTTPS (Let's Encrypt)
# ══════════════════════════════════════════════════════════════

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$DEPLOY_DIR/config.sh"

ACTION="${1:-update}"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'

step() { echo -e "\n${YELLOW}═══════════════════════════════════════════════${NC}"; echo -e "${YELLOW}  $1${NC}"; echo -e "${YELLOW}═══════════════════════════════════════════════${NC}"; }

case "${ACTION}" in
  all)
    step "01 · Instalar dependencias del servidor"
    bash "${DEPLOY_DIR}/01-install-deps.sh"
    step "02 · Desplegar backend"
    bash "${DEPLOY_DIR}/02-deploy-backend.sh"
    step "03 · Desplegar frontend"
    bash "${DEPLOY_DIR}/03-deploy-frontend.sh"
    step "04 · Configurar HTTPS"
    bash "${DEPLOY_DIR}/04-configure-ssl.sh"
    ;;
  deps)
    step "01 · Instalar dependencias del servidor"
    bash "${DEPLOY_DIR}/01-install-deps.sh"
    ;;
  backend)
    step "02 · Desplegar backend"
    bash "${DEPLOY_DIR}/02-deploy-backend.sh"
    ;;
  frontend)
    step "03 · Desplegar frontend"
    bash "${DEPLOY_DIR}/03-deploy-frontend.sh"
    ;;
  ssl)
    step "04 · Configurar HTTPS"
    bash "${DEPLOY_DIR}/04-configure-ssl.sh"
    ;;
  update)
    # Flujo normal para actualizar el proyecto ya desplegado
    step "02 · Actualizar backend"
    bash "${DEPLOY_DIR}/02-deploy-backend.sh"
    step "03 · Actualizar frontend"
    bash "${DEPLOY_DIR}/03-deploy-frontend.sh"
    ;;
  *)
    echo "Uso: $0 [all|deps|backend|frontend|ssl|update]  (por defecto: update)" >&2
    exit 1
    ;;
esac

echo -e "${GREEN}✅ Deploy finalizado (${ACTION}).${NC}"

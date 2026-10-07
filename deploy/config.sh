#!/usr/bin/env bash
# ══════════════════════════════════════════════════════════════
# CONFIGURACIÓN CENTRAL DE DESPLIEGUE
# Edita este archivo ANTES de ejecutar cualquier script de deploy.
# ══════════════════════════════════════════════════════════════
# Cómo usar:
#   ./deploy/deploy.sh            # actualiza backend + frontend
#   ./deploy/deploy.sh all        # instalación completa (deps → backend → frontend → ssl)
#   ./deploy/deploy.sh backend    # solo backend
#   ./deploy/deploy.sh frontend   # solo frontend
#   ./deploy/deploy.sh ssl        # solo certificado HTTPS (Let's Encrypt)
# ══════════════════════════════════════════════════════════════

# ─────────────────────────────────────────────
# 🔌 SERVIDOR (DigitalOcean Droplet)
# ─────────────────────────────────────────────
SSH_HOST="${SSH_HOST:-206.189.189.34}"
SSH_USER="${SSH_USER:-root}"
SSH_PORT="${SSH_PORT:-22}"
REMOTE_ROOT="${REMOTE_ROOT:-/opt/gestion-proyectos}"

# ─────────────────────────────────────────────
# 🌐 DOMINIO + EMAIL (HTTPS / Let's Encrypt)
#   ⚠️ Requisito: el registro A de $DOMAIN debe apuntar a $SSH_HOST
#   Ejemplo:
#     DOMAIN="gestion-proyectos.com"
#     WWW_DOMAIN="www.gestion-proyectos.com"   # (opcional)
#     LETSENCRYPT_EMAIL="admin@gestion-proyectos.com"
# ─────────────────────────────────────────────
DOMAIN="${DOMAIN:-project.business-and-growth.com}"
WWW_DOMAIN="${WWW_DOMAIN:-}"
LETSENCRYPT_EMAIL="${LETSENCRYPT_EMAIL:-}"

# ─────────────────────────────────────────────
# 🗄️ Base de datos
#   Los passwords se generan automáticamente en el servidor
#   durante el primer deploy (fichero $REMOTE_ROOT/.env).
# ─────────────────────────────────────────────
DB_DATABASE="${DB_DATABASE:-gestion_db}"
DB_USERNAME="${DB_USERNAME:-gestion_user}"

# ══════════════════════════════════════════════════════════════
# FUNCIONES DE VALIDACIÓN (se invocan desde los scripts)
# ══════════════════════════════════════════════════════════════
DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd)"

_require_domain() {
    if [ -z "${DOMAIN:-}" ]; then
        echo "❌ El DOMINIO no está definido." >&2
        echo "   Edita deploy/config.sh y configura DOMAIN (y WWW_DOMAIN / LETSENCRYPT_EMAIL)." >&2
        echo "   Ej: DOMAIN=\"gestion-proyectos.com\"" >&2
        exit 1
    fi
}

_require_email() {
    if [ -z "${LETSENCRYPT_EMAIL:-}" ]; then
        echo "❌ LETSENCRYPT_EMAIL no está definido." >&2
        echo "   Edita deploy/config.sh y añade el email para Let's Encrypt." >&2
        exit 1
    fi
}

_require_root() {
    if [ "$(id -u)" != "0" ]; then
        echo "❌ Este script debe ejecutarse como root o con sudo." >&2
        exit 1
    fi
}

# Despliegue — Gestión de Proyectos

Scripts para subir el proyecto completo al servidor `206.189.189.34`
y actualizarlo cuando sea necesario.

## Arquitectura desplegada

```
Internet ──▶ Nginx del HOST (instalado por apt, HTTPS con Let's Encrypt)
                ├── /            → frontend estático (dist/ compilado)
                ├── /api/*       → proxy → 127.0.0.1:8000  (nginx Docker) → PHP-FPM
                ├── /horizon/*   → proxy → 127.0.0.1:8001  (Horizon, opcional)
                └── /app         → proxy websocket → 127.0.0.1:8080 (Reverb, wss)

Docker Compose en /opt/gestion-proyectos (docker-compose.prod.yml):
  backend · horizon · scheduler · nginx · reverb · redis · db (MySQL sin puerto público)
```

## Archivos

| Archivo | Rol |
|---------|-----|
| `config.sh` | Configuración central (IP, usuario SSH, dominio, email, BD) |
| `deploy.sh` | Orquestador: `all`, `deps`, `backend`, `frontend`, `ssl`, `update` (default) |
| `01-install-deps.sh` | Instala Docker, Compose, nginx, rsync y certbot en el servidor |
| `02-deploy-backend.sh` | Sync del código, levanta el stack Docker y migra |
| `03-deploy-frontend.sh` | Compila el frontend y lo sube + actualiza nginx host |
| `04-configure-ssl.sh` | Emite el certificado HTTPS (Let's Encrypt) y activa la config 443 |
| `docker-compose.prod.yml` | Compose de producción completo e independiente |
| `nginx-site-http.conf` | Site temporal HTTP (para certbot / antes del SSL) |
| `nginx-site-https.conf` | Site definitivo HTTPS (SPA + API + Reverb wss) |
| `env.production.example` | Plantilla del `.env` del backend para producción |

## Configuración previa (una sola vez)

1. **DNS**: crea el registro A de tu dominio → `206.189.189.34`
   (y `www` si quieres).
2. Edita `deploy/config.sh`:

   ```bash
   SSH_HOST="206.189.189.34"
   SSH_USER="root"

   DOMAIN="gestion-proyectos.com"          # ← tu dominio
   WWW_DOMAIN="www.gestion-proyectos.com"  # opcional
   LETSENCRYPT_EMAIL="admin@tudominio.com" # email para Let's Encrypt
   ```

3. (Opcional) Si quieres sobrescribir el `.env` del backend con tus
   credenciales exactas, crea `deploy/.env.production` — está ignorado
   por git. Por defecto el backend adapta tu `backend/.env` local
   (conserva FIREBASE/REVERB/RESEND) y si no existe usa
   `env.production.example`.

## Instalación inicial

```bash
./deploy/deploy.sh all
```

Pasos equivalentes por separado:

```bash
./deploy/deploy.sh deps      # 01: dependencias del servidor
./deploy/deploy.sh backend   # 02: código + stack + migraciones
./deploy/deploy.sh frontend  # 03: build + dist + nginx
./deploy/deploy.sh ssl       # 04: certificado HTTPS
```

> ⚠️ `all` incluye el SSL. Si el DNS aún no propaga, ejecuta primero
> `deps`, `backend` y `frontend`; más tarde `./deploy/deploy.sh ssl`.

## Actualizaciones posteriores (cada vez que cambie el código)

```bash
./deploy/deploy.sh            # equivalente a: backend + frontend
# o por partes:
./deploy/deploy.sh backend
./deploy/deploy.sh frontend
```

Lo que **nunca** se sobreescribe en el servidor:

- `/opt/gestion-proyectos/backend/.env`
- `/opt/gestion-proyectos/.env` (passwords aleatorios de MySQL)
- `/opt/gestion-proyectos/frontend/dist/` (solo lo actualiza 03)

## Notas importantes

- **Primer arranque**: `docker compose up -d --build` compila la imagen
  PHP (instala Composer dentro) y puede tardar varios minutos.
- **Usuarios iniciales** (si la BD es nueva): el seeder crea
  `superadmin@test.com / password` (más `pm@test.com`, `dev@test.com`,
  `qa@test.com`, `support@test.com`, `client@test.com`, todos `password`).
  Para poblar además proyectos demo: `SEED_DEMO=1 ./deploy/deploy.sh backend`.
- **Renovación HTTPS**: automática vía `certbot.timer` (el deploy-hook
  recarga nginx). Para renovar manualmente: `./deploy/deploy.sh ssl`.
- **Chat en tiempo real**: el frontend conecta a `wss://DOMINIO/app`
  (proxificado por nginx al contenedor Reverb en `127.0.0.1:8080`).
- **Cambiar el dominio o credenciales de BD**: edita `config.sh` y
  vuelve a ejecutar `./deploy/deploy.sh backend`; los passwords de BD se
  conservan. Para forzar un `.env` nuevo en backend:
  `ssh root@206.189.189.34 "rm /opt/gestion-proyectos/backend/.env"` y redeploy.

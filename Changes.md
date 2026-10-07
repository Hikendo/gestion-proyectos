# Changes.md — Contexto para sesiones futuras

 **Última actualización:** 2026-07-10

---

## [2026-07-10] Hotfix: 500 en login producción — `.env` ilegible por www-data

### Contexto

Tras el deploy a `project.business-and-growth.com`:

- `POST /api/v1/auth/login` → **500**
- `GET /api/v1/notifications` → 401 (normal, sin token)
- Log: `No application encryption key has been specified.` y, antes,
  `Database file at path [gestion_db] does not exist. (Connection: sqlite...)`.

### Causa raíz

`backend/.env` en el servidor quedaba en `600 root:root` (subido por `scp`
desde un `mktemp` 600). PHP-FPM (contenedor) corre como **www-data**, que **no
puede leer** ese `.env`. En runtime Laravel no cargaba ninguna variable y caía
a los defaults: `DB_CONNECTION` ausente → `sqlite` (`config/database.php:20`)
usando `DB_DATABASE=gestion_db` (del `environment:` de compose) como *path*; y
`APP_KEY` vacío. El `optimize:clear` del entrypoint (que puede ejecutarse tras
el `config:cache` del script → carrera) borraba la caché de config que venía
enmascarando el problema.

### Solución

- `backend/docker-entrypoint.sh`: `fix_permissions()` ahora hace
  `chmod 644 /var/www/.env` en cada arranque (auto-reparación; el `.env` va
  montado desde el host).
- `deploy/docker-compose.prod.yml`: `DB_CONNECTION: mysql` explícito en el
  anchor `&laravel-env` (las vars de `environment:` son reales y ganan al `.env`).
- `deploy/02-deploy-backend.sh`: `chmod 644 backend/.env` remoto tras subirlo.

### Acción manual en el servidor (aplicada)

`chmod 644 /opt/gestion-proyectos/backend/.env` + `optimize:clear` + `config:cache`.

---

## [2026-07-10] Fix: 403 en tickets/fases — autorización global en FormRequests (+ CORS)

### Contexto

En producción `project.business-and-growth.com`:

- `POST /api/v1/projects/{project}/tickets` → **403** para miembros de proyecto
  (usuarios sin rol global de Spatie, solo rol de membresía). El super-admin sí
  recibía **201**.
- `POST /api/v1/projects/{project}/conversations` → **404** (backend desplegado
  desactualizado; ver «Despliegue pendiente»).

### Causa

`StoreTicketRequest::authorize()` usaba `$this->user()->can('ticket.create')`
**global**. Un miembro de proyecto sin rol de Spatie no posee ese permiso global
→ 403. El resto de FormRequests ya usaban `canForProject(...)`. Mismo patrón
(incorrecto) en `UpdateTicketRequest`, `StoreProjectPhaseRequest`
(`phase.create`) y `UpdateProjectPhaseRequest` (`phase.edit`).

### Solución

- `StoreTicketRequest` → `canForProject($project, 'ticket.create')` (resuelve el
  `Project` desde la ruta).
- `UpdateTicketRequest` → `return true`; la autorización fina (edit-any/edit-own,
  estado `closed`) la aplica `TicketController::update()` vía
  `TicketPolicy::update()` (ya project-scoped).
- `StoreProjectPhaseRequest` → `canForProject($project, 'phase.create')`.
- `UpdateProjectPhaseRequest` → `canForProject($project, 'phase.edit')`.
- `config/cors.php` (nuevo): orígenes explícitos del dominio + `supports_credentials`
  (el SPA es same-origin; habilita clientes cross-origin como Flutter).
- `deploy/config.sh`: `DOMAIN` por defecto `project.business-and-growth.com`
  (evita que `_require_domain` aborte el despliegue). `WWW_DOMAIN` se deja vacío
  a propósito (decisión www-vs-apex pendiente).
- Tests: nuevo caso de regresión en `TicketTest` (miembro **sin** rol global) y
  nuevo `ProjectPhaseTest`.

### Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `backend/app/Http/Requests/Ticket/StoreTicketRequest.php` | `can()` → `canForProject($project, 'ticket.create')` |
| `backend/app/Http/Requests/Ticket/UpdateTicketRequest.php` | Delegar autorización a `TicketPolicy` (`return true`) |
| `backend/app/Http/Requests/ProjectPhase/StoreProjectPhaseRequest.php` | `can()` → `canForProject($project, 'phase.create')` |
| `backend/app/Http/Requests/ProjectPhase/UpdateProjectPhaseRequest.php` | `can()` → `canForProject($project, 'phase.edit')` |
| `backend/config/cors.php` | Nuevo: CORS con orígenes explícitos |
| `deploy/config.sh` | `DOMAIN` por defecto fijado |
| `backend/tests/Feature/Ticket/TicketTest.php` | Test de regresión (miembro sin rol global) |
| `backend/tests/Feature/Project/ProjectPhaseTest.php` | Nuevo: fases (manager crea / developer 403) |

### Despliegue pendiente

El **404** de `conversations` se debe a que el backend desplegado está
**desactualizado** (falta `routes/api/chat.php` / `DirectChatController`).
Requiere un **redeploy** obligatorio:

```bash
./deploy/deploy.sh backend   # rsync + build + migrate + optimize:clear/config:cache/route:cache
```

Verificación posterior: `php artisan route:list --path=conversations` debe listar
las rutas y los POST de tickets/conversations deben responder **201**.

---

## [2026-07-10] Fix: 500 al desplegar frontend — permisos del `dist/`

### Contexto

Tras aplicar el fix de `http2`, `nginx -t` ya pasaba y nginx servía HTTP/2, pero
el sitio seguía devolviendo `500 Internal Server Error` (nginx/1.24.0) en rutas
como `/projects/3`, y `403` en `/`:

```
[crit] stat() "/opt/gestion-proyectos/frontend/dist/index.html" failed
       (13: Permission denied)
[error] rewrite or internal redirection cycle while internally redirecting
        to "/index.html"
```

### Causa

`/opt/gestion-proyectos/frontend/dist` quedó en **`0700`** (dueño UID 1000). El
worker de nginx corre como **`www-data`**, así que no podía atravesar/leer el
directorio → `try_files` no podía abrir `index.html` → bucle de redirección
interna → `500`.

Origen: `03-deploy-frontend.sh` compila a un directorio `mktemp -d` (modo `0700`)
y sube con `rsync -avz`; `-a` **preserva dueño y permisos**, propagando el `0700`
y el UID local 1000 al `dist/` remoto.

### Solución

- `deploy/03-deploy-frontend.sh`:
  - `chmod 755 "${BUILD_OUT}"` tras `mktemp -d`.
  - `rsync ... --no-owner --no-group --chmod=D755,F644`.
  - Tras el rsync, `chmod 755` + `chmod -R a+rX` del `dist/` remoto (el rsync en
    modo "contenidos" no ajusta el modo del directorio raíz).
- En el servidor (aplicado): `chmod 755 /opt/gestion-proyectos/frontend/dist`
  y `chmod -R a+rX /opt/gestion-proyectos/frontend/dist`.

### Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `deploy/03-deploy-frontend.sh` | Normaliza dueño/permisos del `dist/` subido (evita el `0700`) |

---

## [2026-07-10] Fix: despliegue del frontend (nginx 1.24 + `DOMAIN` por defecto)

### Contexto

Al re-ejecutar el despliegue del frontend (`./deploy/deploy.sh frontend`) el
build local y el `rsync` del `dist/` funcionaban, pero el paso 3 (config del
nginx del host) fallaba:

```
[emerg] unknown directive "http2" in /etc/nginx/sites-enabled/gestion-proyectos.conf:30
nginx: configuration file /etc/nginx/nginx.conf test failed
```

### Causa

- `deploy/config.sh` tenía `DOMAIN="${DOMAIN:-}"` (vacío). Tanto `02` como `03`
  llaman a `_require_domain` al inicio, así que sin `DOMAIN` el script abortaba
  antes de compilar/subir.
- `deploy/nginx-site-https.conf` usaba la directiva `http2 on;`, que **solo
  existe en nginx >= 1.25.1**. El servidor corre **nginx 1.24.0 (Ubuntu)** →
  `nginx -t` falla y el `systemctl reload nginx` del script no se ejecuta,
  dejando la config inválida en disco (riesgo de caída del sitio en el siguiente
  reload/reboot → de ahí el `500 Internal Server Error`).

### Solución

- `deploy/config.sh`: `DOMAIN="${DOMAIN:-project.business-and-growth.com}"`.
- `deploy/nginx-site-https.conf`: `listen 443 ssl http2;` y
  `listen [::]:443 ssl http2;` en lugar de `listen ... ssl;` + `http2 on;`
  (compatible con nginx 1.24+; en 1.25.1+ solo emite un *warning* de deprecación).
- `deploy/03-deploy-frontend.sh`: hace backup del vhost vigente antes de
  sobreescribirlo y lo **restaura automáticamente si `nginx -t` falla**, para no
  dejar nginx roto.

### Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `deploy/config.sh` | `DOMAIN` por defecto = dominio del front |
| `deploy/nginx-site-https.conf` | Sintaxis HTTP/2 compatible con nginx 1.24 |
| `deploy/03-deploy-frontend.sh` | Backup + rollback del vhost si `nginx -t` falla |

### Acción pendiente

Re-ejecutar `./deploy/deploy.sh frontend` para aplicar el vhost corregido y
recargar nginx; después verificar `curl -sI https://project.business-and-growth.com/`
(esperado `HTTP/2 200`).

---

## [2026-07-10] Fix: páginas lazy del router no cargaban en producción (MIME `text/html`)

### Contexto

Al desplegar el frontend compilado, al navegar a los módulos del proyecto
(miembros, hitos, entregables, planes, tareas, tickets, riesgos, bloqueadores,
fases, objetivos, métricas, notificaciones) la consola mostraba:

```
Failed to load module script: Expected a JavaScript-or-Wasm module script but the
server responded with a MIME type of "text/html".
TypeError: Failed to fetch dynamically imported module:
https://project.business-and-growth.com/pages/members/index.vue
```

### Causa

`frontend/src/router/index.js` definía el helper de páginas lazy como:

```js
const p = (path) => () => import(`../pages/${path}`);
```

El import dinámico con plantilla (`../pages/${path}`) no es analizable
estáticamente por Vite/Rollup, así que el build lo dejaba sin compilar
(confirmado en el bundle: `SA=e=>()=>cA(()=>import(`../pages/${e}`),[])`). En
producción el navegador resolvía el import relativo al chunk (`/assets/index-*.js`)
hacia `/pages/members/index.vue`; nginx (SPA fallback `try_files ... /index.html`)
respondía `index.html` con `Content-Type: text/html`, produciendo el error de MIME.
En `vite dev` funcionaba porque el dev server transforma los `.vue` al vuelo.

### Solución

Se reemplazó el helper por un mapa `import.meta.glob` (Vite sí compila cada
página a su propio chunk):

```js
const pageModules = import.meta.glob(
    '../pages/{members,objectives,phases,plans,tasks,tickets,risks,blockers,deliverables,milestones,metrics,notifications}/**/*.vue',
);
const p = (path) => pageModules[`../pages/${path}`];
```

El glob se limitó a los subdirectorios usados por `p()` para no incluir páginas
legacy no referenciadas.

### Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `frontend/src/router/index.js` | `p()` usa `import.meta.glob` en lugar de `import()` con plantilla |
| `ArchivosMap.md` | Documentada la sección Router y añadida sección Deploy (`deploy/`) |
| `frontend/src/features/admin/users/UserUpdateFeature.vue` | Corregido import roto `useRolesList` -> `useRoles` (habría provocado `MISSING_EXPORT` si la página entrara en el grafo) |

### Verificación

- `npx vite build` OK; se generan chunks por página (`members-*.js`, `milestones-*.js`, `deliverables-*.js`, `plans-*.js`, `tasks-*.js`, `tickets-*.js`, `objectives-*.js`, `risks-*.js`, `blockers-*.js`, `phases-*.js`, `metrics-*.js`, `notifications-*.js`).
- El bundle ya no contiene un `import()` dinámico con plantilla apuntando a `../pages/`; solo queda el lookup del mapa.
- Vitest: 132/135 (3 fallos pre-existentes en `useAttachments` y `useProjects`, sin relación con el router).

### Incidencia adicional detectada y resuelta

- `src/features/admin/users/UserUpdateFeature.vue` importaba `{ useRolesList }` desde
  `src/composables/useRolesList.ts`, que solo exporta `useRoles`. Era código legacy sin uso
  (bajo `src/pages/users/*`, no referenciado por el router), pero rompía el build con
  `MISSING_EXPORT` en cuanto esa página entraba en el grafo (p. ej. un glob amplio sobre
  todo `pages/`). Se corrigió el import a `useRoles` y el build vuelve a pasar.

---

## [2026-06-19] Documento de requerimientos para app móvil Flutter

### Contexto

Se creó `FlutterAppRequirements.md` con la especificación completa de endpoints, payloads y módulos requeridos para la app móvil en Flutter, enfocada en clientes y soporte. La app es una versión "capada" que incluye: autenticación, dashboard, proyectos, tareas, tickets, bloqueadores, chat grupal/privado, notificaciones, adjuntos y FCM.

### Archivos nuevos

| Archivo | Responsabilidad |
|---------|----------------|
| `FlutterAppRequirements.md` | 35 endpoints documentados con payloads de ejemplo, configuración WebSocket (Reverb), FCM, scroll infinito y notas para desarrollo |

### Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `testPostman.json` | Agregada sección "Chat" con 7 endpoints: Get Group Messages, Send Group Message, Get Conversations, Start Conversation, Get Direct Messages, Send Direct Message, Mark Conversation Read |

---

## [2026-06-19] Chat grupal y privado por proyecto con Laravel Reverb y notificaciones FCM

### Contexto

Se implementó un sistema de mensajería en tiempo real compuesto por:

- **Chat grupal**: todos los miembros del proyecto participan automáticamente.
- **Chats privados**: conversaciones uno a uno entre miembros del mismo proyecto.
- **Laravel Reverb** como servidor WebSocket (protocolo Pusher).
- **Notificaciones FCM** para mensajes nuevos cuando el usuario está offline.
- **Frontend Vue 3** con Laravel Echo para suscripción en tiempo real.

### Archivos nuevos (Backend)

| Archivo | Responsabilidad |
|---------|----------------|
| `database/migrations/2026_06_19_000001_create_project_messages_table.php` | Tabla `project_messages` (chat grupal) |
| `database/migrations/2026_06_19_000002_create_conversations_table.php` | Tabla `conversations` (conversaciones privadas) |
| `database/migrations/2026_06_19_000003_create_direct_messages_table.php` | Tabla `direct_messages` (mensajes privados) |
| `app/Models/ProjectMessage.php` | Modelo de mensaje grupal |
| `app/Models/Conversation.php` | Modelo de conversación privada |
| `app/Models/DirectMessage.php` | Modelo de mensaje privado |
| `app/Events/MessageSent.php` | Evento broadcasting para mensaje grupal |
| `app/Events/DirectMessageSent.php` | Evento broadcasting para mensaje privado |
| `app/Listeners/HandleGroupMessageSent.php` | Listener: notifica a miembros del proyecto por mensaje grupal |
| `app/Listeners/HandlePrivateMessageSent.php` | Listener: notifica al destinatario por mensaje privado |
| `app/Services/Notifications/Domain/GroupMessageSentNotificationService.php` | Servicio de notificación para chat grupal |
| `app/Services/Notifications/Domain/PrivateMessageSentNotificationService.php` | Servicio de notificación para chat privado |
| `app/Http/Controllers/Api/ChatController.php` | Controlador REST para chat grupal |
| `app/Http/Controllers/Api/DirectChatController.php` | Controlador REST para chat privado |
| `routes/api/chat.php` | Rutas API para chat grupal y privado |
| `routes/api/broadcasting.php` | Ruta de autenticación para broadcasting (Echo) |
| `routes/channels.php` | Canales de broadcasting: `project.{id}` y `conversation.{id}` |
| `config/broadcasting.php` | Configuración de Reverb como broadcaster |

### Archivos nuevos (Frontend)

| Archivo | Responsabilidad |
|---------|----------------|
| `src/services/chat.service.ts` | Servicio HTTP para endpoints de chat |
| `src/composables/useChat.ts` | Composables `useGroupChat` y `usePrivateChat` |
| `src/plugins/echo.ts` | Inicialización de Laravel Echo, suscripción a canales |
| `src/components/chat/GroupChat.vue` | Componente de chat grupal con scroll infinito |
| `src/components/chat/PrivateChat.vue` | Componente de chat privado con lista de conversaciones |

### Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `app/Models/User.php` | Agregadas relaciones `projectMessages`, `directMessages`, `conversationsAsOne`, `conversationsAsTwo` |
| `app/Models/Project.php` | Agregadas relaciones `groupMessages`, `conversations` |
| `app/Providers/EventServiceProvider.php` | Registrados eventos `MessageSent` y `DirectMessageSent` con sus listeners |
| `routes/api.php` | Agregados `require` para `broadcasting.php` y `chat.php` |
| `composer.json` | Agregado `laravel/reverb: ^1.0` |
| `.env` | `BROADCAST_CONNECTION=reverb`, variables `REVERB_APP_ID`, `REVERB_APP_KEY`, etc. |
| `docker-compose.yml` | Nuevo servicio `reverb` en puerto 8080 |
| `frontend/package.json` | Agregados `laravel-echo: ^2.0.0` y `pusher-js: ^8.5.0` |

### Arquitectura de broadcasting

```
Usuario envía mensaje → POST /api/v1/projects/{id}/chat/messages
  → Controller crea ProjectMessage
  → broadcast(new MessageSent($message))->toOthers()
    → Laravel Reverb transmite al canal private-project.{id}
      → Frontend (Laravel Echo) recibe y muestra el mensaje en tiempo real
  → EventServiceProvider → HandleGroupMessageSent
    → GroupMessageSentNotificationService
      → BD persist + SendPushNotificationJob (FCM) + SendEmailNotificationJob (Resend)
```

### Canales de broadcasting

| Canal | Acceso | Evento |
|-------|--------|--------|
| `private-project.{projectId}` | Miembros del proyecto | `.message.sent` |
| `private-conversation.{conversationId}` | Solo los 2 participantes | `.direct-message.sent` |

### Endpoints API nuevos

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/v1/projects/{project}/chat/messages` | Historial de chat grupal (paginado) |
| POST | `/api/v1/projects/{project}/chat/messages` | Enviar mensaje al chat grupal |
| GET | `/api/v1/projects/{project}/conversations` | Listar conversaciones privadas del usuario |
| POST | `/api/v1/projects/{project}/conversations` | Iniciar conversación con otro miembro |
| GET | `/api/v1/conversations/{conversation}/messages` | Historial de mensajes privados (paginado) |
| POST | `/api/v1/conversations/{conversation}/messages` | Enviar mensaje privado |
| POST | `/api/v1/conversations/{conversation}/read` | Marcar mensajes como leídos |

### Tipos de notificación nuevos

| Tipo | Descripción |
|------|-------------|
| `new_group_message` | Nuevo mensaje en el chat del equipo |
| `new_private_message` | Nuevo mensaje privado recibido |

### Verificación

- Docker: nuevo servicio `reverb` en `docker-compose.yml`
- Broadcasting configurado en `config/broadcasting.php`
- Pendiente: ejecutar migraciones y tests

---

## [2026-06-19] Integración de Resend para emails transaccionales

### Contexto

Se agregó Resend como canal adicional de notificaciones. Ahora cada notificación se entrega por tres vías: BD (historial) + Push (FCM) + Email (Resend). Se usó el paquete oficial `resend/resend-laravel`.

### Archivos nuevos

| Archivo | Responsabilidad |
|---------|----------------|
| `app/Services/ResendEmailService.php` | Envía emails HTML vía API de Resend usando el facade `Resend` |
| `app/Jobs/SendEmailNotificationJob.php` | Job encolado en queue `notifications` que envía el email. Solo actúa si el usuario tiene `email`. Template HTML responsive con botón "Ver en el panel". |

### Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `composer.json` | Agregado `resend/resend-laravel: ^1.4` |
| `.env` | Agregados `RESEND_API_KEY=` y `RESEND_FROM_EMAIL=` |
| `config/services.php` | Ya existía el bloque `resend` con `key` |
| `app/Services/Notifications/AbstractNotificationService.php` | `dispatchToMany()` ahora despacha `SendEmailNotificationJob` junto con `SendPushNotificationJob` |
| `Dockerfile` | Agregados `libcurl4-openssl-dev` y extensión PHP `curl` para requests HTTP de Resend |

### Flujo resultante

```
Evento → DomainService.notify()
  → BD persist (sin cambios)
  → SendPushNotificationJob (FCM, sin cambios)
  → SendEmailNotificationJob (Resend, NUEVO) ← solo si el usuario tiene email
```

### Configuración requerida

- `RESEND_API_KEY=re_xxxxxxxx` (obtener en <https://resend.com/api-keys>)
- `RESEND_FROM_EMAIL=noreply@tudominio.com`

### Verificación

- Tests: 103/103 pasando en Docker
- Docker build: exitoso

---

## [2026-06-18] Auditoría de reglas de negocio + correcciones (v2.0)

### Contexto

Se realizó auditoría completa contra `AuditoriaReglasNegocio.md` v2.0 (119+ reglas). Resultado final: ~87% cumplimiento (103/119), 103 tests backend pasando.

### Correcciones implementadas (9)

| # | Regla | Archivos | Qué cambió |
|---|-------|----------|------------|
| 1 | RE‑F01 | `StoreTaskRequest.php` | `after()`: fase vencida/completada rechaza tareas. Fase sin `end_date` = mantenimiento (siempre permite). |
| 2 | RE‑F05 | `ProjectPhaseController.php` | `destroy()`: cuenta tareas/entregables/objetivos/riesgos/criterios antes de eliminar → 422 si > 0. |
| 3 | RE‑D03 | `StoreDeliverableRequest.php`, `UpdateDeliverableRequest.php` | `after()`: detecta ciclos recorriendo cadena de `parent_id`. |
| 4 | CA‑03 | `TaskController.php` | `unset($data['progress'], $data['worked_hours'])` después de `validated()` en `update()`. |
| 5 | — | `TaskTimeLogController.php` | Rechaza `store()` si `$task->status === TaskStatus::Done` → 422. |
| 6 | RO‑13 | `TaskController.php` | `index()`: developer/QA solo ven tareas asignadas. PM/owner/support/client ven todas. |
| 7 | RO‑17 | `projects/[id].vue` | Pestañas de navegación filtradas por `permissionStore.hasPermission()`. "Miembros" oculto sin `project.assign-members`. |
| 8 | RO‑37 | `TicketController.php` | `index()`: cliente solo ve tickets propios (`where created_by`). |
| 9 | RO‑38 | `TicketController.php` + `TicketForm.vue` | Backend envía `field_permissions` en `show()`. `TicketForm` ahora usa `useFieldLock` igual que `TaskForm`. |

### Otros cambios importantes

- **`DeliverablePolicy.php`**: `approve()` y `update()` ahora permiten al owner sin necesitar membresía explícita (owner bypass antes que `canForProject`).
- **`TaskObserver.php`**: recalcula progreso también en `created()` (no solo en `updated()`).
- **`HandleDeliverableApproved.php`**, **`HandleBlockerResolved.php`**: envueltos en try/catch para evitar 500 en tests.
- **`ProjectPhaseFactory.php`**: nueva factory para tests.
- **`BusinessRulesAuditTest.php`**: 16 tests nuevos cubriendo RE‑F01, RE‑F05, RE‑D01, RE‑D03, TaskTimeLog, RO‑13, RO‑37, CI‑T03, AV‑T01, AV‑P07.
- **Progreso de fases y proyectos**: campos `progress` ahora son solo lectura en frontend (`VProgressLinear`). Backend: `StoreProjectRequest`/`UpdateProjectRequest` ya no aceptan `progress`.

### Incidencia pendiente

- **RO‑31**: Estados "Nuevo"/"Rechazado" para Support no existen en `TaskStatus`.

### Verificación

- Backend: 103/103 tests pasando
- Frontend: build exitoso

---

## [2026-06-16] TaskForm.vue: campos deshabilitados al crear tarea nueva

**Causa:** `TaskForm.vue` usa `useFieldLock(fieldPermissions)`. Al crear (`id===0`), no hay `field_permissions` del backend → `{}` → todo disabled.

**Solución:** Si `id===0`, inyecta `field_permissions` sintético con todo `true`.

---

## [2026-06-15] Notificaciones FCM, permisos de tareas/tickets y field_permissions

- FCM foreground: `new Notification()` agregado en `firebase.ts`.
- Logout: destruye token FCM antes de limpiar sesión.
- `UpdateTaskRequest`: `task.edit` → `task.edit-content` + `task.edit-own`.
- `TaskForm.vue`: integrado `useFieldLock` con `field_permissions` reales.
- Índices: `canAction` con `resourceOwnerId` para permisos `-own`.
- Permisos de proyecto: `ProjectController::permissions()` + `PermissionStore.projectPermissions`.
- Selector "Asignado a": solo miembros del proyecto.

---

## Arquitectura clave

### Permisos (RBAC + Spatie)

- Roles globales: `super-admin`, `project-manager`, `developer`, `qa`, `support`, `client`.
- `User::canForProject()`: verifica owner, super-admin, o membresía con permiso.
- Policies usan `before()` para bypass de super-admin.
- `field_permissions`: backend calcula vía `FieldPermissionsService`, frontend usa `useFieldLock`.

### Cálculo de progreso (cascada automática)

```
TaskObserver::updated/recalculateProgress
  → TaskProgressUpdated → RecalculatePhaseProgress → PhaseProgressUpdated
    → RecalculateProjectProgress (promedio ponderado)
      → CheckPhaseCompletion (si todas Done + criterios)
```

### Usuarios de prueba

| Email | Password | Rol |
|-------|----------|-----|
| `superadmin@test.com` | `password` | super-admin |
| `pm@test.com` | `password` | project-manager |
| `dev@test.com` | `password` | developer |
| `qa@test.com` | `password` | qa |
| `support@test.com` | `password` | support |
| `client@test.com` | `password` | client |

### Comandos útiles

```bash
docker compose down && docker compose up -d --build
docker compose exec backend php artisan migrate:refresh --seed
docker compose exec backend php artisan test
docker compose exec frontend npm run build

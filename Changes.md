# Changes.md — Contexto para sesiones futuras

 **Última actualización:** 2026-06-19

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

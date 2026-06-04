# 📋 Contexto del Proyecto — Gestión de Proyectos

> **¿Para qué sirve este archivo?**  
> Este documento es la **memoria de contexto** del proyecto. Al iniciar una nueva sesión de desarrollo, léelo de arriba a abajo para recordar:
>
> - Qué funcionalidades están implementadas y en qué archivos
> - Qué decisiones de arquitectura se tomaron y por qué
> - Qué bugs se corrigieron y cómo
> - Qué tareas quedan pendientes
>
> Está organizado por **features** (notificaciones, adjuntos, vistas, etc.) con tablas que muestran exactamente qué archivo implementa qué parte. Usa este documento como punto de partida antes de tocar cualquier código.

---

## 🧭 Estado General (última sesión: 2026-06-05)

### Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12, PHP 8.4, MySQL 8, Redis (opcional) |
| Frontend | Vue 3 (Composition API), Vuetify 3, Pinia, Vite, TypeScript |
| Autenticación | Laravel Sanctum (token Bearer) |
| Autorización | Spatie Laravel Permission (roles: Super Admin, PM, Developer, Client) |
| Notificaciones | Firebase Cloud Messaging (FCM) + jobs asíncronos (Horizon/Redis) |
| Almacenamiento | Disco local con aislamiento por `project_uuid` |
| Contenedores | Docker Compose (backend + frontend + nginx + mysql + redis) |
| IDE | VSCode (errores ts-plugin por entorno local — no afectan build de Vite) |

---

## 🔔 Sistema de Notificaciones (FCM + Bandeja)

### Funcionalidad

- Notificaciones push vía Firebase Cloud Messaging en primer plano y segundo plano
- Bandeja de notificaciones en frontend: campanita con badge numérico + tray desplegable
- Página completa de historial con paginación y marca de leídas
- Las notificaciones FCM entrantes se capturan en `App.vue` y se inyectan al store

### Archivos involucrados

**Backend:**

| Archivo | Rol |
|---|---|
| `backend/app/Jobs/SendPushNotificationJob.php` | Job asíncrono para enviar push FCM |
| `backend/app/Services/FirebaseNotificationService.php` | Servicio de envío FCM |
| `backend/app/Services/Notifications/AbstractNotificationService.php` | Clase base para servicios de notificación |
| `backend/app/Services/Notifications/NotificationRecipientResolver.php` | Resuelve destinatarios de notificaciones |
| `backend/app/Services/Notifications/PolicyAwareRecipientFilter.php` | Filtra destinatarios según políticas |
| `backend/app/Console/Commands/CleanStaleFcmTokens.php` | Comando para limpiar tokens FCM obsoletos |
| `backend/app/Http/Controllers/Api/NotificationController.php` | CRUD de notificaciones (`index`, `show`, `markRead`, `markAllRead`, `schedule`) |
| `backend/app/Models/Notification.php` | Modelo Eloquent (fillable: `user_id, title, body, type, data, status, sent_at, read_at`) |
| `backend/routes/api/notifications.php` | Rutas: `GET /notifications`, `GET /notifications/{id}`, `POST /notifications/mark-read`, `POST /notifications/mark-all-read`, `POST /notifications/schedule` |
| `backend/routes/api/notifications.php` (FCM) | `GET /fcm/tokens`, `POST /fcm/register-token`, `POST /fcm/remove-token` |
| `backend/config/queue.php` | Configuración de colas (Redis) |

**Frontend:**

| Archivo | Rol |
|---|---|
| `frontend/src/services/firebase.ts` | Inicialización de Firebase, `requestNotificationPermission()`, `saveTokenToBackend()`, `listenForegroundNotifications()` — emite evento `fcm:foreground-notification` |
| `frontend/src/services/notifications.service.ts` | HTTP: `fetchNotifications()`, `markNotificationRead()`, `markAllNotificationsRead()`, `fetchUnreadCount()` |
| `frontend/src/store/useNotificationStore.ts` | Estado Pinia: `notifications[]`, `unreadCount`, `trayOpen`, paginación; acciones: `fetchNotifications`, `markAsRead`, `markAllAsRead`, `addNotificationFromFcm`, `toggleTray` |
| `frontend/src/interfaces/NotificationI.ts` | Tipos: `NotificationI`, `NotificationsPaginatedResponse` |
| `frontend/src/components/common/NotificationBell.vue` | Campanita en AppBar con badge numérico + dot rojo |
| `frontend/src/components/common/NotificationTray.vue` | Bandeja desplegable (Teleport al body) con últimas 5 notificaciones, mark all read, link a página completa |
| `frontend/src/pages/notifications/index.vue` | Página completa con lista paginada, indicador de no leídas, marcar individual/todas, iconos por tipo |
| `frontend/src/App.vue` | Suscriptor del evento `fcm:foreground-notification` → inyecta al store |
| `frontend/src/layouts/MainLayout.vue` | Integra `<NotificationBell />` en AppBar + `<NotificationTray />` fuera de VMain; ítem "Notificaciones" en baseMenu del sidebar |
| `frontend/src/services/http.ts` | `apiWithToken` con `baseURL: /api/v1` (corregido de `/api` en esta sesión) |
| `frontend/src/router/index.js` | Ruta `/notifications` |

**Errores corregidos en esta sesión:**

- `apiWithToken.baseURL` era `/api`, se cambió a `/api/v1` para coincidir con `getApiBaseUrl()`
- `store.fetchNotifications` fallaba con `response.meta is undefined` → se robusteció el parsing
- `composables/index.ts` re-exportaba `useRolesList` sin alias → `useRoles as useRolesList`

---

## 📎 Gestión Documental (Attachments)

### Funcionalidad

- Subida, descarga, reemplazo y eliminación de archivos por proyecto, tarea, ticket y bloqueador
- Almacenamiento aislado: `projects/{project_uuid}/{attachment_uuid}.ext`
- Modelo polimórfico con trait `HasAttachments`
- Componente `DocumentManager.vue` reutilizable con drag & drop
- Sección "Expediente digital" en todas las páginas de detalle y edición

### Archivos involucrados

**Backend:**

| Archivo | Rol |
|---|---|
| `backend/app/Models/Attachment.php` | Modelo polimórfico (`attachable`) con campos: `uuid, original_name, disk_path, mime_type, size, uploaded_by` |
| `backend/app/Traits/HasAttachments.php` | Trait para modelos (Project, Task, Ticket, Blocker): relación `attachments()`, `resolveProject()`, `getProjectUuid()` |
| `backend/app/Services/AttachmentService.php` | Lógica de negocio: `upload()`, `uploadMany()`, `delete()`, `deleteProjectDirectory()`, `ensureProjectUuid()` |
| `backend/app/Http/Controllers/Api/AttachmentController.php` | `download()` (con Gate::authorize), `destroy()` (NUEVO), `replace()` (NUEVO — reemplaza archivo manteniendo UUID) |
| `backend/app/Http/Controllers/Api/ProjectController.php` | `uploadAttachments()` (NUEVO — subida múltiple a proyecto existente) |
| `backend/app/Http/Controllers/Api/TaskController.php` | `uploadAttachments()` (NUEVO — subida múltiple a tarea existente) |
| `backend/app/Http/Controllers/Api/TicketController.php` | `uploadAttachments()` (NUEVO — subida múltiple a ticket existente) |
| `backend/app/Http/Controllers/Api/BlockerController.php` | `uploadAttachments()` (NUEVO — subida múltiple a bloqueador existente) |
| `backend/routes/api/attachments.php` | `GET /attachments/download/{uuid}`, `DELETE /attachments/{uuid}`, `POST /attachments/{uuid}/replace` |
| `backend/routes/api/projects.php` | `POST /projects/{project}/attachments` |
| `backend/routes/api/tasks.php` | `POST /tasks/{task}/attachments` |
| `backend/routes/api/tickets.php` | `POST /projects/{project}/tickets/{ticket}/attachments` |
| `backend/routes/api/blockers.php` | `POST /projects/{project}/blockers/{blocker}/attachments` |
| `backend/database/migrations/2026_06_04_010000_add_uuid_to_projects_table.php` | Agrega columna `uuid` a `projects` |
| `backend/database/migrations/2026_06_04_010001_create_attachments_table.php` | Crea tabla `attachments` polimórfica |

**Frontend:**

| Archivo | Rol |
|---|---|
| `frontend/src/interfaces/AttachmentI.ts` | Tipo `AttachmentI` |
| `frontend/src/composables/useAttachments.ts` | Composable: `upload()`, `download()`, `remove()`, `replace()`, `getFileIcon()`, `formatSize()` — eliminación de `buildFormData` duplicado |
| `frontend/src/components/common/DocumentManager.vue` | Componente genérico drag & drop (NUEVO) — recibe `parentType`, `parentId`, `attachments`, `canManage`; emite `@refresh` |
| `frontend/src/components/common/AttachmentList.vue` | Componente simple de lista (legacy, aún existe) |
| `frontend/src/pages/project-detail/ProjectOverviewTab.vue` | Sección "Expediente digital del proyecto" con `DocumentManager` |
| `frontend/src/pages/projects/[id].vue` | Integra `ProjectOverviewTab` con `:attachments`, `:can-delete`, `@refresh="loadProject"` |
| `frontend/src/pages/tasks/[id].vue` | Integra `DocumentManager` debajo del `TaskForm` |
| `frontend/src/pages/tickets/[id].vue` | Integra `DocumentManager` debajo del `TicketForm` |
| `frontend/src/pages/blockers/[id].vue` | Integra `DocumentManager` debajo del `BlockerForm` |
| `frontend/src/pages/tasks/view/[id].vue` | Vista de solo lectura con `DocumentManager` (NUEVO) |
| `frontend/src/pages/tickets/view/[id].vue` | Vista de solo lectura con `DocumentManager` (NUEVO) |
| `frontend/src/pages/blockers/view/[id].vue` | Vista de solo lectura con `DocumentManager` (NUEVO) |
| `frontend/src/services/projects.service.ts` | Se eliminó `buildFormData` (era función síncrona en un servicio async, rompía `createServiceComposable`) |

**Campos de archivos redundantes eliminados:**

- `frontend/src/components/tasks/TaskForm.vue` — ya no tiene `VFileInput`
- `frontend/src/components/tickets/TicketForm.vue` — ya no tiene `VFileInput`

---

## 📋 Vistas de solo lectura (View)

### Funcionalidad

- Páginas de visualización sin edición para tasks, tickets y blockers
- Botón 👁️ (mdi-eye) en las tablas de índice que navega a la vista
- Cada vista incluye datos del elemento + `DocumentManager` al final

### Archivos involucrados

| Archivo | Rol |
|---|---|
| `frontend/src/pages/tasks/view/[id].vue` | Vista tarea (NUEVO) |
| `frontend/src/pages/tickets/view/[id].vue` | Vista ticket (NUEVO) |
| `frontend/src/pages/blockers/view/[id].vue` | Vista blocker (NUEVO) |
| `frontend/src/router/index.js` | Rutas: `tasks-view`, `tickets-view`, `blockers-view` (NUEVO) |
| `frontend/src/pages/tasks/index.vue` | Botón ojo agregado en columna Acciones |
| `frontend/src/pages/tickets/index.vue` | Botón ojo agregado en columna Acciones |
| `frontend/src/pages/blockers/index.vue` | Botón ojo agregado en columna Acciones |

---

## 📐 Formulario de Fases (Refactorización)

### Funcionalidad

- Formulario unificado `PhaseForm.vue` reutilizado en `new.vue` y `[id].vue`
- Usa `Pick<ProjectPhaseI, 'name' | 'start_date' | 'end_date' | 'progress'>`
- Usa el composable existente `useProjectPhasesService` (creado con `createServiceComposable`)
- Campos: name, start_date, end_date, progress (slider 0-100%) — SIN order ni description

### Archivos involucrados

| Archivo | Rol |
|---|---|
| `frontend/src/components/project-phases/PhaseForm.vue` | Componente reutilizable (NUEVO) |
| `frontend/src/pages/phases/new.vue` | Refactorizado: ~17 líneas de lógica, usa `PhaseForm` + `useProjectPhasesService` |
| `frontend/src/pages/phases/[id].vue` | Refactorizado: ~50 líneas, usa `PhaseForm` + `useProjectPhasesService` |
| `frontend/src/interfaces/ProjectPhaseI.ts` | Interfaz: `project_id, name, start_date, end_date, progress` (sin order ni description) |
| `backend/app/Models/ProjectPhase.php` | Modelo: `project_id, name, start_date, end_date, progress` |
| `frontend/src/composables/index.ts` | `phaseFields = ['name', 'start_date', 'end_date', 'progress']` → `useProjectPhasesService` |

---

## 🔄 Router y Sesión

### Correcciones aplicadas

- **Restauración de `currentProject` en recarga**: El `beforeEach` del router ahora detecta si la ruta tiene `projectId`, y si el store no tiene `currentProject` (o es otro), llama a `projectsService.show()` para restaurarlo. Esto evita que el submenú lateral desaparezca al recargar en un submódulo.
- **Ítem "Notificaciones"** agregado al `baseMenu` del sidebar.
- **Rutas de vista** agregadas: `tasks-view`, `tickets-view`, `blockers-view`.
- **Ruta de vista de proyecto**: La página `[id].vue` ahora integra `ProjectOverviewTab`.

### Archivos involucrados

| Archivo | Rol |
|---|---|
| `frontend/src/router/index.js` | Guard `beforeEach` con restauración de `currentProject` + rutas nuevas |
| `frontend/src/layouts/MainLayout.vue` | `baseMenu` incluye "Notificaciones"; integra `NotificationBell` + `NotificationTray` |

---

## 🛠️ Otros fixes aplicados en esta sesión

| Problema | Solución | Archivos |
|---|---|---|
| `testPostman.json` desactualizado | Se agregaron endpoints de Notifications y Attachments | `testPostman.json` |
| `README.md` mínimo | Reescritura completa con arquitectura, instalación, endpoints, estructura | `README.md` |
| `checklist.md` vacío | Este documento | `checklist.md` |
| `composables/index.ts` error de export `useRolesList` | `export { useRoles as useRolesList }` | `frontend/src/composables/index.ts` |
| `projects.service.ts` exportaba `buildFormData` (síncrono) | Eliminado (ya existe en `useAttachments.ts`) | `frontend/src/services/projects.service.ts` |

---

## ⚠️ Puntos pendientes / Mejoras futuras

- [ ] Eliminar `frontend/src/components/common/AttachmentList.vue` si ya no se usa (reemplazado por `DocumentManager.vue`)
- [ ] Agregar tests E2E con Cypress o Playwright
- [x] Implementar vista de solo lectura para el resto de entidades (phases, risks, deliverables, milestones, objectives)
- [x] Agregar botón de ojo en los índices de risks, deliverables, milestones y objectives
- [ ] Eliminar archivos legacy: `frontend/src/pages/ProjectDetailPage.vue`, `ProjectFeaturesLayoutPage.vue` si ya no se usan (migrados a `projects/[id].vue`)
- [ ] Verificar que `frontend/src/services/project-reports.service.ts` esté integrado
- [ ] Refactorizar `TicketForm.vue` y `TaskForm.vue` para que también usen composables existentes (`useTicketsService`, `useProjectTasksService`) en lugar de imports directos de servicios
- [ ] Agregar indicador de carga en vistas de solo lectura
- [ ] Mejorar manejo de errores en `DocumentManager.vue` con snackbar en lugar de solo consola
- [x] Proteger campo `budget` con `canAction('Proyecto.ViewBudget')` en métricas y detalle de proyecto
- [x] Agregar métricas de usuario en `members/view/[id].vue`
- [x] Corregir orden de rutas `view/:id` antes de `:id` para evitar conflicto de router
- [ ] Actualizar `archivosMap.md` con los nuevos archivos creados

---

## 🔒 Protección de datos sensibles

El campo `budget` (presupuesto) está protegido con `canAction('Proyecto.ViewBudget')` en:

| Archivo | Elemento |
|---|---|
| `pages/metrics/index.vue` | `VListItem` de Presupuesto |
| `pages/projects/[id].vue` | `<VCol>` de Presupuesto |

`ProjectForm.vue` ya está protegido implícitamente porque el formulario entero se renderiza bajo `v-if="canAction('Proyecto.Update')"`.

---

## 🧪 Testing

```bash
# Todos los tests
docker exec -it gestion_proyectos_backend_app php artisan test

# Tests específicos
docker exec -it gestion_proyectos_backend_app php artisan test tests/Feature/Ticket/
docker exec -it gestion_proyectos_backend_app php artisan test tests/Unit/Notifications/
```

---

## 📦 Documentación relacionada

- `README.md` — Documentación completa del proyecto
- `testPostman.json` — Colección Postman actualizada con todos los endpoints
- `archivosMap.md` — Mapa de archivos (posiblemente desactualizado)

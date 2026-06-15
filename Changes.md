# Changes.md — Contexto para sesiones futuras

**Última actualización:** 2026-06-15

---

## [2026-06-15] Notificaciones FCM, permisos de tareas/tickets y field_permissions

### ✅ Notificación nativa del navegador en primer plano

**Causa:** `listenForegroundNotifications()` en `frontend/src/services/firebase.ts` solo despachaba un `CustomEvent` (`fcm:foreground-notification`) pero nunca llamaba a `new Notification()`. En primer plano, el Service Worker no interviene (Firebase SDK lo enruta al handler `onMessage`).

**Solución:** Agregado `new Notification()` con `tag: payload.messageId` para evitar duplicados entre pestañas.

**Archivos modificados:**

- `frontend/src/services/firebase.ts`

### ✅ Destrucción del token FCM al cerrar sesión (evita conflicto multi-usuario)

**Causa:** Al cerrar sesión, el token FCM sobrevivía en el navegador y en la BD. Si otro usuario iniciaba sesión en el mismo navegador, `getToken()` devolvía el mismo token → conflicto.

**Solución:**

1. Nueva función `deleteFcmToken()` en `frontend/src/services/firebase.ts`: ejecuta `deleteToken(messaging)` (SDK Firebase) + `POST /fcm/remove-token` (backend).
2. `clearSession()` en `useAuthStore.ts` ahora es `async` y llama `await deleteFcmToken()` **antes** de limpiar el auth token.
3. `MainLayout.vue::handleLogout()` reordenado: primero `clearSession()` (FCM + permisos), luego `logout()` (servidor).

**Archivos modificados:**

- `frontend/src/services/firebase.ts`
- `frontend/src/store/useAuthStore.ts`
- `frontend/src/layouts/MainLayout.vue`

### 🔴 Permiso fantasma `task.edit` en `UpdateTaskRequest`

**Causa:** `UpdateTaskRequest::authorize()` usaba `task.edit`, un permiso que nunca se creó en el seeder. Los permisos reales son `task.edit-content` (PM/owner) y `task.edit-own` (developer/QA).

**Solución:** Reemplazado `task.edit` → `task.edit-content`, y agregado `task.edit-own` al OR de `authorize()`.

**Archivos modificados:**

- `backend/app/Http/Requests/Task/UpdateTaskRequest.php`

### 🔴 Índices de tareas y tickets: `canAction` sin `resourceOwnerId` ocultaba el botón de editar

**Causa:** Los índices usaban `canAction(['task.edit-content', 'task.edit-own'])` sin `resourceOwnerId`. `canAction()` explícitamente retorna `false` para permisos `-own` en contexto de lista sin owner. Resultado: developers/QA nunca veían el botón de editar en sus propias tareas.

**Solución:**

- `frontend/src/pages/tasks/index.vue`: `canAction(['task.edit-content', 'task.edit-own'], item.assigned_to)`
- `frontend/src/pages/tickets/index.vue`: `canAction(['ticket.edit-any', 'ticket.edit-own'], item.created_by)` (3 ubicaciones: lista, kanban cards, kanban menu)

**Archivos modificados:**

- `frontend/src/pages/tasks/index.vue`
- `frontend/src/pages/tickets/index.vue`

### 🔴 `TaskForm.vue` no implementaba `field_permissions`

**Causa:** El backend calcula correctamente `field_permissions` en `FieldPermissionsService` y los retorna en el endpoint `GET /tasks/{id}`, pero el `TaskForm.vue` nunca los consumía. Todos los campos aparecían editables, el usuario intentaba editar, el backend rechazaba con 403.

**Solución:** Integrado `useFieldLock` en `TaskForm.vue`. Cada campo (`title`, `description`, `status`, `priority`, `due_date`, `estimated_hours`, `progress`, `phase_id`, `assigned_to`) ahora tiene `:disabled="!fl.<campo>.value"`. El developer ve `status` y `progress` habilitados (según `TaskPolicy::updateStatus` y `FieldPermissionsService::forTask`), y el resto deshabilitados.

**Archivos modificados:**

- `frontend/src/components/tasks/TaskForm.vue`

### 🔴 Acciones de resolución/aprobación faltantes en blockers, deliverables y milestones

**Causa:** El backend tiene endpoints y permisos correctos para resolver blockers (`blocker.resolve`), aprobar deliverables (`deliverable.approve`), y marcar milestones completados (`milestone.edit`). Pero el frontend no exponía estas acciones correctamente:

- **Blockers vista detalle:** Sin botón "Resolver" (solo editar)
- **Blockers vista lista:** Sin botón rápido "Resolver"
- **Deliverables vista detalle:** Sin botón "Aprobar"
- **Deliverables vista lista:** El `VSwitch` de aprobado no respetaba permisos (cualquiera podía togglear)
- **Milestones vista lista:** El `VSwitch` de completado no respetaba permisos

**Solución:**

- `frontend/src/pages/blockers/view/[id].vue`: + botón "Resolver" (`canAction('blocker.resolve') && !blocker.resolved`) + función `resolveBlocker()`
- `frontend/src/pages/blockers/index.vue`: + botón "Resolver" en la lista
- `frontend/src/pages/deliverables/view/[id].vue`: + botón "Aprobar" (`canAction('deliverable.approve') && !item.approved`) + función `approveDeliverable()`
- `frontend/src/pages/deliverables/index.vue`: `VSwitch` de aprobado ahora con `:disabled="!canAction('deliverable.approve')"`
- `frontend/src/pages/milestones/index.vue`: `VSwitch` de completado ahora con `:disabled="!canAction('milestone.edit')"`

**Archivos modificados:**

- `frontend/src/pages/blockers/view/[id].vue`
- `frontend/src/pages/blockers/index.vue`
- `frontend/src/pages/deliverables/view/[id].vue`
- `frontend/src/pages/deliverables/index.vue`
- `frontend/src/pages/milestones/index.vue`

### 🔴 `DocumentManager` usaba permisos incorrectos para adjuntos

**Causa:** Las vistas de tasks y tickets pasaban `canAction(['task.edit-content', 'task.edit-own'])` y `canAction(['ticket.edit-any', 'ticket.edit-own'])` a `DocumentManager`, en lugar del permiso real de adjuntos (`task.manage-attachments`, `ticket.manage-attachments`).

**Solución:** Corregido en 4 archivos.

**Archivos modificados:**

- `frontend/src/pages/tasks/[id].vue`
- `frontend/src/pages/tasks/view/[id].vue`
- `frontend/src/pages/tickets/[id].vue`
- `frontend/src/pages/tickets/view/[id].vue`

### 🔴 Permisos de proyecto nunca llegaban al frontend (roles sin rol global)

**Causa:** `AuthController` usa `$user->getAllPermissions()` (Spatie), que solo devuelve permisos **globales**. Usuarios sin rol global (dev, qa, support, client) recibían `permissions: []`. Sus permisos reales (por `ProjectMemberRole`) nunca se enviaban. El `PermissionStore` arrancaba vacío y `canAction()` siempre retornaba `false`.

**Solución:**

1. `backend/.../ProjectController.php`: `permissions()` ahora incluye `permissions: [...]` planos del rol del usuario en el proyecto (usando `ProjectMemberRole::permissionsFor()`).
2. `frontend/src/store/usePermissionStore.ts`: nuevo array `projectPermissions` + `setProjectPermissions()`; `hasPermission` consulta ambos.
3. `frontend/src/composables/useEnsureCurrentProject.ts`: tras cargar proyecto, llama `GET /projects/{id}/permissions`.
4. `frontend/src/router/index.js`: `beforeEach` también inyecta permisos al precachear proyecto.
5. `useEnsureCurrentProject()` agregado en **13 archivos** (7 vistas de detalle + 6 índices que no lo tenían).

**Archivos modificados:**

- `backend/app/Http/Controllers/Api/ProjectController.php`
- `frontend/src/store/usePermissionStore.ts`
- `frontend/src/store/useAuthStore.ts`
- `frontend/src/composables/useEnsureCurrentProject.ts`
- `frontend/src/router/index.js`
- `frontend/src/pages/tasks/index.vue`, `tasks/view/[id].vue`
- `frontend/src/pages/tickets/index.vue`, `tickets/view/[id].vue`
- `frontend/src/pages/blockers/index.vue`, `blockers/view/[id].vue`
- `frontend/src/pages/risks/index.vue`, `risks/view/[id].vue`
- `frontend/src/pages/milestones/index.vue`, `milestones/view/[id].vue`
- `frontend/src/pages/deliverables/index.vue`, `deliverables/view/[id].vue`
- `frontend/src/pages/objectives/index.vue`, `objectives/view/[id].vue`
- `frontend/src/pages/members/index.vue`
- `frontend/src/pages/plans/index.vue`

### ✅ Selector "Asignado a" solo muestra miembros del proyecto

**Causa:** `TaskForm.vue` y `TicketForm.vue` usaban `usersService.all()` (todos los usuarios del sistema), permitiendo asignar tareas/tickets a usuarios que no son miembros del proyecto.

**Solución:**

1. Nuevo endpoint `GET /projects/{project}/members/users` en `ProjectMemberController`.
2. Nuevo método `membersAsUsers()` en `project-members.service.ts`.
3. `TaskForm.vue` y `TicketForm.vue` usan `membersAsUsers(props.projectId)` en vez de `usersService.all()`.

**Archivos modificados:**

- `backend/app/Http/Controllers/Api/ProjectMemberController.php`
- `backend/routes/api/members.php`
- `frontend/src/services/project-members.service.ts`
- `frontend/src/components/tasks/TaskForm.vue`
- `frontend/src/components/tickets/TicketForm.vue`
- `frontend/src/pages/tickets/[id].vue`, `tickets/new.vue`

### ✅ Endpoint para cambiar rol de miembro (antes no existía)

**Causa:** `ProjectMemberController` solo tenía `store` (crear, falla si ya existe) y `destroy`. No había forma de cambiar el rol de un miembro existente (ej. developer → QA).

**Solución:**

1. `ProjectService::updateMember()` — actualiza el rol si el miembro existe.
2. `ProjectException`: + `memberNotFound()`, `cannotChangeOwnerRole()`.
3. `ProjectMemberController::update()` — `PUT /projects/{project}/members/{user}`.
4. `members/[id].vue`: `membersService.store()` → `membersService.update()`; roles: `'support'` → `'analyst'`.

**Archivos modificados:**

- `backend/app/Services/ProjectService.php`
- `backend/app/Exceptions/ProjectException.php`
- `backend/app/Http/Controllers/Api/ProjectMemberController.php`
- `backend/routes/api/members.php`
- `frontend/src/services/project-members.service.ts`
- `frontend/src/pages/members/[id].vue`

### ✅ Notificaciones al agregar/cambiar rol de miembro

**Causa:** `store()` y `update()` en `ProjectMemberController` no disparaban notificaciones. El servicio `ProjectMemberAddedNotificationService` ya existía pero nunca se llamaba.

**Solución:**

1. Inyectados `ProjectMemberAddedNotificationService` y nuevo `ProjectMemberRoleChangedNotificationService` en el controller.
2. `store()`: notifica al nuevo miembro ("Fuiste añadido a un proyecto") y al owner.
3. `update()`: notifica al usuario cuyo rol cambió ("Tu rol ahora es X").

**Archivos creados:**

- `backend/app/Services/Notifications/Domain/ProjectMemberRoleChangedNotificationService.php`

**Archivos modificados:**

- `backend/app/Http/Controllers/Api/ProjectMemberController.php`

### 🔴 Transición de estado inválida (X → X) y error `TaskStatus::tryFrom()`

**Causa:** Al actualizar solo `progress` sin cambiar `status`, el frontend enviaba `status: "review"` y el backend intentaba `changeStatus(Review → Review)`. `TaskStatus::canTransitionTo()` rechaza la transición. Además, `TaskService::changeStatus()` pasaba `$newStatus` (enum) a `$task->update(['status' => ...])` y el cast `TaskStatus::class` llamaba `tryFrom()` sobre un enum.

**Solución:**

- `TaskController::update()`: verifica `$task->status !== $newStatus` antes de `changeStatus()`.
- `TaskService::changeStatus()`: `$newStatus` → `$newStatus->value`.

**Archivos modificados:**

- `backend/app/Http/Controllers/Api/TaskController.php`
- `backend/app/Services/TaskService.php`

### 🔴 `UpdateTaskRequest::authorize()` no permitía al asignado editar

**Causa:** `authorize()` usaba solo `canForProject()` que requiere membresía explícita. Si el developer era miembro con rol "developer", `canForProject('task.edit-own')` retornaba `true`. Pero si el rol en la membresía era otro (ej. "qa"), fallaba aunque el usuario estuviera asignado a la tarea.

**Solución:** El FormRequest ahora también verifica `$task->assigned_to === $user->id` en combinación con `canForProject('task.edit-own')` o `'task.update-status'`.

**Archivos modificados:**

- `backend/app/Http/Requests/Task/UpdateTaskRequest.php`

---

## [2026-06-15] Corrección de notificaciones internas

### 🔴 Notificaciones nunca llegaban al crear tareas con assigned_to

**Causa:** Triple bug:

1. **`APP_ENV` no definido en `docker-compose.yml`** → Laravel usa `production` por defecto. En `production`, `shouldDiscoverEvents()` retorna `false` y se requiere cache de eventos. El entrypoint ejecutaba `optimize:clear` sin regenerarlo → eventos sin listeners.
2. **`EventServiceProvider` no registrado en `bootstrap/providers.php`** → En Laravel 11+, los providers deben estar explícitamente en `bootstrap/providers.php`. Sin él, el array `$listen` nunca se cargaba → eventos sin listeners.
3. **`$task->priority?->value` en `TaskAssignedNotificationService`** → `priority` es `string` en el modelo `Task`, no un enum. Causaba "Attempt to read property value on string" al intentar notificar.

**Soluciones:**

1. `docker-compose.yml`: Agregado `APP_ENV: local` y `APP_DEBUG: "true"` al environment `&laravel-env`.
2. `bootstrap/providers.php`: Agregado `App\Providers\EventServiceProvider::class`.
3. `app/Services/Notifications/Domain/TaskAssignedNotificationService.php`: Cambiado `$task->priority?->value` → `$task->priority`.

**Archivos modificados:**

- `docker-compose.yml`
- `backend/bootstrap/providers.php`
- `backend/app/Services/Notifications/Domain/TaskAssignedNotificationService.php`

---

## [2026-06-12] Menú lateral, shortcuts, permisos de proyecto y sincronización de roles

### ✅ Menú lateral con persistencia localStorage + FAB

- `frontend/src/layouts/MainLayout.vue`: estado `drawer`/`rail` guardado en `localStorage` (`gestion_proyectos_sidebar`).
- FAB (`VBtn` flotante `ri-menu-line`) visible solo cuando el drawer está cerrado y no en modo rail. Al hacer clic, reabre el drawer.
- Watch reactivo actualiza localStorage en cada cambio.

### ✅ Dashboard shortcuts

- `frontend/src/pages/DashboardPage.vue`: sección de atajos (`shortcuts` computed) con tarjetas enlace a Proyectos, Tareas, Tickets, Miembros y Métricas.
- Las tarjetas usan `VCard` con `hover`, `ripple`, `VAvatar` con icono de color y navegan con `router.push`.

### ✅ Permiso de edición de proyecto — endpoint + composable

**Backend:**

- `backend/app/Http/Controllers/Api/ProjectController.php`: método `permissions()` — `GET /api/v1/projects/{project}/permissions`.
- Usa `Gate::check()` para devolver `{ can_edit, can_delete, can_assign_members, can_manage_attachments, is_owner, project_role }`.
- `backend/routes/api/projects.php`: ruta registrada.

**Frontend:**

- `frontend/src/composables/useProjectPermission.ts`: composable que acepta `Ref<number | null>` (ID del proyecto), hace la petición y expone `canEdit`, `canDelete`, `canAssignMembers`, `canManageAttachments`, `isOwner`, `projectRole`, `loading`, `error`.
- Reactivo: se vuelve a consultar automáticamente si cambia el `projectId`.

### ✅ Sincronización de permisos al cambiar roles (ROLE_MISMATCH)

**Backend — Migración:**

- `backend/database/migrations/2026_06_12_000001_add_role_changed_at_to_users_table.php`: columna `role_changed_at TIMESTAMP NULL` en `users`.

**Backend — Modelo:**

- `backend/app/Models/User.php`: `role_changed_at` agregado a `$fillable` y `$casts` como `datetime`.

**Backend — Evento:**

- `backend/app/Events/RoleChanged.php`: evento con `$user`, `$oldRole`, `$newRole`. Se dispara desde `UserController::update()`.

**Backend — Listener:**

- `backend/app/Listeners/InvalidateUserSession.php`: al recibir `RoleChanged`, actualiza `role_changed_at`, limpia caché Spatie y despacha `SendPermissionsUpdatedNotificationJob` (FCM).

**Backend — Middleware:**

- `backend/app/Http/Middleware/CheckRoleChanged.php`: middleware registrado en el grupo `api`. Compara `user.role_changed_at` con `token.created_at`. Si el rol cambió después de emitir el token, responde **HTTP 409** con `{ code: 'ROLE_MISMATCH' }`.

**Backend — Registro:**

- `backend/app/Providers/EventServiceProvider.php`: `RoleChanged::class => [InvalidateUserSession::class]`.
- `backend/bootstrap/app.php`: middleware `check.role.changed` registrado como alias y agregado al grupo `api`.

**Backend — Controller:**

- `backend/app/Http/Controllers/Api/UserController.php`: al cambiar rol, dispara `event(new RoleChanged(...))` en lugar de solo limpiar caché. Si solo cambian permisos (sin cambio de rol), mantiene el comportamiento anterior.

**Frontend — Axios interceptor:**

- `frontend/src/services/http.ts`: response interceptor que detecta `409 + code === 'ROLE_MISMATCH'` y emite `window.dispatchEvent(new CustomEvent('auth:role-mismatch'))`.

**Frontend — Diálogo:**

- `frontend/src/components/common/RoleMismatchDialog.vue`: `VDialog` persistente con mensaje "Tus permisos han sido actualizados..." y botón "Recargar ahora" (`window.location.reload()`). Escucha evento `auth:role-mismatch`.

**Frontend — Integración:**

- `frontend/src/App.vue`: importa y renderiza `<RoleMismatchDialog />` al final del template.

**Flujo completo:**

1. Admin cambia rol → `UserController` dispara `RoleChanged`
2. `InvalidateUserSession` actualiza `role_changed_at`, limpia caché Spatie, envía FCM `permissions_updated`
3. Usuario afectado recibe FCM → `refreshPermissions()` (ya implementado)
4. Si el usuario hace petición API antes del FCM, middleware devuelve 409
5. Interceptor axios emite `auth:role-mismatch` → diálogo pide recargar
6. Al recargar, `router.beforeEach` → `me()` → `setSession()` → `permissionStore.setPermissions()` con permisos frescos

---

## [2026-06-09] Correcciones de infraestructura, navegación y permisos

### 🔴 502 en backend (puerto 8000)

**Causa:** Nginx cacheaba IP del contenedor `backend`. Al reiniciar el backend, obtenía nueva IP pero nginx seguía apuntando a la vieja → `Connection refused`.

**Solución:** `nginx.conf` — agregado `resolver 127.0.0.11 valid=10s ipv6=off;` (DNS interno Docker) y `fastcgi_pass` con variable (`$backend_upstream`) para forzar re-resolución DNS cada 10s.

### 🔴 Iconos Remix Icons rotos

**Causa:** `frontend/src/plugins/remix-icons.ts` hacía spread de `...props` que incluía la prop `icon` (string como `"ri-home-line"`), sobreescribiendo el `class` CSS recién generado.

**Solución:** Desestructurar `icon` de props antes del spread al DOM. Solo props DOM-viables llegan al elemento `<i>`.

### 🔴 Navegación requería doble clic

**Causa:** `router.beforeEach` hacía `await projectsService.show(projectId)` sin feedback visual.

**Solución:** Se agregó `appStore.loader = true` antes de la llamada y `appStore.loader = false` en `finally`. El loader global (VOverlay + VProgressCircular en `App.vue`) se muestra inmediatamente. Navegaciones subsecuentes son instantáneas (proyecto en caché).

### 🔴 Sincronización de permisos Frontend ↔ Backend

**Causa:** El frontend usaba `canAction('Proyecto.Store')` (español, PascalCase) pero el backend envía `project.create` (inglés, minúsculas). El PermissionStore nunca hacía match.

**Solución:**

- `canAction()` ahora acepta `string | string[]` (OR lógico) + ownership para `-own`
- Reemplazo masivo (sed) de 30+ llamadas en 22+ archivos Vue a los nombres reales de permisos
- Mapeo completo en `usersCanAction.md`

---

## Arquitectura de permisos (RBAC + Spatie)

### Flujo backend → frontend

```
POST /auth/login  →  { user: { roles: [...], permissions: [...] } }
GET  /auth/me     →  { items: { roles: [...], permissions: [...] } }

useAuthStore.setSession()  →  permissionStore.setPermissions(user.permissions)
canAction('project.create') →  permissionStore.hasPermission('project.create')
```

### Roles globales (Spatie)

| Rol | Permisos clave |
|-----|---------------|
| `super-admin` | Bypass de todas las Policies (gate-before). CRUD completo de usuarios. |
| `project-manager` | Crear proyectos, gestionar miembros, adjuntos, aprobar/resolver. Sin eliminar proyectos ni gestionar usuarios. |
| `developer` | Tareas propias (edit-own, update-status, log-time). Tickets propios. Bloqueadores (solo crear). |
| `qa` | Igual que developer pero sin `log-time`. |
| `support` | Tickets (crear, edit-own, assign). Ver tareas y usuarios. |
| `client` | Solo lectura + tickets propios (solo si Open). Ver milestones, deliverables, reportes. |

### Reglas de Policies (backend)

- **Tareas `Done`:** Nadie edita ni cambia estado
- **Tickets `Closed`:** Nadie edita
- **Blockers `resolved`:** Nadie edita ni resuelve
- **`task.edit-own`:** Solo tareas asignadas al usuario, solo si no Done
- **`ticket.edit-own` (Client):** Además verifica que el ticket esté `Open`
- **Adjuntos:** Solo PM/owner (`task.manage-attachments`, `ticket.manage-attachments`)

Archivos clave:

- `backend/database/seeders/RolesAndPermissionsSeeder.php` — permisos y roles
- `backend/app/Policies/TaskPolicy.php` — reglas de tareas
- `backend/app/Policies/TicketPolicy.php` — reglas de tickets
- `backend/app/Policies/BlockerPolicy.php` — reglas de bloqueadores
- `usersCanAction.md` — matriz completa de permisos por rol

---

## Infraestructura Docker

### Servicios (`docker-compose.yml`)

| Servicio | Puerto host | Puerto interno | Notas |
|----------|------------|---------------|-------|
| `nginx` | 8000, 8001 | 80, 8001 | Proxy → backend:9000 |
| `backend` | — | 9000 | PHP-FPM, entrypoint con migraciones y seeders |
| `frontend` | 5173 | 5173 | Vite dev server con HMR |
| `mysql` | 3319 | 3306 | MySQL 8.4 |
| `redis` | — | 6379 | Cache + queues |
| `horizon` | — | — | Laravel Horizon (queues) |
| `scheduler` | — | — | Laravel schedule:work |

### Usuarios de prueba

| Email | Password | Rol |
|-------|----------|-----|
| `superadmin@test.com` | `password` | super-admin |
| `pm@test.com` | `password` | project-manager |
| `dev@test.com` | `password` | Sin rol global (se asigna por proyecto) |
| `qa@test.com` | `password` | Sin rol global |
| `support@test.com` | `password` | Sin rol global |
| `client@test.com` | `password` | Sin rol global |

### Comandos útiles

```bash
# Reiniciar todo
docker compose down && docker compose up -d --build

# Refrescar BD con datos de demo
docker compose exec backend php artisan migrate:refresh --seed

# Logs
docker logs gestion_proyectos_nginx --tail 50
docker logs gestion_proyectos_frontend_app --tail 20

# Tests
docker compose exec backend php artisan test
docker compose exec frontend npm run test
```

---

## Frontend — Estructura clave

### Stores (Pinia)

- `useAuthStore` — authUser, currentProject, roles, setSession/clearSession
- `usePermissionStore` — permissions[], hasPermission, setPermissions/refreshPermissions
- `useAppStore` — loader, snackbar (globales)

### Helpers

- `canAction(action: string | string[], ownerId?)` — verifica permisos + ownership
- `useEnsureCurrentProject()` — carga proyecto en onMounted para sidebar

### Plugins

- `remix-icons.ts` — Custom icon set de Vuetify para Remix Icons. **No hacer spread de `...props` sin desestructurar `icon`.**

### Router

- `beforeEach`: restaura sesión (me), precarga currentProject con loader. No bloquear la navegación sin feedback visual.

---

## Skills aprendidas

1. **Nginx + Docker:** Usar `resolver 127.0.0.11 valid=10s` y variables en `fastcgi_pass` para evitar 502 cuando los contenedores se reinician.
2. **Iconos en Vuetify:** Los custom icon sets deben limpiar la prop `icon` antes del spread al DOM.
3. **Navegación async:** Siempre mostrar loader cuando `beforeEach` hace llamadas API bloqueantes.
4. **Permisos frontend:** Los nombres en `canAction()` deben coincidir **exactamente** con los que devuelve `$user->getAllPermissions()->pluck('name')` del backend. Usar arrays para OR lógico (`['task.edit-content', 'task.edit-own']`).
5. **Permisos -own:** Sin `resourceOwnerId`, `canAction('task.edit-own')` retorna false en contextos de lista.
6. **FormRequests:** Son la fuente de verdad de qué campos acepta cada endpoint. Verificar antes de agregar campos al frontend.
7. **Endpoints dedicados:** Usar `PATCH /resolve`, `PATCH /approve` en lugar de switches en formularios genéricos.
8. **FCM:** Nunca llamadas HTTP externas sincrónicas desde controllers. Usar Jobs asíncronos.

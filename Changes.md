# Changes.md — Historial de cambios y skills del proyecto

**Última actualización:** 2026-08-06

---

## [2026-08-06] Auditoría de coherencia Frontend-Backend + Correcciones

### Diagnóstico

Se realizó una auditoría completa comparando 30+ archivos del backend (Requests, Policies) con 35+ archivos del frontend (Services, Composables, Formularios, Interfaces). Se encontraron y corrigieron **15 hallazgos**.

### Cambios realizados

#### 🔴 CRÍTICO (1)

| Archivo | Cambio |
|---------|--------|
| `backend/app/Http/Requests/Auth/LoginRequest.php` | `authorize()` retornaba `false` (login imposible). Corregido a `return true;` con reglas `email` + `password` requeridos. |

#### 🔴 ALTA (4)

| Archivo | Cambio |
|---------|--------|
| `backend/app/Http/Requests/Project/StoreProjectRequest.php` | Agregado `'progress' => ['nullable','integer','min:0','max:100']` (frontend lo enviaba sin que backend lo aceptara) |
| `frontend/src/components/blockers/BlockerForm.vue` | Eliminado switch `resolved` — la resolución se hace vía endpoint `PATCH /resolve`, no desde el formulario |
| `backend/app/Http/Requests/Milestone/StoreMilestoneRequest.php` | Agregado `'completed' => ['nullable','boolean']` |
| `frontend/src/components/deliverables/DeliverableForm.vue` | Eliminado switch `approved` — la aprobación se hace vía `PATCH /approve` |

#### 🟡 MEDIA (5)

| Archivo | Cambio |
|---------|--------|
| `backend/app/Http/Requests/Project/UpdateProjectRequest.php` | Agregado `'code' => ['sometimes','string','max:50','unique:projects,code']` para permitir editar código |
| `backend/app/Http/Requests/ProjectPhase/StoreProjectPhaseRequest.php` | Agregado `progress` en creación |
| `backend/app/Http/Requests/Objective/StoreObjectiveRequest.php` | Agregado `completed` en creación |
| `backend/app/Http/Requests/ProjectPlan/` | Eliminado `UpdateUserRequest.php` (duplicado mal ubicado). Creado `UpdateProjectPlanRequest.php` correcto |
| `frontend/src/components/users/UserForm.vue` | Roles alineados con backend: `super-admin` y `project-manager` (eliminados `admin`, `user` que no son válidos) |

#### 🟢 BAJA (5)

| Archivo | Cambio |
|---------|--------|
| `frontend/src/components/tasks/TaskForm.vue` | Agregado selector `phase_id` con carga de fases del proyecto + prop `projectId` |
| `frontend/src/pages/tasks/new.vue`, `[id].vue` | Pasan prop `projectId` al TaskForm |
| `frontend/src/components/blockers/BlockerForm.vue` | Agregado VFileInput para upload de attachments |
| `frontend/src/composables/useBlockers.ts` | Adaptado con `buildFormData` para soportar archivos |
| `frontend/src/services/project-blockers.service.ts` | `store()` y `update()` aceptan `BlockerPayload | FormData` |
| `frontend/src/pages/blockers/new.vue` | Captura evento `@update:attachments` |
| `frontend/src/components/tickets/TicketForm.vue` | Agregado VFileInput + emit `update:attachments` |
| `frontend/src/interfaces/ProjectI.ts` | `budget` tipado corregido: `string | null` → `number | null` |

#### ✅ Verificado

| Archivo | Resultado |
|---------|-----------|
| `backend/app/Policies/BlockerPolicy.php` | Existe y es correcto — `update()` y `resolve()` verifican owner/manager + estado `resolved` |

### Lecciones aprendidas (skills)

1. **Los FormRequests del backend son la fuente de verdad de qué campos acepta cada endpoint.** Antes de agregar un campo al formulario, verificar que el Request correspondiente lo acepte en el modo correcto (create vs update).
2. **Campos de estado con acciones dedicadas (resolved, approved) no deben estar en formularios genéricos.** Usar endpoints separados (`PATCH /resolve`, `PATCH /approve`) con botones de acción específicos.
3. **Los roles del frontend deben coincidir exactamente con los del backend.** Si el backend solo acepta `['super-admin', 'project-manager']` en `StoreUserRequest`, el formulario no debe ofrecer `admin` o `user`.
4. **Cuando se agrega una prop a un componente compartido entre create y edit, actualizar todas las páginas que lo usan** (ej: `projectId` en TaskForm → `tasks/new.vue` y `tasks/[id].vue`).
5. **Los servicios que aceptan archivos deben tener firmas `Payload | FormData`** y los composables deben usar `buildFormData` condicionalmente.

---

## Arquitectura de autorización (RBAC+ABAC Híbrido)

### Skills fundamentales

#### Flujo de permisos

```
Backend (Laravel Policies + Spatie)
  ├─ User::canForProject($project, 'permiso') → consulta project_members
  ├─ User::hasProjectRole($project, 'rol')     → verifica rol en proyecto
  ├─ FieldPermissionsService                   → computa field_permissions por recurso
  └─ API devuelve permissions[] + field_permissions

Frontend (PermissionStore + canAction + useFieldLock)
  ├─ PermissionStore.hasPermission('permiso')  → lista plana de strings
  ├─ canAction('permiso', ownerId?)            → verifica token + permiso + ownership (-own)
  └─ useFieldLock(fieldPermissions)            → Proxy reactivo de computed<boolean> por campo
```

#### Principios

- **Backend es fuente única de verdad.** El frontend no duplica reglas de estado (done/closed) ni de roles.
- **Field-level locking:** La API devuelve `field_permissions: { title: true, status: false, ... }` y el frontend bloquea campos reactivamente con `useFieldLock`.
- **Invalidación en tiempo real:** FCM envía `{ type: 'permissions_updated' }` → `PermissionStore.refreshPermissions()` → UI se actualiza sin recarga.
- **Acciones `-own`:** `canAction('ticket.edit-own', ownerId)` verifica que `authUser.id === ownerId`. Sin reglas de estado.

#### Adjuntos (ciclo de vida temporal)

```
1. Frontend sube archivo → POST /api/attachments/upload-temp → status='temp' en drafts/
2. Usuario crea/edita recurso + envía UUIDs → POST /api/attachments/claim
3. AttachmentService::claim() → DB::transaction:
   - rename() de drafts/{uuid}.ext → projects/{project_uuid}/{uuid}.ext
   - UPDATE status='claimed', attachable_type, attachable_id
```

---

## Testing

### Backend (PHPUnit)

```bash
docker compose exec backend php artisan test
# 88 tests, 88 assertions
```

### Frontend (Vitest)

```bash
docker compose exec frontend npm run test
# 15 archivos, 135 tests
```

### E2E (Playwright)

```bash
cd frontend && npx playwright test --headed
# 2 archivos, 6 grupos de tests
```

### Documentación

- `docs/testing-documentation.md` — Diagramas Mermaid, matriz de cobertura, CI/CD
- `backend/tests/Feature/Attachment/AttachmentClaimTest.php` — 3 tests de ciclo de vida de adjuntos
- `backend/tests/Feature/Auth/PermissionsFlowTest.php` — 4 tests de flujo de permisos
- `backend/tests/Feature/Project/ProjectScopedAccessTest.php` — 4 tests de scoping cross-project

---

## Notas operativas para sesiones futuras

1. **Migraciones ya aplicadas en BD de desarrollo:** `add_status_to_attachments_table`, `make_attachable_columns_nullable`.
2. **FieldPermissionsService** actualmente solo en `TaskController.show()`. Pendiente agregar a otros controllers.
3. **Firebase:** Configurar variables de entorno en `backend/.env` para que FCM funcione. Si no, falla silenciosamente.
4. **Migración progresiva de `v-if="canAction(...)"`** a `field_permissions` + `useFieldLock` para bloqueo de campos en formularios.
5. **El código en `backend/app/Http/Requests/ProjectPlan/`** debe usar `UpdateProjectPlanRequest` (ya creado) en el controller correspondiente.
6. **Antes de agregar campos a formularios,** verificar que el FormRequest del backend los acepte en el modo correcto (create/store vs update).

---

## [2026-08-06] Auditoría FCM — Puntos críticos + Cobertura de eventos

### 🔴 CRÍTICOS FCM (3)

| # | Archivo | Problema | Corrección |
|---|---------|----------|------------|
| C1 | `FirebaseNotificationService.php` | `private_key` vacía → `RuntimeException` al firmar JWT → crash de notificaciones | Agregado flag `$configured`. Si `project_id` o `private_key` están vacíos, `sendToToken()` retorna `false` graceful. Log warning. |
| C2 | `SendTaskAssignedNotificationJob.php` | **Job vacío** (`handle() {}`). Las notificaciones de asignación nunca se enviaban. | Implementado completamente: carga `assignee`, tokens, envía push con título "Nueva tarea asignada", 3 reintentos, backoff exponencial. |
| C3 | `UserController::update()` | Envío FCM sincrónico bloqueaba el request HTTP (2-5s de latencia). Si Google OAuth fallaba, 500 aunque datos ya guardados. | Creado `SendPermissionsUpdatedNotificationJob`. Reemplazada llamada directa por `::dispatch($user)` asíncrono. |

### 🔴 ALTA FCM (2)

| # | Archivo | Problema | Corrección |
|---|---------|----------|------------|
| A1 | `SendPushNotificationJob.php` | Bug: estado `sent`/`failed` se sobrescribía por el último token. Si usuario tiene 3 dispositivos y el 1ro OK pero el 2do falla, estado final = `failed`. | Flag `$anySuccess`. Al final del loop: `status = $anySuccess ? 'sent' : 'failed'`. `sent_at` solo si éxito. |
| A2 | `FcmTokenController::register()` | **Robo de token entre usuarios.** Búsqueda sin scope: si token existía para cualquier usuario, se reasignaba al actual. Usuario A cierra sesión, B inicia en mismo navegador → B recibe notificaciones de A. | Búsqueda con scope: `$user->fcmTokens()->where('token', ...)`. Si pertenece a otro, se crea nuevo registro. |

### 🟡 MEDIA FCM (3 — documentados)

| # | Hallazgo | Recomendación |
|---|----------|---------------|
| M1 | API keys Firebase hardcodeadas en `firebase.ts` + `firebase-messaging-sw.js` | Verificar restricción por dominio en GCP Console |
| M2 | Polling cada 30s en `App.vue` redundante con FCM push | Eliminar `setInterval`, confiar en foreground notifications |
| M3 | Notificaciones foreground `id: Date.now()` temporal — se pierden al recargar | Persistir en `localStorage` hasta sincronizar con backend |

### 🟢 BAJA FCM (2 — documentados)

| # | Hallazgo | Recomendación |
|---|----------|---------------|
| B1 | `Notification.permission === 'denied'` no se re-chequea si usuario reactiva permisos manualmente | Escuchar `navigator.permissions.query({name:'notifications'}).onchange` |
| B2 | `setSession()` inicia FCM pero router `beforeEach` no espera → race condition | Mover `requestNotificationPermission()` a `App.vue onMounted` |

### Skills FCM aprendidas

1. **Nunca hacer llamadas HTTP externas sincrónicas desde un controller.** Usar Jobs asíncronos (Horizon/Redis) para FCM, emails, reportes.
2. **Validar configuración al inicio del servicio, no en cada llamada.** Si `private_key` está vacía, loguear warning una vez y deshabilitar.
3. **Scope de tokens FCM por usuario.** Un token de dispositivo pertenece a un usuario específico. No reasignar sin verificar ownership.
4. **Estado de notificación multi-dispositivo:** Usar flag `anySuccess` — si al menos un dispositivo recibe la notificación, considerar éxito.
5. **No mezclar polling con push.** Si ya tienes FCM, no hagas `setInterval` cada 30s.

---

## [2026-08-06] Cobertura de notificaciones por eventos

### Pipeline de notificaciones

```
Observer (Eloquent) → Event → Listener → DomainNotificationService → SendPushNotificationJob → FCM
                                       ├─ NotificationRecipientResolver (destinatarios)
                                       ├─ PolicyAwareRecipientFilter (filtro por permisos)
                                       └─ AbstractNotificationService (persiste + despacha)
```

### Eventos IMPLEMENTADOS (14 — cobertura completa)

| Evento | Observer | Destinatarios |
|--------|----------|---------------|
| `TaskAssigned` | TaskObserver | Asignado |
| `TaskCompleted` | TaskObserver | Creador + PM |
| `TaskStatusChanged` | TaskObserver | Creador + Asignado |
| `TaskCreated` | TaskObserver | Asignado (si se crea con `assigned_to`) |
| `CommentCreated` | (controller) | Asignado + Creador tarea |
| `TicketCreated` | TicketObserver | Miembros del proyecto |
| `TicketAssigned` | TicketObserver | Asignado + Creador ticket |
| **`TicketClosed`** ⭐ | TicketObserver | Creador + Asignado ticket |
| `BlockerCreated` | BlockerObserver | PM + managers + Asignado tarea |
| **`BlockerResolved`** ⭐ | BlockerObserver | Reporter del blocker + Asignado tarea |
| `MilestoneCompleted` | MilestoneObserver | Miembros del proyecto |
| `ProjectUpdated` | ProjectObserver | Miembros del proyecto |
| `ProjectMemberAdded` | ProjectObserver | Nuevo miembro |
| **`DeliverableApproved`** ⭐ | DeliverableObserver | Miembros del proyecto |
| **`RiskDetected`** ⭐ | **RiskObserver (NUEVO)** | Miembros con `risk.view` |

⭐ = Agregado en esta sesión

### Observers mejorados

| Observer | Antes | Ahora |
|----------|-------|-------|
| `TaskObserver` | Vacío (stub) | `created()` → TaskCreated + TaskAssigned. `updated()` → TaskStatusChanged + TaskCompleted + TaskAssigned |
| `TicketObserver` | `created()` → TicketCreated + TicketAssigned. `updated()` → TicketAssigned | Agregado `TicketClosed` cuando status → `'closed'` |
| `RiskObserver` | **NO existía** | `created()` → RiskDetected |

### Archivos creados (FCM + eventos): 13

```
backend/app/
├── Jobs/
│   └── SendPermissionsUpdatedNotificationJob.php  (NUEVO — FCM asíncrono para permisos)
├── Events/
│   ├── TicketClosed.php        (reescrito — tenía constructor vacío)
│   └── RiskDetected.php        (reescrito — tenía constructor vacío)
├── Listeners/
│   ├── HandleBlockerResolved.php   (MOD — inyecta BlockerResolvedNotificationService)
│   ├── HandleDeliverableApproved.php (MOD — inyecta DeliverableApprovedNotificationService)
│   ├── HandleTicketClosed.php     (NUEVO)
│   └── HandleRiskDetected.php     (NUEVO)
├── Services/Notifications/Domain/
│   ├── BlockerResolvedNotificationService.php     (NUEVO)
│   ├── TicketClosedNotificationService.php       (NUEVO)
│   ├── DeliverableApprovedNotificationService.php (NUEVO)
│   └── RiskDetectedNotificationService.php       (NUEVO)
├── Observers/
│   ├── TaskObserver.php     (reescrito — ahora dispara eventos)
│   ├── TicketObserver.php   (MOD — agrega TicketClosed)
│   └── RiskObserver.php     (NUEVO)
└── Providers/
    └── EventServiceProvider.php  (MOD — registra RiskDetected, TicketClosed, RiskObserver)
```

### Archivos modificados (Fase 1 + Fase 2 + Fase 3): 35 archivos totales

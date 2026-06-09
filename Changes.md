# Changes.md — Refactorización RBAC+ABAC Híbrido, Field-Locking y Attachments Asíncronos

**Fecha:** 2026-06-08  
**Rama base:** `main` (commit `767283b`)  
**Archivo de referencia:** `refactorizar.md`

---

## Resumen General

Se realizó una refactorización de 5 puntos débiles identificados en una auditoría previa del sistema de gestión de proyectos (Laravel 12 + Vue 3 + Pinia + Vuetify 3). El objetivo fue mantener coherencia con el codebase existente, no romper tests existentes, y seguir los estándares de desarrollo del proyecto (simplicidad, no duplicación, autorización centralizada).

---

## Punto 1: Autorización con alcance de proyecto (`canForProject` / `hasProjectRole`)

### Diagnóstico

Los métodos `canForProject()` y `hasProjectRole()` **ya existían** en el modelo `User` (líneas 219-244). Estos métodos consultan la membresía del usuario en el proyecto a través de la tabla `project_members` y verifican permisos usando `ProjectMemberRole::permissionsFor()`. Las policies (`TaskPolicy`, `TicketPolicy`, etc.) ya los usaban correctamente.

### Cambios realizados

- **Ningún cambio en código de producción.** El mecanismo ya estaba implementado.
- **Creado:** `backend/tests/Feature/Project/ProjectScopedAccessTest.php`
  - `test_non_member_cannot_view_project_tasks`: Un developer que no es miembro del proyecto 2 no puede ver sus tareas (403).
  - `test_member_can_view_own_project_tasks`: Un developer miembro del proyecto 1 sí puede ver sus tareas.
  - `test_task_from_wrong_project_returns_404`: Una tarea del proyecto 2 no es accesible vía URL del proyecto 1.
  - `test_cross_project_permission_scoping`: Intento de edición cross-project devuelve 404.

### Por qué

El mecanismo de scoping ya existía pero no tenía cobertura de tests que validaran explícitamente el bloqueo cross-project. Estos tests aseguran que la lógica de `BelongsToProject` y las policies funcionan correctamente en conjunto.

---

## Punto 2: Eliminación de lógica de autorización duplicada entre frontend y backend

### Diagnóstico

- `frontend/src/helpers/canAction.ts` era un stub que solo verificaba si había token, devolviendo `true` para todo. No existía lógica de negocio duplicada realmente, pero tampoco había integración con el backend.
- Las policies de Laravel ya contienen toda la lógica de negocio (estados done/closed, ownership, roles de proyecto).

### Cambios realizados

#### Frontend — PermissionStore

- **Creado:** `frontend/src/store/usePermissionStore.ts`
  - Almacena `permissions: string[]` cargados desde el backend.
  - `setPermissions(perms)`: Inicializa desde login/me.
  - `refreshPermissions()`: Llama a `POST /api/auth/refresh-permissions` (post-FCM invalidation).
  - `clearPermissions()`: Limpia en logout.
  - `hasPermission`: Computed que verifica si un permiso está en la lista.

#### Frontend — canAction.ts

- **Modificado:** `frontend/src/helpers/canAction.ts`
  - Ahora recibe `action: string` y `resourceOwnerId?: number | null`.
  - Si el `PermissionStore` no está cargado aún, es permisivo (retorna `true` con token). Una vez cargado, se vuelve restrictivo reactivamente.
  - Si el permiso termina en `-own`, verifica que `authStore.authUser.id === resourceOwnerId`.
  - **No contiene ninguna regla de estado** (done/closed), rol, ni ownership que no sea el `-own`. Todo eso lo determina el backend vía `field_permissions`.

#### Frontend — useAuthStore

- **Modificado:** `frontend/src/store/useAuthStore.ts`
  - `setSession()`: Después de guardar el usuario, llama a `permissionStore.setPermissions(user.permissions)`.
  - `clearSession()`: También limpia `permissionStore.clearPermissions()`.

#### Backend — FieldPermissionsService

- **Creado:** `backend/app/Services/FieldPermissionsService.php`
  - Servicio que computa `field_permissions` para cada tipo de recurso llamando a los métodos de las policies reales (`Gate::forUser($user)->allows('update', $task)`, etc.).
  - Soporta: Task, Ticket, Project, Risk, Blocker, Milestone, Deliverable, Objective.
  - Para Task: `title`, `description`, `status`, `priority`, `due_date`, `estimated_hours`, `progress`, `assigned_to`, `attachments`, `log_time`. Cada campo se evalúa contra la policy correspondiente.
  - Para Ticket: `title`, `description`, `status`, `priority`, `category`, `assigned_to`, `attachments`.

#### Backend — TaskController

- **Modificado:** `backend/app/Http/Controllers/Api/TaskController.php`
  - Inyecta `FieldPermissionsService` en el constructor.
  - `show()`: Después de cargar relaciones, adjunta `$task->field_permissions = $this->fieldPermissionsService->compute(...)`.

#### Tests

- **Modificado:** `frontend/src/__tests__/helpers/canAction.spec.ts`
  - 7 tests que cubren: sin token, token vacío, permisivo sin store cargado, permiso presente, permiso ausente, acción `-own` con dueño correcto, acción `-own` con dueño incorrecto.
- **Creado:** `backend/tests/Feature/Auth/PermissionsFlowTest.php`
  - `test_me_endpoint_returns_permissions`
  - `test_refresh_permissions_returns_updated_permissions`
  - `test_unauthenticated_cannot_refresh_permissions`
  - `test_task_show_includes_field_permissions`

### Por qué

El backend debe ser la única fuente de verdad para autorización. El frontend solo debe reflejar lo que el backend dicta. `field_permissions` permite que los formularios se bloqueen reactivamente sin duplicar reglas de negocio. `PermissionStore` centraliza los permisos del usuario y permite invalidación en tiempo real vía FCM.

---

## Punto 3: Refactorización de `v-can-action` a composable reactivo

### Diagnóstico

No existía una directiva `v-can-action` en el código. La tarea pide crear un composable reactivo que reemplace el patrón de directiva, ya que las directivas de Vue no reaccionan a cambios profundos en el contexto.

### Cambios realizados

- **Creado:** `frontend/src/composables/useFieldLock.ts`
  - `useFieldLock(fieldPermissions)`: Retorna un `Proxy` que crea `computed<boolean>` por campo bajo demanda. Totalmente reactivo.
  - `useField(fieldPermissions, fieldName)`: Versión explícita para un solo campo.
  - Ejemplo de uso:

    ```ts
    const { canEditTitle } = useFieldLock(fieldPermissions);
    // <VTextField :disabled="!canEditTitle" />
    ```

- **Modificado:** `frontend/src/composables/index.ts`
  - Agregado `export { useFieldLock, useField } from './useFieldLock';`

### Por qué

Los composables son reactivos por naturaleza (usan `computed`), a diferencia de las directivas cuyo hook `updated` solo se dispara cuando cambia la referencia del binding, no sus propiedades internas. Usar `computed` asegura que cualquier cambio en `field_permissions` (ej. status cambia de open a closed) se refleje inmediatamente en la UI.

---

## Punto 4: Invalidación de caché de permisos en tiempo real vía FCM

### Diagnóstico

Cuando un admin cambia roles/permisos de un usuario (`PUT /api/admin/users/{user}`), el cambio no afecta las sesiones activas porque Spatie cachea los permisos. No existía mecanismo de invalidación.

### Cambios realizados

#### Backend — refresh-permissions endpoint

- **Modificado:** `backend/app/Http/Controllers/Api/AuthController.php`
  - Nuevo método `refreshPermissions()`:
    - Verifica autenticación.
    - Llama a `PermissionRegistrar::forgetCachedPermissions()` para forzar recarga desde BD.
    - Retorna `$user->getAllPermissions()->pluck('name')`.
- **Modificado:** `backend/routes/api/auth.php`
  - Agregada ruta `POST refresh-permissions` dentro del grupo `auth:sanctum`.

#### Backend — FCM invalidation en UserController

- **Modificado:** `backend/app/Http/Controllers/Api/UserController.php`
  - En `update()`: después de `syncPermissions()` o cambio de rol, si es admin:
    - Limpia caché con `PermissionRegistrar::forgetCachedPermissions()`.
    - Envía notificación FCM silenciosa con `['type' => 'permissions_updated']` al usuario afectado usando `FirebaseNotificationService::sendToUser()`.

#### Frontend — Escucha FCM en App.vue

- **Modificado:** `frontend/src/App.vue`
  - En `handleForegroundNotification()`: si `fcmData?.type === 'permissions_updated'`, llama a `permissionStore.refreshPermissions()` y retorna temprano (no crea notificación visual).
  - Importa y usa `usePermissionStore`.

### Por qué

Spatie cachea permisos por 24 horas por defecto. Sin invalidación, un usuario recién degradado seguiría teniendo acceso hasta que expire su token o cierre sesión. La notificación FCM silenciosa permite al frontend refrescar permisos sin intervención del usuario ni recarga de página.

---

## Punto 5: Movimiento robusto de archivos adjuntos durante el claim

### Diagnóstico

No existía un mecanismo de "claim" en el código. Los archivos se subían directamente asociados a un recurso padre. La tarea pide implementar un ciclo de vida temporal con movimiento seguro de archivos, evitando `str_replace` frágil.

### Cambios realizados

#### Migración

- **Creado:** `backend/database/migrations/2026_06_14_000001_add_status_to_attachments_table.php`
  - Agrega columna `status` (string, default `'claimed'`) a la tabla `attachments`.
  - Valores: `'temp'` (temporal, en drafts/) o `'claimed'` (asociado a un padre, en projects/).

#### Modelo

- **Modificado:** `backend/app/Models/Attachment.php`
  - Agregado `'status'` al array `$fillable`.

#### AttachmentService

- **Modificado:** `backend/app/Services/AttachmentService.php`
  - Nueva constante `DRAFT_ROOT = 'drafts'`.
  - `upload()`: Ahora también persiste `status = 'claimed'`.
  - `uploadTemporary(UploadedFile, User)`: Nuevo método.
    - Sube a `drafts/{uuid}.{ext}`.
    - Crea registro con `status = 'temp'`, sin `attachable_type` ni `attachable_id`.
  - `claim(Model, array uuids, User)`: Nuevo método.
    - Busca attachments del usuario con `status = 'temp'` y UUIDs dados.
    - Envuelve todo en `DB::transaction()`.
    - Usa `Str::afterLast($diskPath, '/')` para extraer el nombre de archivo de forma segura (sin `str_replace`).
    - Reconstruye rutas usando `config('filesystems.disks.local.root')` como base.
    - Mueve archivo con `rename()`.
    - Actualiza `disk_path`, `status = 'claimed'`, `attachable_type`, `attachable_id`.
    - Crea directorio destino si no existe (`mkdir(..., 0755, true)`).

#### AttachmentController

- **Modificado:** `backend/app/Http/Controllers/Api/AttachmentController.php`
  - `uploadTemporary(Request)`: Valida array de files, delega en `AttachmentService::uploadTemporary()`.
  - `claim(Request)`: Valida `parent_type` (tasks/tickets/blockers/projects/deliverables), `parent_id`, `uuids[]`. Resuelve el modelo padre dinámicamente con `match()`. Autoriza con `Gate::authorize('update', $parent)`. Delega en `AttachmentService::claim()`.

#### Rutas

- **Modificado:** `backend/routes/api/attachments.php`
  - `POST attachments/upload-temp`
  - `POST attachments/claim`

#### Tests

- **Creado:** `backend/tests/Feature/Attachment/AttachmentClaimTest.php`
  - `test_full_temp_upload_and_claim_lifecycle`: Subida temporal → crear tarea → claim → verificar movimiento de archivo y cambio de estado.
  - `test_cannot_claim_other_user_temp_attachments`: Un usuario no puede claimear archivos temporales de otro.
  - `test_unauthenticated_cannot_upload_temp`: Sin autenticación no se puede subir.

### Por qué

`str_replace('drafts/...', 'projects/...', $path)` es frágil porque:

1. Si la estructura de directorios cambia, el reemplazo falla silenciosamente.
2. Si el token aparece en otra parte del path (poco probable pero posible), se corrompe la ruta.

El nuevo enfoque:

- Usa `Str::afterLast()` para extraer solo el nombre del archivo.
- Reconstruye rutas desde directorios base configurables.
- Usa transacción de BD para atomicidad.
- Crea directorios destino si no existen.

---

## Estructura de archivos creados

```
frontend/src/
├── store/
│   └── usePermissionStore.ts          # NUEVO: Store centralizado de permisos
├── composables/
│   ├── useFieldLock.ts                # NUEVO: Composable de field-locking reactivo
│   └── index.ts                       # MOD: Agregados exports de useFieldLock/useField
├── helpers/
│   └── canAction.ts                   # MOD: Lógica mínima, usa PermissionStore
├── store/
│   └── useAuthStore.ts                # MOD: Prima/limpia PermissionStore
├── App.vue                            # MOD: Escucha permissions_updated FCM
└── __tests__/helpers/
    └── canAction.spec.ts              # MOD: 7 tests actualizados

backend/
├── app/
│   ├── Models/
│   │   └── Attachment.php             # MOD: Agregado status a fillable
│   ├── Services/
│   │   ├── AttachmentService.php      # MOD: uploadTemporary + claim con safe path
│   │   └── FieldPermissionsService.php # NUEVO: Cómputo de field_permissions
│   └── Http/Controllers/Api/
│       ├── AttachmentController.php   # MOD: uploadTemporary + claim endpoints
│       ├── AuthController.php         # MOD: refreshPermissions endpoint
│       ├── TaskController.php         # MOD: Inyecta FieldPermissionsService
│       └── UserController.php         # MOD: FCM invalidation en update()
├── database/migrations/
│   └── 2026_06_14_000001_add_status_to_attachments_table.php  # NUEVO
├── routes/api/
│   ├── auth.php                       # MOD: refresh-permissions route
│   └── attachments.php                # MOD: upload-temp + claim routes
└── tests/Feature/
    ├── Attachment/
    │   └── AttachmentClaimTest.php    # NUEVO: 3 tests de ciclo de vida
    ├── Auth/
    │   └── PermissionsFlowTest.php    # NUEVO: 4 tests de permisos
    └── Project/
        └── ProjectScopedAccessTest.php # NUEVO: 4 tests de scoping
```

## Tests existentes no modificados

Ningún test existente fue eliminado o modificado (excepto `canAction.spec.ts` que se actualizó para reflejar la nueva lógica mínima). Los tests de backend (`TaskTest.php`, `ProjectTest.php`, `TicketTest.php`, etc.) usan `RefreshDatabase` con el seeder `RolesAndPermissionsSeeder` y los métodos `canForProject`/`hasProjectRole` que ya existían, por lo que deben seguir pasando.

## Resultados de tests

### Backend: 88/88 pasan ✅

**Tests nuevos (11/11):**

- `ProjectScopedAccessTest` — 4 tests ✅
- `PermissionsFlowTest` — 4 tests ✅
- `AttachmentClaimTest` — 3 tests ✅

**Tests existentes (77/77):** Todos pasan. Los 2 fallos pre-existentes en `TicketTest` fueron corregidos al encontrar y arreglar la causa raíz: `UpdateTicketRequest::authorize()` verificaba `can('ticket.edit')` (permiso inexistente) en lugar de `can('ticket.edit-any') || can('ticket.edit-own')`.

### Frontend: 13/13 archivos, 124/124 tests ✅

**Tests actualizados:**

- `canAction.spec.ts` — 7 tests (nueva lógica basada en `PermissionStore`, sin reglas de negocio)

**Fix de infraestructura:** Se agregó mock de Firebase Messaging en `setup.ts` para evitar crash en Node.js por APIs de navegador faltantes.

## Notas para sesiones futuras

1. **Migraciones ya aplicadas:** `2026_06_14_000001_add_status_to_attachments_table` y `2026_06_14_000002_make_attachable_columns_nullable` ya están ejecutadas en la BD de desarrollo.
2. **Ejecutar tests:** `docker compose exec backend php artisan test` para backend.
3. **El `FieldPermissionsService` solo se agregó a `TaskController.show()`.** Falta agregarlo a `TicketController`, `ProjectController`, `RiskController`, `BlockerController`, `MilestoneController`, `DeliverableController`, `ObjectiveController` en sus métodos `show()` respectivos.
4. **El `PermissionStore` en frontend asume que el backend devuelve `permissions` en `auth/me` y `auth/login`.** Esto ya ocurre (ver `AuthController`).
5. **Firebase debe estar configurado** (variables de entorno en `backend/.env`) para que `FirebaseNotificationService` funcione. Si no, los FCM silenciosos fallarán silenciosamente (logueado).
6. **La directiva `v-can-action` no existía** en el código original. Se creó el composable `useFieldLock` como reemplazo proactivo. Los templates existentes que usan `v-if="canAction('...')"` deben migrarse progresivamente a usar `field_permissions` + `useFieldLock` para el bloqueo de campos.
7. **Correcciones aplicadas durante testing:**
   - `PermissionRegistrar::forgetCachedPermissions()` no es estático → se usa `app(PermissionRegistrar::class)->forgetCachedPermissions()`.
   - Las columnas `attachable_type`/`attachable_id` eran NOT NULL → se creó migración `000002` para hacerlas nullable (soporta SQLite y MySQL).
   - El método `claim()` usaba funciones raw de PHP (`file_exists`, `rename`, `mkdir`) → se refactorizó para usar `Storage::disk('local')` (compatible con `Storage::fake()` en tests).

# Mapa de archivos — Gestión de Proyectos

> **Propósito:** Referencia rápida para sesiones de desarrollo. Cada archivo tiene una descripción de su responsabilidad en el sistema.

---

## Backend (Laravel 12)

### Controllers (`backend/app/Http/Controllers/Api/`)

| Archivo | Responsabilidad |
|---------|----------------|
| `AttachmentController.php` | Descarga, subida temporal (`upload-temp`), claim, eliminación y reemplazo de adjuntos |
| `AuthController.php` | Login, logout, registro, perfil (`me`), refresh de permisos vía FCM |
| `BlockerController.php` | CRUD de bloqueadores (impedimentos de tareas) |
| `DashboardController.php` | Dashboard resumen por usuario autenticado |
| `DeliverableController.php` | CRUD y aprobación de entregables de proyecto |
| `FcmTokenController.php` | Registro y eliminación de tokens FCM para notificaciones push |
| `MilestoneController.php` | CRUD de hitos (milestones) |
| `NotificationController.php` | CRUD de notificaciones internas, marcar como leídas, contador no leídas |
| `ObjectiveController.php` | CRUD de objetivos del proyecto |
| `ProjectController.php` | CRUD de proyectos, listado con scope por usuario |
| `ProjectMemberController.php` | Gestión de miembros del proyecto (agregar, remover) |
| `ProjectMetricsController.php` | Métricas calculadas del proyecto (progreso, presupuesto, tareas) |
| `ProjectPhaseController.php` | CRUD de fases del proyecto |
| `ProjectPlanController.php` | CRUD de planes del proyecto (alcance, requerimientos) |
| `ProjectReportController.php` | Generación de reportes (DOCX, XLSX) |
| `RiskController.php` | CRUD de riesgos del proyecto |
| `RoleController.php` | Listado de roles y permisos disponibles |
| `TaskCommentController.php` | CRUD de comentarios en tareas |
| `TaskController.php` | CRUD de tareas, cambio de status, asignación, logs de tiempo, adjuntos, `field_permissions` |
| `TaskTimeLogController.php` | Registro y listado de horas trabajadas en tareas |
| `TicketController.php` | CRUD de tickets, asignación, adjuntos |
| `UserController.php` | CRUD de usuarios, métricas, asignación de roles/permisos, invalidación FCM |

### Requests (FormRequest) (`backend/app/Http/Requests/`)

Validación de entrada para cada recurso. Organizados por subdirectorio: `Auth/`, `Blocker/`, `Deliverable/`, `Member/`, `Milestone/`, `Objective/`, `Project/`, `ProjectPhase/`, `ProjectPlan/`, `Report/`, `Risk/`, `Task/`, `TaskComment/`, `TaskTimeLog/`, `Ticket/`, `User/`.

### Models (`backend/app/Models/`)

| Archivo | Tabla | Relaciones clave |
|---------|-------|-----------------|
| `User.php` | `users` | Spatie HasRoles, `projectMemberships`, `ownedProjects`, `assignedTasks`, `fcmTokens` |
| `Project.php` | `projects` | `owner`, `members`, `phases`, `tasks`, `tickets`, `risks`, `blockers`, `deliverables`, `milestones`, `metrics` |
| `ProjectMember.php` | `project_members` | `user_id`, `project_id`, `role` |
| `Task.php` | `tasks` | `project`, `assignee`, `creator`, `phase`, `comments`, `timeLogs`, `attachments` |
| `Ticket.php` | `tickets` | `project`, `creator`, `assignee`, `attachments` |
| `Risk.php` | `risks` | `project`, `reported_by` |
| `Blocker.php` | `blockers` | `project`, `task`, `reported_by`, `resolved_by` |
| `Milestone.php` | `milestones` | `project` |
| `Deliverable.php` | `deliverables` | `project`, `approved_by` |
| `Objective.php` | `objectives` | `project` |
| `ProjectPhase.php` | `project_phases` | `project` |
| `ProjectPlan.php` | `project_plans` | `project` |
| `ProjectMetric.php` | `project_metrics` | `project` |
| `Attachment.php` | `attachments` | Polimórfico (`attachable`), `uploader`, `uuid`, `disk_path`, `status` |
| `TaskAttachment.php` | `task_attachments` | Modelo legacy — reemplazado por polimórfico `Attachment` |
| `TaskComment.php` | `task_comments` | `task`, `user` |
| `TaskTimeLog.php` | `task_time_logs` | `task`, `user` |
| `ActivityLog.php` | `activity_logs` | `user`, polimórfico `subject` |
| `Notification.php` | `notifications` | `user`, datos JSON, `read_at` |
| `FcmToken.php` | `fcm_tokens` | `user`, `token`, `platform`, `browser` |
| `UserMetric.php` | `user_metrics` | `user` |

### Policies (`backend/app/Policies/`)

| Archivo | Controla acceso a |
|---------|------------------|
| `UserPolicy.php` | `viewAny`, `view`, `create`, `update`, `delete` de usuarios |
| `ProjectPolicy.php` | `viewAny`, `view`, `create`, `update`, `delete`, `assignMembers`, `manageAttachments` |
| `TaskPolicy.php` | `view`, `create`, `update`, `updateStatus`, `assign`, `delete`, `logTime`, `manageAttachments` |
| `TicketPolicy.php` | `view`, `create`, `update` (edit-any vs edit-own + estado closed), `assign`, `delete`, `manageAttachments` |
| `BlockerPolicy.php` | `view`, `create`, `update`, `resolve` |
| `RiskPolicy.php` | `view`, `create`, `update`, `delete` |
| `MilestonePolicy.php` | `view`, `create`, `update`, `delete` |
| `DeliverablePolicy.php` | `view`, `create`, `update`, `approve` |
| `ObjectivePolicy.php` | `view`, `create`, `update` |

Todas las policies usan `$user->canForProject($project, 'permiso')` y `$user->hasProjectRole($project, 'rol')` para scope por proyecto.

### Services (`backend/app/Services/`)

| Archivo | Responsabilidad |
|---------|----------------|
| `AttachmentService.php` | Subida, subida temporal (`uploadTemporary`), claim (mueve archivos de `drafts/` a `projects/{uuid}/` con `DB::transaction`) |
| `FieldPermissionsService.php` | Computa `field_permissions` para cada recurso llamando a las policies reales via `Gate::forUser()` |
| `FirebaseNotificationService.php` | Envío de notificaciones push FCM v1 (token único, masivo, por usuario), Google OAuth2 JWT |
| `ProjectService.php` | Lógica de negocio de proyectos (crear, agregar/quitar miembros) |
| `TaskService.php` | Lógica de negocio de tareas (crear, cambio de status con transiciones válidas) |
| `TicketService.php` | Lógica de negocio de tickets (crear, cambio de estado) |
| `ProjectDashboardReportService.php` | Generación de reportes XLSX de proyecto |
| `ProjectExecutiveReportService.php` | Generación de reportes DOCX ejecutivos |
| `Notifications/AbstractNotificationService.php` | Clase base: resuelve destinatarios, filtra por policy, persiste en BD y despacha `SendPushNotificationJob` en chunks de 50 |
| `Notifications/NotificationRecipientResolver.php` | Resuelve destinatarios por rol, permiso, membresía de proyecto o asignado de tarea/ticket |
| `Notifications/PolicyAwareRecipientFilter.php` | Filtra usuarios por policy (`Gate::forUser()->check()`) dejando solo autorizados |
| `Notifications/Domain/TaskAssignedNotificationService.php` | Notificación de tarea asignada: destinatario = asignado, filtro `task.view` |

### Traits (`backend/app/Traits/`)

| Archivo | Uso |
|---------|-----|
| `BelongsToProject.php` | Verifica que un recurso pertenezca a un proyecto (usado en controllers) |
| `HasActivityLog.php` | Registra actividad automáticamente en modelos |
| `HasAttachments.php` | Relación polimórfica `attachments()`, resuelve `project` padre y `getProjectUuid()` |
| `HasMetrics.php` | Relación con métricas de proyecto/usuario |
| `HasProjectAccess.php` | `assertCanAccessProject()`, `assertProjectIsOpen()` para services/controllers |

### Jobs (`backend/app/Jobs/`)

Tareas asíncronas (Horizon/Redis): `GenerateProjectMetricsJob`, `GenerateProjectReportJob`, `LogActivityJob`, `ProcessRiskAnalysisJob`, `RecalculateProjectMetricsJob`, `RecalculateUserMetricsJob`, `SendPushNotificationJob`, `SendTaskAssignedNotificationJob`.

### Events & Listeners (`backend/app/Events/`, `backend/app/Listeners/`)

Sistema de eventos para notificaciones: cada evento de dominio (TaskCreated, TicketAssigned, BlockerResolved, etc.) tiene su listener que dispara notificaciones internas y push.

### Observers (`backend/app/Observers/`)

Observers de Eloquent para generar eventos automáticamente en cambios de modelo.

### Enums (`backend/app/Enums/`)

PHP 8.1 Backed Enums para estados, prioridades, roles, severidades. Cada enum tiene método `permissions()` o `label()`.

### Notifications (`backend/app/Notifications/`)

Clases de notificación Laravel para email y canales internos. Subdirectorio `Domain/` con notificaciones específicas por evento.

### Repositories (`backend/app/Repositories/`)

Capa de acceso a datos con interfaces (`Contracts/`). Implementaciones: `ProjectRepository`, `TaskRepository`, `TicketRepository`, `RiskRepository`.

---

## Frontend (Vue 3 + Pinia + Vuetify 3)

### Stores — Pinia (`frontend/src/store/`)

| Archivo | Estado que gestiona |
|---------|-------------------|
| `useAppStore.ts` | `loader` global, `snackbar` (éxito/error) |
| `useAuthStore.ts` | `authUser`, `currentProject`, `currentProjectRole`, roles, sesión, Firebase init |
| `useNotificationStore.ts` | Notificaciones internas: lista, no leídas, bandeja, FCM en primer plano |
| `usePermissionStore.ts` | Permisos del usuario (`permissions[]`), `hasPermission`, `refreshPermissions()` post-FCM |
| `useThemeStore.ts` | Tema Vuetify (claro/oscuro) |

### Composables (`frontend/src/composables/`)

| Archivo | Uso |
|---------|-----|
| `useFieldLock.ts` | `useFieldLock(fieldPermissions)` — Proxy reactivo que devuelve `computed<boolean>` por campo. Reemplaza v-can-action. |
| `useAttachments.ts` | Subida, descarga, eliminación, reemplazo de archivos |
| `createServiceComposable.ts` | Factory para crear composables de servicio CRUD genéricos |
| `useServiceRequest.ts` | Wrapper para peticiones HTTP con manejo de errores |
| `useEnsureCurrentProject.ts` | Asegura que `currentProject` esté cargado en el store |
| `useBlockers.ts`, `useDeliverables.ts`, `useMilestones.ts`, `useObjectives.ts`, `useProjects.ts`, `useRisks.ts`, `useTasks.ts`, `useTickets.ts` | Composables CRUD para cada recurso |
| `useUsers.ts`, `useUserForm.ts`, `useUserCreate.ts`, `useUserUpdate.ts`, `useUserDelete.ts`, `useUserList.ts` | Gestión de usuarios |
| `useRolesList.ts` | Listado de roles y permisos |
| `useConfirmAction.ts` | Diálogo de confirmación reutilizable |

### Services (`frontend/src/services/`)

| Archivo | Endpoints |
|---------|-----------|
| `http.ts` | Axios instance (`apiWithToken`), interceptors, helpers de token (localStorage) |
| `firebase.ts` | Inicialización de Firebase, `getToken`, `onMessage`, registro de token FCM |
| `auth.service.ts` | `login`, `logout`, `me`, `register` |
| `dashboard.service.ts` | Dashboard del usuario autenticado |
| `projects.service.ts` | CRUD de proyectos |
| `project-tasks.service.ts` | CRUD de tareas |
| `tickets.service.ts` | CRUD de tickets |
| `project-members.service.ts` | Gestión de miembros |
| `project-blockers.service.ts` | CRUD de bloqueadores |
| `project-deliverables.service.ts` | CRUD de entregables |
| `project-milestones.service.ts` | CRUD de hitos |
| `project-objectives.service.ts` | CRUD de objetivos |
| `project-phases.service.ts` | CRUD de fases |
| `project-plans.service.ts` | CRUD de planes |
| `project-reports.service.ts` | Generación de reportes |
| `project-risks.service.ts` | CRUD de riesgos |
| `roles.service.ts` | Listado de roles |
| `task-comments.service.ts` | Comentarios de tareas |
| `task-time-logs.service.ts` | Registro de horas |
| `users.service.ts` | CRUD de usuarios (admin) |
| `notifications.service.ts` | Notificaciones internas |

### Helpers (`frontend/src/helpers/`)

| Archivo | Propósito |
|---------|-----------|
| `canAction.ts` | Verificación mínima de permisos: token + `PermissionStore` + ownership para acciones `-own` |

### Router (`frontend/src/router/index.js`)

Rutas lazy-loading con guard `beforeEach`: restaura sesión desde token, carga `currentProject` al entrar a submódulos, protege rutas admin con `requiresSuperAdmin`.

### Layouts (`frontend/src/layouts/`)

`MainLayout.vue`: AppBar + NavigationDrawer con menú dinámico (base, proyecto activo, admin), NotificationBell, ThemeSelector.

### Components (`frontend/src/components/`)

| Archivo | Responsabilidad |
|---------|----------------|
| `tasks/TaskForm.vue` | Formulario de creación/edición de tareas. En **creación** (`id===0`) inyecta `field_permissions` sintéticos (todo `true`) porque el backend solo retorna `field_permissions` en `show()`. En **edición** usa `useFieldLock` con `field_permissions` reales del backend para bloquear campos según el rol. |
| `tickets/TicketForm.vue` | Formulario de creación/edición de tickets. Sin bloqueo de campos por permisos. |
| `blockers/BlockerForm.vue` | Formulario de creación/edición de bloqueadores. |
| `deliverables/DeliverableForm.vue` | Formulario de creación/edición de entregables. |
| `milestones/MilestoneForm.vue` | Formulario de creación/edición de hitos. |
| `objectives/ObjectiveForm.vue` | Formulario de creación/edición de objetivos. |
| `risks/RiskForm.vue` | Formulario de creación/edición de riesgos. |
| `projects/ProjectForm.vue` | Formulario de creación/edición de proyectos. |
| `users/UserForm.vue` | Formulario de creación/edición de usuarios (admin). |
| `KanbanBoard.vue` | Tablero Kanban para tickets. |
| `GanttChart.vue` | Gráfico Gantt para tareas. |
| `common/DocumentManager.vue` | Gestión de adjuntos con drag & drop. |
| `common/NotificationBell.vue` | Campana de notificaciones con contador no leídas. |
| `common/NotificationTray.vue` | Bandeja desplegable de notificaciones. |

### Pages (`frontend/src/pages/`)

Vistas organizadas por módulo: `projects/`, `tasks/`, `tickets/`, `blockers/`, `risks/`, `milestones/`, `deliverables/`, `objectives/`, `phases/`, `plans/`, `members/`, `metrics/`, `admin/`, `profile/`.

---

## Base de datos — Migraciones

| Archivo | Tabla/Columna |
|---------|--------------|
| `..._create_attachments_table.php` | `attachments` (polimórfica: `attachable_type` + `attachable_id` nullable, `uuid`, `disk_path`) |
| `..._add_status_to_attachments_table.php` | Agrega `status` (`temp`/`claimed`) a `attachments` |
| `..._make_attachable_columns_nullable.php` | Hace nullable `attachable_type`/`attachable_id` para attachments temporales |

---

## Testing

| Framework | Ubicación | Archivos | Tests | Ejecutar |
|-----------|-----------|----------|-------|----------|
| PHPUnit (backend) | `backend/tests/` | 12 | 88 | `docker compose exec backend php artisan test` |
| Vitest (frontend) | `frontend/src/__tests__/` | 15 | 135 | `docker compose exec frontend npm run test` |
| Playwright (E2E) | `frontend/tests/e2e/` | 2 | 6 grupos | `cd frontend && npx playwright test --headed` |

### Documentación de testing

`docs/testing-documentation.md` — Diagramas Mermaid (flujo cross-role, autorización, attachments), matriz de cobertura, instrucciones de ejecución, CI/CD.

---

## Archivos de configuración raíz

| Archivo | Propósito |
|---------|-----------|
| `docker-compose.yml` | Servicios: backend (PHP-FPM), nginx, frontend (Vite), horizon, scheduler, redis, mysql |
| `nginx.conf` | Proxy reverso: API en puerto 8000, Horizon en 8001 |
| `refactorizar.md` | Especificación original de la refactorización (5 puntos) |
| `Changes.md` | Registro completo de cambios de esta sesión |
| `ARCHITECTURAL_AUDIT.md` | Auditoría arquitectónica previa |
| `ARCHITECTURE_V2.md` | Documento de arquitectura V2 |
| `checklist.md` | Checklist de tareas pendientes |
| `backend/bootstrap/providers.php` | Registro de Service Providers (Laravel 11+) — incluye `EventServiceProvider` que mapea eventos→listeners |

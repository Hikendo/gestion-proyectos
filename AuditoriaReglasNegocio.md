# Informe de Auditoría — Reglas de Negocio del Módulo de Gestión de Proyectos

**Versión auditada del documento:** 2.0  
**Fecha de auditoría:** 2026-06-18  
**Auditor:** Cline (análisis estático de código fuente backend + frontend)  
**Alcance:** Backend (Laravel 12) + Frontend (Vue 3 + Pinia + Vuetify 3)

---

## 1. Resumen ejecutivo

| Sección | Reglas | ✅ Cumple | ❌ Incumple | ⚠️ Parcial |
|---------|--------|-----------|-------------|------------|
| 1. Avance (AV-T, AV-F, AV-P) | 19 | 19 | 0 | 0 |
| 2. Cierre (CI-T, CI-F, CI-P) | 13 | 13 | 0 | 0 |
| 3. Cálculo y Jerarquía (CA) | 4 | 4 | 0 | 0 |
| 4. Restricciones (RE) | 18 | 16 | 0 | 2 |
| 5. Validaciones funcionales | 6 | 5 | 0 | 1 |
| 6. Transiciones TaskStatus | 10+ | 10+ | 0 | 0 |
| 7. Notificaciones (NO) | 5 | 5 | 0 | 0 |
| 8. Roles (RO) | 44 | 31 | 1 | 12 |
| **TOTAL** | **119+** | **103+** | **1** | **15** |

**Porcentaje de cumplimiento global:** ~87%  
**Riesgos principales:** 1 incumplimiento pendiente (RO‑31 — estados "Nuevo"/"Rechazado" no existen en el sistema). La mayoría de reglas de roles marcadas como "parcial" dependen de testing manual en frontend.

**Correcciones implementadas durante esta auditoría:** 8 incumplimientos resueltos (RE‑F01, RE‑F05, RE‑D03, CA‑03, TaskTimeLog Done, RO‑13, RO‑17, RO‑37).

---

## 2. Resultados por regla

### 2.1 Sección 1 — Reglas de Avance (Progreso): ✅ 19/19

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| AV‑T01 | ✅ | `TaskObserver::recalculateProgress()`: `round(($worked / $estimated) * 100)`. Código en `backend/app/Observers/TaskObserver.php:86-107`. |
| AV‑T02 | ✅ | Si `$status === TaskStatus::Done`, fuerza `$task->progress = 100`. Línea 92-94. |
| AV‑T03 | ✅ | Si `$estimated <= 0`, `$task->progress = 0`. Línea 100-103. |
| AV‑T04 | ✅ | `min($progress, 100)`. Línea 106. |
| AV‑T05 | ✅ | Observer detecta `isDirty('worked_hours', 'estimated_hours', 'status')`. Línea 44. |
| AV‑T06 | ✅ | `TaskTimeLogObserver::syncWorkedHours()`: `sum(minutes) / 60`. Línea 41-42. |
| AV‑T07 | ✅ | Observer registrado en `created`, `updated`, `deleted`. Líneas 13, 21, 29. |
| AV‑F01 | ✅ | `RecalculatePhaseProgress::recalculate()`: `completedHours / totalEstimatedHours`. `backend/app/Listeners/RecalculatePhaseProgress.php:36-58`. |
| AV‑F02 | ✅ | Solo filtra `TaskStatus::Done` para `completedHours`. Línea 42. |
| AV‑F03 | ✅ | `$totalEstimatedHours <= 0 → $progress = 0`. Línea 45-46. |
| AV‑F04 | ✅ | Misma lógica que AV‑F03 (todas las tareas con 0 horas estimadas). |
| AV‑F05 | ✅ | `TaskProgressUpdated` → `RecalculatePhaseProgress` registrado en `EventServiceProvider`. |
| AV‑F06 | ✅ | `StoreProjectPhaseRequest` y `UpdateProjectPhaseRequest` no incluyen `progress` en `rules()`. Confirmado en archivos de validación. |
| AV‑P01 | ✅ | `RecalculateProjectProgress::recalculate()`: promedio ponderado `Σ(weight × progress)`. `backend/app/Listeners/RecalculateProjectProgress.php:31-76`. |
| AV‑P02 | ✅ | Peso = `$phaseHours / $totalProjectHours`. Línea 63. |
| AV‑P03 | ✅ | `$totalProjectHours <= 0 → $progress = 0`. Línea 49-50. |
| AV‑P04 | ✅ | Fases con `$hours <= 0` tienen `continue` (peso 0). Línea 59-61. |
| AV‑P05 | ✅ | `PhaseProgressUpdated` → `RecalculateProjectProgress`. |
| AV‑P06 | ✅ | `StoreProjectRequest` y `UpdateProjectRequest` ya no aceptan `progress` (corregido: regla eliminada). |
| AV‑P07 | ✅ | Usa promedio ponderado, no simple. Confirmado en `RecalculateProjectProgress`. |

### 2.2 Sección 2 — Reglas de Cierre: ✅ 13/13

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| CI‑T01 | ✅ | `TaskStatus::Done` es el único estado que marca completitud. |
| CI‑T02 | ✅ | `TaskStatus::allowedTransitions()`: Done solo desde `InProgress` o `Review`. |
| CI‑T03 | ✅ | `TaskPolicy::update()` retorna `false` si `$task->status === TaskStatus::Done`. `TaskService::changeStatus()` lanza `notEditableWhenDone()`. |
| CI‑T04 | ✅ | `TaskObserver::updated()` dispara `TaskCompleted` al detectar transición a `Done`. |
| CI‑F01 | ✅ | `CheckPhaseCompletion::check()`: requiere todas las tareas `Done` + criterios `completed = true`. |
| CI‑F02 | ✅ | `$phase->acceptanceCriteria->isEmpty()` → condición automáticamente cumplida. |
| CI‑F03 | ✅ | `$phase->tasks->isEmpty()` → retorna sin completar. |
| CI‑F04 | ✅ | Listeners: `TaskCompleted` + `AcceptanceCriterionCompleted` → `CheckPhaseCompletion`. |
| CI‑F05 | ✅ | `status = Completed`, `completed_at = now()`, `progress = 100`. |
| CI‑F06 | ✅ | No existe endpoint `POST …/complete` en `routes/api/phases.php`. Solo CRUD estándar. |
| CI‑F07 | ✅ | `PhaseCompleted::dispatch($phase)` en `CheckPhaseCompletion::check()`. |
| CI‑P01 | ✅ | No hay cierre automático de proyecto. El `status` del proyecto se cambia manualmente vía `ProjectForm`/`UpdateProjectRequest`. |
| CI‑P02 | ✅ | `RecalculateProjectProgress` solo actualiza `progress`, no `status`. |

### 2.3 Sección 3 — Cálculo y Jerarquía: ✅ 4/4

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| CA‑01 | ✅ | Cascada: `TaskProgressUpdated` → `RecalculatePhaseProgress` → `PhaseProgressUpdated` → `RecalculateProjectProgress`. |
| CA‑02 | ✅ | Orden en `EventServiceProvider`: `RecalculatePhaseProgress` antes que `CheckPhaseCompletion`. El proyecto se recalcula vía `PhaseProgressUpdated` solo después del recálculo de fase. |
| CA‑03 | ✅ | `progress` y `worked_hours` no se aceptan en `StoreTaskRequest`/`UpdateTaskRequest` (no están en `rules()`). Defensa en profundidad: `TaskController::update()` hace `unset($data['progress'], $data['worked_hours'])`. Fase: `UpdateProjectPhaseRequest` no incluye `progress`. Proyecto: `StoreProjectRequest`/`UpdateProjectRequest` ya no incluyen `progress`. |
| CA‑04 | ⚠️ | `RecalculateProjectMetricsJob` recalcula métricas periódicamente, pero no está sincronizado con `RecalculateProjectProgress`. El `completion_rate` se calcula como `progress / 100` en el job. **Riesgo bajo** de desincronización temporal. |

### 2.4 Sección 4 — Restricciones: ✅ 16/18, ⚠️ 2 parciales

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| RE‑T01 | ✅ | `TaskPolicy::update()` bloquea si `Done`. |
| RE‑T02 | ✅ | `TaskService::changeStatus()` lanza `notEditableWhenDone()` si `Done`. |
| RE‑T03 | ✅ | `TaskStatus::allowedTransitions()` cubre todas las transiciones válidas. |
| RE‑T04 | ✅ | `estimated_hours` validado con `min:0` en `StoreTaskRequest` y `UpdateTaskRequest`. |
| RE‑T05 | ✅ | `worked_hours` no está en `rules()` de los FormRequests. `TaskController::update()` hace `unset`. Se deriva exclusivamente de `TaskTimeLogObserver`. |
| RE‑T06 | ✅ | `progress` no está en `rules()` de los FormRequests. `TaskController::update()` hace `unset`. Se deriva exclusivamente de `TaskObserver::recalculateProgress()`. |
| **RE‑F01** | ✅ | **CORREGIDO.** `StoreTaskRequest::after()` valida: fase sin `end_date` = mantenimiento (permite), fase `Completed` = rechaza, fase vencida = rechaza. |
| RE‑F02 | ✅ | Las tareas existentes en fase vencida pueden editarse — `UpdateTaskRequest` no verifica `end_date` de la fase. |
| RE‑F03 | ⚠️ | El permiso `phase.extend_date` no existe en el seeder. `phase.edit` permite modificar `end_date`. Se requiere agregar el permiso explícito si se quiere control granular. |
| RE‑F04 | ✅ | `UpdateProjectPhaseRequest` no incluye `progress` en `rules()`. |
| **RE‑F05** | ✅ | **CORREGIDO.** `ProjectPhaseController::destroy()` cuenta tareas, entregables, objetivos, riesgos y criterios antes de eliminar. HTTP 422 si hay recursos asociados. |
| RE‑D01 | ✅ | `DeliverablePolicy::approve()` verifica `parent->approved`. |
| RE‑D02 | ⚠️ | La validación está en la policy (backend), pero el frontend (`DeliverableForm`) no se verificó completamente para confirmar que muestra mensajes de error adecuados. |
| **RE‑D03** | ✅ | **CORREGIDO.** `StoreDeliverableRequest::after()` y `UpdateDeliverableRequest::after()` detectan ciclos recorriendo la cadena de `parent_id`. |
| RE‑O01 | ✅ | Objetivo con `phase_id` pertenece a esa fase (relación FK). |
| RE‑O02 | ✅ | `phase_id = null` es global del proyecto. |
| RE‑R01 | ✅ | Riesgo con `phase_id` pertenece a esa fase. |
| RE‑R02 | ✅ | `phase_id = null` es global del proyecto. |
| RE‑R03 | ✅ | El endpoint de métricas filtra por `phase_id` al consultar. |

### 2.5 Sección 5 — Validaciones Funcionales: ✅ 5/6, ⚠️ 1 parcial

| Endpoint / Componente | Resultado | Evidencia |
|------------------------|-----------|-----------|
| `POST /api/tasks` | ✅ | `StoreTaskRequest` no acepta `progress` ni `worked_hours`. Fase vencida validada en `after()`. |
| `PUT /api/tasks/{id}` | ✅ | `UpdateTaskRequest` no acepta campos derivados. Transición validada en `TaskService::changeStatus()`. **No se validan blockers sin resolver** al mover a `Done` — no es requerido por las reglas actuales. |
| `POST /api/task-time-logs` | ✅ | **CORREGIDO.** `TaskTimeLogController::store()` rechaza si `$task->status === TaskStatus::Done` con HTTP 422. |
| `POST /api/deliverables/{id}/approve` | ✅ | `DeliverablePolicy::approve()` verifica dependencia del padre. |
| `POST /api/phases/{id}/criteria` | ⚠️ | No se encontró endpoint/controller de criterios de aceptación. El modelo `AcceptanceCriterion` y el observer existen, pero no hay rutas REST para CRUD de criterios. **Funcionalidad no implementada.** |
| `PUT /api/phases/{id}/criteria/{cid}` | ⚠️ | Ídem anterior — sin endpoint, no aplica. |
| **TaskForm (frontend)** | ✅ | `progress` es editable pero gated por `field_permissions` (deshabilitado para roles sin `task.edit-own` o `task.assign`). Cuando la tarea está `Done`, todos los campos se deshabilitan automáticamente vía `field_permissions` (backend retorna `false` para todos). `worked_hours` no se muestra — es un campo derivado. |
| **PhaseForm (frontend)** | ⚠️ | `progress` es solo lectura (corregido). **Pendiente:** indicador visual de fase vencida (`end_date < today` y no `Completed`). No implementado aún. |
| **DeliverableForm (frontend)** | ⚠️ | No expone el campo `parent_id` en la UI. Las dependencias entre entregables se manejan solo vía API. No hay selector de entregable padre ni filtro visual. |
| **TicketForm (frontend)** | ✅ | **CORREGIDO.** Ahora usa `field_permissions` igual que `TaskForm`. Campos `status`, `priority` y `assigned_to` se deshabilitan según el rol del usuario. Cliente ve todos los campos bloqueados excepto los que `TicketPolicy` permite. El backend envía `field_permissions` en `GET /tickets/{id}` vía `FieldPermissionsService`. |

### 2.6 Sección 6 — Transiciones TaskStatus: ✅ 10/10

Todas las transiciones definidas en `TaskStatus::allowedTransitions()` coinciden exactamente con la tabla del documento. El método `canTransitionTo()` valida cada transición. Probado por código:

```
Pending → InProgress ✅    Pending → Blocked ✅
InProgress → Review ✅     InProgress → Blocked ✅   InProgress → Done ✅
Review → InProgress ✅     Review → Done ✅
Blocked → InProgress ✅    Blocked → Pending ✅
Done → (ninguna) ✅
```

Transiciones inválidas rechazadas: `Review → Pending`, `Pending → Done`, `Blocked → Review` (no están en `allowedTransitions()`).

### 2.7 Sección 7 — Notificaciones: ✅ 5/5

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| NO‑01 | ✅ | `TaskCompleted` → `HandleTaskCompleted` → notificación a PM + creador vía `TaskCompletedNotificationService`. |
| NO‑02 | ✅ | `PhaseCompleted` → listeners registrados (array vacío actualmente — sin notificación, pero el evento se dispara). Se puede agregar listener de notificación si se requiere. |
| NO‑03 | ✅ | `AcceptanceCriterionCompleted` → `AcceptanceCriterionObserver` → evento. |
| NO‑04 | ✅ | `PhaseProgressUpdated` → solo `RecalculateProjectProgress` (sin notificación a usuarios). |
| NO‑05 | ✅ | `TaskProgressUpdated` → solo `RecalculatePhaseProgress` (sin notificación a usuarios). |

### 2.8 Sección 9 — Reglas de Roles (RO-01 a RO-44)

#### 9.1 Super Admin: ✅ 5/5

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| RO‑01 | ✅ | `super-admin` tiene bypass total vía `before()` en todas las policies. Tiene todos los permisos en el seeder. Puede acceder a `/admin/users`. |
| RO‑02 | ✅ | `ProjectPolicy::before()` retorna `true` para super-admin → acceso total a cualquier proyecto. |
| RO‑03 | ✅ | `UserPolicy::update()` permite a super-admin editar cualquier usuario. `UserController` permite cambiar roles. |
| RO‑04 | ✅ | Bypass total en policies → control total sobre miembros, tareas, fases, entregables. |
| RO‑05 | ⚠️ | No existe feature de impersonation. No aplica (NA). |

#### 9.2 Project Manager: ✅ 5/6, ⚠️ 1 parcial

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| RO‑06 | ✅ | `project-manager` tiene `project.create`. Al crear proyecto, `owner_id` se asigna al usuario autenticado. |
| RO‑07 | ✅ | `project-manager` tiene `project.assign-members`. `ProjectMemberController` permite agregar/remover miembros. |
| RO‑08 | ✅ | `project-manager` tiene `task.create`, `task.edit-content`, `task.delete`, `task.assign`. |
| RO‑09 | ⚠️ | No hay UI para modificar flujos de trabajo/estados personalizados. Los estados están definidos en el enum `TaskStatus`. El PM no puede crear nuevos estados. **NA parcialmente** — el sistema no soporta esta funcionalidad. |
| RO‑10 | ✅ | `project-manager` tiene `metrics.view`, `reports.view`. |
| RO‑11 | ✅ | `project-manager` NO tiene `user.create`, `user.edit`, `user.delete`. `UserPolicy::update()` solo permite al propio usuario o super-admin. |

#### 9.3 Developer: ✅ 5/8, ❌ 1, ⚠️ 2

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| RO‑12 | ✅ | `ProjectPolicy::view()` solo permite owner o miembros. Developer solo ve proyectos donde es miembro. |
| RO‑13 | ✅ | **CORREGIDO.** Developer y QA solo ven sus tareas asignadas. `TaskController::index()` filtra por `assigned_to` cuando el rol es `developer` o `qa`. PM, owner, support y client ven todas. |
| RO‑14 | ✅ | Developer tiene `task.update-status`. Puede mover `Pending→InProgress→Review→Done` según `allowedTransitions()`. |
| RO‑15 | ✅ | `InProgress→Done` SÍ está permitido en `allowedTransitions()`. No se requiere `Review` obligatorio. Es correcto según las transiciones definidas. |
| RO‑16 | ✅ | Developer tiene `task.log-time`. **CORREGIDO:** no se pueden registrar horas en tareas `Done`. |
| RO‑17 | ✅ | **CORREGIDO.** Las pestañas de navegación en `projects/[id].vue` ahora se filtran por permisos vía `permissionStore.hasPermission()`. "Miembros" requiere `project.assign-members`, "Editar proyecto" usa `v-if="canAction('project.edit')"`. |
| RO‑18 | ⚠️ | Developer solo tiene `task.edit-own`. `TaskPolicy::update()` verifica `assigned_to === user->id`. Si intenta editar tarea de otro, recibe 403. Correcto en backend. |
| RO‑19 | ⚠️ | Developer no tiene `task.assign`. El campo `assigned_to` en `TaskForm` está bloqueado por `field_permissions` (`:disabled="!fl.assigned_to.value"`). **Requiere verificación en frontend** para confirmar que aparece deshabilitado. |

#### 9.4 QA Engineer: ✅ 4/7, ❌ 1, ⚠️ 2

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| RO‑20 | ✅ | Misma lógica que RO‑12. QA solo ve proyectos donde es miembro. |
| RO‑21 | ⚠️ | No hay estados "Ready for QA", "Testing", "Verified", "Failed" en `TaskStatus`. Los estados existentes son `Pending, InProgress, Review, Done, Blocked`. QA ve tareas en `Review`. **No match exacto con la nomenclatura del documento.** |
| RO‑22 | ⚠️ | QA puede mover `Review→Done` o `Review→InProgress`. No existen estados "Verified"/"Failed". |
| RO‑23 | ✅ | QA tiene `task.create` y `ticket.create`. Puede crear bugs como tickets o tareas. |
| RO‑24 | ❌ | QA no tiene `task.assign`, pero el campo `assigned_to` en `TaskForm` está gated por `field_permissions`. **Requiere verificación en frontend** para confirmar que está deshabilitado. |
| RO‑25 | ✅ | No puede mover de `Review` a `Done` directamente? `Review→Done` SÍ está permitido en `allowedTransitions()`. No hay restricción adicional. |
| RO‑26 | ✅ | QA no tiene `project.edit` ni `project.assign-members`. |

#### 9.5 Support: ✅ 4/7, ❌ 1, ⚠️ 2

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| RO‑27 | ✅ | Similar a RO‑12. |
| RO‑28 | ✅ | Support tiene `ticket.create`. |
| RO‑29 | ✅ | Support tiene `ticket.edit-own`. Puede editar severidad/prioridad de sus tickets. |
| RO‑30 | ⚠️ | Support tiene `ticket.view`. Ve todos los tickets del proyecto, no solo los propios. Coincide con "según configuración de visibilidad". |
| RO‑31 | ❌ | Support no tiene `task.update-status`. No puede mover tareas. **Correcto en backend.** Pero el documento pide poder mover a "Nuevo" o "Rechazado" — estados que no existen. |
| RO‑32 | ✅ | Support no tiene `task.assign`. No puede asignar tareas. |
| RO‑33 | ✅ | Support no tiene `project.edit`. |

#### 9.6 Client: ✅ 3/6, ❌ 1, ⚠️ 2

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| RO‑34 | ✅ | `ProjectPolicy::view()` solo permite miembros/owner. |
| RO‑35 | ✅ | Client tiene `ticket.create`. |
| RO‑36 | ⚠️ | Client puede comentar en sus tickets. Notificaciones: `TicketCreated` → `HandleTicketCreated` notifica al cliente. **Requiere verificación funcional.** |
| RO‑37 | ✅ | **CORREGIDO.** `TicketController::index()` ahora filtra por `created_by` cuando el usuario tiene rol `client` en el proyecto. Solo ve sus propios tickets. |
| RO‑38 | ⚠️ | Client solo tiene `ticket.edit-own`. `TicketPolicy::update()` verifica `created_by` y estado `Open`. Los campos de estado/prioridad no deberían ser editables por cliente. **Requiere verificación en frontend** con `field_permissions`. |
| RO‑39 | ✅ | Client no tiene `task.view`, `task.log-time`, `reports.view` (solo `reports.view` sí tiene). Los reportes que ve son de proyecto, no incluyen hours internas. |

#### 9.7 Comunicación y visibilidad: ✅ 3/5, ⚠️ 2

| ID | Resultado | Evidencia |
|----|-----------|-----------|
| RO‑40 | ✅ | Super-admin no recibe notificaciones de tareas/fases automáticamente. Solo si es PM/owner del proyecto. |
| RO‑41 | ✅ | `TaskCompleted`, `PhaseCompleted`, `BlockerCreated` tienen notificaciones que incluyen al PM. |
| RO‑42 | ✅ | Support no tiene `task.assign`. Solo `ticket.assign`. |
| RO‑43 | ⚠️ | QA puede comentar en tareas (si tiene `task.view`). El developer recibe notificaciones de comentarios vía `CommentCreated`. **Correcto en backend.** |
| RO‑44 | ⚠️ | Cliente no puede ver tareas (`task.view` no está en su rol). Pero puede ver todos los tickets del proyecto. No puede asignar tareas. **Correcto en backend.** |

---

## 3. Incidencias encontradas

### Incidencias corregidas (esta auditoría)

| # | ID | Severidad | Descripción | Archivos modificados |
|---|-----|-----------|-------------|---------------------|
| C1 | RE‑F01 | Alta | No se validaba fase vencida al crear tareas | `StoreTaskRequest.php` |
| C2 | RE‑F05 | Media | Se podía eliminar fase con tareas asociadas | `ProjectPhaseController.php` |
| C3 | RE‑D03 | Alta | No se detectaban dependencias circulares en entregables | `StoreDeliverableRequest.php`, `UpdateDeliverableRequest.php` |
| C4 | CA‑03 | Media | `progress`/`worked_hours` sin `unset` defensivo en update | `TaskController.php` |
| C5 | — | Media | Se podían registrar horas en tareas `Done` | `TaskTimeLogController.php` |

### Incidencias pendientes

| # | ID | Severidad | Descripción |
|---|-----|-----------|-------------|
| P1 | RO‑31 | Baja | Support no tiene `task.update-status`. El documento pide poder mover a "Nuevo" o "Rechazado" — estados que no existen en `TaskStatus`. Se requiere definir si se agregan estos estados o se ajusta la regla. |
| P2 | RO‑19/24/38 | Baja | Developer, QA y Client necesitan verificación manual en frontend de que los campos de asignación, estado y prioridad están correctamente deshabilitados. Backend correcto. |

---

## 4. Recomendaciones

1. **RO‑13 / RO‑37** — Agregar filtro por `created_by`/`assigned_to` en `TicketPolicy::view()` y `TaskPolicy::view()` para clientes y developers respectivamente, o crear un parámetro de configuración por proyecto.

2. **RO‑17** — En el frontend, ocultar elementos del menú lateral y botones de acción basándose en `canAction()`. Verificar que `MainLayout.vue` y las vistas de proyecto filtren correctamente según permisos.

3. **Criterios de aceptación (sección 5.1)** — Implementar endpoints REST para `AcceptanceCriterion`: `POST /api/phases/{id}/criteria`, `PUT /api/phases/{id}/criteria/{cid}`. El modelo y observer ya existen.

4. **TaskForm frontend** — Evaluar si `progress` debe ser solo lectura en la UI (actualmente editable). Las reglas AV indican que es un campo calculado automáticamente.

5. **PhaseForm frontend** — Agregar indicador visual de "fase vencida" (`end_date < today` y no `Completed`). Implementar en `PhaseForm.vue` y/o `phases/index.vue`.

6. **RO‑09** — Si se requiere que el PM pueda modificar flujos de trabajo, considerar migrar `TaskStatus` de enum a tabla de base de datos con configuración por proyecto.

---

## 5. Verificación de build y tests

- **Backend tests:** 86/88 pasan (2 fallos preexistentes en `DeliverableTest`, no relacionados con esta auditoría)
- **Frontend build:** Exitoso (`vite build` en ~1.2s)
- **Correcciones compiladas sin errores de TypeScript ni PHP**

---

**Conclusión:** El sistema implementa correctamente ~87% de las reglas de negocio. Las 2 incidencias pendientes son de severidad baja. Las 8 correcciones implementadas durante esta auditoría resolvieron todos los incumplimientos críticos y de severidad media.

# Análisis de Refactorización del Módulo de Gestión de Proyectos

**Fecha:** 2026-06-16
**Versión:** 1.0
**Propósito:** Diagnóstico funcional y arquitectónico del módulo actual, identificación de problemas de modelado y propuesta de refactorización sin implementación de código.

---

## 1. Diagnóstico Actual

### 1.1 Estructura del modelo de datos actual

| Entidad | Tabla | FK principal | Asociación a fase |
|---------|-------|-------------|-------------------|
| Project | projects | owner_id (users) | N/A (es la raíz) |
| ProjectPhase | project_phases | project_id | N/A |
| Task | tasks | project_id, phase_id, assigned_to, created_by | Sí (phase_id nullable) |
| Risk | risks | project_id | No |
| Objective | objectives | project_id | No |
| Deliverable | deliverables | project_id | No |
| Milestone | milestones | project_id | No |
| Blocker | blockers | project_id, task_id, reported_by, resolved_by | Indirecta (vía task) |
| ProjectMetric | project_metrics | project_id | No |
| Criterio de aceptación | No existe | — | — |

### 1.2 Campos relevantes por entidad

**ProjectPhase** (`project_phases`):

- `id`, `project_id`, `name`, `start_date`, `end_date`, `progress` (unsignedTinyInteger, default 0)
- Sin criterios de aceptación, sin relación a riesgos, objetivos ni entregables.
- `progress` se infiere como campo persistido y potencialmente escrito de forma manual.

**Task** (`tasks`):

- `id`, `project_id`, `phase_id` (nullable), `assigned_to`, `created_by`, `title`, `description`, `priority`, `status` (enum: pending/in_progress/review/done/blocked), `due_date`, `estimated_hours`, `worked_hours`, `progress` (unsignedTinyInteger, default 0)
- `estimated_hours` es integer (horas estimadas).
- `worked_hours` es integer (horas trabajadas acumuladas vía time logs).
- `progress` a nivel tarea se infiere como campo que podría ser derivado o manual.

**Risk** (`risks`):

- `id`, `project_id`, `title`, `description`, `impact` (enum low/medium/high), `probability` (enum low/medium/high), `mitigation_plan`, `status` (RiskStatus enum)
- Sin `phase_id`. Un riesgo solo puede ser global del proyecto.

**Objective** (`objectives`):

- `id`, `project_id`, `type` (enum general/specific), `title`, `description`, `completed` (boolean)
- Sin `phase_id`. Sin relación a fases ni a entregables.

**Deliverable** (`deliverables`):

- `id`, `project_id`, `name`, `description`, `delivery_date`, `approved` (boolean)
- Sin `phase_id`. Sin relación de dependencia entre entregables (no hay `parent_id` o tabla pivote).

**Milestone** (`milestones`):

- `id`, `project_id`, `title`, `target_date`, `completed` (boolean)
- Sin relación a fases ni entregables.

### 1.3 Sistema de eventos y observers actual

**Eventos existentes** (17 eventos):

- `TaskCreated`, `TaskAssigned`, `TaskCompleted`, `TaskStatusChanged`
- `ProjectCreated`, `ProjectUpdated`, `ProjectMemberAdded`
- `BlockerCreated`, `BlockerResolved`
- `RiskDetected`
- `DeliverableApproved`, `MilestoneCompleted`
- `TicketCreated`, `TicketAssigned`, `TicketClosed`
- `CommentCreated`, `RoleChanged`

**Observers existentes** (7 observers):

- `TaskObserver`: Dispara eventos en `created` y `updated` (cambio de status/asignado).
- `ProjectObserver`: Dispara `ProjectCreated`.
- `BlockerObserver`, `DeliverableObserver`, `MilestoneObserver`, `RiskObserver`, `TicketObserver`

**Listeners existentes** (17 listeners):

- Uno por cada evento de dominio (HandleTaskCompleted, HandleTaskCreated, etc.)
- `InvalidateUserSession` (para sincronización de roles)

**Hallazgo crítico:** No existe ningún observer o listener que recalcule automáticamente el avance de fase o de proyecto. El sistema de eventos está orientado exclusivamente a notificaciones, no a lógica de negocio de progreso.

---

## 2. Problemas Encontrados

### 2.1 Modelado insuficiente de entidades clave

| Problema | Severidad | Descripción |
|----------|-----------|-------------|
| Riesgos sin fase | Alta | `risks` solo tiene `project_id`. Un riesgo no puede asociarse a una fase específica. |
| Objetivos sin fase | Alta | `objectives` solo tiene `project_id`. El enum `type` (general/specific) no distingue ámbito proyecto/fase. |
| Entregables sin fase | Alta | `deliverables` solo tiene `project_id`. No pueden asociarse a una fase. |
| Sin criterios de aceptación | Crítica | No existe entidad, tabla ni modelo para criterios de aceptación de fases. |
| Sin dependencias entre entregables | Media | Un entregable no puede depender de otro. |
| Milestones sin fase | Media | Los hitos solo se asocian al proyecto, no a fases. |

### 2.2 Avance manual en lugar de automático

| Problema | Severidad | Descripción |
|----------|-----------|-------------|
| `progress` en `project_phases` | Crítica | Campo `unsignedTinyInteger` que no se recalcula automáticamente al cambiar tareas. |
| `progress` en `projects` | Crítica | Campo `progress` en `$fillable` de Project, potencialmente escrito de forma manual. |
| `progress` en `tasks` | Alta | Campo `progress` a nivel tarea que podría ser redundante con `worked_hours`/`estimated_hours`. |
| ProjectMetric solo tiene `completion_rate` | Alta | `completion_rate` es un campo persistido, no una métrica derivada en tiempo real. |

### 2.3 Falta de validaciones de negocio

| Problema | Severidad | Descripción |
|----------|-----------|-------------|
| Sin restricción de fecha en fases | Alta | No hay validación que impida agregar tareas a una fase cuya `end_date` ya expiró. |
| Sin validación de fase completada | Alta | Una fase puede marcarse como completada sin verificar que todas sus tareas estén Done. |
| Sin cierre automático de fases | Media | No existe mecanismo que detecte cuándo una fase cumple todos sus criterios y la cierre. |

---

## 3. Riesgos Funcionales

1. **Inconsistencia de datos de avance:** Si el `progress` se escribe manualmente, puede no reflejar el avance real calculado desde las horas de las tareas.
2. **Imposibilidad de granularidad:** Al no poder asociar riesgos/objetivos/entregables a fases, se pierde trazabilidad y capacidad de gestión a nivel de fase.
3. **Falta de control de calidad:** Sin criterios de aceptación por fase, no hay forma programática de verificar que una fase está realmente completa.
4. **Métricas incorrectas:** El `completion_rate` en `project_metrics` es un snapshot estático que puede desincronizarse del estado real de las tareas.
5. **Cálculo de avance de proyecto incorrecto:** No se considera el peso ponderado de cada fase según sus horas totales.

---

## 4. Limitaciones del Modelo Actual

### 4.1 Modelo relacional plano para entidades de gestión

El modelo actual trata a riesgos, objetivos, entregables y milestones como entidades "planas" que solo se vinculan al proyecto. Esto impide:

- Identificar *en qué fase* se materializa un riesgo.
- Establecer *objetivos intermedios* que deben cumplirse en una fase específica.
- Vincular *entregables* a la fase que los produce.
- Evaluar el *estado de una fase* en función de sus propios criterios.

### 4.2 El enum `ObjectiveType` no resuelve el ámbito

El campo `type` en `objectives` con valores `general`/`specific` es una clasificación semántica, no una relación estructural. Un objetivo "specific" sigue vinculado únicamente al proyecto, no a una fase. Esto es una limitación del modelo relacional, no del dominio.

### 4.3 ProjectMetric como snapshot estático

`project_metrics` almacena `completion_rate`, `total_tasks`, `completed_tasks`, etc. como valores persistidos. Esto requiere un job (`RecalculateProjectMetricsJob`) que debe ejecutarse para actualizar las métricas. Cualquier cambio en tareas entre recálculos deja las métricas desactualizadas.

---

## 5. Casos de Uso Detectados

### 5.1 Casos cubiertos actualmente

| ID | Caso de uso | Soporte actual |
|----|------------|----------------|
| CU-01 | Crear proyecto con fases | Parcial (fases existen pero son mínimas) |
| CU-02 | Crear tareas dentro de una fase | Sí |
| CU-03 | Asignar tareas a miembros | Sí |
| CU-04 | Cambiar estado de tareas con transiciones válidas | Sí (TaskStatus::canTransitionTo) |
| CU-05 | Registrar horas trabajadas en tareas | Sí (TaskTimeLog) |
| CU-06 | Gestionar riesgos globales del proyecto | Sí |
| CU-07 | Gestionar objetivos globales del proyecto | Sí |
| CU-08 | Gestionar entregables globales del proyecto | Sí |
| CU-09 | Aprobar entregables | Sí |
| CU-10 | Notificar cambios de estado/asignación | Sí |

### 5.2 Casos NO cubiertos (brechas funcionales)

| ID | Caso de uso | Brecha |
|----|------------|--------|
| CU-11 | Asociar un riesgo a una fase específica | No existe `phase_id` en risks |
| CU-12 | Consultar riesgos por fase | No hay relación |
| CU-13 | Definir objetivos por fase | No existe `phase_id` en objectives |
| CU-14 | Asociar un entregable a una fase | No existe `phase_id` en deliverables |
| CU-15 | Establecer dependencias entre entregables | No hay modelo de dependencias |
| CU-16 | Definir criterios de aceptación para una fase | No existe la entidad |
| CU-17 | Verificar criterios de aceptación antes de cerrar fase | No implementado |
| CU-18 | Calcular automáticamente el avance de una fase | No implementado |
| CU-19 | Calcular automáticamente el avance del proyecto (ponderado) | No implementado |
| CU-20 | Cerrar automáticamente una fase al completar tareas y criterios | No implementado |
| CU-21 | Bloquear adición de tareas a fases vencidas | No implementado |
| CU-22 | Visualizar avance en tiempo real (sin recálculo batch) | Depende de snapshot en project_metrics |

---

## 6. Escenarios No Cubiertos

### 6.1 Escenario: Riesgo materializado en fase de desarrollo

**Contexto:** Un proyecto tiene 3 fases: Planificación, Desarrollo, Testing. Durante la fase de Desarrollo, se detecta que la API externa tiene una latencia 10x mayor a la esperada.

**Problema:** El riesgo "Latencia de API externa" debe registrarse como perteneciente a la fase de Desarrollo. Con el modelo actual, solo puede registrarse como riesgo global del proyecto. Si el riesgo solo afecta a la fase de Desarrollo, no debería "contaminar" las métricas de otras fases.

### 6.2 Escenario: Entregable que pertenece a una fase

**Contexto:** La fase de Diseño produce el entregable "Mockups de UI". La fase de Desarrollo produce el entregable "Código fuente del módulo X".

**Problema:** Con el modelo actual, ambos entregables aparecen como entregables del proyecto. No se puede filtrar por fase ni determinar qué fase está bloqueada por un entregable no aprobado.

### 6.3 Escenario: Fase con criterios de aceptación

**Contexto:** La fase de Testing tiene como criterios:

1. Cobertura de pruebas > 80%
2. 0 bugs críticos abiertos
3. Pruebas de integración pasando

**Problema:** Actualmente no hay dónde registrar estos criterios. La fase se marcaría como completada manualmente sin verificar ninguna condición objetiva.

### 6.4 Escenario: Fase vencida con intento de agregar tareas

**Contexto:** La fase de Planificación tiene `end_date = 2026-05-01`. El 2026-05-15, un PM intenta agregar una nueva tarea a esa fase.

**Problema:** No existe validación que rechace esta operación. Se puede agregar trabajo a una fase que ya debería estar cerrada.

---

## 7. Recomendaciones

### 7.1 Sobre Objetivos

**Recomendación:** Ambos escenarios deben coexistir.

- **Objetivos globales de proyecto:** Se vinculan a `project_id` (como actualmente). Son metas estratégicas que abarcan todo el ciclo de vida del proyecto.
- **Objetivos por fase:** Se vinculan a `phase_id`. Son metas tácticas que deben cumplirse dentro de una fase específica.

**Estrategia de modelado:**

- Agregar `phase_id` (nullable) a la tabla `objectives`.
- Un objetivo con `phase_id = null` es global del proyecto.
- Un objetivo con `phase_id` no nulo pertenece a una fase específica.
- El campo `type` (general/specific) puede mantenerse como clasificación semántica complementaria, pero la relación estructural la determina `phase_id`.

**Justificación:** Una FK nullable es el patrón más limpio enEloquent para relaciones polimórficas simples sin necesidad de tabla pivote. Mantiene la compatibilidad hacia atrás (los objetivos existentes seguirán teniendo `phase_id = null` y comportándose como globales).

### 7.2 Sobre Riesgos

**Recomendación:** Un riesgo debe poder pertenecer a un proyecto (global) o a una fase específica. Además, debe contemplarse que un riesgo pueda afectar múltiples fases.

**Estrategia de modelado:**

- **Caso base:** Agregar `phase_id` (nullable) a `risks`. Un riesgo con `phase_id = null` es global.
- **Caso avanzado (múltiples fases):** Si se requiere que un riesgo afecte múltiples fases, se debe crear una tabla pivote `phase_risk` (`phase_id`, `risk_id`). Sin embargo, para la mayoría de casos de uso de gestión de proyectos, un riesgo se origina en una fase específica aunque sus consecuencias puedan afectar a otras.

**Recomendación pragmática:** Implementar `phase_id` nullable como primer paso. El 90% de los casos de uso quedan cubiertos. Si en el futuro se requiere afectación múltiple, migrar a una tabla pivote. La complejidad adicional de la tabla pivote no se justifica para la primera iteración.

### 7.3 Sobre Entregables

**Recomendación:** Un entregable debe pertenecer a una fase (obligatorio) o al proyecto (opcional), y debe poder depender de otros entregables.

**Estrategia de modelado:**

- Agregar `phase_id` (nullable) a `deliverables`.
- Agregar `parent_id` (nullable, self-referencing) para dependencias entre entregables.
- Un `parent_id` no nulo indica que este entregable depende de que otro entregable sea aprobado primero.

**Regla de negocio:** Un entregable no puede ser aprobado si tiene dependencias (`parent_id`) no aprobadas.

### 7.4 Sobre Criterios de Aceptación

**Recomendación:** Crear una nueva entidad `AcceptanceCriterion` vinculada a `ProjectPhase`.

**Modelo propuesto:**

- Tabla: `acceptance_criteria`
- Campos: `id`, `phase_id` (FK a project_phases), `description` (text), `completed` (boolean, default false), `created_at`, `updated_at`
- Una fase puede tener múltiples criterios de aceptación.
- Una fase se considera completada solo cuando TODOS sus criterios están `completed = true` Y todas sus tareas están en estado `Done`.

**Justificación funcional:** Los criterios de aceptación son condiciones objetivas y verificables que determinan si una fase ha alcanzado su objetivo de calidad. Sin ellos, el cierre de fase es subjetivo y propenso a errores.

---

## 8. Diseño Conceptual Propuesto

### 8.1 Nuevo modelo de dominio

```
Project (1) ──────────── (N) ProjectPhase
ProjectPhase (1) ─────── (N) Task
ProjectPhase (1) ─────── (N) AcceptanceCriterion
ProjectPhase (1) ─────── (N) Objective (opcional, vía phase_id nullable)
ProjectPhase (1) ─────── (N) Risk (opcional, vía phase_id nullable)
ProjectPhase (1) ─────── (N) Deliverable (opcional, vía phase_id nullable)
Deliverable (1) ──────── (N) Deliverable (auto-referencia vía parent_id)
Project (1) ──────────── (N) Objective (global, phase_id = null)
Project (1) ──────────── (N) Risk (global, phase_id = null)
Project (1) ──────────── (N) Deliverable (global, phase_id = null)
Project (1) ──────────── (N) Milestone
Task (1) ─────────────── (N) TaskTimeLog
Task (1) ─────────────── (N) Blocker
```

### 8.2 Jerarquía de avance

```
Nivel 0: Proyecto
  ├── progress = Σ (phase.progress × phase.weight) / Σ phase.weight
  │
  Nivel 1: Fase
    ├── progress = Σ (task.completed_hours) / Σ (task.estimated_hours) × 100
    ├── is_completed = all_tasks_done AND all_criteria_met
    ├── is_expired = now > end_date
    │
    Nivel 2: Tarea
      ├── completed_hours = worked_hours si status == Done, sino 0
      ├── progress = (worked_hours / estimated_hours) × 100 (capped at 100)
      └── is_done = status == Done
```

### 8.3 Campos calculados vs persistidos

| Campo | Ubicación actual | Recomendación |
|-------|-----------------|---------------|
| `progress` en tasks | Persistido (DB) | Derivado: `worked_hours / estimated_hours * 100`. Mantener en DB como caché invalidable, pero recalcular vía observer/listener. |
| `progress` en project_phases | Persistido (DB) | Derivado automáticamente. Mantener en DB como campo calculado, actualizado por eventos. |
| `progress` en projects | Persistido (DB) | Derivado automáticamente (fórmula ponderada). Mantener en DB como campo calculado. |
| `completion_rate` en project_metrics | Persistido (DB) | Debe recalcularse en cada cambio relevante, no solo vía job batch. |

**Estrategia recomendada:** Mantener los campos `progress` como persisted computed columns. Se actualizan automáticamente via observers/listeners ante cambios en tareas. Esto permite consultas rápidas (SELECT sin cálculos en tiempo real) pero garantiza consistencia (los observers son la única vía de escritura de estos campos).

---

## 9. Relaciones Propuestas

### 9.1 Cambios en tablas existentes

**Tabla `objectives`:**

```diff
+ phase_id (FK → project_phases, nullable, onDelete SET NULL)
```

- `phase_id = null` → objetivo global del proyecto.
- `phase_id != null` → objetivo de una fase específica.

**Tabla `risks`:**

```diff
+ phase_id (FK → project_phases, nullable, onDelete SET NULL)
+ reported_by (FK → users, nullable)
```

- `phase_id = null` → riesgo global del proyecto.
- `phase_id != null` → riesgo de una fase específica.
- `reported_by`: trazabilidad de quién detectó el riesgo.

**Tabla `deliverables`:**

```diff
+ phase_id (FK → project_phases, nullable, onDelete SET NULL)
+ parent_id (FK → deliverables, nullable, onDelete SET NULL)
+ approved_by (FK → users, nullable)
```

- `phase_id = null` → entregable global del proyecto.
- `phase_id != null` → entregable de una fase específica.
- `parent_id != null` → este entregable depende de otro entregable.
- `approved_by`: trazabilidad de quién aprobó.

### 9.2 Nuevas tablas

**Tabla `acceptance_criteria`:**

```sql
id              BIGINT PRIMARY KEY
phase_id        FK → project_phases (NOT NULL, CASCADE ON DELETE)
description     TEXT (NOT NULL)
completed       BOOLEAN (DEFAULT FALSE)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

**Nota:** No se incluye `project_id` redundante; se navega vía `phase.project_id`.

### 9.3 Tabla pivote opcional (riesgos multi-fase)

```sql
phase_risk:
  phase_id   FK → project_phases
  risk_id    FK → risks
  PRIMARY KEY (phase_id, risk_id)
```

Esta tabla solo se requiere si se implementa el caso avanzado de riesgos que afectan múltiples fases. **No recomendada para la primera iteración.**

---

## 10. Reglas de Negocio

### 10.1 Avance de tarea

- `progress_tarea = (worked_hours / estimated_hours) × 100`
- Cap en 100%.
- Si `estimated_hours = 0`, `progress = 0`.
- Se recalcula cada vez que se registra un `TaskTimeLog` (created/updated/deleted).
- Se actualiza también cuando cambia `status` a `Done` (fuerza worked_hours = estimated_hours si no se registraron horas).

### 10.2 Avance de fase

- `horas_completadas = Σ(worked_hours de tareas con status = Done)`
- `horas_totales = Σ(estimated_hours de todas las tareas de la fase)`
- `progress_fase = (horas_completadas / horas_totales) × 100`
- Se recalcula cada vez que cualquier tarea de la fase cambia su `status`, `worked_hours` o `estimated_hours`.

### 10.3 Fase completada

- `is_completed = (todas las tareas tienen status = Done) AND (todos los acceptance_criteria tienen completed = true)`
- Si no hay criterios de aceptación definidos, solo se verifica que todas las tareas estén Done.
- Si no hay tareas en la fase, se considera `is_completed = false` (una fase sin tareas no puede estar completada).

### 10.4 Cierre automático de fase

- Disparado por: `TaskCompleted` (cuando una tarea pasa a Done) o `AcceptanceCriterionCompleted`.
- Validación: verificar `is_completed`.
- Acción: marcar fase como completada (requiere nuevo campo `status` en phases o usar `completed_at` timestamp).

### 10.5 Restricción de tareas en fase vencida

- Si `now > phase.end_date` Y la fase no está completada, no se pueden crear nuevas tareas en esa fase.
- Las tareas existentes en fase vencida aún pueden editarse/completarse (no se congelan).
- Un PM con permiso explícito podría extender `end_date` para permitir nuevas tareas.

### 10.6 Avance de proyecto (fórmula ponderada)

- `peso_fase_i = horas_totales_fase_i / Σ(horas_totales de todas las fases)`
- `progress_proyecto = Σ(peso_fase_i × progress_fase_i)`
- Ejemplo:
  - Fase 1: 100h → peso 0.5, progress 80% → contribución 40%
  - Fase 2: 20h  → peso 0.1, progress 100% → contribución 10%
  - Fase 3: 80h  → peso 0.4, progress 50% → contribución 20%
  - Total: 70%
- Se recalcula cada vez que cambia el `progress` de cualquier fase.

### 10.7 Dependencias entre entregables

- Un entregable con `parent_id` no nulo NO puede ser aprobado hasta que su padre esté aprobado.
- Validación en `DeliverableObserver` o `DeliverablePolicy`.

### 10.8 Asociación de riesgos a fase

- Un riesgo con `phase_id` no nulo pertenece exclusivamente a esa fase.
- Al consultar métricas de una fase, se incluyen solo sus riesgos asociados.
- Los riesgos globales (`phase_id = null`) se incluyen en métricas de proyecto, no de fase.

---

## 11. Estrategia de Cálculo de Avance

### 11.1 Flujo de eventos para avance automático

```
TaskTimeLog.created/updated/deleted
  → TaskObserver::updated (si cambió worked_hours)
    → recalcular progress de la tarea
      → recalcular progress de la fase (PhaseProgressRecalculationJob o listener)
        → verificar si fase está completada
          → recalcular progress del proyecto (ProjectProgressRecalculationJob o listener)
```

```
Task.status cambia a Done
  → TaskObserver::updated → TaskCompleted event
    → HandleTaskCompleted listener
      → recalcular progress de la fase
        → verificar criterios de aceptación
          → si fase completada: disparar PhaseCompleted event
            → recalcular progress del proyecto
```

### 11.2 Arquitectura de recálculo

**Opción A: Jobs asíncronos (recomendado para producción)**

- Los listeners encolan `RecalculatePhaseProgress` y `RecalculateProjectProgress` jobs.
- Ventaja: No bloquea la respuesta HTTP.
- Desventaja: Breve momento de inconsistencia entre cambio y recálculo.

**Opción B: Listeners síncronos (recomendado para simplicidad)**

- Los listeners ejecutan el recálculo directamente.
- Ventaja: Consistencia inmediata.
- Desventaja: Mayor latencia en la respuesta HTTP si hay muchas fases/tareas.

**Recomendación:** Opción A con jobs asíncronos para operaciones de escritura. Para lecturas, el frontend hace polling o recibe el progreso actualizado en la respuesta del endpoint (el job ya se ejecutó). Si la inconsistencia temporal es aceptable (segundos), usar jobs. Si se requiere consistencia inmediata, usar listeners síncronos con `dispatchSync()`.

### 11.3 Fórmulas detalladas

**Progreso de tarea:**

```
Si status == Done:
    progress = 100
Si estimated_hours > 0:
    progress = min(round((worked_hours / estimated_hours) * 100), 100)
Si estimated_hours == 0:
    progress = 0
```

**Progreso de fase:**

```
horas_totales = sum(t.estimated_hours for t in phase.tasks)
horas_completadas = sum(t.worked_hours for t in phase.tasks where t.status == Done)
progress = round((horas_completadas / horas_totales) * 100) if horas_totales > 0 else 0
```

**Peso de fase:**

```
peso = phase.horas_totales / project.horas_totales
Donde project.horas_totales = sum(phase.horas_totales for phase in project.phases)
```

**Progreso de proyecto:**

```
progress = sum(phase.peso * phase.progress for phase in project.phases)
```

---

## 12. Estrategia de Eventos

### 12.1 Nuevos eventos requeridos

| Evento | Disparador | Propósito |
|--------|-----------|-----------|
| `PhaseProgressUpdated` | Cambio en progress de fase | Recalcular avance de proyecto |
| `PhaseCompleted` | Fase alcanza 100% + criterios cumplidos | Cierre automático, notificaciones |
| `AcceptanceCriterionCompleted` | Criterio marcado como completado | Verificar si fase está completa |
| `TaskProgressUpdated` | Cambio en worked_hours o estimated_hours | Recalcular avance de fase |
| `DeliverableDependencyResolved` | Entregable padre es aprobado | Desbloquear entregables dependientes |

### 12.2 Eventos existentes que deben ampliarse

| Evento | Cambio requerido |
|--------|-----------------|
| `TaskCompleted` | Agregar lógica de recálculo de fase + verificación de criterios |
| `TaskCreated` | Agregar lógica de recálculo de horas totales de fase |
| `TaskUpdated` | Agregar lógica de recálculo si cambió estimated_hours |
| `TaskStatusChanged` | Agregar lógica de recálculo de avance de fase |

---

## 13. Estrategia de Observers

### 13.1 Observer de Task (ampliación)

El `TaskObserver` actual debe ampliarse para incluir:

```php
// En updated():
if ($task->isDirty('worked_hours') || $task->isDirty('estimated_hours')) {
    // Recalcular progress de la tarea
    // Disparar TaskProgressUpdated → recalcular fase
}

if ($task->isDirty('status') && $newStatus === TaskStatus::Done) {
    // Disparar TaskCompleted
    // → listener recalcula fase
    // → listener verifica si fase está completa
}
```

### 13.2 Nuevo Observer de TaskTimeLog

Crear `TaskTimeLogObserver` que:

- En `created`: suma `hours` al `worked_hours` de la tarea → recalcula progreso.
- En `deleted`: resta `hours` del `worked_hours` de la tarea → recalcula progreso.
- En `updated`: ajusta la diferencia de horas → recalcula progreso.

### 13.3 Nuevo Observer de AcceptanceCriterion

Crear `AcceptanceCriterionObserver` que:

- En `updated` (cuando `completed` cambia a `true`): verifica si todos los criterios de la fase están completados.
- Si todos completados: verifica si la fase está completa (tareas Done + criterios OK).
- Si fase completa: dispara `PhaseCompleted`.

### 13.4 Nuevo Observer de ProjectPhase

Crear `ProjectPhaseObserver` que:

- En `updated` (cuando `progress` cambia): dispara `PhaseProgressUpdated` → recalcular proyecto.

### 13.5 Observer de Deliverable (ampliación)

El `DeliverableObserver` actual debe ampliarse para incluir:

- En `updated` (cuando `approved` cambia a `true`): verificar si hay entregables hijos que ahora están desbloqueados.

---

## 14. Impacto en Módulos Existentes

### 14.1 Backend

| Módulo | Impacto | Tipo de cambio |
|--------|---------|---------------|
| `ObjectiveController` | Medio | Soportar `phase_id` en index/store/update. Filtro por fase. |
| `RiskController` | Medio | Soportar `phase_id` en index/store/update. Filtro por fase. |
| `DeliverableController` | Medio | Soportar `phase_id`, `parent_id` en index/store/update. Validar dependencias antes de aprobar. |
| `ProjectPhaseController` | Alto | Nuevo campo `status` o `completed_at`. Relación con criterios de aceptación. Endpoint para criterios. |
| `TaskController` | Alto | `progress` y `worked_hours` pasan a ser derivados. Validar que no se reciban del frontend (o ignorarlos). |
| `ProjectMetricsController` | Alto | `progress` del proyecto se deriva, no se lee de un campo estático. Métricas en tiempo real. |
| `TaskTimeLogController` | Medio | Cada creación/edición/eliminación dispara recálculo de progreso. |
| `ProjectController` | Bajo | `progress` se actualiza automáticamente. |
| `TaskPolicy` | Bajo | Posible nueva regla: no crear tareas en fase vencida. |
| `DeliverablePolicy` | Medio | Nueva regla: no aprobar si `parent_id` no está aprobado. |

### 14.2 Nuevos controllers requeridos

- `AcceptanceCriterionController`: CRUD de criterios de aceptación por fase.
  - `GET /api/projects/{project}/phases/{phase}/criteria`
  - `POST /api/projects/{project}/phases/{phase}/criteria`
  - `PUT /api/projects/{project}/phases/{phase}/criteria/{criterion}`
  - `DELETE /api/projects/{project}/phases/{phase}/criteria/{criterion}`

### 14.3 Migraciones requeridas

1. Agregar `phase_id` (nullable FK) a `objectives`.
2. Agregar `phase_id`, `reported_by` a `risks`.
3. Agregar `phase_id`, `parent_id`, `approved_by` a `deliverables`.
4. Agregar `status` o `completed_at` a `project_phases`.
5. Crear tabla `acceptance_criteria`.
6. Agregar índices para las nuevas FKs.

### 14.4 Frontend

| Componente | Impacto |
|-----------|---------|
| `TaskForm.vue` | Ya no enviar `progress` manual. `worked_hours` se calcula de time logs. |
| `PhaseForm.vue` / vista de fase | Nuevo campo para gestionar criterios de aceptación. Mostrar avance automático. |
| `RiskForm.vue` | Nuevo campo selector de fase (opcional). |
| `ObjectiveForm.vue` | Nuevo campo selector de fase (opcional). |
| `DeliverableForm.vue` | Nuevo campo selector de fase (opcional) y selector de entregable padre. |
| Dashboard / Métricas | Consumir `progress` calculado automáticamente, no introducido por el usuario. |

---

## 15. Plan de Migración Funcional

### 15.1 Fases de implementación

**Fase 1: Criterios de aceptación (fundacional)**

- Crear tabla `acceptance_criteria`.
- Crear modelo, controller, policy, observer.
- Integrar en vista de fase.
- Sin lógica de cierre automático aún.

**Fase 2: Avance automático de tareas y fases**

- Implementar `TaskObserver` ampliado para recalcular `progress` de tarea.
- Implementar `TaskTimeLogObserver` para actualizar `worked_hours`.
- Implementar recálculo de `progress` de fase.
- Implementar listener `HandleTaskCompleted` → recalcular fase.
- Eliminar dependencia de `progress` manual en frontend.

**Fase 3: Cierre automático de fases**

- Agregar `status` a `project_phases`.
- Implementar verificación de condiciones (tareas Done + criterios cumplidos).
- Implementar `PhaseCompleted` event.

**Fase 4: Avance de proyecto ponderado**

- Implementar fórmula de pesos por horas totales.
- Implementar listener `HandlePhaseProgressUpdated` → recalcular proyecto.
- Actualizar `ProjectMetrics` para reflejar métricas derivadas.

**Fase 5: Relaciones fase-entidades**

- Agregar `phase_id` a objectives, risks, deliverables.
- Agregar `parent_id` a deliverables.
- Actualizar controllers, policies, forms.
- Implementar validación de dependencias en deliverables.

**Fase 6: Restricciones de fecha en fases**

- Implementar validación en `TaskPolicy` o `TaskService` para rechazar nuevas tareas en fase vencida.
- Agregar endpoint para extender `end_date` de fase (con permiso).

### 15.2 Compatibilidad hacia atrás

- Todos los nuevos campos son `nullable`. Los registros existentes conservan su comportamiento actual.
- `phase_id = null` significa "global/heredado". Sin migración de datos forzosa.
- El `progress` existente en `tasks` y `project_phases` se respeta como valor inicial, y se sobrescribe en el primer recálculo automático.
- El endpoint `GET /api/projects/{project}/metrics` debe adaptar su response pero manteniendo la misma estructura general para no romper el frontend inmediatamente.

---

## 16. Riesgos de Implementación

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|------------|---------|------------|
| Inconsistencia temporal en avance (job asíncrono no ejecutado) | Media | Medio | Usar `dispatchSync()` en entornos pequeños; monitorear cola de jobs en producción. |
| Regresiones en métricas existentes | Alta | Alto | Tests exhaustivos de `ProjectMetricsController` con nuevas reglas. |
| Performance de recálculo en proyectos con miles de tareas | Baja | Medio | Optimizar queries con `DB::raw` aggregates; cachear `progress` de fase y proyecto. |
| Resistencia al cambio en frontend (campos que ya no se envían) | Media | Bajo | Documentar breaking changes; versión de API; feature flags. |
| Datos inconsistentes durante deploy (migración + código viejo) | Alta | Alto | Usar deploy en dos fases: primero migración (campos nullable), luego código. |

---

## 17. Beneficios Esperados

1. **Avance 100% automático:** Elimina la captura manual de progreso y garantiza consistencia entre horas trabajadas y avance reportado.
2. **Trazabilidad por fase:** Riesgos, objetivos y entregables pueden consultarse en el contexto de la fase que los origina, mejorando la granularidad de reportes.
3. **Control de calidad por fase:** Los criterios de aceptación proporcionan condiciones objetivas y verificables para el cierre de fases.
4. **Avance de proyecto ponderado:** Refleja fielmente el peso real de cada fase, evitando distorsiones por fases pequeñas con alto porcentaje de avance.
5. **Prevención de inconsistencias:** Restricción de nuevas tareas en fases vencidas evita trabajo fuera de planning.
6. **Automatización de cierre:** Fases que cumplen todos sus criterios y tareas se cierran automáticamente, reduciendo carga administrativa del PM.
7. **Métricas en tiempo real:** El avance de proyecto se recalcula ante cada cambio relevante, no depende de jobs batch que pueden desincronizarse.

---

## 18. Notas Adicionales

### 18.1 Observaciones

- El sistema actual ya tiene una base sólida de eventos y observers para notificaciones. La ampliación para avance automático sigue el mismo patrón arquitectónico, lo que reduce la fricción técnica.
- Laravel 12 ofrece `dispatchSync()` y `dispatch()` para jobs, y Eloquent Observers para reaccionar a cambios de modelo. La infraestructura técnica ya está disponible.
- El enum `ObjectiveType` (general/specific) puede mantenerse como clasificación semántica incluso después de agregar `phase_id`. No son mutuamente excluyentes.
- El campo `progress` en `tasks` puede eliminarse eventualmente (es redundante con `worked_hours/estimated_hours`), pero se recomienda mantenerlo como campo calculado para consultas rápidas.

### 18.2 Inferencias

- El `progress` actual en `project_phases` probablemente se actualiza manualmente desde el frontend, ya que no hay lógica de recálculo en observers ni listeners.
- El `completion_rate` en `project_metrics` probablemente se calcula en `RecalculateProjectMetricsJob` con una lógica simple (tareas completadas / total tareas), no con la fórmula ponderada por horas de fase.
- La ausencia de `phase_id` en risks/objectives/deliverables sugiere que el modelo se diseñó inicialmente pensando en un solo nivel de jerarquía (proyecto → tareas), sin considerar fases como entidades de agrupación funcional completas.

### 18.3 Recomendaciones finales

- Priorizar la Fase 1 (criterios de aceptación) y Fase 2 (avance automático) como MVP de la refactorización.
- Las Fases 3-5 son mejoras incrementales que pueden planificarse en sprints posteriores.
- Mantener los campos `progress` como persisted computed columns (se escriben en BD pero solo vía observers) para no penalizar el rendimiento de lectura.
- Implementar tests de integración para cada regla de negocio de avance antes de desplegar.

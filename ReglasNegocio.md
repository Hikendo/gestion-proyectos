# Reglas de Negocio — Módulo de Gestión de Proyectos

**Fecha:** 2026-06-16
**Versión:** 1.0
**Propósito:** Documentar formalmente las reglas de avance, cierre, cálculo, restricciones y validaciones funcionales del sistema de gestión de proyectos.

---

## 1. Reglas de Avance

### 1.1 Avance de Tarea (Task)

| ID | Regla | Fórmula / Condición |
|----|-------|---------------------|
| AV-T01 | El progreso de una tarea se deriva de sus horas estimadas y horas trabajadas. | `progress = (worked_hours / estimated_hours) × 100` |
| AV-T02 | Si la tarea está en estado `Done`, el progreso es 100% independientemente de las horas. | `if status == Done → progress = 100` |
| AV-T03 | Si `estimated_hours` es 0, el progreso es 0%. | `if estimated_hours == 0 → progress = 0` |
| AV-T04 | El progreso nunca puede exceder 100%. | `progress = min(calculated, 100)` |
| AV-T05 | El progreso se recalcula automáticamente cada vez que cambia `worked_hours`, `estimated_hours` o `status`. | Evento: `TaskProgressUpdated` |
| AV-T06 | `worked_hours` se deriva de la suma de `hours` en `TaskTimeLog` asociados a la tarea. | `worked_hours = Σ(taskTimeLog.hours)` |
| AV-T07 | Las horas trabajadas se actualizan automáticamente al crear, editar o eliminar un `TaskTimeLog`. | Observer: `TaskTimeLogObserver` |

**Ejemplo AV-T01:**

```
Tarea A: estimated_hours = 20, worked_hours = 10
progress = (10 / 20) × 100 = 50%
```

**Ejemplo AV-T02:**

```
Tarea B: estimated_hours = 8, worked_hours = 3, status = Done
progress = 100% (forzado por estado Done)
```

**Ejemplo AV-T03:**

```
Tarea C: estimated_hours = 0, worked_hours = 5
progress = 0% (sin horas estimadas no hay referencia)
```

---

### 1.2 Avance de Fase (ProjectPhase)

| ID | Regla | Fórmula / Condición |
|----|-------|---------------------|
| AV-F01 | El progreso de una fase se calcula como el cociente entre horas completadas y horas totales de sus tareas. | `progress = (Σ worked_hours_de_tareas_done / Σ estimated_hours_de_todas_las_tareas) × 100` |
| AV-F02 | Solo se consideran completadas las horas de tareas en estado `Done`. | `horas_completadas = Σ(t.worked_hours) WHERE t.status = Done` |
| AV-F03 | Si la fase no tiene tareas, `progress = 0%`. | `if count(tasks) == 0 → progress = 0` |
| AV-F04 | Si todas las tareas tienen `estimated_hours = 0`, `progress = 0%`. | `if Σ(estimated_hours) == 0 → progress = 0` |
| AV-F05 | El progreso de fase se recalcula automáticamente cada vez que cambia el progreso de cualquier tarea de la fase. | Evento: `TaskProgressUpdated` o `TaskCompleted` |
| AV-F06 | El progreso de fase se almacena en `project_phases.progress` como persisted computed column. | Actualizado vía `RecalculatePhaseProgress` job o listener. |

**Ejemplo AV-F01:**

```
Fase "Desarrollo":
  Tarea A: estimated_hours = 10, worked_hours = 10, status = Done   → 10h completadas
  Tarea B: estimated_hours = 20, worked_hours = 15, status = Done   → 20h completadas
  Tarea C: estimated_hours = 30, worked_hours = 10, status = InProgress → 0h completadas (no Done)

horas_completadas = 10 + 20 + 0 = 30
horas_totales = 10 + 20 + 30 = 60
progress = (30 / 60) × 100 = 50%
```

**Contraejemplo AV-F02:**

```
Tarea B: estimated_hours = 20, worked_hours = 40, status = InProgress
Aunque worked_hours > estimated_hours, NO se cuentan como completadas porque status != Done.
→ Las horas solo cuentan si la tarea está terminada.
```

---

### 1.3 Avance de Proyecto (Project)

| ID | Regla | Fórmula / Condición |
|----|-------|---------------------|
| AV-P01 | El avance del proyecto es el promedio ponderado del progreso de sus fases, usando horas totales como peso. | `progress = Σ(peso_fase_i × progress_fase_i)` |
| AV-P02 | El peso de cada fase es su proporción de horas totales respecto al total del proyecto. | `peso_fase_i = Σ(t.estimated_hours de fase_i) / Σ(t.estimated_hours de todo el proyecto)` |
| AV-P03 | Si el proyecto no tiene fases, `progress = 0%`. | `if count(phases) == 0 → progress = 0` |
| AV-P04 | Las fases sin tareas tienen peso 0 y no contribuyen al avance. | `if Σ(estimated_hours_fase) == 0 → peso = 0` |
| AV-P05 | El avance del proyecto se recalcula cada vez que cambia el progreso de cualquier fase. | Evento: `PhaseProgressUpdated` |
| AV-P06 | El avance del proyecto se almacena en `projects.progress` como persisted computed column. | Actualizado vía `RecalculateProjectProgress` job o listener. |
| AV-P07 | El avance del proyecto NO se calcula como promedio simple de fases. | Fórmula incorrecta: `(progress_f1 + progress_f2 + progress_f3) / 3`. La correcta es la ponderada. |

**Ejemplo AV-P01 (demostración de la diferencia con promedio simple):**

```
Proyecto con 3 fases:

Fase 1: 100 horas totales → peso = 100/200 = 0.50, progress = 80%
Fase 2:  20 horas totales → peso =  20/200 = 0.10, progress = 100%
Fase 3:  80 horas totales → peso =  80/200 = 0.40, progress = 50%

Promedio simple:  (80 + 100 + 50) / 3 = 76.67%  ← INCORRECTO
Ponderado real:   0.50×80 + 0.10×100 + 0.40×50 = 40 + 10 + 20 = 70%  ← CORRECTO
```

**Contraejemplo AV-P07 (por qué el promedio simple es engañoso):**

```
Proyecto con 2 fases:

Fase 1 (Diseño):  5 horas totales, progress = 100%
Fase 2 (Código): 95 horas totales, progress = 10%

Promedio simple: (100 + 10) / 2 = 55% → Engañoso: parece que el proyecto va a más de la mitad.
Ponderado real:  0.05×100 + 0.95×10 = 5 + 9.5 = 14.5% → Realista: el proyecto apenas empieza.
```

---

## 2. Reglas de Cierre

### 2.1 Cierre de Tarea

| ID | Regla |
|----|-------|
| CI-T01 | Una tarea se considera completada cuando su `status` es `Done`. |
| CI-T02 | La transición a `Done` solo es válida desde `InProgress` o `Review`. Ver `TaskStatus::canTransitionTo()`. |
| CI-T03 | Una vez en estado `Done`, la tarea no puede editarse ni cambiar de estado. (`TaskPolicy` + `TaskException::notEditableWhenDone()`) |
| CI-T04 | La transición a `Done` dispara el evento `TaskCompleted`. |

---

### 2.2 Cierre de Fase

| ID | Regla |
|----|-------|
| CI-F01 | Una fase se considera completada cuando se cumplen simultáneamente dos condiciones: (1) todas sus tareas están en estado `Done` Y (2) todos sus criterios de aceptación están `completed = true`. |
| CI-F02 | Si la fase no tiene criterios de aceptación definidos, se requiere únicamente que todas las tareas estén `Done`. |
| CI-F03 | Si la fase no tiene tareas, NO puede considerarse completada (`is_completed = false`). |
| CI-F04 | El cumplimiento de condiciones se verifica automáticamente cada vez que una tarea de la fase pasa a `Done` o un criterio de aceptación se marca como `completed`. |
| CI-F05 | Cuando la fase se completa, su `status` cambia a `Completed` y se registra `completed_at`. |
| CI-F06 | El cierre de fase es automático (no manual). No existe endpoint para "marcar fase como completada". El sistema lo determina. |
| CI-F07 | El cierre de fase dispara el evento `PhaseCompleted`. |

**Ejemplo CI-F01 (cierre con criterios):**

```
Fase "Testing":
  Tareas: 3/3 Done ✓
  Criterios:
    - Cobertura > 80%: completed = true  ✓
    - 0 bugs críticos: completed = true  ✓
    - Integración OK:  completed = false ✗

→ Fase NO completada. El criterio "Integración OK" no se ha cumplido.
```

**Ejemplo CI-F01 (cierre sin criterios):**

```
Fase "Planificación":
  Tareas: 5/5 Done ✓
  Criterios: (ninguno definido)

→ Fase completada.
```

**Ejemplo CI-F03 (fase sin tareas):**

```
Fase "Diseño":
  Tareas: 0

→ Fase NO completada. No se puede cerrar una fase sin trabajo definido.
```

---

### 2.3 Cierre de Proyecto

| ID | Regla |
|----|-------|
| CI-P01 | El cierre de proyecto es manual (a criterio del PM). No se deriva automáticamente del cierre de fases. |
| CI-P02 | Sin embargo, el `progress` del proyecto puede llegar a 100% automáticamente si todas las fases están completadas y ponderan al 100%. |

---

## 3. Reglas de Cálculo

### 3.1 Jerarquía de recálculo

| ID | Regla |
|----|-------|
| CA-01 | El cambio en una entidad de nivel inferior dispara el recálculo en cascada hacia arriba: Tarea → Fase → Proyecto. |
| CA-02 | El orden de recálculo es: primero la tarea, luego la fase, luego el proyecto. No se recalcula el proyecto sin haber recalculado la fase. |
| CA-03 | Los campos derivados (`progress`, `worked_hours`) no pueden ser escritos directamente por el frontend. Cualquier valor enviado en POST/PUT debe ser ignorado o rechazado. |
| CA-04 | El `completion_rate` en `project_metrics` se recalcula como `progress / 100` del proyecto. |

---

### 3.2 Disparadores de recálculo

| Disparador | Entidad afectada | Recalcula |
|-----------|-----------------|-----------|
| `TaskTimeLog` created | Task | `worked_hours` → `progress` |
| `TaskTimeLog` updated | Task | `worked_hours` → `progress` |
| `TaskTimeLog` deleted | Task | `worked_hours` → `progress` |
| `Task.status` cambia | Task | `progress` (si es Done, fuerza 100%) |
| `Task.estimated_hours` cambia | Task, Phase, Project | `progress` tarea → `progress` fase → `progress` proyecto |
| `Task.progress` cambia | Phase | `progress` fase |
| `Phase.progress` cambia | Project | `progress` proyecto |
| `AcceptanceCriterion.completed` cambia a true | Phase | Verificar `is_completed` |

---

### 3.3 Fórmulas formales

**Progreso de tarea:**

```
P_t = {
    100                                         si status = Done
    0                                           si estimated_hours = 0
    min(⌊(worked_hours / estimated_hours) × 100⌋, 100)   en otro caso
}
```

**Progreso de fase:**

```
H_done = Σ t.worked_hours        ∀ t ∈ phase.tasks : t.status = Done
H_total = Σ t.estimated_hours    ∀ t ∈ phase.tasks

P_f = {
    0                       si H_total = 0
    ⌊(H_done / H_total) × 100⌋   en otro caso
}
```

**Peso de fase:**

```
W_f = H_total_f / H_total_project

Donde H_total_project = Σ H_total_fi     ∀ fi ∈ project.phases
```

**Progreso de proyecto:**

```
P_p = Σ (W_fi × P_fi)     ∀ fi ∈ project.phases
```

**Condición de fase completada:**

```
is_completed(fase) = (∀ t ∈ fase.tasks : t.status = Done) ∧ (∀ c ∈ fase.criteria : c.completed = true) ∧ (count(fase.tasks) > 0)
```

---

## 4. Restricciones

### 4.1 Restricciones de Tarea

| ID | Restricción |
|----|------------|
| RE-T01 | Una tarea en estado `Done` no puede editarse (título, descripción, prioridad, etc.). |
| RE-T02 | Una tarea en estado `Done` no puede cambiar de estado. |
| RE-T03 | Las transiciones de estado están limitadas a las definidas en `TaskStatus::allowedTransitions()`. |
| RE-T04 | `estimated_hours` debe ser ≥ 0. |
| RE-T05 | `worked_hours` no puede ser escrito manualmente; se deriva de `TaskTimeLog`. |
| RE-T06 | `progress` no puede ser escrito manualmente; se deriva de horas. |

---

### 4.2 Restricciones de Fase

| ID | Restricción |
|----|------------|
| RE-F01 | No se pueden crear tareas en una fase cuya `end_date` ya expiró (`now > end_date`) Y la fase no está completada. |
| RE-F02 | Las tareas existentes en una fase vencida pueden seguir editándose y completándose. |
| RE-F03 | Un usuario con permiso explícito (`phase.extend_date`) puede modificar `end_date` para reabrir la creación de tareas. |
| RE-F04 | El `progress` de la fase no puede ser escrito manualmente; se deriva del progreso de sus tareas. |
| RE-F05 | Una fase no puede eliminarse si tiene tareas asociadas. (Validación estándar de FK con `nullOnDelete` o `restrict`.) |

**Ejemplo RE-F01:**

```
Fase "Planificación": end_date = 2026-05-01
Hoy: 2026-05-15

Intentar POST /api/projects/1/phases/1/tasks → HTTP 422 "La fase ha expirado. No se pueden agregar nuevas tareas."
```

**Ejemplo RE-F01 (excepción):**

```
Fase "Desarrollo": end_date = 2026-04-01, status = Completed
Hoy: 2026-05-15

No se pueden crear tareas porque la fase ya está completada (el `status` lo impide, no la fecha).
```

---

### 4.3 Restricciones de Entregables

| ID | Restricción |
|----|------------|
| RE-D01 | Un entregable con `parent_id` no nulo NO puede ser aprobado hasta que su entregable padre esté aprobado. |
| RE-D02 | La validación de dependencias se ejecuta en el observer (`DeliverableObserver`) y en la policy (`DeliverablePolicy`). |
| RE-D03 | No se pueden crear dependencias circulares (A → B → A). Validar en `DeliverableService` antes de guardar. |

**Ejemplo RE-D01:**

```
Entregable "Código fuente" (id=1): approved = false
Entregable "Manual de usuario" (id=2): parent_id = 1, approved = false

Intentar PATCH /api/deliverables/2/approve → HTTP 422 "El entregable padre 'Código fuente' no ha sido aprobado."
```

---

### 4.4 Restricciones de Objetivos y Riesgos

| ID | Restricción |
|----|------------|
| RE-O01 | Un objetivo con `phase_id` no nulo pertenece exclusivamente a esa fase. |
| RE-O02 | Un objetivo con `phase_id = null` es global del proyecto. |
| RE-R01 | Un riesgo con `phase_id` no nulo pertenece exclusivamente a esa fase. |
| RE-R02 | Un riesgo con `phase_id = null` es global del proyecto. |
| RE-R03 | Al consultar métricas de una fase, solo se incluyen riesgos con `phase_id = phase.id`. |

---

## 5. Validaciones Funcionales

### 5.1 Validaciones en FormRequests (backend)

| Endpoint | Validación |
|----------|-----------|
| `POST /api/tasks` | No aceptar `progress` ni `worked_hours` (se derivan). `phase.end_date` no debe estar vencida si `phase.status != Completed`. |
| `PUT /api/tasks/{id}` | No aceptar `progress` ni `worked_hours`. Validar transición de estado con `TaskStatus::canTransitionTo()`. Si `status = Done`, verificar que no existan blockers sin resolver. |
| `POST /api/task-time-logs` | `hours` > 0. `task.status != Done` (no se pueden registrar horas en tareas completadas). |
| `POST /api/deliverables/{id}/approve` | Verificar que `parent_id` sea null o que el padre esté aprobado. |
| `POST /api/phases/{id}/criteria` | `description` no vacío. |
| `PUT /api/phases/{id}/criteria/{cid}` | Solo permitir `completed` de false → true. No revertir. |

---

### 5.2 Validaciones en frontend (UI)

| Componente | Validación |
|-----------|-----------|
| `TaskForm.vue` | No mostrar campos `progress` ni `worked_hours` como editables. Mostrar como solo lectura (calculados). |
| `PhaseForm.vue` | Mostrar `progress` como solo lectura. Mostrar indicador de fase vencida. |
| `DeliverableForm.vue` | Si se selecciona `parent_id`, mostrar solo entregables no aprobados del proyecto (excluir entregables ya aprobados y dependencias circulares). |

---

## 6. Reglas de Transición de Estados

### 6.1 TaskStatus

```
Pending ──────→ InProgress
Pending ──────→ Blocked
InProgress ───→ Review
InProgress ───→ Blocked
InProgress ───→ Done
Review ───────→ InProgress
Review ───────→ Done
Blocked ──────→ InProgress
Blocked ──────→ Pending
Done ─────────→ (ninguna)
```

Fuente: `backend/app/Enums/TaskStatus.php::allowedTransitions()`

---

### 6.2 PhaseStatus (nuevo, propuesto)

```
Planned ──────→ InProgress
InProgress ───→ Completed     (automático: todas las tareas Done + criterios cumplidos)
InProgress ───→ Expired       (automático: now > end_date sin completar)
Expired ──────→ InProgress    (manual: PM extiende end_date)
Completed ────→ (ninguna)
```

---

## 7. Reglas de Notificación

| ID | Evento | Notificar a |
|----|--------|-------------|
| NO-01 | `TaskCompleted` | PM del proyecto, creador de la tarea |
| NO-02 | `PhaseCompleted` | PM del proyecto, todos los miembros de la fase |
| NO-03 | `AcceptanceCriterionCompleted` | PM del proyecto |
| NO-04 | `PhaseProgressUpdated` | No notificar (evento interno de recálculo) |
| NO-05 | `TaskProgressUpdated` | No notificar (evento interno de recálculo) |

---

## 8. Resumen de Reglas por Entidad

### 8.1 Task

| Regla | Tipo | Descripción breve |
|-------|------|-------------------|
| AV-T01 | Avance | progress = worked_hours / estimated_hours × 100 |
| AV-T02 | Avance | Si Done, progress = 100 |
| AV-T06 | Avance | worked_hours = Σ(timeLog.hours) |
| CI-T01 | Cierre | Completada cuando status = Done |
| CI-T03 | Cierre | No editable cuando Done |
| RE-T01 | Restricción | No editar cuando Done |
| RE-T03 | Restricción | Transiciones limitadas por enum |
| RE-T04 | Restricción | estimated_hours ≥ 0 |
| RE-T05 | Restricción | worked_hours derivado, no manual |
| RE-T06 | Restricción | progress derivado, no manual |

### 8.2 ProjectPhase

| Regla | Tipo | Descripción breve |
|-------|------|-------------------|
| AV-F01 | Avance | progress = horas_done / horas_totales × 100 |
| CI-F01 | Cierre | Todas las tareas Done + todos los criterios cumplidos |
| CI-F04 | Cierre | Verificación automática en cada cambio relevante |
| CI-F06 | Cierre | Cierre automático, no manual |
| RE-F01 | Restricción | No crear tareas en fase vencida |
| RE-F04 | Restricción | progress derivado, no manual |

### 8.3 Project

| Regla | Tipo | Descripción breve |
|-------|------|-------------------|
| AV-P01 | Avance | progress = Σ(peso_fase × progress_fase) |
| AV-P07 | Avance | NO usar promedio simple |
| CI-P01 | Cierre | Cierre manual por PM |

### 8.4 Deliverable

| Regla | Tipo | Descripción breve |
|-------|------|-------------------|
| RE-D01 | Restricción | No aprobar si padre no aprobado |
| RE-D03 | Restricción | No dependencias circulares |

---

## 9. Glosario

| Término | Definición |
|---------|-----------|
| **Horas estimadas** (`estimated_hours`) | Horas planificadas para completar una tarea. Definidas al crear la tarea. |
| **Horas trabajadas** (`worked_hours`) | Horas reales registradas mediante `TaskTimeLog`. Suma acumulada. |
| **Horas completadas** | `worked_hours` de tareas en estado `Done`. Solo cuentan si la tarea está terminada. |
| **Horas totales de fase** | Suma de `estimated_hours` de todas las tareas de la fase. |
| **Horas totales de proyecto** | Suma de horas totales de todas las fases del proyecto. |
| **Peso de fase** | Proporción de horas totales de la fase respecto a las horas totales del proyecto. |
| **Progreso** | Porcentaje de avance (0-100). Derivado, no manual. |
| **Criterio de aceptación** | Condición objetiva y verificable que debe cumplirse para que una fase se considere completada. |
| **Fase vencida** | Fase cuya `end_date` es anterior a la fecha actual y no está completada. |
| **Cierre automático** | El sistema determina que una fase cumple todas las condiciones y la marca como completada sin intervención manual. |
| **Persisted computed column** | Campo almacenado en BD pero actualizado exclusivamente por observers/listeners (nunca por input de usuario). |

---

## 10. Referencias

- `AnalisisRefactorizacionGestionProyectos.md` — Diagnóstico completo y propuesta de refactorización.
- `DiagramaDominio.md` — Diagramas Mermaid del modelo de dominio.
- `backend/app/Enums/TaskStatus.php` — Transiciones de estado de tareas.
- `backend/app/Observers/TaskObserver.php` — Observer actual de tareas.
- `backend/app/Services/TaskService.php` — Servicio de dominio de tareas.

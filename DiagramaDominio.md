# Diagrama de Dominio — Módulo de Gestión de Proyectos

**Fecha:** 2026-06-16
**Versión:** 1.0
**Notación:** Mermaid (Entity Relationship Diagram)
**Propósito:** Representar gráficamente el modelo de dominio propuesto tras la refactorización.

---

## 1. Diagrama ER — Modelo de dominio completo

```mermaid
erDiagram
    Project ||--o{ ProjectPhase : "has phases"
    Project ||--o{ Task : "has tasks"
    Project ||--o{ Objective : "has global objectives"
    Project ||--o{ Risk : "has global risks"
    Project ||--o{ Deliverable : "has global deliverables"
    Project ||--o{ Milestone : "has milestones"
    Project ||--o{ Ticket : "has tickets"
    Project ||--o{ Blocker : "has blockers"
    Project ||--o{ ProjectMember : "has members"
    Project ||--|| ProjectMetric : "has metrics"
    Project }o--|| User : "owned by (owner_id)"

    ProjectPhase ||--o{ Task : "contains tasks"
    ProjectPhase ||--o{ AcceptanceCriterion : "has acceptance criteria"
    ProjectPhase ||--o{ Objective : "has phase objectives"
    ProjectPhase ||--o{ Risk : "has phase risks"
    ProjectPhase ||--o{ Deliverable : "has phase deliverables"

    Task ||--o{ TaskTimeLog : "has time logs"
    Task ||--o{ TaskComment : "has comments"
    Task ||--o{ Blocker : "is blocked by"
    Task }o--|| User : "assigned to"
    Task }o--|| User : "created by"

    Deliverable ||--o{ Deliverable : "parent → child dependency"
    Deliverable }o--|| User : "approved by"

    Risk }o--|| User : "reported by"

    ProjectMember }o--|| User : "belongs to user"

    AcceptanceCriterion {
        bigint id PK
        bigint phase_id FK "NOT NULL"
        text description
        boolean completed "DEFAULT FALSE"
        timestamp created_at
        timestamp updated_at
    }

    Project {
        bigint id PK
        bigint owner_id FK
        string name
        string code
        text description
        enum status "ProjectStatus"
        date start_date
        date end_date
        decimal budget
        tinyint progress "DERIVED"
        uuid uuid
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    ProjectPhase {
        bigint id PK
        bigint project_id FK
        string name
        date start_date
        date end_date
        tinyint progress "DERIVED"
        enum status "PhaseStatus (NUEVO)"
        timestamp completed_at "NUEVO"
        timestamp created_at
        timestamp updated_at
    }

    Task {
        bigint id PK
        bigint project_id FK
        bigint phase_id FK "nullable"
        bigint assigned_to FK "nullable"
        bigint created_by FK
        string title
        text description
        string priority "low|medium|high|critical"
        enum status "TaskStatus"
        datetime due_date
        int estimated_hours
        int worked_hours "DERIVED"
        tinyint progress "DERIVED"
        timestamp created_at
        timestamp updated_at
    }

    Objective {
        bigint id PK
        bigint project_id FK
        bigint phase_id FK "NUEVO nullable"
        enum type "ObjectiveType"
        string title
        text description
        boolean completed
        timestamp created_at
        timestamp updated_at
    }

    Risk {
        bigint id PK
        bigint project_id FK
        bigint phase_id FK "NUEVO nullable"
        bigint reported_by FK "NUEVO nullable"
        string title
        text description
        enum impact "RiskImpact"
        enum probability "RiskProbability"
        text mitigation_plan
        enum status "RiskStatus"
        timestamp created_at
        timestamp updated_at
    }

    Deliverable {
        bigint id PK
        bigint project_id FK
        bigint phase_id FK "NUEVO nullable"
        bigint parent_id FK "NUEVO nullable self-ref"
        bigint approved_by FK "NUEVO nullable"
        string name
        text description
        date delivery_date
        boolean approved
        timestamp created_at
        timestamp updated_at
    }

    Milestone {
        bigint id PK
        bigint project_id FK
        string title
        date target_date
        boolean completed
        timestamp created_at
        timestamp updated_at
    }

    TaskTimeLog {
        bigint id PK
        bigint task_id FK
        bigint user_id FK
        int hours
        string description
        timestamp logged_at
        timestamp created_at
        timestamp updated_at
    }

    TaskComment {
        bigint id PK
        bigint task_id FK
        bigint user_id FK
        text body
        timestamp created_at
        timestamp updated_at
    }

    Ticket {
        bigint id PK
        bigint project_id FK
        bigint created_by FK
        bigint assigned_to FK
        string title
        text description
        string priority
        enum status "TicketStatus"
        timestamp created_at
        timestamp updated_at
    }

    Blocker {
        bigint id PK
        bigint project_id FK
        bigint task_id FK
        bigint reported_by FK
        bigint resolved_by FK
        string title
        text description
        enum severity "BlockerSeverity"
        boolean resolved
        timestamp resolved_at
        timestamp created_at
        timestamp updated_at
    }

    User {
        bigint id PK
        string name
        string email
        string password
        timestamp role_changed_at
        timestamp created_at
        timestamp updated_at
    }

    ProjectMember {
        bigint id PK
        bigint project_id FK
        bigint user_id FK
        string role "ProjectMemberRole"
        timestamp created_at
        timestamp updated_at
    }

    ProjectMetric {
        bigint id PK
        bigint project_id FK
        int total_tasks
        int completed_tasks
        int open_tickets
        int total_blockers
        decimal completion_rate "DERIVED"
        timestamp created_at
        timestamp updated_at
    }
```

---

## 2. Diagrama jerárquico de avance (flujo de cálculo)

```mermaid
flowchart TD
    subgraph "Nivel 0: Proyecto"
        PP[progress_proyecto] -->|"Σ peso_fase × progress_fase"| PF1[Fase 1 progress]
        PP -->|"Σ peso_fase × progress_fase"| PF2[Fase 2 progress]
        PP -->|"Σ peso_fase × progress_fase"| PF3[Fase N progress]
    end

    subgraph "Nivel 1: Fase"
        PHASE[Fase]
        PHASE -->|"Σ worked_hours_done"| T1[Tarea done 1]
        PHASE -->|"Σ worked_hours_done"| T2[Tarea done 2]
        PHASE -->|"Σ estimated_hours"| T3[Tarea pendiente 1]
        PHASE -->|"completed = all Done AND all criteria met"| CRITERIA[Criterios de aceptación]
        PHASE -->|"is_expired = now > end_date"| EXPIRED[Fase vencida]
    end

    subgraph "Nivel 2: Tarea"
        TASK[Tarea]
        TASK -->|"worked_hours / estimated_hours"| TPROG[progress_tarea]
        TASK -->|"status == Done"| TDONE[is_done]
        TASK -->|"worked_hours"| TWL[TaskTimeLogs]
    end

    subgraph "Nivel auxiliar: Criterios"
        AC1[Criterio 1: completed?]
        AC2[Criterio 2: completed?]
        CRITERIA --> AC1
        CRITERIA --> AC2
    end

    TWL -->|"created/updated/deleted"| TPROG
    TPROG -->|"recalcula"| PHASE
    TDONE -->|"dispara TaskCompleted"| PHASE
    AC1 -->|"completado"| CRITERIA
    AC2 -->|"completado"| CRITERIA
    CRITERIA -->|"todos cumplidos + todas tareas Done"| PHASE
    PHASE -->|"PhaseProgressUpdated"| PP
```

---

## 3. Diagrama de eventos y observers (flujo de recálculo automático)

```mermaid
flowchart LR
    subgraph "Origen del cambio"
        TTL[TaskTimeLog\ncreated/updated/deleted]
        TS[Task.status\ncambia a Done]
        TE[Task.estimated_hours\ncambia]
        AC[AcceptanceCriterion\ncompleted = true]
    end

    subgraph "Observers"
        TTLO[TaskTimeLogObserver]
        TO[TaskObserver]
        ACO[AcceptanceCriterionObserver]
        PO[ProjectPhaseObserver]
    end

    subgraph "Events"
        TCE[TaskCompleted]
        TPU[TaskProgressUpdated]
        ACC[AcceptanceCriterionCompleted]
        PCE[PhaseCompleted]
        PPU[PhaseProgressUpdated]
    end

    subgraph "Listeners / Jobs"
        RPP[RecalculatePhaseProgress]
        RPPJ[RecalculateProjectProgress]
        CVP[CheckPhaseCompletion]
    end

    subgraph "Resultado"
        PHASE_PROG[Fase.progress\nactualizado]
        PROJ_PROG[Proyecto.progress\nactualizado]
        PHASE_DONE[Fase completada\nstatus = completed]
    end

    TTL --> TTLO
    TTLO -->|"suma/resta worked_hours"| TO
    TS --> TO
    TE --> TO

    TO -->|"recalcula task.progress"| TPU
    TO -->|"si status == Done"| TCE

    TPU --> RPP
    TCE --> RPP

    RPP -->|"recalcula"| PHASE_PROG
    RPP --> CVP

    CVP -->|"verifica tareas + criterios"| PCE
    PCE -->|"marca fase completada"| PHASE_DONE

    AC --> ACO
    ACO -->|"dispara"| ACC
    ACC --> CVP

    PHASE_PROG --> PO
    PO -->|"dispara"| PPU
    PPU --> RPPJ
    RPPJ -->|"recalcula (fórmula ponderada)"| PROJ_PROG
```

---

## 4. Modelo de estados de tarea (TaskStatus)

```mermaid
stateDiagram-v2
    [*] --> Pending
    Pending --> InProgress : "Iniciar trabajo"
    Pending --> Blocked : "Bloqueada por impedimento"
    InProgress --> Review : "Enviar a revisión"
    InProgress --> Blocked : "Bloqueada por impedimento"
    InProgress --> Done : "Completar directamente"
    Review --> InProgress : "Rechazar / volver a progreso"
    Review --> Done : "Aprobar revisión"
    Blocked --> InProgress : "Desbloquear / reanudar"
    Blocked --> Pending : "Desbloquear / volver a pendiente"
    Done --> [*]
```

---

## 5. Modelo de estados de fase (nuevo — PhaseStatus)

```mermaid
stateDiagram-v2
    [*] --> Planned
    Planned --> InProgress : "Iniciar fase"
    InProgress --> Completed : "Todas las tareas Done\n+ todos los criterios cumplidos"
    InProgress --> Expired : "end_date vencida\nsin completar"
    Expired --> InProgress : "Extender end_date"
    Completed --> [*]
```

---

## 6. Relaciones polimórficas — Entidades vinculables a fase o proyecto

```mermaid
flowchart TD
    subgraph "Ámbito: Proyecto (global)"
        OG[Objective\nglobal]
        RG[Risk\nglobal]
        DG[Deliverable\nglobal]
    end

    subgraph "Ámbito: Fase específica"
        OF[Objective\nde fase]
        RF[Risk\nde fase]
        DF[Deliverable\nde fase]
    end

    PROJECT[Project] -->|"phase_id IS NULL"| OG
    PROJECT -->|"phase_id IS NULL"| RG
    PROJECT -->|"phase_id IS NULL"| DG

    PHASE[ProjectPhase] -->|"phase_id = phase.id"| OF
    PHASE -->|"phase_id = phase.id"| RF
    PHASE -->|"phase_id = phase.id"| DF

    OG -.-|"solo project_id"| PROJECT
    OF -.-|"project_id + phase_id"| PROJECT
    RG -.-|"solo project_id"| PROJECT
    RF -.-|"project_id + phase_id"| PROJECT
    DG -.-|"solo project_id"| PROJECT
    DF -.-|"project_id + phase_id"| PROJECT
```

---

## 7. Notas del diagrama

- Los campos marcados como **"NUEVO"** en el ER son adiciones propuestas en la refactorización.
- Los campos marcados como **"DERIVED"** son calculados automáticamente vía observers/listeners; nunca deben ser escritos directamente por el frontend.
- Las relaciones `phase_id` en `objectives`, `risks` y `deliverables` son **nullable**. Un valor `null` significa que la entidad pertenece al proyecto en lugar de a una fase específica.
- La relación `parent_id` en `deliverables` es **self-referencing** (apunta a la misma tabla `deliverables`). Permite establecer una jerarquía de dependencias entre entregables.
- `AcceptanceCriterion` es una entidad nueva que no existe en el modelo actual. Se vincula obligatoriamente a `ProjectPhase` (`phase_id` NOT NULL).
- `TaskStatus` es un enum con transiciones controladas por el método `canTransitionTo()`.
- `PhaseStatus` (Planned → InProgress → Completed / Expired) es un enum nuevo propuesto para la refactorización.

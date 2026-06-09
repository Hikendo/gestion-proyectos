# Documentación de Testing — Gestión de Proyectos

**Fecha:** 2026-06-08  
**Versión:** 1.0  
**Rama base:** `main` (commit `767283b`)

---

## Índice

1. [Arquitectura de testing](#arquitectura-de-testing)
2. [Diagrama de flujo de interacción E2E](#diagrama-de-flujo-de-interacción-e2e)
3. [Tests unitarios (Vitest)](#tests-unitarios-vitest)
4. [Tests de integración (PHPUnit)](#tests-de-integración-phpunit)
5. [Tests E2E (Playwright)](#tests-e2e-playwright)
6. [Cómo ejecutar los tests](#cómo-ejecutar-los-tests)
7. [Permisos por rol](#permisos-por-rol)

---

## Arquitectura de testing

```
┌─────────────────────────────────────────────────────────┐
│                    TESTING PYRAMID                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│                    ┌───────────┐                         │
│                    │   E2E     │  ← Playwright           │
│                    │  6 tests  │     (navegador real)    │
│                    └─────┬─────┘                         │
│                          │                               │
│               ┌──────────┴──────────┐                    │
│               │    Integration      │  ← PHPUnit          │
│               │    88 tests         │     (HTTP + DB)     │
│               └──────────┬──────────┘                    │
│                          │                               │
│         ┌────────────────┴────────────────┐              │
│         │           Unit                   │  ← Vitest    │
│         │         135 tests                │     (JS/TS)  │
│         └─────────────────────────────────┘              │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

| Capa | Framework | Ubicación | # Tests | Ejecución |
|------|-----------|-----------|---------|-----------|
| Unidad (Frontend) | Vitest | `frontend/src/__tests__/` | 135 | `npm run test` |
| Unidad (Backend) | PHPUnit | `backend/tests/Unit/` | 12 | `php artisan test --testsuite=Unit` |
| Integración (Backend) | PHPUnit | `backend/tests/Feature/` | 76 | `php artisan test --testsuite=Feature` |
| E2E | Playwright | `frontend/tests/e2e/` | 6 grupos | `npx playwright test --headed` |

---

## Diagrama de flujo de interacción E2E

### Flujo completo cross-role

```mermaid
sequenceDiagram
    actor Admin as Super-Admin
    actor PM as Project Manager
    actor Dev as Developer
    actor QA as QA
    actor Support as Support
    actor Client as Client

    Admin->>PM: Crea usuario PM
    Admin->>Dev: Crea usuario Developer
    Admin->>QA: Crea usuario QA
    Admin->>Support: Crea usuario Support
    Admin->>Client: Crea usuario Client

    PM->>Project: Crea proyecto
    PM->>Project: Agrega miembros (Dev, QA, Support, Client)
    PM->>Project: Crea objetivo
    PM->>Project: Crea fase
    PM->>Project: Crea plan
    PM->>Project: Crea riesgo
    PM->>Project: Crea hito
    PM->>Project: Crea entregable
    PM->>Task: Crea tarea (asignada a Dev)
    PM->>Ticket: Crea ticket

    Dev->>Task: Ve tarea asignada
    Dev->>Task: Cambia status → in_progress
    Dev->>Ticket: Crea ticket de reporte
    Dev->>Blocker: Crea blocker
    Dev->>Admin: Verifica que NO ve panel admin

    QA->>Task: Ve tareas del proyecto
    QA->>Ticket: Crea ticket de validación
    QA->>Blocker: Crea blocker de QA

    Support->>Ticket: Crea ticket de soporte
    Support->>Blocker: Ve blockers existentes
    Support->>Admin: Verifica que NO ve panel admin

    Client->>Ticket: Crea ticket de solicitud
    Client->>Milestone: Ve hitos del proyecto
    Client->>Deliverable: Ve entregables
    Client->>Objective: Ve objetivos
    Client->>Metrics: Ve métricas
    Client->>Task: Verifica que NO puede crear tareas
    Client->>Risk: Verifica que NO puede crear riesgos
    Client->>Admin: Verifica que NO ve panel admin

    Admin->>Users: Lista usuarios del sistema
    Admin->>Roles: Ve roles y permisos
```

### Flujo de autorización por rol

```mermaid
flowchart TD
    Login[Usuario hace login] --> RoleCheck{¿Qué rol tiene?}

    RoleCheck -->|super-admin| SA[Acceso total]
    RoleCheck -->|project-manager| PM[PM: Crear/editar/eliminar todo en su proyecto]
    RoleCheck -->|developer| DEV[Dev: Tareas asignadas, tickets propios, blockers]
    RoleCheck -->|qa| QA2[QA: Tareas asignadas, tickets, blockers]
    RoleCheck -->|support| SUP[Support: Tickets, asignación, blockers view]
    RoleCheck -->|client| CLI[Client: Tickets propios, hitos, entregables view]

    SA --> SA_ACTIONS[Ver admin panel<br/>Gestionar usuarios<br/>Ver roles]

    PM --> PM_ACTIONS[Crear proyecto<br/>Asignar miembros<br/>Objetivos/Fases/Planes<br/>Riesgos/Hitos/Entregables<br/>Tareas/Tickets]

    DEV --> DEV_ACTIONS[Ver tareas asignadas<br/>Cambiar status<br/>Log time<br/>Crear tickets/blockers]

    QA2 --> QA_ACTIONS[Ver tareas<br/>Cambiar status<br/>Crear tickets/blockers]

    SUP --> SUP_ACTIONS[Crear/asignar tickets<br/>Ver blockers]

    CLI --> CLI_ACTIONS[Crear tickets (solo Open)<br/>Ver hitos/entregables<br/>Ver objetivos/métricas]

    DEV_ACTIONS --> DEV_RESTRICT{¿Acción permitida?}
    QA_ACTIONS --> QA_RESTRICT{¿Acción permitida?}
    CLI_ACTIONS --> CLI_RESTRICT{¿Acción permitida?}

    DEV_RESTRICT -->|Crear proyecto| FORBIDDEN[403 Forbidden]
    DEV_RESTRICT -->|Editar tarea ajena| FORBIDDEN
    QA_RESTRICT -->|Crear proyecto| FORBIDDEN
    CLI_RESTRICT -->|Crear tarea| BUTTON_HIDDEN[Botón oculto en UI]
    CLI_RESTRICT -->|Crear riesgo| BUTTON_HIDDEN
    CLI_RESTRICT -->|Ver admin| MENU_HIDDEN[Menú no visible]
```

### Flujo del ciclo de vida de attachments

```mermaid
sequenceDiagram
    actor User as Usuario
    participant API as API (Laravel)
    participant Disk as Storage (local)
    participant DB as Base de Datos

    User->>API: POST /attachments/upload-temp
    API->>Disk: Guarda en drafts/{uuid}.ext
    API->>DB: Crea registro (status=temp)
    API-->>User: Retorna UUID

    User->>API: POST /tasks (crear tarea)
    API->>DB: Crea tarea
    API-->>User: Retorna task ID

    User->>API: POST /attachments/claim
    Note over User,API: { parent_type: "tasks", parent_id: X, uuids: [...] }
    API->>API: Gate::authorize('update', $task)
    API->>DB: Busca attachments (status=temp, user=current)
    API->>Disk: Mueve drafts/ → projects/{project_uuid}/
    API->>DB: Actualiza registro (status=claimed, attachable=X)
    API-->>User: Retorna attachments claimados
```

---

## Tests unitarios (Vitest)

### Archivos de test

| Archivo | Tests | Descripción |
|---------|-------|-------------|
| `useAppStore.spec.ts` | 5 | Snackbar y loader global |
| `useNotificationStore.spec.ts` | 22 | CRUD de notificaciones, FCM, bandeja |
| `usePermissionStore.spec.ts` | 6 | Carga, verificación, refresh, clear de permisos |
| `useAttachments.spec.ts` | 29 | Subida, descarga, reemplazo, eliminación de archivos |
| `useFieldLock.spec.ts` | 5 | Reactividad del field-locking, Proxy + computed |
| `useProjects.spec.ts` | 8 | Ciclo de vida de proyectos |
| `useTasks.spec.ts` | 4 | Ciclo de vida de tareas |
| `useTickets.spec.ts` | 6 | Ciclo de vida de tickets |
| `useMilestones.spec.ts` | 3 | Ciclo de vida de hitos |
| `useRisks.spec.ts` | 3 | Ciclo de vida de riesgos |
| `canAction.spec.ts` | 7 | Verificación de permisos (token, store, -own) |
| `auth.service.spec.ts` | 7 | Login, logout, me, register |
| `projects.service.spec.ts` | 12 | CRUD de proyectos |
| `tickets.service.spec.ts` | 7 | CRUD de tickets |
| `notifications.service.spec.ts` | 11 | API de notificaciones |

### Stores testeadas

```mermaid
flowchart LR
    subgraph "Pinia Stores"
        AppStore[useAppStore]
        AuthStore[useAuthStore]
        NotificationStore[useNotificationStore]
        PermissionStore[usePermissionStore]
        ThemeStore[useThemeStore]
    end

    subgraph "Tests"
        AppStore --> AppSpec[useAppStore.spec.ts ✅]
        NotificationStore --> NotifSpec[useNotificationStore.spec.ts ✅]
        PermissionStore --> PermSpec[usePermissionStore.spec.ts ✅]
    end

    subgraph "Composables"
        FieldLock[useFieldLock]
        FieldLock --> FieldLockSpec[useFieldLock.spec.ts ✅]
    end
```

---

## Tests de integración (PHPUnit)

### Estructura

```
backend/tests/
├── Feature/
│   ├── Attachment/
│   │   └── AttachmentClaimTest.php      # 3 tests — ciclo temp→claim
│   ├── Auth/
│   │   ├── AuthTest.php                 # 6 tests — login/logout/register
│   │   └── PermissionsFlowTest.php      # 4 tests — refresh, field_permissions
│   ├── Dashboard/
│   │   └── DashboardTest.php            # 5 tests — dashboard por rol
│   ├── Project/
│   │   ├── BlockerTest.php              # 5 tests
│   │   ├── DeliverableTest.php          # 4 tests
│   │   ├── MilestoneTest.php            # 3 tests
│   │   ├── ProjectMemberTest.php        # 4 tests
│   │   ├── ProjectScopedAccessTest.php  # 4 tests — scope cross-project
│   │   └── ProjectTest.php              # 6 tests
│   ├── Task/
│   │   ├── TaskCommentTest.php          # 3 tests
│   │   ├── TaskTest.php                 # 5 tests
│   │   └── TaskTimeLogTest.php          # 5 tests
│   ├── Ticket/
│   │   └── TicketTest.php               # 5 tests
│   └── User/
│       └── UserTest.php                 # 12 tests
└── Unit/
    ├── ExampleTest.php
    └── Notifications/
        ├── NotificationRecipientResolverTest.php  # 8 tests
        └── PolicyAwareRecipientFilterTest.php     # 4 tests
```

### Tests creados en esta refactorización

```mermaid
flowchart TD
    subgraph "Nuevos tests (11)"
        PSA[ProjectScopedAccessTest<br/>4 tests]
        PFT[PermissionsFlowTest<br/>4 tests]
        ACT[AttachmentClaimTest<br/>3 tests]
    end

    subgraph "Qué validan"
        PSA --> PSA1[No-miembro no puede ver tareas]
        PSA --> PSA2[Miembro sí puede ver tareas]
        PSA --> PSA3[Cross-project devuelve 404]
        PSA --> PSA4[Edición cross-project bloqueada]

        PFT --> PFT1[/me retorna permisos]
        PFT --> PFT2[/refresh-permissions funciona]
        PFT --> PFT3[No autenticado → 401]
        PFT --> PFT4[field_permissions en respuesta]

        ACT --> ACT1[Subida temp → claim → verificar]
        ACT --> ACT2[Otro user no puede claimear]
        ACT --> ACT3[No autenticado no puede subir]
    end
```

---

## Tests E2E (Playwright)

### Archivo único: `01-cross-role-lifecycle.spec.ts`

```mermaid
flowchart TD
    BeforeAll[beforeAll] --> CreateUsers[SuperAdmin crea 5 usuarios<br/>PM + Dev + QA + Support + Client]
    CreateUsers --> CreateProject[PM crea proyecto]
    CreateProject --> AddMembers[PM agrega miembros al proyecto]

    AddMembers --> Test1

    Test1[Test 1: PM] --> PM_Obj[Objetivos]
    Test1 --> PM_Phases[Fases]
    Test1 --> PM_Plans[Planes]
    Test1 --> PM_Risks[Riesgos]
    Test1 --> PM_Mstones[Hitos]
    Test1 --> PM_Deliv[Entregables]
    Test1 --> PM_Tasks[Tareas → asignada a Dev]
    Test1 --> PM_Tickets[Tickets]

    Test2[Test 2: Developer] --> Dev_Task[Ve tarea asignada]
    Dev_Task --> Dev_Status[Cambia status → in_progress]
    Dev_Status --> Dev_Ticket[Crea ticket]
    Dev_Ticket --> Dev_Blocker[Crea blocker]
    Dev_Blocker --> Dev_NoAdmin[Verifica sin admin panel]

    Test3[Test 3: QA] --> QA_Tasks[Ve tareas]
    QA_Tasks --> QA_Ticket[Crea ticket QA]
    QA_Ticket --> QA_Blocker[Crea blocker QA]

    Test4[Test 4: Support] --> Sup_Ticket[Crea ticket soporte]
    Sup_Ticket --> Sup_Blockers[Ve blockers]
    Sup_Blockers --> Sup_NoAdmin[Verifica sin admin]

    Test5[Test 5: Client] --> Cli_Ticket[Crea ticket]
    Cli_Ticket --> Cli_View[Ve hitos, entregables,<br/>objetivos, métricas]
    Cli_View --> Cli_Restrict[Verifica restricciones:<br/>no tareas, no riesgos, no admin]

    Test6[Test 6: Admin] --> Adm_Menu[Ve panel admin]
    Adm_Menu --> Adm_Users[Lista usuarios]
    Adm_Users --> Adm_Roles[Ve roles]

    style Test1 fill:#4caf50,color:#fff
    style Test2 fill:#2196f3,color:#fff
    style Test3 fill:#ff9800,color:#fff
    style Test4 fill:#9c27b0,color:#fff
    style Test5 fill:#f44336,color:#fff
    style Test6 fill:#607d8b,color:#fff
```

### Módulos cubiertos por rol

| Módulo | PM | Dev | QA | Support | Client | Admin |
|--------|:--:|:---:|:--:|:-------:|:------:|:-----:|
| Proyectos | ✅ C/E | ✅ V | ✅ V | ✅ V | ✅ V | ✅ V |
| Objetivos | ✅ C | ❌ | ❌ | ❌ | ✅ V | ❌ |
| Fases | ✅ C | ❌ | ❌ | ❌ | ❌ | ❌ |
| Planes | ✅ C | ❌ | ❌ | ❌ | ❌ | ❌ |
| Tareas | ✅ C/E | ✅ V/U | ✅ V | ❌ | ❌ V | ❌ |
| Tickets | ✅ C | ✅ C | ✅ C | ✅ C | ✅ C | ❌ |
| Riesgos | ✅ C | ❌ | ❌ | ❌ | ❌ V | ❌ |
| Bloqueadores | ✅ | ✅ C | ✅ C | ✅ V | ❌ | ❌ |
| Hitos | ✅ C | ✅ V | ❌ | ❌ | ✅ V | ❌ |
| Entregables | ✅ C | ✅ V | ❌ | ❌ | ✅ V | ❌ |
| Métricas | ✅ V | ✅ V | ❌ | ❌ | ✅ V | ❌ |
| Miembros | ✅ C/E | ❌ | ❌ | ❌ | ❌ | ❌ |
| Admin/Users | ❌ | ❌ V | ❌ V | ❌ V | ❌ V | ✅ |
| Roles | ❌ | ❌ V | ❌ V | ❌ V | ❌ V | ✅ |

> **Leyenda:** C = Create, E = Edit, V = View, U = Update status, ❌ V = Verificar que NO tiene acceso

### Helpers de API

```mermaid
flowchart LR
    subgraph "helpers/api.ts"
        Login[login(email, password)] --> Token[Retorna token + user]
        CreateUser[createUser(adminToken, user)] --> Token
        Token --> CreateUser
        CreateUser --> Login
        DeleteUser[deleteUser(adminToken, id)]
        SeedUsers[seedTestUsers(adminToken)] --> CreateUser
    end
```

---

## Cómo ejecutar los tests

### Backend (PHPUnit) — dentro de Docker

```bash
# Todos los tests
docker compose exec backend php artisan test

# Solo un archivo
docker compose exec backend php artisan test --filter="ProjectScopedAccessTest"

# Con coverage (requiere Xdebug en Dockerfile)
docker compose exec backend php artisan test --coverage
```

### Frontend (Vitest) — dentro de Docker

```bash
# Todos los tests unitarios
docker compose exec frontend npm run test

# Modo watch
docker compose exec frontend npm run test:watch

# Con coverage
docker compose exec frontend npm run test:coverage
```

### E2E (Playwright) — desde el HOST

```bash
cd ~/Documentos/gestion-proyectos/frontend

# Todos los tests E2E (navegador visible)
npx playwright test --headed

# Solo el archivo cross-role
npx playwright test tests/e2e/01-cross-role-lifecycle.spec.ts --headed

# Debug paso a paso
npx playwright test --headed --debug

# Generar reporte HTML
npx playwright test --headed
npx playwright show-report
```

### Requisitos previos para E2E

1. Docker services corriendo: `docker compose up -d`
2. Migraciones aplicadas: `docker compose exec backend php artisan migrate --force`
3. Seeder ejecutado: `docker compose exec backend php artisan db:seed --class=RolesAndPermissionsSeeder`
4. Usuario admin creado: `docker compose exec backend php artisan app:create-admin`

---

## Permisos por rol

### Referencia completa del `RolesAndPermissionsSeeder`

```mermaid
graph TD
    subgraph "super-admin"
        SA[super-admin] --> ALL[TODOS los permisos]
    end

    subgraph "project-manager"
        PM[project-manager] --> PM_Dashboard[dashboard.view]
        PM --> PM_Project[project.view/create/edit/assign-members/manage-attachments]
        PM --> PM_Phase[phase.view/create/edit/delete]
        PM --> PM_Task[task.view/create/edit-content/delete/assign/update-status/manage-attachments]
        PM --> PM_Ticket[ticket.view/edit-any/assign/manage-attachments]
        PM --> PM_Risk[risk.view/create/edit/delete]
        PM --> PM_Blocker[blocker.view/create/edit/resolve]
        PM --> PM_Milestone[milestone.view/create/edit/delete]
        PM --> PM_Deliverable[deliverable.view/create/edit/approve]
        PM --> PM_Objective[objective.view/create/edit]
        PM --> PM_Metrics[metrics.view]
        PM --> PM_Reports[reports.view]
        PM --> PM_User[user.view]
    end

    subgraph "developer"
        DEV[developer] --> DEV_Dashboard[dashboard.view]
        DEV --> DEV_Project[project.view]
        DEV --> DEV_Task[task.view/create/edit-own/update-status/log-time]
        DEV --> DEV_Ticket[ticket.view/create/edit-own]
        DEV --> DEV_Blocker[blocker.view/create]
        DEV --> DEV_Milestone[milestone.view]
        DEV --> DEV_Risk[risk.view]
        DEV --> DEV_Deliverable[deliverable.view]
        DEV --> DEV_Objective[objective.view]
        DEV --> DEV_Metrics[metrics.view]
    end

    subgraph "qa"
        QA[qa] --> QA_Dashboard[dashboard.view]
        QA --> QA_Project[project.view]
        QA --> QA_Task[task.view/create/edit-own/update-status]
        QA --> QA_Ticket[ticket.view/create/edit-own]
        QA --> QA_Blocker[blocker.view/create]
        QA --> QA_Risk[risk.view]
        QA --> QA_Milestone[milestone.view]
        QA --> QA_Deliverable[deliverable.view]
        QA --> QA_Objective[objective.view]
        QA --> QA_Metrics[metrics.view]
    end

    subgraph "support"
        SUP[support] --> SUP_Dashboard[dashboard.view]
        SUP --> SUP_Project[project.view]
        SUP --> SUP_Task[task.view]
        SUP --> SUP_Ticket[ticket.view/create/edit-own/assign]
        SUP --> SUP_Blocker[blocker.view]
        SUP --> SUP_User[user.view]
    end

    subgraph "client"
        CLI[client] --> CLI_Dashboard[dashboard.view]
        CLI --> CLI_Project[project.view]
        CLI --> CLI_Ticket[ticket.view/create/edit-own]
        CLI --> CLI_Milestone[milestone.view]
        CLI --> CLI_Deliverable[deliverable.view]
        CLI --> CLI_Objective[objective.view]
        CLI --> CLI_Metrics[metrics.view]
        CLI --> CLI_Reports[reports.view]
    end
```

---

## Notas para CI/CD

### Ejecución en GitHub Actions / Jenkins

```yaml
# .github/workflows/tests.yml (ejemplo)
name: Tests

on: [push, pull_request]

jobs:
  backend:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.4
        env:
          MYSQL_DATABASE: gestion_db
          MYSQL_ROOT_PASSWORD: root
        ports:
          - 3306:3306
    steps:
      - uses: actions/checkout@v4
      - name: Backend tests
        run: |
          cd backend
          cp .env.ci .env
          composer install
          php artisan test

  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Frontend tests
        run: |
          cd frontend
          npm ci
          npm run test

  e2e:
    runs-on: ubuntu-latest
    needs: [backend, frontend]
    steps:
      - uses: actions/checkout@v4
      - name: E2E tests
        run: |
          cd frontend
          npm ci
          npx playwright install chromium
          npx playwright test

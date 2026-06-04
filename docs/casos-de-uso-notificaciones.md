# 📬 Casos de Uso — Sistema de Notificaciones (FCM + Bandeja)

```mermaid
graph TB
    %% ── Roles ──
    SA["🛡️ Super Admin<br/>(todos los permisos)"]
    PM["📋 Project Manager<br/>(gestión de proyectos)"]
    DEV["💻 Developer<br/>(tareas y tickets)"]
    CL["👤 Client<br/>(vista limitada del proyecto)"]

    %% ── Eventos disparadores ──
    EVT_PROJECT_CREATED["🆕 Proyecto creado"]
    EVT_TASK_ASSIGNED["📌 Tarea asignada"]
    EVT_TASK_COMPLETED["✅ Tarea completada"]
    EVT_TICKET_OPENED["🎫 Ticket abierto"]
    EVT_TICKET_ASSIGNED["🔀 Ticket reasignado"]
    EVT_BLOCKER_REPORTED["🚧 Bloqueador reportado"]
    EVT_MEMBER_ADDED["➕ Miembro agregado al proyecto"]

    %% ── Servicio Abstracto ──
    subgraph BACKEND["⚙️ Backend: AbstractNotificationService"]
        RESOLVER["NotificationRecipientResolver<br/>━━━━━━━━━━━━━━━━<br/>• resolveByRole('Project Manager')<br/>• resolveByRoles(['PM','Dev'])<br/>• resolveByPermission('Proyecto.ViewBudget')<br/>• resolveProjectMembers(project)<br/>• resolveTaskAssignees(task)<br/>• resolveTicketAssignees(ticket)"]
        POLICY["PolicyAwareRecipientFilter<br/>━━━━━━━━━━━━━━━━<br/>• filter(users, ability, resource)<br/>  Ej: can('view', \$project)"]
        PERSIST["Persistencia en BD<br/>━━━━━━━━━━━━━━━━<br/>• Bulk INSERT (chunks 50)<br/>• status: 'pending'<br/>• type: project_created, task.assigned, ..."]
        JOB["SendPushNotificationJob<br/>━━━━━━━━━━━━━━━━<br/>• Cola: 'notifications' (Horizon/Redis)<br/>• Envía push FCM por cada token<br/>• Registra FCM token errors"]
    end

    %% ── Frontend ──
    subgraph FRONTEND["🖥️ Frontend: Vue 3 + Pinia"]
        FCM_INIT["Firebase Init<br/>━━━━━━━━━━━━━━━━<br/>• requestPermission()<br/>• getToken() → saveTokenToBackend()<br/>• onMessage() → evento fcm:foreground"]
        STORE["useNotificationStore<br/>━━━━━━━━━━━━━━━━<br/>• fetchNotifications(page)<br/>• addNotificationFromFcm(notif)<br/>• markAsRead(id)<br/>• markAllAsRead()<br/>• refreshUnreadCount()<br/>• toggleTray() / closeTray()"]
        BELL["🔔 NotificationBell<br/>━━━━━━━━━━━━━━━━<br/>• Badge numérico<br/>• Dot rojo si hay no leídas<br/>• Click → toggleTray"]
        TRAY["📥 NotificationTray<br/>━━━━━━━━━━━━━━━━<br/>• Últimas 5 notificaciones<br/>• Marcar todas leídas<br/>• Link a página completa"]
        PAGE["📄 Página /notifications<br/>━━━━━━━━━━━━━━━━<br/>• Lista paginada<br/>• Marcar individual/todas<br/>• Iconos por tipo"]
    end

    %% ── Flujo: Roles → Eventos → Resolución → Envío ──
    SA --> EVT_PROJECT_CREATED
    PM --> EVT_PROJECT_CREATED
    PM --> EVT_TASK_ASSIGNED
    PM --> EVT_MEMBER_ADDED
    DEV --> EVT_TASK_COMPLETED
    DEV --> EVT_BLOCKER_REPORTED
    CL --> EVT_TICKET_OPENED
    PM --> EVT_TICKET_ASSIGNED

    EVT_PROJECT_CREATED --> RESOLVER
    EVT_TASK_ASSIGNED --> RESOLVER
    EVT_TASK_COMPLETED --> RESOLVER
    EVT_TICKET_OPENED --> RESOLVER
    EVT_TICKET_ASSIGNED --> RESOLVER
    EVT_BLOCKER_REPORTED --> RESOLVER
    EVT_MEMBER_ADDED --> RESOLVER

    RESOLVER -->|"Collection<User>"| POLICY
    POLICY -->|"Filtrados"| PERSIST
    PERSIST -->|"Notification[] status=pending"| JOB

    %% ── Push → Frontend ──
    JOB -->|"FCM Push (firebase-admin)"| FCM_INIT
    FCM_INIT -->|"fcm:foreground-notification"| STORE
    STORE --> BELL
    STORE --> TRAY
    STORE --> PAGE

    %% ── Usuario leyendo ──
    BELL -->|"click: toggleTray"| TRAY
    TRAY -->|"click: markAllAsRead"| STORE
    PAGE -->|"click: markAsRead(id)"| STORE
    STORE -->|"POST /notifications/mark-read"| BACKEND
```

---

## 📋 Matriz Roles × Acciones de Notificación

| Acción | Super Admin | Project Manager | Developer | Client |
|---|---|---|---|---|
| **Recibir push FCM** | ✅ | ✅ | ✅ | ✅ |
| **Ver campanita con badge** | ✅ | ✅ | ✅ | ✅ |
| **Abrir bandeja (tray)** | ✅ | ✅ | ✅ | ✅ |
| **Marcar una como leída** | ✅ | ✅ | ✅ | ✅ |
| **Marcar todas como leídas** | ✅ | ✅ | ✅ | ✅ |
| **Ver historial paginado** | ✅ | ✅ | ✅ | ✅ |
| **Programar notificación** | ✅ | ✅ | ❌ | ❌ |
| **Gestionar tokens FCM** | ✅ | ✅ (propios) | ✅ (propios) | ✅ (propios) |

---

## 🔄 Flujo de Vida de una Notificación

```mermaid
sequenceDiagram
    actor PM as 📋 Project Manager
    participant Controller as Laravel Controller
    participant Service as AbstractNotificationService
    participant Resolver as NotificationRecipientResolver
    participant Filter as PolicyAwareRecipientFilter
    participant DB as MySQL (notifications)
    participant Queue as Redis Queue
    participant Job as SendPushNotificationJob
    participant FCM as Firebase Cloud Messaging
    participant Frontend as Vue 3 (Pinia Store)
    participant Dev as 💻 Developer

    PM->>Controller: Create task → asigna a Dev
    Controller->>Service: notifyTaskAssigned(task)
    Service->>Resolver: resolveTaskAssignees(task)
    Resolver->>Service: Collection([Dev])
    Service->>Filter: filter(users, 'view', task)
    Filter->>Service: Collection([Dev])
    Service->>DB: INSERT notification (status=pending)
    Service->>Queue: dispatch(SendPushNotificationJob)
    Queue->>Job: Process job
    Job->>DB: UPDATE notifications SET status=sent, sent_at=now
    Job->>FCM: sendMulticast(tokens, payload)
    FCM-->>Frontend: Push notification (foreground)
    Frontend->>Frontend: onMessage → fcm:foreground-notification
    Frontend->>Frontend: store.addNotificationFromFcm(notif)
    Frontend-->>Dev: 🔔 Badge +1, tray muestra la notificación
    Dev->>Frontend: Click "Marcar como leída"
    Frontend->>Controller: POST /notifications/mark-read {notification_id}
    Controller->>DB: UPDATE notifications SET read_at=now
    Frontend->>Frontend: store.markAsRead(id) → badge -1
```

---

## 🎯 Escenarios de Resolución de Destinatarios

```mermaid
graph LR
    subgraph "Caso 1: Proyecto creado"
        A1["PM crea proyecto"] --> R1["resolveByRole('Super Admin')"]
        R1 --> F1["Todos los Super Admin reciben push"]
    end

    subgraph "Caso 2: Tarea asignada"
        A2["PM asigna tarea a Dev"] --> R2["resolveTaskAssignees(task)"]
        R2 --> F2["Dev asignado recibe push"]
    end

    subgraph "Caso 3: Ticket abierto"
        A3["Client abre ticket"] --> R3["resolveByRoles(['Super Admin','Project Manager'])<br/>+ resolveProjectMembers(project)"]
        R3 --> F3["Super Admin + PM + miembros del proyecto reciben push"]
    end

    subgraph "Caso 4: Bloqueador reportado"
        A4["Dev reporta bloqueador"] --> R4["resolveProjectMembers(project)"]
        R4 --> F4["Owner + miembros del proyecto reciben push"]
    end

    subgraph "Caso 5: Miembro agregado"
        A5["PM agrega miembro"] --> R5["resolveUser(member)"]
        R5 --> F5["Miembro agregado recibe push de bienvenida"]
    end
```

---

## 📊 Estados de una Notificación

| Estado | Significado | Transiciones |
|---|---|---|
| `pending` | Persistida en BD, aún no enviada | → `sent` (job exitoso) |
| `sent` | FCM confirmó entrega | → `delivered` (dispositivo recibió) |
| `failed` | FCM rechazó o token inválido | — |
| `read` | Usuario marcó `read_at` | — |

---

## 🔐 Registro de Token FCM

```mermaid
sequenceDiagram
    actor User as 👤 Usuario (cualquier rol)
    participant Browser as Navegador
    participant Firebase as Firebase SDK
    participant Backend as Laravel API
    participant DB as MySQL (fcm_tokens)

    User->>Browser: Inicia sesión en la app
    Browser->>Firebase: requestPermission()
    Firebase-->>Browser: Token FCM: "abc123..."
    Browser->>Backend: POST /fcm/register-token { token }
    Backend->>DB: INSERT O UPDATE (user_id, token)
    Backend-->>Browser: { status: true }

    Note over User,DB: En logout o cambio de sesión:
    Browser->>Backend: POST /fcm/remove-token { token }
    Backend->>DB: DELETE WHERE user_id AND token
```

---

## 🧹 Limpieza de Tokens Obsoletos

El comando `php artisan notifications:clean-stale-fcm-tokens` (programado en `scheduler`) elimina tokens que FCM reportó como inválidos después de múltiples intentos fallidos (`SendPushNotificationJob` registra los errores en `fcm_token_errors`).

```mermaid
graph TD
    JOB["SendPushNotificationJob"] -->|"FCM error: NOT_FOUND"| ERR["Registra en fcm_token_errors"]
    ERR --> CLEAN["CleanStaleFcmTokens<br/>(scheduler diario)"]
    CLEAN -->|"DELETE tokens con >3 errores"| DB["fcm_tokens"]

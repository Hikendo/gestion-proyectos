# 🗂️ Gestión de Proyectos

Sistema completo de gestión de proyectos con backend API REST en **Laravel 12** y frontend SPA en **Vue 3 + Vuetify 3**. Incluye autenticación, control de acceso por roles/permisos, notificaciones push vía Firebase Cloud Messaging (FCM), gestión documental polimórfica con aislamiento por proyecto, y un ecosistema de módulos interconectados (tareas, tickets, bloqueadores, riesgos, fases, hitos, entregables, etc.).

---

## 🎯 ¿De qué trata el proyecto?

**Gestión de Proyectos** es una plataforma web para administrar el ciclo de vida completo de proyectos. Permite:

- Crear proyectos con fases, hitos, entregables, objetivos y planes
- Gestionar **tareas** con prioridades, estados, asignaciones, comentarios y registro de tiempo
- Gestionar **tickets** (incidencias/soporte) con flujo de estados y prioridades
- Registrar **bloqueadores** que impiden el avance y resolverlos
- Identificar y mitigar **riesgos**
- Administrar **miembros del proyecto** con roles específicos
- Subir, descargar, reemplazar y eliminar **documentos** asociados a cada entidad (expediente digital por proyecto/tarea/ticket/bloqueador)
- Recibir **notificaciones push** en el navegador vía Firebase Cloud Messaging, con bandeja en tiempo real y campanita con contador de no leídas
- Visualizar dashboards con métricas y gráficas (kanban, gantt, reportes)

El sistema soporta **roles diferenciados**: Super Admin, Project Manager, Developer y Client, cada uno con permisos granulares vía Spatie Laravel Permission.

---

## 🛠️ Requisitos

| Componente | Tecnología / Versión |
|---|---|
| Contenedores | Docker + Docker Compose |
| Backend | PHP 8.4+, Laravel 12, Composer |
| Base de datos | MySQL 8 (producción), SQLite (testing) |
| Frontend | Node.js 20+, Vue 3, Vuetify 3, Pinia, Vite |
| Notificaciones | Firebase Cloud Messaging (FCM) |
| Cache / Colas | Redis (opcional, para Horizon y jobs de notificaciones) |

---

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Hikendo/gestion-proyectos.git
cd gestion-proyectos
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Editar `.env` con las credenciales deseadas:

```env
APP_NAME="Gestión de Proyectos"
APP_URL=http://localhost:8085

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=gestion_db
DB_USERNAME=gestion_user
DB_PASSWORD=gestion_password_local

# Credenciales del super-admin inicial
ADMIN_NAME="Administrador"
ADMIN_EMAIL=admin@admin.com
ADMIN_PASSWORD=Admin1234!
```

### 3. Configurar Firebase (FCM)

Para habilitar las notificaciones push, crear un proyecto en [Firebase Console](https://console.firebase.google.com/) y copiar las credenciales de la cuenta de servicio. Las variables se configuran directamente en el `.env`:

```env
FIREBASE_PROJECT_ID=tu-project-id
FIREBASE_PRIVATE_KEY_ID=tu-private-key-id
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
FIREBASE_CLIENT_EMAIL=firebase-adminsdk@tu-project.iam.gserviceaccount.com
FIREBASE_CLIENT_ID=tu-client-id
```

> ⚠️ La `FIREBASE_PRIVATE_KEY` debe ir en una sola línea con `\n` reemplazando los saltos de línea reales.

El frontend ya está configurado con el proyecto `gestionfcm-57769`. Las claves VAPID se inyectan vía `VITE_FIREBASE_VAPID_KEY`.

### 4. Levantar los contenedores

```bash
docker compose up -d --build
```

**Servicios expuestos:**

| Servicio | URL |
|---|---|
| Backend API | `http://localhost:8085` |
| Frontend Vue | `http://localhost:5173` |
| MySQL | `localhost:3319` |

El entrypoint del contenedor backend ejecuta automáticamente:

- `composer install`
- Generación de `APP_KEY`
- Migraciones pendientes
- Seeders de roles y permisos
- Creación del super-admin inicial

### 5. Frontend en desarrollo local

```bash
cd frontend
npm install
npm run dev
```

La variable `VITE_API_BASE_URL` en el frontend apunta por defecto a `/api/v1`. En Docker, un proxy Nginx enruta las peticiones al contenedor backend, evitando problemas de CORS.

---

## 👤 Usuarios por defecto (Seeders)

| Rol | Email | Contraseña |
|---|---|---|
| Super Admin | <superadmin@test.com> | password |
| Project Manager | <pm@test.com> | password |
| Developer | <developer@test.com> | password |
| Client | <client@test.com> | password |

---

## 🔑 Autenticación

Todas las rutas de API (excepto login/register) requieren el header:

```
Authorization: Bearer {token}
```

**Login:**

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "superadmin@test.com",
    "password": "password"
}
```

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Vue 3 + Vite)                  │
│  ┌──────────┐ ┌───────────┐ ┌────────────┐ ┌────────────┐ │
│  │  Views   │ │Components │ │ Composables│ │   Store    │ │
│  │ (Pages)  │ │  (Forms,  │ │(useTasks,  │ │  (Pinia)   │ │
│  │          │ │  Layouts, │ │ useTickets,│ │            │ │
│  │          │ │  Common)  │ │ useAttach) │ │            │ │
│  └──────────┘ └───────────┘ └────────────┘ └────────────┘ │
│                        │ HTTP (Axios / Fetch)               │
│                        │ Token Bearer automático             │
└────────────────────────┼────────────────────────────────────┘
                         │
┌────────────────────────┼────────────────────────────────────┐
│              Nginx Proxy (puerto 8085)                       │
│         /api/v1/* → backend:9000 (PHP-FPM)                  │
└────────────────────────┼────────────────────────────────────┘
                         │
┌────────────────────────┼────────────────────────────────────┐
│               Backend (Laravel 12)                           │
│  ┌──────────┐ ┌───────────┐ ┌────────────┐ ┌────────────┐ │
│  │ Routes   │ │Controllers│ │  Services  │ │   Models   │ │
│  │(api/v1/) │ │   (Api)   │ │(Attachment,│ │(Project,   │ │
│  │          │ │           │ │ Task,Ticket│ │ Task,Ticket│ │
│  │          │ │           │ │ Project)   │ │ Blocker…)  │ │
│  └──────────┘ └───────────┘ └────────────┘ └────────────┘ │
│  ┌──────────┐ ┌───────────┐ ┌────────────┐ ┌────────────┐ │
│  │ Policies │ │  Requests │ │  Traits    │ │    Jobs    │ │
│  │(Gate)    │ │(Validation│ │(HasAttach- │ │(SendPush   │ │
│  │          │ │  Rules)   │ │ ments)     │ │NotifJob)   │ │
│  └──────────┘ └───────────┘ └────────────┘ └────────────┘ │
│                                                             │
│  Storage: projects/{project_uuid}/{attachment_uuid}.ext     │
│  Cache/Queue: Redis (Horizon)                               │
└─────────────────────────────────────────────────────────────┘
```

**Capas del frontend:**

- **Pages**: Vistas de listado, creación, edición y visualización de cada recurso
- **Components**: Formularios reutilizables (`TaskForm`, `TicketForm`, `PhaseForm`) y componentes comunes (`DocumentManager`, `NotificationBell`, `NotificationTray`)
- **Composables**: Lógica reutilizable con estado reactivo (`useAttachments`, `useProjectPhasesService`, `useNotificationStore`)
- **Store**: Estado global con Pinia (`useAuthStore`, `useNotificationStore`, `useThemeStore`)

**Capas del backend:**

- **Controllers**: Reciben peticiones HTTP, validan y delegan en servicios
- **Services**: Lógica de negocio pura (`AttachmentService`, `TaskService`, `TicketService`, `ProjectService`)
- **Models**: Eloquent con relaciones polimórficas (Project, Task, Ticket, Blocker usan `HasAttachments`)
- **Policies**: Autorización granular con Laravel Gates y Spatie Permissions
- **Jobs**: `SendPushNotificationJob` para envío asíncrono de notificaciones FCM vía Redis/Horizon

---

## 📡 Endpoints de la API

| Recurso | Base URL |
|---|---|
| Auth | `/api/v1/auth` |
| Users | `/api/v1/users` |
| Dashboard | `/api/v1/dashboard` |
| Roles | `/api/v1/roles` |
| Projects | `/api/v1/projects` |
| Project Members | `/api/v1/projects/{id}/members` |
| Project Attachments | `/api/v1/projects/{id}/attachments` |
| Project Metrics | `/api/v1/projects/{id}/metrics` |
| Phases | `/api/v1/projects/{id}/phases` |
| Objectives | `/api/v1/projects/{id}/objectives` |
| Plans | `/api/v1/projects/{id}/plan` |
| Milestones | `/api/v1/projects/{id}/milestones` |
| Deliverables | `/api/v1/projects/{id}/deliverables` |
| Risks | `/api/v1/projects/{id}/risks` |
| Tasks | `/api/v1/projects/{id}/tasks` |
| Task Attachments | `/api/v1/tasks/{id}/attachments` |
| Task Comments | `/api/v1/tasks/{id}/comments` |
| Task Time Logs | `/api/v1/tasks/{id}/time-logs` |
| Tickets | `/api/v1/projects/{id}/tickets` |
| Ticket Attachments | `/api/v1/projects/{pid}/tickets/{id}/attachments` |
| Blockers | `/api/v1/projects/{id}/blockers` |
| Blocker Attachments | `/api/v1/projects/{pid}/blockers/{id}/attachments` |
| Notifications | `/api/v1/notifications` |
| FCM Tokens | `/api/v1/fcm/register-token` |
| Attachments (gestión) | `/api/v1/attachments/{uuid}` |

📦 La colección de Postman está en `testPostman.json` (raíz del proyecto).

---

## 📁 Estructura del proyecto

```
gestion-proyectos/
├── docker-compose.yml
├── nginx.conf                          # Proxy reverso Nginx
├── testPostman.json                    # Colección Postman actualizada
├── backend/
│   ├── app/
│   │   ├── Enums/                      # TaskStatus, TicketStatus, etc.
│   │   ├── Exceptions/                 # DomainExceptions
│   │   ├── Http/
│   │   │   ├── Controllers/Api/        # Controladores REST
│   │   │   └── Requests/               # Form Requests con validación
│   │   ├── Jobs/                       # SendPushNotificationJob
│   │   ├── Models/                     # Eloquent: Project, Task, Ticket, Blocker, Attachment, Notification…
│   │   ├── Policies/                   # Gates por modelo
│   │   ├── Services/                   # AttachmentService, TaskService, TicketService…
│   │   │   └── Notifications/          # FirebaseNotificationService, NotificationRecipientResolver
│   │   └── Traits/                     # HasAttachments (polimórfico)
│   ├── config/                         # queue.php, firebase.php, permission.php…
│   ├── database/
│   │   └── seeders/                    # RolesAndPermissionsSeeder
│   ├── routes/api/                     # Archivos de rutas por recurso
│   └── tests/                          # Feature y Unit tests
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   │   ├── common/                 # DocumentManager, NotificationBell, NotificationTray, AttachmentList
│   │   │   ├── project-phases/         # PhaseForm (reutilizable)
│   │   │   ├── tasks/                  # TaskForm
│   │   │   ├── tickets/                # TicketForm
│   │   │   └── blockers/               # BlockerForm
│   │   ├── composables/                # useAttachments, useTasks, useTickets, useNotificationStore…
│   │   ├── interfaces/                 # TypeScript: ProjectI, TaskI, TicketI, NotificationI, AttachmentI…
│   │   ├── layouts/                    # MainLayout (sidebar + appbar + bandeja notificaciones)
│   │   ├── pages/                      # Vistas por recurso (index, new, [id], view/[id])
│   │   ├── router/                     # Vue Router con guards de auth y restauración de sesión
│   │   ├── services/                   # HTTP: auth, projects, tasks, tickets, notifications, firebase…
│   │   └── store/                      # Pinia: useAuthStore, useNotificationStore, useThemeStore
│   └── Dockerfile
└── README.md
```

---

## 🔔 Sistema de Notificaciones

- **Backend**: `SendPushNotificationJob` encola notificaciones vía Redis/Horizon. Se persisten en la tabla `notifications` con estado `read_at`.
- **Frontend**: `useNotificationStore` gestiona el estado. `NotificationBell` muestra un badge con el contador. `NotificationTray` muestra un dropdown con las últimas 5 notificaciones. La página `/notifications` permite ver el historial completo con paginación.
- **FCM**: `listenForegroundNotifications()` en `firebase.ts` captura notificaciones en primer plano y las emite como eventos del DOM, que `App.vue` recoge e inyecta en el store.
- **Endpoints**: `GET /notifications`, `POST /notifications/mark-read`, `POST /notifications/mark-all-read`.

---

## 📎 Gestión Documental (Attachments)

- **Almacenamiento**: Archivos aislados por proyecto en `storage/app/projects/{project_uuid}/{attachment_uuid}.ext`
- **Modelo polimórfico**: `Project`, `Task`, `Ticket`, `Blocker` usan el trait `HasAttachments`
- **Frontend**: `DocumentManager.vue` — componente drag & drop reutilizable con subida, descarga, reemplazo y eliminación. Integrado en:
  - Detalle de proyecto (`ProjectOverviewTab`)
  - Edición de tarea (`tasks/[id].vue`)
  - Edición de ticket (`tickets/[id].vue`)
  - Edición de bloqueador (`blockers/[id].vue`)
  - Vistas de solo lectura (`view/[id].vue`)
- **Endpoints**:
  - `POST /projects/{id}/attachments` — subida a proyecto
  - `POST /tasks/{id}/attachments` — subida a tarea
  - `POST /projects/{pid}/tickets/{id}/attachments` — subida a ticket
  - `POST /projects/{pid}/blockers/{id}/attachments` — subida a bloqueador
  - `DELETE /attachments/{uuid}` — eliminar
  - `POST /attachments/{uuid}/replace` — reemplazar archivo

---

## 🧪 Tests

```bash
# Todos los tests
docker exec -it gestion_proyectos_backend_app php artisan test

# Un grupo específico
docker exec -it gestion_proyectos_backend_app php artisan test tests/Feature/Ticket/
docker exec -it gestion_proyectos_backend_app php artisan test tests/Unit/Notifications/
```

---

## 📄 Licencia

MIT

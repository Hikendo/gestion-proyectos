# Requerimientos para App Móvil — Gestión de Proyectos (Flutter)

> **Propósito:** Especificación de endpoints, payloads y módulos requeridos para la versión "capada" de la app móvil en Flutter, enfocada en clientes y soporte para comunicación, evidencia y reporte de estado.

**Fecha:** 2026-06-19  
**Versión:** 1.0  

---

## 1. Alcance de la App Móvil

La app móvil es una versión reducida del sistema web. Está dirigida a **todos los roles** del sistema, cada uno con capacidades específicas según su función:

| Rol | Descripción en la app |
|-----|----------------------|
| **Cliente (`client`)** | Consulta estado de sus proyectos, tareas asignadas y tickets. Crea tickets, envía mensajes al chat, sube evidencia. |
| **Soporte (`support`)** | Atiende tickets, reporta bloqueadores, actualiza estado de tareas y tickets, sube evidencia. |
| **Developer (`developer`)** | Ve sus tareas asignadas, actualiza su estado (mover a "In Progress", "Done"), registra horas, reporta bloqueadores. |
| **QA (`qa`)** | Ve sus tareas asignadas, actualiza su estado, reporta bugs vía tickets, verifica tareas completadas. |
| **Project Manager (`pm`)** | Vista general de todas las tareas y tickets del proyecto. Actualiza estados, asigna tareas/tickets, revisa bloqueadores, monitorea progreso. |
| **Super Admin (`super-admin`)** | Acceso completo a todos los módulos de la app (mismos que el PM + admin de usuarios). |

### 1.1 Matriz de permisos por rol

| Capacidad | Cliente | Soporte | Developer | QA | PM | Super Admin |
|-----------|---------|---------|-----------|-----|-----|-------------|
| Ver proyectos (solo los suyos) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Ver estado/progreso del proyecto | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Ver tareas asignadas | ✅ | ✅ | ✅ | ✅ | — | — |
| Ver todas las tareas del proyecto | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ |
| Actualizar estado de tarea | ❌ | ✅ | ✅ (solo a In Progress/Done) | ✅ | ✅ | ✅ |
| Actualizar prioridad/descripción de tarea | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| Comentar en tareas | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Registrar horas en tareas | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Subir evidencia (adjuntos) a tareas | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Ver tickets propios | ✅ | ✅ | ✅ | ✅ | — | — |
| Ver todos los tickets del proyecto | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ |
| Crear tickets | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Actualizar estado de ticket | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ |
| Asignar ticket a otro miembro | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| Ver bloqueadores | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Crear bloqueadores | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Resolver bloqueadores | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ |
| Chat del equipo | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Chats privados | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Listar miembros del proyecto | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

> **Nota sobre `field_permissions`**: El backend envía en cada recurso (`show()`) un objeto `field_permissions` que indica qué campos puede modificar el rol del usuario autenticado. La app Flutter debe usar esto para habilitar/deshabilitar campos en formularios.

### 1.2 Transiciones de estado

**Tareas (`TaskStatus`):**

```
To Do → In Progress → In Review → Done
           ↳ On Hold → In Progress
```

- Developer: puede mover de `To Do` → `In Progress`, `In Progress` → `Done`
- QA: puede mover de `In Review` → `Done` (aprobar) o `In Review` → `In Progress` (rechazar)
- PM/Soporte: pueden ejecutar cualquier transición válida
- Cliente: no puede cambiar estados (solo lectura)

**Tickets (`TicketStatus`):**

```
Open → In Progress → Resolved → Closed
         ↳ Open (reabrir si no está Closed)
```

- Soporte: puede mover `Open` → `In Progress`, `In Progress` → `Resolved`
- PM: puede ejecutar cualquier transición, incluyendo `Resolved` → `Closed`
- Developer/QA/Cliente: no pueden cambiar estados de tickets

### 1.3 Módulos incluidos

| Módulo | Cliente | Soporte | Developer | QA | PM | Super Admin |
|--------|---------|---------|-----------|-----|-----|-------------|
| Autenticación (login/logout/perfil) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Dashboard (resumen del usuario) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Proyectos (listar, ver detalle, estado) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tareas (ver, comentar, adjuntos) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tareas (actualizar estado) | ❌ | ✅ | ✅ (suyas) | ✅ (suyas) | ✅ | ✅ |
| Tareas (editar campos, asignar) | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| Tickets (listar, crear, ver, comentar) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tickets (actualizar estado) | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ |
| Bloqueadores (listar, crear, ver) | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Chat del equipo | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Chats privados | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Notificaciones (listar, marcar leídas) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Adjuntos (subir, descargar) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Miembros del proyecto (listar) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### 1.4 Módulos NO incluidos

- Objetivos, Fases, Planes, Hitos, Entregables, Riesgos, Métricas, Reportes
- Administración de usuarios/roles (excepto Super Admin que sí puede)
- Dashboard Admin
- Kanban, Gantt
- Creación/edición de proyectos
- Gestión de miembros (agregar/quitar)

---

## 2. Autenticación

Todos los endpoints requieren el header `Authorization: Bearer {token}`.

### 2.1 Login

```
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "client@test.com",
  "password": "password"
}
```

**Respuesta (200):**

```json
{
  "status": true,
  "message": "Inicio de sesión exitoso.",
  "items": {
    "user": {
      "id": 6,
      "name": "Cliente Demo",
      "email": "client@test.com",
      "roles": ["client"]
    },
    "token": "1|abc123..."
  }
}
```

### 2.2 Logout

```
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "message": "Sesión cerrada."
}
```

### 2.3 Perfil (me)

```
GET /api/v1/auth/me
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "id": 6,
    "name": "Cliente Demo",
    "email": "client@test.com",
    "roles": ["client"]
  }
}
```

### 2.4 Refresh de permisos (FCM)

```
POST /api/v1/auth/refresh-permissions
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "permissions": ["project.view", "task.view", "ticket.view", "ticket.create", ...]
  }
}
```

---

## 3. Dashboard

### 3.1 Dashboard del usuario autenticado

```
GET /api/v1/dashboard
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "summary": {
      "total_projects": 3,
      "my_pending_tasks": 5,
      "open_tickets": 2,
      "active_blockers_count": 1
    },
    "projects": [
      {
        "id": 1,
        "name": "Proyecto Alpha",
        "code": "PA-001",
        "status": "active",
        "progress": 45,
        "end_date": "2026-12-31"
      }
    ],
    "my_tasks": [
      {
        "id": 10,
        "title": "Revisar diseño",
        "status": "in_progress",
        "priority": "high",
        "due_date": "2026-06-30",
        "project_id": 1,
        "project": { "id": 1, "name": "Proyecto Alpha", "code": "PA-001" }
      }
    ],
    "open_tickets": [
      {
        "id": 5,
        "subject": "Bug en login",
        "priority": "high",
        "project_id": 1,
        "project": { "id": 1, "name": "Proyecto Alpha", "code": "PA-001" }
      }
    ]
  }
}
```

---

## 4. Proyectos

### 4.1 Listar proyectos del usuario

```
GET /api/v1/projects
Authorization: Bearer {token}
Query params: ?query=&status=active
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "data": [
      {
        "id": 1,
        "name": "Proyecto Alpha",
        "code": "PA-001",
        "description": "Descripción...",
        "status": "active",
        "progress": 45,
        "start_date": "2026-01-01",
        "end_date": "2026-12-31",
        "owner": { "id": 2, "name": "PM Demo", "email": "pm@test.com" }
      }
    ]
  }
}
```

### 4.2 Ver detalle de proyecto

```
GET /api/v1/projects/{projectId}
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "id": 1,
    "name": "Proyecto Alpha",
    "code": "PA-001",
    "description": "Descripción del proyecto",
    "status": "active",
    "start_date": "2026-01-01",
    "end_date": "2026-12-31",
    "budget": "150000.00",
    "progress": 45,
    "owner": { "id": 2, "name": "PM Demo", "email": "pm@test.com" },
    "attachments": [
      {
        "id": 1,
        "uuid": "abc-123",
        "original_name": "contrato.pdf",
        "disk_path": "projects/abc-123/contrato.pdf",
        "mime_type": "application/pdf",
        "size": 102400
      }
    ]
  }
}
```

### 4.3 Permisos del usuario en el proyecto

```
GET /api/v1/projects/{projectId}/permissions
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "permissions": ["project.view", "task.view", "ticket.view", "ticket.create"]
  }
}
```

---

## 5. Tareas

### 5.1 Listar tareas del proyecto

```
GET /api/v1/projects/{projectId}/tasks
Authorization: Bearer {token}
Query params: ?status=in_progress&priority=high&page=1
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "data": [
      {
        "id": 10,
        "title": "Revisar diseño",
        "description": "Revisar el diseño de la pantalla de login",
        "status": "in_progress",
        "priority": "high",
        "due_date": "2026-06-30",
        "progress": 60,
        "project_id": 1,
        "assignee": { "id": 3, "name": "Dev Demo", "email": "dev@test.com" },
        "phase": { "id": 2, "name": "Desarrollo" }
      }
    ],
    "meta": { "current_page": 1, "last_page": 3, "total": 25 }
  }
}
```

> **Nota:** Clientes solo ven tareas donde están asignados (backend filtra por `role`).

### 5.2 Ver detalle de tarea

```
GET /api/v1/projects/{projectId}/tasks/{taskId}
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "id": 10,
    "title": "Revisar diseño",
    "description": "Revisar el diseño de la pantalla de login",
    "status": "in_progress",
    "priority": "high",
    "due_date": "2026-06-30",
    "progress": 60,
    "worked_hours": 480,
    "project_id": 1,
    "assigned_to": 3,
    "assignee": { "id": 3, "name": "Dev Demo", "email": "dev@test.com" },
    "phase": { "id": 2, "name": "Desarrollo" },
    "comments": [
      {
        "id": 1,
        "body": "Ya revisé, falta ajustar colores",
        "user": { "id": 6, "name": "Cliente Demo" },
        "created_at": "2026-06-18T14:30:00Z"
      }
    ],
    "attachments": [
      {
        "id": 5,
        "uuid": "def-456",
        "original_name": "screenshot.png",
        "disk_path": "projects/abc-123/screenshot.png",
        "mime_type": "image/png",
        "size": 51200
      }
    ],
    "time_logs": [
      {
        "id": 3,
        "user_id": 3,
        "minutes": 120,
        "description": "Ajustes de diseño",
        "created_at": "2026-06-18T10:00:00Z"
      }
    ],
    "field_permissions": {
      "title": true,
      "description": true,
      "status": false,
      "priority": false,
      "assigned_to": false,
      "due_date": false,
      "phase_id": false,
      "progress": false
    }
  }
}
```

> **Nota:** El backend envía `field_permissions` indicando qué campos puede modificar cada rol. Cliente tiene la mayoría de campos en `false` (solo lectura).

### 5.3 Comentar en tarea

```
POST /api/v1/tasks/{taskId}/comments
Authorization: Bearer {token}
Content-Type: application/json

{
  "body": "Comentario del cliente sobre la tarea"
}
```

**Respuesta (201):**

```json
{
  "status": true,
  "message": "Comentario creado.",
  "items": {
    "id": 8,
    "body": "Comentario del cliente sobre la tarea",
    "user": { "id": 6, "name": "Cliente Demo" },
    "created_at": "2026-06-19T02:00:00Z"
  }
}
```

### 5.4 Listar comentarios de tarea

```
GET /api/v1/tasks/{taskId}/comments
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": [
    {
      "id": 1,
      "body": "Ya revisé, falta ajustar colores",
      "user": { "id": 6, "name": "Cliente Demo" },
      "created_at": "2026-06-18T14:30:00Z"
    }
  ]
}
```

### 5.5 Adjuntos en tarea (subir)

```
POST /api/v1/attachments/upload-temp
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: (binary)
```

**Respuesta (201):**

```json
{
  "status": true,
  "message": "Archivo subido temporalmente.",
  "items": {
    "uuid": "ghi-789",
    "original_name": "evidencia.jpg",
    "mime_type": "image/jpeg",
    "size": 204800
  }
}
```

Luego claim del attachment a la tarea:

```
POST /api/v1/tasks/{taskId}/attachments/claim
Authorization: Bearer {token}
Content-Type: application/json

{
  "uuids": ["ghi-789"]
}
```

**Respuesta (200):**

```json
{
  "status": true,
  "message": "Adjuntos reclamados exitosamente.",
  "items": [
    {
      "id": 10,
      "uuid": "ghi-789",
      "original_name": "evidencia.jpg",
      "disk_path": "projects/abc-123/evidencia.jpg",
      "mime_type": "image/jpeg",
      "size": 204800
    }
  ]
}
```

### 5.6 Descargar adjunto

```
GET /api/v1/attachments/{uuid}/download
Authorization: Bearer {token}
```

**Respuesta (200):** Stream binario del archivo con headers `Content-Type` y `Content-Disposition`.

---

## 6. Tickets

### 6.1 Listar tickets del proyecto

```
GET /api/v1/projects/{projectId}/tickets
Authorization: Bearer {token}
Query params: ?status=open&priority=high&page=1
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "data": [
      {
        "id": 5,
        "subject": "Bug en login",
        "description": "El botón de login no responde en Safari móvil",
        "status": "open",
        "priority": "high",
        "project_id": 1,
        "created_by": 6,
        "assigned_to": 5,
        "creator": { "id": 6, "name": "Cliente Demo" },
        "assignee": { "id": 5, "name": "Soporte Demo" },
        "created_at": "2026-06-15T09:00:00Z"
      }
    ],
    "meta": { "current_page": 1, "last_page": 1, "total": 2 }
  }
}
```

> **Nota:** Clientes solo ven sus propios tickets (`created_by = user.id`).

### 6.2 Crear ticket

```
POST /api/v1/projects/{projectId}/tickets
Authorization: Bearer {token}
Content-Type: application/json

{
  "subject": "Error al cargar imágenes",
  "description": "Las imágenes no cargan en la sección de adjuntos. Probado en Chrome y Firefox.",
  "priority": "medium"
}
```

**Respuesta (201):**

```json
{
  "status": true,
  "message": "Ticket creado exitosamente.",
  "items": {
    "id": 6,
    "subject": "Error al cargar imágenes",
    "description": "Las imágenes no cargan...",
    "status": "open",
    "priority": "medium",
    "project_id": 1,
    "created_by": 6,
    "assigned_to": null,
    "creator": { "id": 6, "name": "Cliente Demo" },
    "assignee": null,
    "created_at": "2026-06-19T02:10:00Z",
    "attachments": [],
    "field_permissions": {
      "subject": true,
      "description": true,
      "status": false,
      "priority": false,
      "assigned_to": false
    }
  }
}
```

### 6.3 Ver detalle de ticket

```
GET /api/v1/projects/{projectId}/tickets/{ticketId}
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "id": 5,
    "subject": "Bug en login",
    "description": "El botón de login no responde en Safari móvil",
    "status": "open",
    "priority": "high",
    "project_id": 1,
    "created_by": 6,
    "assigned_to": 5,
    "creator": { "id": 6, "name": "Cliente Demo" },
    "assignee": { "id": 5, "name": "Soporte Demo" },
    "created_at": "2026-06-15T09:00:00Z",
    "attachments": [],
    "field_permissions": {
      "subject": true,
      "description": true,
      "status": false,
      "priority": false,
      "assigned_to": false
    }
  }
}
```

### 6.4 Adjuntos en ticket (subir)

Mismo flujo que tareas (ver sección 5.5):

```
POST /api/v1/attachments/upload-temp
POST /api/v1/tickets/{ticketId}/attachments/claim
GET /api/v1/attachments/{uuid}/download
```

---

## 7. Bloqueadores (solo Soporte)

### 7.1 Listar bloqueadores del proyecto

```
GET /api/v1/projects/{projectId}/blockers
Authorization: Bearer {token}
Query params: ?include_resolved=false
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": [
    {
      "id": 3,
      "title": "Servidor de staging caído",
      "description": "No se puede probar la build más reciente",
      "severity": "critical",
      "status": "open",
      "project_id": 1,
      "task_id": 10,
      "reported_by": 5,
      "reporter": { "id": 5, "name": "Soporte Demo" },
      "task": { "id": 10, "title": "Revisar diseño" },
      "created_at": "2026-06-18T11:00:00Z"
    }
  ]
}
```

### 7.2 Crear bloqueador

```
POST /api/v1/projects/{projectId}/blockers
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Falta acceso a API de terceros",
  "description": "La API de pagos está caída desde las 10am",
  "severity": "high",
  "task_id": 12
}
```

**Respuesta (201):**

```json
{
  "status": true,
  "message": "Bloqueador creado exitosamente.",
  "items": {
    "id": 4,
    "title": "Falta acceso a API de terceros",
    "description": "La API de pagos está caída desde las 10am",
    "severity": "high",
    "status": "open",
    "project_id": 1,
    "task_id": 12,
    "reported_by": 5,
    "created_at": "2026-06-19T02:15:00Z"
  }
}
```

---

## 8. Chat del equipo

### 8.1 Historial de mensajes del chat grupal

```
GET /api/v1/projects/{projectId}/chat/messages
Authorization: Bearer {token}
Query params: ?page=1
```

**Respuesta (200):**

```json
{
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "user_id": 2,
      "user_name": "PM Demo",
      "content": "Bienvenidos al chat del equipo",
      "created_at": "2026-06-19T01:00:00.000000Z"
    },
    {
      "id": 2,
      "project_id": 1,
      "user_id": 5,
      "user_name": "Soporte Demo",
      "content": "Gracias, estaré atento a los tickets",
      "created_at": "2026-06-19T01:05:00.000000Z"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 50, "total": 2 }
}
```

> **Nota:** El backend retorna mensajes en orden `created_at DESC` (más nuevos primero). El frontend Flutter debe invertirlos (`reversed`) para mostrar los más viejos arriba y los más nuevos abajo.

### 8.2 Enviar mensaje al chat grupal

```
POST /api/v1/projects/{projectId}/chat/messages
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Hola equipo, ¿cómo va el diseño?"
}
```

**Respuesta (201):**

```json
{
  "message": "Mensaje enviado correctamente.",
  "data": {
    "id": 3,
    "project_id": 1,
    "user_id": 6,
    "user_name": "Cliente Demo",
    "content": "Hola equipo, ¿cómo va el diseño?",
    "created_at": "2026-06-19T02:20:00.000000Z"
  }
}
```

### 8.3 WebSocket — Canal del chat grupal

**Canal:** `private-project.{projectId}`  
**Evento:** `.message.sent`  
**Servidor:** `ws://{host}:8080` (Laravel Reverb)  
**Auth endpoint:** `POST /api/v1/broadcasting/auth`

**Payload del evento recibido:**

```json
{
  "id": 4,
  "project_id": 1,
  "user_id": 2,
  "user_name": "PM Demo",
  "content": "El diseño va al 80%, falta la revisión final",
  "created_at": "2026-06-19T02:25:00.000000Z"
}
```

**Configuración en Flutter:**

- Usar `pusher_channels_flutter` o `laravel_echo` package.
- Autenticar el canal privado contra `POST /api/v1/broadcasting/auth` con el header `Authorization: Bearer {token}`.
- Los parámetros de autenticación son: `socket_id` y `channel_name`.

---

## 9. Chats privados

### 9.1 Listar conversaciones del usuario en el proyecto

```
GET /api/v1/projects/{projectId}/conversations
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "other_user": { "id": 5, "name": "Soporte Demo", "email": "support@test.com" },
      "unread_count": 2,
      "updated_at": "2026-06-19T02:00:00.000000Z"
    }
  ]
}
```

### 9.2 Iniciar conversación con otro miembro

```
POST /api/v1/projects/{projectId}/conversations
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 5
}
```

**Respuesta (201):**

```json
{
  "message": "Conversación lista.",
  "data": {
    "id": 1,
    "project_id": 1,
    "other_user": { "id": 5 }
  }
}
```

> **Nota:** Si ya existe una conversación entre los dos usuarios, retorna la existente (no duplica).

### 9.3 Historial de mensajes privados

```
GET /api/v1/conversations/{conversationId}/messages
Authorization: Bearer {token}
Query params: ?page=1
```

**Respuesta (200):**

```json
{
  "data": [
    {
      "id": 1,
      "conversation_id": 1,
      "user_id": 6,
      "user_name": "Cliente Demo",
      "content": "Hola, ¿puedes revisar el ticket #5?",
      "created_at": "2026-06-19T01:30:00.000000Z"
    },
    {
      "id": 2,
      "conversation_id": 1,
      "user_id": 5,
      "user_name": "Soporte Demo",
      "content": "Claro, lo reviso ahora mismo",
      "created_at": "2026-06-19T01:35:00.000000Z"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 50, "total": 2 }
}
```

> **Nota:** Solo los 2 participantes pueden acceder a los mensajes. Otros miembros reciben 403.

### 9.4 Enviar mensaje privado

```
POST /api/v1/conversations/{conversationId}/messages
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Gracias, avísame cuando esté listo"
}
```

**Respuesta (201):**

```json
{
  "message": "Mensaje enviado correctamente.",
  "data": {
    "id": 3,
    "conversation_id": 1,
    "user_id": 6,
    "user_name": "Cliente Demo",
    "content": "Gracias, avísame cuando esté listo",
    "created_at": "2026-06-19T02:30:00.000000Z"
  }
}
```

### 9.5 Marcar mensajes como leídos

```
POST /api/v1/conversations/{conversationId}/read
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "message": "Mensajes marcados como leídos."
}
```

> **Llamar al:** abrir una conversación y al recibir un mensaje nuevo vía WebSocket.

### 9.6 WebSocket — Canal de conversación privada

**Canal:** `private-conversation.{conversationId}`  
**Evento:** `.direct-message.sent`  

**Payload del evento recibido:**

```json
{
  "id": 4,
  "conversation_id": 1,
  "user_id": 5,
  "user_name": "Soporte Demo",
  "content": "El ticket #5 ya está resuelto",
  "created_at": "2026-06-19T02:35:00.000000Z"
}
```

---

## 10. Miembros del proyecto

### 10.1 Listar miembros

```
GET /api/v1/projects/{projectId}/members
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": [
    {
      "id": 1,
      "project_id": 1,
      "user_id": 5,
      "role": "support",
      "user": { "id": 5, "name": "Soporte Demo", "email": "support@test.com" }
    },
    {
      "id": 2,
      "project_id": 1,
      "user_id": 6,
      "role": "client",
      "user": { "id": 6, "name": "Cliente Demo", "email": "client@test.com" }
    }
  ]
}
```

> **Uso:** Para mostrar la lista de miembros disponibles al iniciar un chat privado.

---

## 11. Notificaciones

### 11.1 Listar notificaciones del usuario

```
GET /api/v1/notifications
Authorization: Bearer {token}
Query params: ?page=1
```

**Respuesta (200):**

```json
{
  "status": true,
  "items": {
    "data": [
      {
        "id": 10,
        "type": "new_group_message",
        "title": "Nuevo mensaje en el chat del equipo",
        "body": "PM Demo: El diseño va al 80%, falta la revisión final",
        "data": {
          "project_id": 1,
          "message_id": 4,
          "sender_name": "PM Demo"
        },
        "read_at": null,
        "created_at": "2026-06-19T02:25:00Z"
      }
    ],
    "meta": { "current_page": 1, "last_page": 1, "total": 5 }
  }
}
```

### 11.2 Contador de notificaciones no leídas

```
GET /api/v1/notifications/unread-count
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "count": 5
}
```

### 11.3 Marcar notificación como leída

```
POST /api/v1/notifications/{notificationId}/read
Authorization: Bearer {token}
```

**Respuesta (200):**

```json
{
  "status": true,
  "message": "Notificación marcada como leída."
}
```

---

## 12. Notificaciones Push (FCM)

### 12.1 Registrar token FCM

```
POST /api/v1/fcm/register-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "token": "fcm-device-token-abc123...",
  "platform": "android",
  "browser": "Flutter",
  "device_name": "Pixel 7"
}
```

**Respuesta (200):**

```json
{
  "status": true,
  "message": "Token FCM registrado exitosamente."
}
```

### 12.2 Eliminar token FCM (logout)

```
POST /api/v1/fcm/remove-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "token": "fcm-device-token-abc123..."
}
```

**Respuesta (200):**

```json
{
  "status": true,
  "message": "Token FCM eliminado exitosamente."
}
```

### 12.3 Payload de notificación push recibida

Cuando el backend envía una notificación push vía FCM, el payload tiene esta estructura:

```json
{
  "notification": {
    "title": "Nuevo mensaje en el chat del equipo",
    "body": "PM Demo: El diseño va al 80%"
  },
  "data": {
    "type": "new_group_message",
    "project_id": "1",
    "message_id": "4",
    "sender_name": "PM Demo",
    "click_action": "/app/projects/1/chat"
  }
}
```

**Tipos de notificación:**

| `data.type` | Significado | Navegar a |
|-------------|-------------|-----------|
| `new_group_message` | Nuevo mensaje en chat grupal | Chat del equipo |
| `new_private_message` | Nuevo mensaje privado | Chat privado (conversación específica) |
| `task_assigned` | Tarea asignada | Detalle de tarea |
| `task_status_changed` | Cambio de estado de tarea | Detalle de tarea |
| `task_completed` | Tarea completada | Detalle de tarea |
| `comment_created` | Nuevo comentario en tarea | Detalle de tarea |
| `ticket_assigned` | Ticket asignado | Detalle de ticket |
| `ticket_closed` | Ticket cerrado | Detalle de ticket |
| `ticket_created` | Ticket creado | Detalle de ticket |
| `blocker_created` | Bloqueador reportado | Detalle de bloqueador |
| `blocker_resolved` | Bloqueador resuelto | Detalle de bloqueador |

---

## 13. Resumen de endpoints requeridos

| # | Método | Endpoint | Módulo | Rol |
|---|--------|----------|--------|-----|
| 1 | POST | `/auth/login` | Auth | Todos |
| 2 | POST | `/auth/logout` | Auth | Todos |
| 3 | GET | `/auth/me` | Auth | Todos |
| 4 | POST | `/auth/refresh-permissions` | Auth | Todos |
| 5 | GET | `/dashboard` | Dashboard | Todos |
| 6 | GET | `/projects` | Proyectos | Todos |
| 7 | GET | `/projects/{id}` | Proyectos | Todos |
| 8 | GET | `/projects/{id}/permissions` | Proyectos | Todos |
| 9 | GET | `/projects/{id}/tasks` | Tareas | Todos |
| 10 | GET | `/projects/{id}/tasks/{id}` | Tareas | Todos |
| 11 | POST | `/tasks/{id}/comments` | Tareas | Todos |
| 12 | GET | `/tasks/{id}/comments` | Tareas | Todos |
| 13 | POST | `/attachments/upload-temp` | Adjuntos | Todos |
| 14 | POST | `/tasks/{id}/attachments/claim` | Adjuntos | Todos |
| 15 | POST | `/tickets/{id}/attachments/claim` | Adjuntos | Todos |
| 16 | GET | `/attachments/{uuid}/download` | Adjuntos | Todos |
| 17 | GET | `/projects/{id}/tickets` | Tickets | Todos |
| 18 | POST | `/projects/{id}/tickets` | Tickets | Todos |
| 19 | GET | `/projects/{id}/tickets/{id}` | Tickets | Todos |
| 20 | GET | `/projects/{id}/blockers` | Bloqueadores | Soporte |
| 21 | POST | `/projects/{id}/blockers` | Bloqueadores | Soporte |
| 22 | GET | `/projects/{id}/chat/messages` | Chat grupal | Todos |
| 23 | POST | `/projects/{id}/chat/messages` | Chat grupal | Todos |
| 24 | GET | `/projects/{id}/conversations` | Chat privado | Todos |
| 25 | POST | `/projects/{id}/conversations` | Chat privado | Todos |
| 26 | GET | `/conversations/{id}/messages` | Chat privado | Todos |
| 27 | POST | `/conversations/{id}/messages` | Chat privado | Todos |
| 28 | POST | `/conversations/{id}/read` | Chat privado | Todos |
| 29 | POST | `/broadcasting/auth` | WebSocket | Todos |
| 30 | GET | `/projects/{id}/members` | Miembros | Todos |
| 31 | GET | `/notifications` | Notificaciones | Todos |
| 32 | GET | `/notifications/unread-count` | Notificaciones | Todos |
| 33 | POST | `/notifications/{id}/read` | Notificaciones | Todos |
| 34 | POST | `/fcm/register-token` | FCM | Todos |
| 35 | POST | `/fcm/remove-token` | FCM | Todos |

---

## 14. Configuración de WebSocket en Flutter

**Servidor Reverb:** `ws://{host}:8080`  
**App Key:** `gestion_proyectos_reverb_key`  
**Auth endpoint:** `POST /api/v1/broadcasting/auth` (con header `Authorization: Bearer {token}`)

**Librerías Flutter recomendadas:**

- `pusher_channels_flutter` para conexión WebSocket
- Para Laravel Echo: usar un adapter manual con `pusher_channels_flutter` ya que `laravel_echo` no tiene soporte oficial para Flutter.

**Flujo de conexión:**

1. Login → obtener token
2. Inicializar Pusher/Reverb con el token
3. Suscribirse a `private-project.{projectId}` para chat grupal
4. Suscribirse a `private-conversation.{conversationId}` al abrir un chat privado
5. Escuchar eventos `.message.sent` y `.direct-message.sent`

---

## 15. Notas para desarrollo Flutter

1. **Almacenar el token** en `flutter_secure_storage` (iOS Keychain / Android EncryptedSharedPreferences).
2. **Interceptor HTTP:** agregar `Authorization: Bearer {token}` automáticamente en todas las peticiones.
3. **Scroll infinito en chats:** cargar página 1 al entrar. Si el usuario hace scroll hasta arriba, cargar `page + 1` y pre-prender los mensajes al listado.
4. **Mensajes en tiempo real:** al recibir un evento del WebSocket, agregarlo al final de la lista y hacer scroll hasta abajo.
5. **Notificaciones push:** usar `firebase_messaging` para obtener el token FCM y manejar notificaciones en foreground/background. Al hacer tap en una notificación, navegar a la pantalla correspondiente según `data.type`.
6. **Subida de archivos:** usar `http.MultipartRequest` para `upload-temp`, luego llamar al endpoint `claim` para asociar el archivo a una tarea o ticket.
7. **Adjuntos:** endpoint `upload-temp` solo acepta un archivo `multipart/form-data` con campo `file`. El UUID retornado se usa luego en `claim` (acepta array de UUIDs).
8. **Estados offline:** cachear conversaciones y mensajes localmente (SQLite/Hive) para acceso sin conexión. Sincronizar al reconectar.

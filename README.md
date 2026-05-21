# 🗂️ Gestión de Proyectos — Backend API

API REST para la gestión de proyectos, tareas, tickets, equipos y más. Construida con **Laravel 12** + **Sanctum** + **Spatie Permissions**.

---

## 📋 Características

- Autenticación con **Laravel Sanctum** (token Bearer)
- Control de roles y permisos con **Spatie Laravel Permission**
- Gestión de **proyectos**, **fases**, **tareas**, **tickets**, **hitos**, **entregables**, **riesgos** y **bloqueadores**
- Registro de tiempo por tarea
- Comentarios en tareas
- Dashboard con métricas por usuario
- Tests de Feature con **PHPUnit**
- Base de datos SQLite para testing / MySQL para producción

---

## 🛠️ Requisitos

- Docker + Docker Compose
- PHP 8.4+
- Composer

---

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/gestion-proyectos-backend.git
cd gestion-proyectos-backend
```

### 2. Copiar el archivo de entorno

```bash
cp .env.example .env
```

### 3. Configurar variables de entorno

Edita `.env` con tus credenciales de base de datos:

```env
APP_NAME="Gestión de Proyectos"
APP_URL=http://localhost:8000

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

### 4. Levantar los contenedores

```bash
docker compose up -d --build
```

> ✅ El entrypoint ejecuta automáticamente al iniciar el contenedor:
>
> - Instalación de dependencias (`composer install`)
> - Generación de `APP_KEY` si no existe
> - Migraciones pendientes
> - Seeders de roles y permisos (solo primera vez)
> - Creación del super-admin inicial (solo si no existe)

---

## 👤 Usuarios por defecto (Seeders)

| Rol | Email | Contraseña |
|---|---|---|
| Super Admin | <superadmin@test.com> | password |
| Project Manager | <pm@test.com> | password |
| Developer | <developer@test.com> | password |
| Client | <client@test.com> | password |

> El super-admin configurado via `.env` (`ADMIN_EMAIL`) se crea adicionalmente si no existe ningún usuario con ese rol.

---

## 🔑 Autenticación

Todas las rutas (excepto login) requieren el header:

```
Authorization: Bearer {token}
```

Obtén tu token haciendo **POST** a `/api/v1/auth/login`:

```json
{
  "email": "superadmin@test.com",
  "password": "password"
}
```

---

## 📡 Endpoints principales

| Recurso | Base URL |
|---|---|
| Auth | `/api/v1/auth` |
| Users | `/api/v1/users` |
| Projects | `/api/v1/projects` |
| Members | `/api/v1/projects/{id}/members` |
| Phases | `/api/v1/projects/{id}/phases` |
| Tasks | `/api/v1/projects/{id}/tasks` |
| Task Comments | `/api/v1/tasks/{id}/comments` |
| Task Time Logs | `/api/v1/tasks/{id}/time-logs` |
| Tickets | `/api/v1/projects/{id}/tickets` |
| Milestones | `/api/v1/projects/{id}/milestones` |
| Deliverables | `/api/v1/projects/{id}/deliverables` |
| Blockers | `/api/v1/projects/{id}/blockers` |
| Risks | `/api/v1/projects/{id}/risks` |
| Objectives | `/api/v1/projects/{id}/objectives` |
| Plan | `/api/v1/projects/{id}/plan` |
| Dashboard | `/api/v1/dashboard` |
| Roles | `/api/v1/roles` |

> 📦 Importa la colección de Postman incluida en `/postman/GestionProyectos.postman_collection.json`

---

## 🧪 Tests

```bash
docker exec -it gestion_proyectos_backend_app php artisan test
```

Ejecutar un grupo específico:

```bash
docker exec -it gestion_proyectos_backend_app php artisan test tests/Feature/Ticket/TicketTest.php
```

---

## 📁 Estructura relevante

```
app/
├── Enums/          # TaskStatus, TicketStatus, TaskPriority...
├── Exceptions/     # DomainExceptions por recurso
├── Http/
│   ├── Controllers/Api/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
└── Services/
docker/
└── entrypoint.sh   # Bootstrap automático del contenedor
postman/
└── GestionProyectos.postman_collection.json
```

---

## 📄 Licencia

MIT

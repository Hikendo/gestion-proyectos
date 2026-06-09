# Changes.md — Contexto para sesiones futuras

**Última actualización:** 2026-06-09

---

## [2026-06-09] Correcciones de infraestructura, navegación y permisos

### 🔴 502 en backend (puerto 8000)

**Causa:** Nginx cacheaba IP del contenedor `backend`. Al reiniciar el backend, obtenía nueva IP pero nginx seguía apuntando a la vieja → `Connection refused`.

**Solución:** `nginx.conf` — agregado `resolver 127.0.0.11 valid=10s ipv6=off;` (DNS interno Docker) y `fastcgi_pass` con variable (`$backend_upstream`) para forzar re-resolución DNS cada 10s.

### 🔴 Iconos Remix Icons rotos

**Causa:** `frontend/src/plugins/remix-icons.ts` hacía spread de `...props` que incluía la prop `icon` (string como `"ri-home-line"`), sobreescribiendo el `class` CSS recién generado.

**Solución:** Desestructurar `icon` de props antes del spread al DOM. Solo props DOM-viables llegan al elemento `<i>`.

### 🔴 Navegación requería doble clic

**Causa:** `router.beforeEach` hacía `await projectsService.show(projectId)` sin feedback visual.

**Solución:** Se agregó `appStore.loader = true` antes de la llamada y `appStore.loader = false` en `finally`. El loader global (VOverlay + VProgressCircular en `App.vue`) se muestra inmediatamente. Navegaciones subsecuentes son instantáneas (proyecto en caché).

### 🔴 Sincronización de permisos Frontend ↔ Backend

**Causa:** El frontend usaba `canAction('Proyecto.Store')` (español, PascalCase) pero el backend envía `project.create` (inglés, minúsculas). El PermissionStore nunca hacía match.

**Solución:**

- `canAction()` ahora acepta `string | string[]` (OR lógico) + ownership para `-own`
- Reemplazo masivo (sed) de 30+ llamadas en 22+ archivos Vue a los nombres reales de permisos
- Mapeo completo en `usersCanAction.md`

---

## Arquitectura de permisos (RBAC + Spatie)

### Flujo backend → frontend

```
POST /auth/login  →  { user: { roles: [...], permissions: [...] } }
GET  /auth/me     →  { items: { roles: [...], permissions: [...] } }

useAuthStore.setSession()  →  permissionStore.setPermissions(user.permissions)
canAction('project.create') →  permissionStore.hasPermission('project.create')
```

### Roles globales (Spatie)

| Rol | Permisos clave |
|-----|---------------|
| `super-admin` | Bypass de todas las Policies (gate-before). CRUD completo de usuarios. |
| `project-manager` | Crear proyectos, gestionar miembros, adjuntos, aprobar/resolver. Sin eliminar proyectos ni gestionar usuarios. |
| `developer` | Tareas propias (edit-own, update-status, log-time). Tickets propios. Bloqueadores (solo crear). |
| `qa` | Igual que developer pero sin `log-time`. |
| `support` | Tickets (crear, edit-own, assign). Ver tareas y usuarios. |
| `client` | Solo lectura + tickets propios (solo si Open). Ver milestones, deliverables, reportes. |

### Reglas de Policies (backend)

- **Tareas `Done`:** Nadie edita ni cambia estado
- **Tickets `Closed`:** Nadie edita
- **Blockers `resolved`:** Nadie edita ni resuelve
- **`task.edit-own`:** Solo tareas asignadas al usuario, solo si no Done
- **`ticket.edit-own` (Client):** Además verifica que el ticket esté `Open`
- **Adjuntos:** Solo PM/owner (`task.manage-attachments`, `ticket.manage-attachments`)

Archivos clave:

- `backend/database/seeders/RolesAndPermissionsSeeder.php` — permisos y roles
- `backend/app/Policies/TaskPolicy.php` — reglas de tareas
- `backend/app/Policies/TicketPolicy.php` — reglas de tickets
- `backend/app/Policies/BlockerPolicy.php` — reglas de bloqueadores
- `usersCanAction.md` — matriz completa de permisos por rol

---

## Infraestructura Docker

### Servicios (`docker-compose.yml`)

| Servicio | Puerto host | Puerto interno | Notas |
|----------|------------|---------------|-------|
| `nginx` | 8000, 8001 | 80, 8001 | Proxy → backend:9000 |
| `backend` | — | 9000 | PHP-FPM, entrypoint con migraciones y seeders |
| `frontend` | 5173 | 5173 | Vite dev server con HMR |
| `mysql` | 3319 | 3306 | MySQL 8.4 |
| `redis` | — | 6379 | Cache + queues |
| `horizon` | — | — | Laravel Horizon (queues) |
| `scheduler` | — | — | Laravel schedule:work |

### Usuarios de prueba

| Email | Password | Rol |
|-------|----------|-----|
| `superadmin@test.com` | `password` | super-admin |
| `pm@test.com` | `password` | project-manager |
| `dev@test.com` | `password` | Sin rol global (se asigna por proyecto) |
| `qa@test.com` | `password` | Sin rol global |
| `support@test.com` | `password` | Sin rol global |
| `client@test.com` | `password` | Sin rol global |

### Comandos útiles

```bash
# Reiniciar todo
docker compose down && docker compose up -d --build

# Refrescar BD con datos de demo
docker compose exec backend php artisan migrate:refresh --seed

# Logs
docker logs gestion_proyectos_nginx --tail 50
docker logs gestion_proyectos_frontend_app --tail 20

# Tests
docker compose exec backend php artisan test
docker compose exec frontend npm run test
```

---

## Frontend — Estructura clave

### Stores (Pinia)

- `useAuthStore` — authUser, currentProject, roles, setSession/clearSession
- `usePermissionStore` — permissions[], hasPermission, setPermissions/refreshPermissions
- `useAppStore` — loader, snackbar (globales)

### Helpers

- `canAction(action: string | string[], ownerId?)` — verifica permisos + ownership
- `useEnsureCurrentProject()` — carga proyecto en onMounted para sidebar

### Plugins

- `remix-icons.ts` — Custom icon set de Vuetify para Remix Icons. **No hacer spread de `...props` sin desestructurar `icon`.**

### Router

- `beforeEach`: restaura sesión (me), precarga currentProject con loader. No bloquear la navegación sin feedback visual.

---

## Skills aprendidas

1. **Nginx + Docker:** Usar `resolver 127.0.0.11 valid=10s` y variables en `fastcgi_pass` para evitar 502 cuando los contenedores se reinician.
2. **Iconos en Vuetify:** Los custom icon sets deben limpiar la prop `icon` antes del spread al DOM.
3. **Navegación async:** Siempre mostrar loader cuando `beforeEach` hace llamadas API bloqueantes.
4. **Permisos frontend:** Los nombres en `canAction()` deben coincidir **exactamente** con los que devuelve `$user->getAllPermissions()->pluck('name')` del backend. Usar arrays para OR lógico (`['task.edit-content', 'task.edit-own']`).
5. **Permisos -own:** Sin `resourceOwnerId`, `canAction('task.edit-own')` retorna false en contextos de lista.
6. **FormRequests:** Son la fuente de verdad de qué campos acepta cada endpoint. Verificar antes de agregar campos al frontend.
7. **Endpoints dedicados:** Usar `PATCH /resolve`, `PATCH /approve` en lugar de switches en formularios genéricos.
8. **FCM:** Nunca llamadas HTTP externas sincrónicas desde controllers. Usar Jobs asíncronos.

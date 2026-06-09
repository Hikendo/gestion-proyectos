# usersCanAction.md — Matriz de permisos por rol

**Última actualización:** 2026-06-09
**Fuentes:** `RolesAndPermissionsSeeder.php`, `TaskPolicy.php`, `TicketPolicy.php`, `BlockerPolicy.php`

---

## Tabla resumen de acciones por rol

| Acción | Super Admin | Project Manager | Developer | QA | Support | Client |
|--------|:-----------:|:---------------:|:---------:|:--:|:-------:|:------:|
| **Dashboard** |||||||
| Ver dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Proyectos** |||||||
| Ver proyectos | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Crear proyecto | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar proyecto (nombre, fechas, presupuesto) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Eliminar proyecto | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Asignar/quitar miembros | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Gestionar adjuntos del proyecto | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Fases** |||||||
| Ver fases | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Crear fases | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar fases | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Eliminar fases | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Tareas** |||||||
| Ver tareas | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Crear tareas | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Editar contenido de cualquier tarea (título, descripción, criterios) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar solo tareas propias (si no están Done) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Cambiar estado de tareas | ✅ | ✅ | ✅* | ✅* | ❌ | ❌ |
| Asignar tareas | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Eliminar tareas | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Registrar tiempo en tareas | ✅ | ❌ | ✅* | ❌ | ❌ | ❌ |
| Gestionar adjuntos de tareas | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Tickets** |||||||
| Ver tickets | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Crear tickets | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editar cualquier ticket | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar solo tickets propios | ✅ | ✅ | ✅ | ✅ | ✅ | ✅* |
| Asignar tickets | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| Eliminar tickets | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Gestionar adjuntos de tickets | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Riesgos** |||||||
| Ver riesgos | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Crear riesgos | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar riesgos | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Eliminar riesgos | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Bloqueadores** |||||||
| Ver bloqueadores | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Crear bloqueadores | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Editar bloqueadores | ✅ | ✅* | ❌ | ❌ | ❌ | ❌ |
| Resolver bloqueadores | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Milestones** |||||||
| Ver milestones | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Crear milestones | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar milestones | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Eliminar milestones | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Entregables** |||||||
| Ver entregables | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Crear entregables | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar entregables | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Aprobar entregables | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Objetivos** |||||||
| Ver objetivos | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Crear objetivos | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar objetivos | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Métricas y Reportes** |||||||
| Ver métricas | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Ver reportes | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **Usuarios** |||||||
| Ver usuarios | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| Crear usuarios | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Editar usuarios | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Eliminar usuarios | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Notas y restricciones especiales

### Super Admin (`super-admin`)

- **Omnipresente:** `gate-before` en todas las Policies retorna `true` antes de cualquier verificación
- Único rol que puede **eliminar proyectos** y **gestionar usuarios** (CRUD completo)
- No necesita ser miembro de un proyecto para ver/editar cualquier recurso

### Project Manager (`project-manager`)

- **Gestión de adjuntos:** Único rol (junto con owner) que puede subir/eliminar adjuntos de tareas y tickets
- **Aprobaciones:** Puede aprobar entregables (`deliverable.approve`)
- **Resolución:** Puede resolver bloqueadores (`blocker.resolve`)
- **Eliminación:** Puede eliminar fases, tareas, tickets, riesgos, milestones
- **Asignación:** Puede asignar tareas (`task.assign`) y tickets (`ticket.assign`)
- No puede eliminar proyectos ni gestionar usuarios globales

### Developer (`developer`)

- **Tareas propias:** `task.edit-own` — solo puede editar tareas asignadas a él, y solo si no están `Done`
- **Cambio de estado:** `task.update-status` — puede mover sus propias tareas entre estados, pero no tareas `Done`
- **Registro de tiempo:** `task.log-time` — solo en tareas asignadas a él
- **Bloqueadores:** Puede crear bloqueadores pero no editarlos ni resolverlos (solo PM/owner)
- **Tickets:** Solo puede editar tickets que él mismo creó (`ticket.edit-own`)
- No puede ver métricas ni reportes

### QA (`qa`)

- Idéntico a Developer en permisos base
- **Diferencia clave:** No tiene `task.log-time` — no registra horas
- Mismas restricciones: solo edita tareas propias, solo tickets propios

### Support (`support`)

- **Enfoque en tickets:** Puede crear, ver y editar sus propios tickets
- **Asignación de tickets:** `ticket.assign` — puede derivar tickets a otros miembros
- **Visibilidad limitada:** Solo ve tareas (no puede crearlas ni editarlas), no ve fases, riesgos, milestones, entregables, objetivos
- Puede ver la lista de usuarios (`user.view`)

### Client (`client`)

- **Solo lectura + tickets:** Mayormente observador
- **Tickets:** Puede crear tickets y editar solo los propios, **únicamente si están en estado `Open`** (no puede modificar tickets ya en progreso o resueltos)
- **Visibilidad:** Ve milestones, entregables, objetivos, métricas y reportes
- No ve tareas, fases, riesgos, ni bloqueadores

---

## Reglas de ownership y estado

### Tareas (`TaskPolicy`)

| Regla | Detalle |
|-------|---------|
| Tareas `Done` | Nadie puede editar ni cambiar estado de tareas completadas |
| `task.edit-content` | Solo PM/owner — edita título, descripción, criterios de aceptación |
| `task.edit-own` | Developer/QA — solo sus propias tareas asignadas, solo si no Done |
| `task.log-time` | Solo el asignado puede registrar tiempo en su tarea |
| `task.manage-attachments` | Solo PM/owner pueden subir/eliminar adjuntos |

### Tickets (`TicketPolicy`)

| Regla | Detalle |
|-------|---------|
| Tickets `Closed` | Nadie puede editar tickets cerrados |
| `ticket.edit-any` | PM/owner — edita cualquier ticket del proyecto |
| `ticket.edit-own` | Resto de roles — solo tickets que crearon |
| Client + `edit-own` | Además verifica que el ticket esté `Open` (no In Progress ni Resolved) |

### Blockers (`BlockerPolicy`)

| Regla | Detalle |
|-------|---------|
| Blocker `resolved` | Nadie puede editar ni resolver un blocker ya resuelto |
| `blocker.edit` | Solo PM/owner |
| `blocker.resolve` | Solo PM/owner |

---

## Diagrama de jerarquía

```
Super Admin (todos los permisos, bypass de policies)
  │
  └─ Project Manager (gestión completa del proyecto)
       │
       ├─ Developer (ejecución: tareas propias, tiempo, bloqueadores)
       ├─ QA (validación: tareas propias, tickets, bloqueadores)
       ├─ Support (soporte: tickets, asignación)
       └─ Client (observador: tickets propios Open, reportes)

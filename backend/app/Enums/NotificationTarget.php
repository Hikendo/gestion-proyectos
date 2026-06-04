<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationTarget: string
{
    /** Un usuario específico por ID */
    case USER = 'user';

    /** Todos los usuarios con un rol específico */
    case ROLE = 'role';

    /** Todos los usuarios con alguno de los roles indicados */
    case ROLES = 'roles';

    /** Todos los usuarios con un permiso específico */
    case PERMISSION = 'permission';

    /** Todos los miembros activos de un proyecto */
    case PROJECT_MEMBERS = 'project_members';

    /** El asignado a una tarea */
    case TASK_ASSIGNEES = 'task_assignees';

    /** El asignado a un ticket */
    case TICKET_ASSIGNEES = 'ticket_assignees';

    /** Lista de usuarios determinada por el llamador */
    case CUSTOM = 'custom';
}

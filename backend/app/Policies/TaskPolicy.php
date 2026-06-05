<?php

namespace App\Policies;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('task.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.view');
    }

    public function create(User $user): bool
    {
        return $user->can('task.create');
    }

    /**
     * PM edita cualquier tarea del proyecto (usa task.edit-content).
     * Developer/QA solo edita sus propias tareas (usa task.edit-own) y solo si no está Done.
     * Nadie puede editar una tarea completada.
     */
    public function update(User $user, Task $task): bool
    {
        if ($task->status === TaskStatus::Done) {
            return false;
        }

        // PM / Owner: puede editar contenido de cualquier tarea del proyecto
        if ($task->project->owner_id === $user->id || $user->hasProjectRole($task->project, 'manager')) {
            return $user->canForProject($task->project, 'task.edit-content');
        }

        // Developer / QA: solo puede editar sus propias tareas asignadas
        return $user->canForProject($task->project, 'task.edit-own')
            && $task->assigned_to === $user->id;
    }

    /**
     * Separado de update(): solo transición de estado.
     * El asignado, el PM y el owner pueden cambiar el estado.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        if ($task->status === TaskStatus::Done) {
            return false;
        }

        return $user->canForProject($task->project, 'task.update-status')
            && ($task->project->owner_id === $user->id
                || $user->hasProjectRole($task->project, 'manager')
                || $task->assigned_to === $user->id);
    }

    public function assign(User $user): bool
    {
        return $user->can('task.assign');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.delete')
            && ($task->project->owner_id === $user->id
                || $user->hasProjectRole($task->project, 'manager'));
    }

    public function logTime(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.log-time')
            && $task->assigned_to === $user->id;
    }

    /**
     * Gestión de adjuntos de tarea.
     * Solo PM/owner pueden subir/eliminar adjuntos en tareas del proyecto.
     */
    public function manageAttachments(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.manage-attachments')
            && ($task->project->owner_id === $user->id
                || $user->hasProjectRole($task->project, 'manager'));
    }
}

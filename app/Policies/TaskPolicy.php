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
        return $user->can('task.view')
            && ($task->project->owner_id === $user->id
                || $task->project->members()->where('user_id', $user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->can('task.create');
    }

    /**
     * PM edita cualquier tarea del proyecto.
     * Developer/QA solo edita sus propias tareas y solo si no está Done.
     */
    public function update(User $user, Task $task): bool
    {
        if ($task->status === TaskStatus::Done) {
            return false;
        }

        if ($task->project->owner_id === $user->id || $user->hasProjectRole($task->project, 'manager')) {
            return $user->canForProject($task->project, 'task.edit');
        }

        return $user->canForProject($task->project, 'task.edit')
            && $task->assigned_to === $user->id;
    }

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
}

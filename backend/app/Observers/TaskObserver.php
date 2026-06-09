<?php

namespace App\Observers;

use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskObserver
{
    /**
     * Dispara TaskCreated al crear la tarea.
     * Si la tarea se crea con asignado, también dispara TaskAssigned.
     */
    public function created(Task $task): void
    {
        $actor = Auth::user() ?? $task->creator;

        TaskCreated::dispatch($task, $actor);

        if ($task->assigned_to && $task->assignee) {
            TaskAssigned::dispatch($task, $task->assignee, $actor);
        }
    }

    /**
     * Detecta cambios de estado y cambios de asignado en update.
     */
    public function updated(Task $task): void
    {
        $actor = Auth::user() ?? $task->creator;
        $previousStatus = TaskStatus::tryFrom($task->getOriginal('status'));

        // 1. Cambio de status
        if ($task->isDirty('status')) {
            $newStatus = TaskStatus::tryFrom($task->status);

            if ($previousStatus && $newStatus) {
                TaskStatusChanged::dispatch(
                    $task,
                    $previousStatus,
                    $newStatus,
                    $actor,
                );

                if ($newStatus === TaskStatus::Done) {
                    TaskCompleted::dispatch($task, $actor);
                }
            }
        }

        // 2. Cambio de asignado
        if ($task->isDirty('assigned_to') && $task->assigned_to && $task->assignee) {
            TaskAssigned::dispatch($task, $task->assignee, $actor);
        }
    }
}
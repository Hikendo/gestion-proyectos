<?php

namespace App\Observers;

use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskProgressUpdated;
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

        // Recalcular progreso al crear (si el factory/envío inicial incluye horas)
        $this->recalculateProgress($task);
        if ($task->isDirty('progress')) {
            $task->saveQuietly();
        }

        TaskCreated::dispatch($task, $actor);

        if ($task->assigned_to && $task->assignee) {
            TaskAssigned::dispatch($task, $task->assignee, $actor);
        }
    }

    /**
     * Detecta cambios de estado, horas y asignado en update.
     * Recalcula automáticamente el progreso de la tarea.
     */
    public function updated(Task $task): void
    {
        $actor = Auth::user() ?? $task->creator;
        $rawStatus = $task->getOriginal('status');
        $previousStatus = $rawStatus instanceof TaskStatus
            ? $rawStatus
            : TaskStatus::tryFrom($rawStatus);

        // 0. Recalcular progreso si cambiaron horas estimadas o trabajadas
        if ($task->isDirty('worked_hours') || $task->isDirty('estimated_hours') || $task->isDirty('status')) {
            $this->recalculateProgress($task);
        }

        // Persistir el progreso si cambió (saveQuietly para no re-disparar observers)
        if ($task->isDirty('progress')) {
            $task->saveQuietly();
            TaskProgressUpdated::dispatch($task, $actor);
        }

        // 1. Cambio de status
        if ($task->isDirty('status')) {
            $newStatus = $task->status instanceof TaskStatus
                ? $task->status
                : TaskStatus::tryFrom($task->status);

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

    /**
     * Recalcula el progreso de la tarea basado en worked_hours / estimated_hours.
     * Si la tarea está Done, fuerza progress = 100.
     * Si no hay estimated_hours, progress = 0.
     * El progreso nunca excede 100.
     */
    protected function recalculateProgress(Task $task): void
    {
        $status = $task->status instanceof TaskStatus
            ? $task->status
            : TaskStatus::tryFrom($task->status);

        if ($status === TaskStatus::Done) {
            $task->progress = 100;
            return;
        }

        $estimated = (int) $task->estimated_hours;
        $worked    = (int) $task->worked_hours;

        if ($estimated <= 0) {
            $task->progress = 0;
            return;
        }

        $progress = (int) round(($worked / $estimated) * 100);
        $task->progress = min($progress, 100);
    }
}

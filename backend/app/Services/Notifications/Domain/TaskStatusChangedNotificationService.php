<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando el estado de una tarea cambia.
 *
 * Destinatarios: creador + asignado de la tarea (si no son el actor).
 * Policy check: task → view
 */
final class TaskStatusChangedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'task_status_changed';
    }

    public function notify(Task $task, TaskStatus $previous, TaskStatus $current, User $actor): void
    {
        Log::channel('notifications')->info(
            "TaskStatusChangedNotificationService: tarea ID {$task->id} " .
                "de '{$previous->value}' a '{$current->value}'."
        );

        $task->loadMissing(['creator.fcmTokens', 'assignee.fcmTokens']);

        // Colectamos creador y asignado, excluyendo al actor y evitando duplicados
        $candidateUsers = collect();

        if ($task->creator && $task->creator->id !== $actor->id) {
            $candidateUsers->push($task->creator);
        }

        if (
            $task->assignee
            && $task->assignee->id !== $actor->id
            && !$candidateUsers->contains('id', $task->assignee->id)
        ) {
            $candidateUsers->push($task->assignee);
        }

        if ($candidateUsers->isEmpty()) {
            return;
        }

        $authorized = $this->policyFilter->filter($candidateUsers, 'view', $task);

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Estado de tarea actualizado',
            body: "La tarea \"{$task->title}\" cambió de {$previous->value} a {$current->value}.",
            data: [
                'type'       => $this->notificationType(),
                'task_id'    => $task->id,
                'task_title' => $task->title,
                'project_id' => $task->project_id,
                'from'       => $previous->value,
                'to'         => $current->value,
                'url'        => config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
            ],
            clickAction: config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
        );
    }
}

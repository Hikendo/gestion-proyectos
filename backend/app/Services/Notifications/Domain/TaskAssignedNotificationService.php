<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Task;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando una tarea es asignada a un usuario.
 *
 * Destinatario: el asignado.
 * Policy check: task → view
 */
final class TaskAssignedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'task_assigned';
    }

    public function notify(Task $task, User $assignee, User $actor): void
    {
        Log::channel('notifications')->info(
            "TaskAssignedNotificationService: tarea ID {$task->id} asignada a user ID {$assignee->id}."
        );

        // El asignado nunca es el actor en este caso, pero lo excluimos por seguridad
        $candidates = $this->resolver->resolveUser($assignee);

        // Solo notificamos si el asignado puede ver la tarea
        $authorized = $this->policyFilter->filter($candidates, 'view', $task);

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Nueva tarea asignada',
            body: "Se te asignó la tarea: \"{$task->title}\"",
            data: [
                'type'       => $this->notificationType(),
                'task_id'    => $task->id,
                'task_title' => $task->title,
                'project_id' => $task->project_id,
                'due_date'   => $task->due_date?->toDateString(),
                'priority'   => $task->priority,
                'url'        => config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
            ],
            clickAction: config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
        );
    }
}

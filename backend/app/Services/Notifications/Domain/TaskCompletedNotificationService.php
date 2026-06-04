<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Task;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando una tarea se marca como completada.
 *
 * Destinatarios: todos los miembros del proyecto que pueden ver la tarea.
 * Policy check: task → view
 */
final class TaskCompletedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'task_completed';
    }

    public function notify(Task $task, User $actor): void
    {
        Log::channel('notifications')->info(
            "TaskCompletedNotificationService: tarea ID {$task->id} completada por user ID {$actor->id}."
        );

        $task->loadMissing('project');

        // Notificamos a todos los miembros del proyecto excepto al actor
        $candidates = $this->resolver->resolveProjectMembers(
            project: $task->project,
            excludeIds: [$actor->id],
        );

        $authorized = $this->policyFilter->filter($candidates, 'view', $task);

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Tarea completada',
            body: "La tarea \"{$task->title}\" ha sido marcada como completada.",
            data: [
                'type'       => $this->notificationType(),
                'task_id'    => $task->id,
                'task_title' => $task->title,
                'project_id' => $task->project_id,
                'url'        => config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
            ],
            clickAction: config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
        );
    }
}

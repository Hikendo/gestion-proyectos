<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Blocker;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando se crea un blocker en una tarea.
 *
 * Destinatarios: miembros del proyecto con roles manager/PM + asignado a la tarea.
 * Policy check: task → view  (el blocker pertenece a la tarea)
 */
final class BlockerCreatedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'blocker_created';
    }

    public function notify(Blocker $blocker, User $actor): void
    {
        Log::channel('notifications')->info(
            "BlockerCreatedNotificationService: blocker ID {$blocker->id} creado por user ID {$actor->id}."
        );

        $blocker->loadMissing(['task.project', 'task.assignee.fcmTokens']);
        $task = $blocker->task;

        if (!$task) {
            Log::channel('notifications')->warning(
                "BlockerCreatedNotificationService: tarea no encontrada para blocker ID {$blocker->id}."
            );
            return;
        }

        // Managers del proyecto (rol) + asignado de la tarea
        $managers = $this->resolver->resolveByRole('manager', excludeIds: [$actor->id]);
        $assigneesCandidates = $this->resolver->resolveTaskAssignees($task, excludeIds: [$actor->id]);

        // Unión sin duplicados, solo los que pertenecen a este proyecto
        $projectMemberIds = $task->project->members()->pluck('user_id')
            ->push($task->project->owner_id)
            ->unique()
            ->toArray();

        $candidates = $managers
            ->merge($assigneesCandidates)
            ->unique('id')
            ->filter(fn($u) => in_array($u->id, $projectMemberIds, true))
            ->values();

        $authorized = $this->policyFilter->filter($candidates, 'view', $task);

        $this->dispatchToMany(
            recipients: $authorized,
            title: '⚠️ Blocker detectado',
            body: "Se reportó un blocker en la tarea \"{$task->title}\": {$blocker->description}",
            data: [
                'type'       => $this->notificationType(),
                'blocker_id' => $blocker->id,
                'task_id'    => $task->id,
                'task_title' => $task->title,
                'project_id' => $task->project_id,
                'severity'   => $blocker->severity?->value,
                'url'        => config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
            ],
            clickAction: config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
        );
    }
}

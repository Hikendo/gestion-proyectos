<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Blocker;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando un blocker es resuelto.
 *
 * Destinatarios: el reporter del blocker + el asignado de la tarea asociada.
 * Policy check: task → view
 */
final class BlockerResolvedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'blocker_resolved';
    }

    public function notify(Blocker $blocker, User $actor): void
    {
        Log::channel('notifications')->info(
            "BlockerResolvedNotificationService: blocker ID {$blocker->id} resuelto por user ID {$actor->id}."
        );

        $blocker->loadMissing(['task.assignee.fcmTokens', 'reportedBy.fcmTokens', 'task.project']);
        $task = $blocker->task;

        if (! $task) {
            Log::channel('notifications')->warning(
                "BlockerResolvedNotificationService: tarea no encontrada para blocker ID {$blocker->id}."
            );
            return;
        }

        $candidates = collect();

        // Reporter del blocker
        if ($blocker->reportedBy && $blocker->reportedBy->id !== $actor->id) {
            $candidates->push($blocker->reportedBy);
        }

        // Asignado de la tarea
        if ($task->assignee && $task->assignee->id !== $actor->id && ! $candidates->contains('id', $task->assignee->id)) {
            $candidates->push($task->assignee);
        }

        if ($candidates->isEmpty()) {
            return;
        }

        $authorized = $this->policyFilter->filter($candidates, 'view', $task);

        $this->dispatchToMany(
            recipients: $authorized,
            title: '✅ Blocker resuelto',
            body: "El blocker \"{$blocker->title}\" en la tarea \"{$task->title}\" fue resuelto.",
            data: [
                'type'       => $this->notificationType(),
                'blocker_id' => $blocker->id,
                'task_id'    => $task->id,
                'task_title' => $task->title,
                'project_id' => $task->project_id,
                'url'        => config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
            ],
            clickAction: config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
        );
    }
}
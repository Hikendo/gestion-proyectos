<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\TaskComment;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando se agrega un comentario en una tarea.
 *
 * Destinatarios:
 *  - El asignado a la tarea
 *  - El creador de la tarea
 *  - Excluye al autor del comentario
 * Policy check: task → view
 */
final class CommentCreatedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'comment_created';
    }

    public function notify(TaskComment $comment, User $author): void
    {
        Log::channel('notifications')->info(
            "CommentCreatedNotificationService: comentario ID {$comment->id} " .
                "en tarea ID {$comment->task_id} por user ID {$author->id}."
        );

        $comment->loadMissing(['task.assignee.fcmTokens', 'task.creator.fcmTokens', 'task.project']);
        $task = $comment->task;

        if (!$task) {
            Log::channel('notifications')->warning(
                "CommentCreatedNotificationService: tarea no encontrada para comentario ID {$comment->id}."
            );
            return;
        }

        $candidates = collect();

        if ($task->assignee && $task->assignee->id !== $author->id) {
            $candidates->push($task->assignee);
        }

        if (
            $task->creator
            && $task->creator->id !== $author->id
            && !$candidates->contains('id', $task->creator->id)
        ) {
            $candidates->push($task->creator);
        }

        if ($candidates->isEmpty()) {
            return;
        }

        $authorized = $this->policyFilter->filter($candidates, 'view', $task);

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Nuevo comentario en tu tarea',
            body: "{$author->name} comentó en \"{$task->title}\": " . mb_substr($comment->comment, 0, 80) . '…',
            data: [
                'type'       => $this->notificationType(),
                'comment_id' => $comment->id,
                'task_id'    => $task->id,
                'task_title' => $task->title,
                'project_id' => $task->project_id,
                'author_id'  => $author->id,
                'author'     => $author->name,
                'url'        => config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
            ],
            clickAction: config('app.url') . "/projects/{$task->project_id}/tasks/{$task->id}",
        );
    }
}

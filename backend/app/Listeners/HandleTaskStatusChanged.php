<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;
use App\Jobs\RecalculateUserMetricsJob;
use App\Notifications\TaskStatusChangedNotification;

class HandleTaskStatusChanged
{
    public function handle(TaskStatusChanged $event): void
    {
        // Notificar al creador si existe y es distinto al actor
        $task = $event->task->loadMissing('creator');

        if ($task->creator && $task->creator->id !== $event->actor->id) {
            $task->creator->notify(new TaskStatusChangedNotification(
                $event->task,
                $event->previous,
                $event->current
            ));
        }

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'task',
            action: 'status_changed',
            data: [
                'task_id'    => $event->task->id,
                'task_title' => $event->task->title,
                'from'       => $event->previous->value,
                'to'         => $event->current->value,
            ]
        );

        RecalculateProjectMetricsJob::dispatch($event->task->project_id);

        if ($event->task->assigned_to) {
            RecalculateUserMetricsJob::dispatch($event->task->assigned_to);
        }
    }
}

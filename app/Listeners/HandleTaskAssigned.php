<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateUserMetricsJob;
use App\Notifications\TaskAssignedNotification;

class HandleTaskAssigned
{
    public function handle(TaskAssigned $event): void
    {
        // Notificar al asignado
        $event->assignee->notify(new TaskAssignedNotification($event->task));

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'task',
            action: 'assigned',
            data: [
                'task_id'     => $event->task->id,
                'task_title'  => $event->task->title,
                'assigned_to' => $event->assignee->id,
                'assignee'    => $event->assignee->name,
            ]
        );

        RecalculateUserMetricsJob::dispatch($event->assignee->id);
    }
}

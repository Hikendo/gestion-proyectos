<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;

class HandleTaskCreated
{
    public function handle(TaskCreated $event): void
    {
        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'task',
            action: 'created',
            data: [
                'task_id'    => $event->task->id,
                'task_title' => $event->task->title,
                'project_id' => $event->task->project_id,
            ]
        );

        RecalculateProjectMetricsJob::dispatch($event->task->project_id);
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;
use App\Jobs\RecalculateUserMetricsJob;
use App\Services\Notifications\Domain\TaskCompletedNotificationService;

class HandleTaskCompleted
{
    public function __construct(
        private readonly TaskCompletedNotificationService $notificationService
    ) {}

    public function handle(TaskCompleted $event): void
    {
        $this->notificationService->notify($event->task, $event->actor);

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'task',
            action: 'completed',
            data: [
                'task_id'    => $event->task->id,
                'task_title' => $event->task->title,
                'project_id' => $event->task->project_id,
            ]
        );

        RecalculateProjectMetricsJob::dispatch($event->task->project_id);

        if ($event->task->assigned_to) {
            RecalculateUserMetricsJob::dispatch($event->task->assigned_to);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;
use App\Jobs\RecalculateUserMetricsJob;
use App\Services\Notifications\Domain\TaskStatusChangedNotificationService;

class HandleTaskStatusChanged
{
    public function __construct(
        private readonly TaskStatusChangedNotificationService $notificationService
    ) {}

    public function handle(TaskStatusChanged $event): void
    {
        $this->notificationService->notify(
            $event->task,
            $event->previous,
            $event->current,
            $event->actor
        );

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

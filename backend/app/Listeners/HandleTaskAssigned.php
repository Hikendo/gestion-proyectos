<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateUserMetricsJob;
use App\Services\Notifications\Domain\TaskAssignedNotificationService;

class HandleTaskAssigned
{
    public function __construct(
        private readonly TaskAssignedNotificationService $notificationService
    ) {}

    public function handle(TaskAssigned $event): void
    {
        // Push notification via FCM (role-aware + policy-aware)
        $this->notificationService->notify($event->task, $event->assignee, $event->actor);

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

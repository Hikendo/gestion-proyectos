<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MilestoneCompleted;
use App\Jobs\LogActivityJob;
use App\Services\Notifications\Domain\MilestoneCompletedNotificationService;

class HandleMilestoneCompleted
{
    public function __construct(
        private readonly MilestoneCompletedNotificationService $notificationService
    ) {}

    public function handle(MilestoneCompleted $event): void
    {
        $this->notificationService->notify($event->milestone, $event->actor);

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'milestone',
            action: 'completed',
            data: [
                'milestone_id' => $event->milestone->id,
                'title'        => $event->milestone->name ?? $event->milestone->title ?? '',
                'project_id'   => $event->milestone->project_id,
            ]
        );
    }
}

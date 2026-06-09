<?php

namespace App\Listeners;

use App\Events\BlockerResolved;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;
use App\Services\Notifications\Domain\BlockerResolvedNotificationService;

class HandleBlockerResolved
{
    public function __construct(
        private readonly BlockerResolvedNotificationService $notificationService
    ) {}

    public function handle(BlockerResolved $event): void
    {
        $this->notificationService->notify($event->blocker, $event->actor);

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'blocker',
            action: 'resolved',
            data: [
                'blocker_id' => $event->blocker->id,
                'title'      => $event->blocker->title,
                'project_id' => $event->blocker->project_id,
            ]
        );

        RecalculateProjectMetricsJob::dispatch($event->blocker->project_id);
    }
}

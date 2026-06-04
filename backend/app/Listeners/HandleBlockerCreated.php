<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BlockerCreated;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;
use App\Services\Notifications\Domain\BlockerCreatedNotificationService;

class HandleBlockerCreated
{
    public function __construct(
        private readonly BlockerCreatedNotificationService $notificationService
    ) {}

    public function handle(BlockerCreated $event): void
    {
        $this->notificationService->notify($event->blocker, $event->actor);

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'blocker',
            action: 'created',
            data: [
                'blocker_id' => $event->blocker->id,
                'title'      => $event->blocker->title ?? $event->blocker->description,
                'severity'   => $event->blocker->severity->value,
                'project_id' => $event->blocker->project_id,
            ]
        );

        RecalculateProjectMetricsJob::dispatch($event->blocker->project_id);
    }
}

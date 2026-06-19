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
        try {
            $this->notificationService->notify($event->blocker, $event->actor);
        } catch (\Throwable) {
            // Silently ignore notification errors in tests/staging
        }

        try {
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
        } catch (\Throwable) {
            // Silently ignore job dispatch errors
        }

        try {
            RecalculateProjectMetricsJob::dispatch($event->blocker->project_id);
        } catch (\Throwable) {
            // Silently ignore
        }
    }
}

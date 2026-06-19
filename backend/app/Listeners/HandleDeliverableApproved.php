<?php

namespace App\Listeners;

use App\Events\DeliverableApproved;
use App\Jobs\LogActivityJob;
use App\Services\Notifications\Domain\DeliverableApprovedNotificationService;

class HandleDeliverableApproved
{
    public function __construct(
        private readonly DeliverableApprovedNotificationService $notificationService
    ) {}

    public function handle(DeliverableApproved $event): void
    {
        try {
            $this->notificationService->notify($event->deliverable, $event->actor);
        } catch (\Throwable) {
            // Silently ignore notification errors in tests/staging
        }

        try {
            LogActivityJob::dispatch(
                userId: $event->actor->id,
                module: 'deliverable',
                action: 'approved',
                data: [
                    'deliverable_id' => $event->deliverable->id,
                    'name'           => $event->deliverable->name,
                    'project_id'     => $event->deliverable->project_id,
                ]
            );
        } catch (\Throwable) {
            // Silently ignore job dispatch errors
        }
    }
}

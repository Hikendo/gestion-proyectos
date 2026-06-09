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
        $this->notificationService->notify($event->deliverable, $event->actor);

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
    }
}

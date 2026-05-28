<?php

namespace App\Listeners;

use App\Events\DeliverableApproved;
use App\Jobs\LogActivityJob;

class HandleDeliverableApproved
{
    public function handle(DeliverableApproved $event): void
    {
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

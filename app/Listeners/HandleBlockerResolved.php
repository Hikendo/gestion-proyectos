<?php

namespace App\Listeners;

use App\Events\BlockerResolved;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;

class HandleBlockerResolved
{
    public function handle(BlockerResolved $event): void
    {
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

<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Jobs\LogActivityJob;

class HandleProjectCreated
{
    public function handle(ProjectCreated $event): void
    {
        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'project',
            action: 'created',
            data: [
                'project_id'   => $event->project->id,
                'project_name' => $event->project->name,
                'project_code' => $event->project->code,
            ]
        );
    }
}

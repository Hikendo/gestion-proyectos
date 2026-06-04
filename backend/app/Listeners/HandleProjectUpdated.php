<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProjectUpdated;
use App\Jobs\LogActivityJob;
use App\Services\Notifications\Domain\ProjectUpdatedNotificationService;

class HandleProjectUpdated
{
    public function __construct(
        private readonly ProjectUpdatedNotificationService $notificationService
    ) {}

    public function handle(ProjectUpdated $event): void
    {
        $this->notificationService->notify(
            $event->project,
            $event->actor,
            $event->changeDescription
        );

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'project',
            action: 'updated',
            data: [
                'project_id'   => $event->project->id,
                'project_name' => $event->project->name,
                'description'  => $event->changeDescription,
            ]
        );
    }
}

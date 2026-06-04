<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProjectMemberAdded;
use App\Jobs\LogActivityJob;
use App\Services\Notifications\Domain\ProjectMemberAddedNotificationService;

class HandleProjectMemberAdded
{
    public function __construct(
        private readonly ProjectMemberAddedNotificationService $notificationService
    ) {}

    public function handle(ProjectMemberAdded $event): void
    {
        $this->notificationService->notify($event->project, $event->newMember, $event->actor);

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'project',
            action: 'member_added',
            data: [
                'project_id'    => $event->project->id,
                'project_name'  => $event->project->name,
                'new_member_id' => $event->newMember->id,
                'new_member'    => $event->newMember->name,
            ]
        );
    }
}

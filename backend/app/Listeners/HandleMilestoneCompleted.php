<?php

namespace App\Listeners;

use App\Events\MilestoneCompleted;
use App\Jobs\LogActivityJob;
use App\Models\User;
use App\Notifications\MilestoneCompletedNotification;

class HandleMilestoneCompleted
{
    public function handle(MilestoneCompleted $event): void
    {
        // Notificar a todos los miembros del proyecto
        $members = User::whereHas('projectMemberships', function ($q) use ($event) {
            $q->where('project_id', $event->milestone->project_id);
        })->get();

        foreach ($members as $member) {
            $member->notify(new MilestoneCompletedNotification($event->milestone));
        }

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'milestone',
            action: 'completed',
            data: [
                'milestone_id' => $event->milestone->id,
                'title'        => $event->milestone->title,
                'project_id'   => $event->milestone->project_id,
            ]
        );
    }
}

<?php

namespace App\Listeners;

use App\Events\BlockerCreated;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;
use App\Models\User;
use App\Notifications\BlockerCreatedNotification;

class HandleBlockerCreated
{
    public function handle(BlockerCreated $event): void
    {
        // Notificar a los managers del proyecto
        $managers = User::whereHas('projectMemberships', function ($q) use ($event) {
            $q->where('project_id', $event->blocker->project_id)
                ->where('role', 'manager');
        })->get();

        foreach ($managers as $manager) {
            if ($manager->id !== $event->actor->id) {
                $manager->notify(new BlockerCreatedNotification($event->blocker));
            }
        }

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'blocker',
            action: 'created',
            data: [
                'blocker_id' => $event->blocker->id,
                'title'      => $event->blocker->title,
                'severity'   => $event->blocker->severity->value,
                'project_id' => $event->blocker->project_id,
            ]
        );

        RecalculateProjectMetricsJob::dispatch($event->blocker->project_id);
    }
}

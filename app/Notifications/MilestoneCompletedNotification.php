<?php

namespace App\Notifications;

use App\Models\Milestone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MilestoneCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Milestone $milestone) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'milestone_completed',
            'milestone_id' => $this->milestone->id,
            'title'        => $this->milestone->title,
            'project_id'   => $this->milestone->project_id,
            'message'      => "Milestone completado: {$this->milestone->title}",
        ];
    }
}

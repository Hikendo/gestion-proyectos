<?php

namespace App\Notifications;

use App\Models\Blocker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BlockerCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Blocker $blocker) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'blocker_created',
            'blocker_id' => $this->blocker->id,
            'title'      => $this->blocker->title,
            'severity'   => $this->blocker->severity->value,
            'project_id' => $this->blocker->project_id,
            'message'    => "Nuevo blocker [{$this->blocker->severity->label()}]: {$this->blocker->title}",
        ];
    }
}

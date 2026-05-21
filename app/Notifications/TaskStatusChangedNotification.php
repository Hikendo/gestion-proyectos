<?php

namespace App\Notifications;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly TaskStatus $previous,
        private readonly TaskStatus $current
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'task_status_changed',
            'task_id'    => $this->task->id,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'previous'   => $this->previous->value,
            'current'    => $this->current->value,
            'message'    => "La tarea '{$this->task->title}' cambió de {$this->previous->label()} a {$this->current->label()}",
        ];
    }
}

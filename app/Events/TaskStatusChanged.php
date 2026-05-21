<?php

namespace App\Events;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly TaskStatus $previous,
        public readonly TaskStatus $current,
        public readonly User $actor
    ) {}
}

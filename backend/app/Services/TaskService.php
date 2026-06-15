<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Exceptions\TaskException;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Traits\HasActivityLog;
use App\Traits\HasMetrics;

class TaskService
{
    use HasActivityLog, HasMetrics;

    public function create(array $data, Project $project, User $creator): Task
    {
        $data['project_id'] = $project->id;
        $data['created_by'] = $creator->id;
        $data['status']     = $data['status'] ?? TaskStatus::Pending->value;
        $data['priority']   = $data['priority'] ?? 'medium';

        return Task::create($data);
    }

    public function changeStatus(Task $task, TaskStatus $newStatus, User $actor): void
    {
        if ($task->status === TaskStatus::Done) {
            throw TaskException::notEditableWhenDone();
        }

        if (! $task->status->canTransitionTo($newStatus)) {
            throw TaskException::invalidStatusTransition($task->status, $newStatus);
        }

        $task->update(['status' => $newStatus->value]);
    }

    public function canEdit(User $user, Task $task): bool
    {
        if ($task->status === TaskStatus::Done) {
            return false;
        }

        if ($user->hasRole(['super-admin', 'project-manager'])) {
            return true;
        }

        return $user->can('task.edit') && $task->assigned_to === $user->id;
    }
}

<?php

namespace App\Jobs;

use App\Enums\TaskStatus;
use App\Models\User;
use App\Models\UserMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateUserMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $userId) {}

    public function handle(): void
    {
        $user = User::with(['assignedTasks', 'taskTimeLogs'])->find($this->userId);

        if (! $user) {
            return;
        }

        $assignedTasks  = $user->assignedTasks->count();
        $completedTasks = $user->assignedTasks->filter(
            fn($t) => $t->status === TaskStatus::Done
        )->count();

        $workedMinutes = $user->taskTimeLogs->sum('minutes');

        $performanceScore = $assignedTasks > 0
            ? round(($completedTasks / $assignedTasks) * 100, 2)
            : 0;

        UserMetric::updateOrCreate(
            ['user_id' => $user->id],
            [
                'assigned_tasks'    => $assignedTasks,
                'completed_tasks'   => $completedTasks,
                'worked_minutes'    => $workedMinutes,
                'performance_score' => $performanceScore,
            ]
        );
    }
}

<?php

namespace App\Jobs;

use App\Enums\TaskStatus;
use App\Enums\TicketStatus;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateProjectMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $projectId) {}

    public function handle(): void
    {
        $project = Project::with(['tasks', 'tickets', 'blockers'])->find($this->projectId);

        if (! $project) {
            return;
        }

        $totalTasks     = $project->tasks->count();
        $completedTasks = $project->tasks->filter(
            fn($t) => $t->status === TaskStatus::Done
        )->count();

        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 2)
            : 0;

        $project->metrics()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'total_tasks'     => $totalTasks,
                'completed_tasks' => $completedTasks,
                'open_tickets'    => $project->tickets->filter(
                    fn($t) => $t->status === TicketStatus::Open
                )->count(),
                'total_blockers'  => $project->blockers->where('resolved', false)->count(),
                'completion_rate' => $completionRate,
            ]
        );

        // Actualizar progreso general del proyecto
        $project->update(['progress' => $completionRate]);
    }
}

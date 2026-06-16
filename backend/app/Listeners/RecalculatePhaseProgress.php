<?php

namespace App\Listeners;

use App\Enums\PhaseStatus;
use App\Enums\TaskStatus;
use App\Events\PhaseProgressUpdated;
use App\Events\TaskCompleted;
use App\Events\TaskProgressUpdated;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Task;

class RecalculatePhaseProgress
{
    /**
     * Maneja TaskProgressUpdated y TaskCompleted.
     * Recalcula el progreso de la fase a la que pertenece la tarea.
     */
    public function handle(TaskProgressUpdated|TaskCompleted $event): void
    {
        $task  = $event->task;
        $phase = $task->phase;

        if (! $phase) {
            return;
        }

        $this->recalculate($phase);
    }

    /**
     * Recalcula el progreso de una fase basado en horas completadas / horas totales.
     * Solo cuentan como completadas las horas de tareas en estado Done.
     */
    public function recalculate(ProjectPhase $phase): void
    {
        $tasks = $phase->tasks()->get();

        $totalEstimatedHours = $tasks->sum('estimated_hours');
        $completedHours      = $tasks
            ->filter(fn(Task $t) => $t->status === TaskStatus::Done)
            ->sum('worked_hours');

        if ($totalEstimatedHours <= 0) {
            $progress = 0;
        } else {
            $progress = (int) round(($completedHours / $totalEstimatedHours) * 100);
        }

        $progress = min($progress, 100);

        if ((int) $phase->progress !== $progress) {
            $phase->progress = $progress;
            $phase->save();

            PhaseProgressUpdated::dispatch($phase);
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\PhaseProgressUpdated;
use App\Models\Project;
use App\Models\ProjectPhase;

class RecalculateProjectProgress
{
    /**
     * Maneja PhaseProgressUpdated.
     * Recalcula el progreso del proyecto usando la fórmula ponderada:
     * project_progress = Σ (phase_weight × phase_progress)
     * donde phase_weight = phase.total_estimated_hours / project.total_estimated_hours
     */
    public function handle(PhaseProgressUpdated $event): void
    {
        $project = $event->phase->project;

        if (! $project) {
            return;
        }

        $this->recalculate($project);
    }

    /**
     * Recalcula el progreso del proyecto como promedio ponderado de sus fases.
     */
    public function recalculate(Project $project): void
    {
        $phases = $project->phases()->with('tasks')->get();

        // Calcular horas totales de cada fase
        $totalProjectHours = 0;
        $phaseData         = [];

        foreach ($phases as $phase) {
            $hours = $phase->tasks->sum('estimated_hours');
            $phaseData[] = [
                'phase'    => $phase,
                'hours'    => $hours,
            ];
            $totalProjectHours += $hours;
        }

        // Si no hay horas en el proyecto, progress = 0
        if ($totalProjectHours <= 0) {
            $progress = 0;
        } else {
            $weightedSum = 0;

            foreach ($phaseData as $data) {
                /** @var ProjectPhase $phase */
                $phase = $data['phase'];
                $hours = $data['hours'];

                if ($hours <= 0) {
                    continue; // peso 0, no contribuye
                }

                $weight   = $hours / $totalProjectHours;
                $weightedSum += $weight * $phase->progress;
            }

            $progress = (int) round($weightedSum);
        }

        $progress = min($progress, 100);

        if ((int) $project->progress !== $progress) {
            $project->progress = $progress;
            $project->save();
        }
    }
}

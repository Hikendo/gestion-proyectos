<?php

namespace App\Listeners;

use App\Enums\PhaseStatus;
use App\Enums\TaskStatus;
use App\Events\AcceptanceCriterionCompleted;
use App\Events\PhaseCompleted;
use App\Events\TaskCompleted;
use App\Models\ProjectPhase;

class CheckPhaseCompletion
{
    /**
     * Maneja TaskCompleted y AcceptanceCriterionCompleted.
     * Verifica si la fase cumple las condiciones para ser completada.
     */
    public function handle(TaskCompleted|AcceptanceCriterionCompleted $event): void
    {
        if ($event instanceof TaskCompleted) {
            $phase = $event->task->phase;
        } else {
            $phase = $event->criterion->phase;
        }

        if (! $phase) {
            return;
        }

        $this->check($phase);
    }

    /**
     * Verifica si una fase está completa:
     * 1. Tiene al menos una tarea.
     * 2. Todas sus tareas están en estado Done.
     * 3. Todos sus criterios de aceptación están completed = true.
     *
     * Si se cumplen las condiciones, marca la fase como Completed.
     */
    public function check(ProjectPhase $phase): void
    {
        // Refrescar relaciones para tener datos actualizados
        $phase->load(['tasks', 'acceptanceCriteria']);

        // Debe tener al menos una tarea
        if ($phase->tasks->isEmpty()) {
            return;
        }

        // Todas las tareas deben estar Done
        $allTasksDone = $phase->tasks->every(
            fn($task) => $task->status === TaskStatus::Done
        );

        if (! $allTasksDone) {
            return;
        }

        // Todos los criterios de aceptación deben estar completados
        $allCriteriaMet = $phase->acceptanceCriteria->isEmpty()
            || $phase->acceptanceCriteria->every(fn($c) => $c->completed);

        if (! $allCriteriaMet) {
            return;
        }

        // Marcar fase como completada
        $phase->status       = PhaseStatus::Completed;
        $phase->completed_at = now();
        $phase->progress     = 100;
        $phase->save();

        PhaseCompleted::dispatch($phase);
    }
}

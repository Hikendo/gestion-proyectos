<?php

namespace App\Traits;

use App\Exceptions\DomainException;

/**
 * Validación de pertenencia de un sub-recurso a un proyecto.
 * Evita repetir el mismo abort_if en cada controlador.
 */
trait BelongsToProject
{
    /**
     * Lanza la excepción de dominio correspondiente si el recurso
     * no pertenece al proyecto indicado.
     *
     * Uso en controller: $this->assertBelongsToProject($task, $project->id);
     */
    public function assertBelongsToProject(mixed $model, int $projectId): void
    {
        if ((int) $model->project_id !== $projectId) {
            $exceptionClass = $this->resolveExceptionClass($model);
            throw $exceptionClass::doesNotBelongToProject();
        }
    }

    private function resolveExceptionClass(mixed $model): string
    {
        $map = [
            \App\Models\Task::class        => \App\Exceptions\TaskException::class,
            \App\Models\Ticket::class      => \App\Exceptions\TicketException::class,
            \App\Models\Blocker::class     => \App\Exceptions\BlockerException::class,
            \App\Models\Deliverable::class => \App\Exceptions\DeliverableException::class,
            \App\Models\Milestone::class   => \App\Exceptions\MilestoneException::class,
        ];

        $class = get_class($model);

        return $map[$class] ?? \App\Exceptions\DomainException::class;
    }
}
